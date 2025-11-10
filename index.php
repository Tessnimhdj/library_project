<?php

session_start();

$err_msg = $_SESSION['err_msg'] ?? '';
unset($_SESSION['err_msg']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$err_msg = $err_msg ?? '';
$file_err = $file_err ?? '';
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




        <?php if (!empty($msg)): ?>
            <div class="alert alert-info" style="color:red; margin:10px 0;">
                <?= htmlspecialchars($msg); ?>
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
                    placeholder="Choose Excel file"
                    aria-describedby="fileHelpId" />
                <div id="fileHelpId" class="form-text">
                    الملفات المسموح بها: .xls و .xlsx — يجب أن يحتوي الصف الأول على أسماء الأعمدة (العناوين).
                </div>
            </div>


            <?php if (!empty($err_msg)) : ?>
                <div style="color:green; margin:10px 0;">
                    <?php echo $err_msg; ?>
                </div>
            <?php endif; ?>




            <button type="submit" class="btn btn-primary" name="submit">Import</button>

        </form>
    </div>


    <script>
        const allowedExtensions = ['xls', 'xlsx'];
        const inputFile = document.getElementById('input_file');
        const fileError = document.querySelector('.text-danger');

        inputFile.addEventListener('change', () => {
            if (!inputFile.value) return;
            const fileName = inputFile.value;
            const fileExt = fileName.split('.').pop().toLowerCase();
            if (!allowedExtensions.includes(fileExt)) {
                fileError.textContent = "ملف غير مسموح! اختر ملف اخر  .xls أو .xlsx";
                inputFile.value = "";
            } else {
                fileError.textContent = "";
            }
        });
    </script>
</body>

</html>