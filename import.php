<?php

session_start();

require 'vendor/autoload.php';
require 'config.php';

function flash_redirect(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: index.php');
    exit;
}

function cell_text($value, bool $wholeNumber = false): string
{
    if (is_int($value)) {
        return (string) $value;
    }
    if (is_float($value)) {
        return $wholeNumber ? sprintf('%.0f', $value) : trim((string) $value);
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_string($value)) {
        return trim($value);
    }

    return '';
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && $_POST === [] && $_FILES === []) {
    flash_redirect('danger', 'File too large. Max 5MB allowed.');
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$file = $_FILES['input_file'] ?? null;
if (!is_array($file) || ($file['name'] ?? '') === '') {
    flash_redirect('danger', 'Please select a file.');
}

$uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($uploadError !== UPLOAD_ERR_OK) {
    $message = in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
        ? 'File too large. Max 5MB allowed.'
        : 'Upload error.';
    flash_redirect('danger', $message);
}

$ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['xls', 'xlsx'], true)) {
    flash_redirect('danger', 'Invalid file extension. Only .xls and .xlsx allowed.');
}
if ((int) ($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
    flash_redirect('danger', 'File too large. Max 5MB allowed.');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
if (!is_string($mime) || !in_array($mime, ALLOWED_MIMES, true)) {
    flash_redirect('danger', 'Invalid file type (MIME).');
}

$upload_path = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
if (!move_uploaded_file((string) $file['tmp_name'], $upload_path)) {
    flash_redirect('danger', 'Failed to upload file.');
}

$reader = null;
$spreadsheet = null;
$resultType = 'danger';
$resultMessage = 'An error occurred while reading the Excel file.';

try {
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($ext === 'xls' ? 'Xls' : 'Xlsx');
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($upload_path);
    $data = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);

    if ($data !== []) {
        array_shift($data);
    }

    $seen = [];
    $skipped = 0;
    $before = (int) $conn->query('SELECT COUNT(*) FROM books')->fetchColumn();

    $conn->beginTransaction();
    $stmt = $conn->prepare(
        'INSERT INTO books (inventory_number, title, author, notes)
        VALUES (:inventory_number, :title, :author, :notes)
        ON DUPLICATE KEY UPDATE title = VALUES(title), author = VALUES(author), notes = VALUES(notes)'
    );

    foreach ($data as $row) {
        if (!is_array($row)) {
            $skipped++;
            continue;
        }

        $inventory_number = cell_text($row[0] ?? null, true);
        $title = cell_text($row[1] ?? null);
        $author = cell_text($row[2] ?? null);
        $notes = cell_text($row[3] ?? null);

        if ($inventory_number === '' || isset($seen[$inventory_number])) {
            $skipped++;
            continue;
        }
        if (
            text_length($inventory_number) > MAX_INVENTORY_LENGTH
            || text_length($title) > MAX_TITLE_LENGTH
            || text_length($author) > MAX_AUTHOR_LENGTH
        ) {
            throw new RuntimeException('A value is longer than the database column allows.');
        }

        $seen[$inventory_number] = true;
        $stmt->execute([
            ':inventory_number' => $inventory_number,
            ':title' => $title,
            ':author' => $author,
            ':notes' => $notes,
        ]);
    }

    if ($seen === []) {
        $conn->rollBack();
        $resultMessage = 'The file has no data rows to import.';
    } else {
        $conn->commit();
        $inserted = (int) $conn->query('SELECT COUNT(*) FROM books')->fetchColumn() - $before;
        $updated = count($seen) - $inserted;
        $resultType = 'success';
        $resultMessage = "Import finished. Added: {$inserted}. Updated: {$updated}. Skipped: {$skipped}.";
    }
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log(date('c') . ' ' . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
} finally {
    if ($spreadsheet !== null) {
        $spreadsheet->disconnectWorksheets();
        $spreadsheet = null;
    }
    $reader = null;
    gc_collect_cycles();
    if (is_file($upload_path)) {
        unlink($upload_path);
    }
}

flash_redirect($resultType, $resultMessage);
