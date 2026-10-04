<?php

ini_set('display_errors', '0');
session_start();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
if (!is_array($flash) || !isset($flash['type'], $flash['message'])) {
    $flash = null;
}
$flashType = (is_array($flash) && $flash['type'] === 'success') ? 'success' : 'danger';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <title>import excel data to mysql</title>
</head>
<body>
    <div class="container">
        <h1>Import Excel Data to MySQL</h1>

        <?php if ($flash !== null) : ?>
            <div class="alert alert-<?= $flashType ?>">
                <?= htmlspecialchars((string) $flash['message']) ?>
            </div>
        <?php endif; ?>

        <form action="import.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="mb-3">
                <label for="input_file" class="form-label fw-bold">Choose file</label>
                <input
                    type="file"
                    class="form-control"
                    name="input_file"
                    id="input_file"
                    accept=".xls,.xlsx"
                    aria-describedby="fileHelpId">
                <div id="fileHelpId" class="form-text">
                    الملفات المسموح بها: .xls و .xlsx — يجب أن يحتوي الصف الأول على أسماء الأعمدة (العناوين).
                </div>
                <div id="fileError" class="text-danger"></div>
            </div>

            <button type="submit" class="btn btn-primary" name="submit">Import</button>
        </form>
    </div>

    <script>
        const allowedExtensions = ['xls', 'xlsx'];
        const maxBytes = 5 * 1024 * 1024;
        const inputFile = document.getElementById('input_file');
        const fileError = document.getElementById('fileError');

        inputFile.addEventListener('change', () => {
            const file = inputFile.files[0];
            if (!file) return;

            const fileExt = file.name.split('.').pop().toLowerCase();
            if (!allowedExtensions.includes(fileExt)) {
                fileError.textContent = 'ملف غير مسموح! اختر ملف آخر .xls أو .xlsx';
                inputFile.value = '';
            } else if (file.size > maxBytes) {
                fileError.textContent = 'الملف أكبر من 5 ميغابايت.';
                inputFile.value = '';
            } else {
                fileError.textContent = '';
            }
        });
    </script>
</body>
</html>
