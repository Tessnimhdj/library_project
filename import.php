<?php
session_start();


require 'vendor/autoload.php';
include 'config.php';


$err_msg = "";
$file_err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }

    if (!isset($_FILES['input_file']) || $_FILES['input_file']['name'] === '') {
        $err_msg = "Please select a file.";
    } else {

        $file_name  = $_FILES['input_file']['name'];
        $tmp_name   = $_FILES['input_file']['tmp_name'];
        $file_size  = $_FILES['input_file']['size'];
        $file_error = $_FILES['input_file']['error'];

        if ($file_error !== UPLOAD_ERR_OK) {
            $err_msg = "Upload error.";
        } else {
            $valid_ext = array("xls", "xlsx");
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($ext, $valid_ext)) {
                $err_msg = "Invalid file extension. Only .xls and .xlsx allowed.";
            } elseif ($file_size > MAX_UPLOAD_BYTES) {
                $err_msg = "File too large. Max 5MB allowed.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmp_name);
                if (!in_array($mime, ALLOWED_MIMES)) {
                    $err_msg = "Invalid file type (MIME).";
                } else {

                    $new_file = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $upload_path = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $new_file;

                    if (!move_uploaded_file($tmp_name, $upload_path)) {
                        $err_msg = "Failed to upload file.";
                    } else {

                        try {
                            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                            $spreadsheet = $reader->load($upload_path);
                            $worksheet = $spreadsheet->getActiveSheet();
                            $data = $worksheet->toArray();

                            if (count($data) > 0) {
                                array_shift($data);
                            }

                            $seen = [];
                            $conn->beginTransaction();
                            $inserted = 0;

                            foreach ($data as $row) {
                                $inventory_number = trim($row[0] ?? '');
                                if ($inventory_number === '') continue;
                                if (isset($seen[$inventory_number])) continue;
                                $seen[$inventory_number] = true;

                                $title  = htmlspecialchars(trim($row[1] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                $author = htmlspecialchars(trim($row[2] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                $notes  = htmlspecialchars(trim($row[3] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                                $stmt = $conn->prepare("INSERT INTO books (inventory_number, title, author, notes)
                                    VALUES (:inventory_number, :title, :author, :notes)
                                    ON DUPLICATE KEY UPDATE title = VALUES(title), author = VALUES(author), notes = VALUES(notes)");
                                $stmt->execute([
                                    ':inventory_number' => $inventory_number,
                                    ':title' => $title,
                                    ':author' => $author,
                                    ':notes' => $notes
                                ]);
                                $inserted++;
                            }

                            $conn->commit();
                            $err_msg = "Data imported successfully!";
                        } catch (Exception $e) {
                            $conn->rollBack();
                            error_log($e->getMessage(), 3, __DIR__ . '/logs/errors.log');
                            $err_msg = "An error occurred while reading the Excel file.";
                        } finally {
                            if (file_exists($upload_path)) {
                                unlink($upload_path);
                            }
                        }
                    }
                }
            }
        }
    }
    $_SESSION['err_msg'] = $err_msg;
header("Location: index.php");
exit;

}
