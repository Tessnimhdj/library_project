<?php
include 'config.php';
if (isset($_POST['submit'])) {
    if ($_FILES['input_file']['name'] == '') {
        $file_err = "Please Select File";
    }else{

        
    }
}
?>


<!DOCTYPE html>
<html lang="en">

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
        <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="input_file" class="form-label fw-bold">Choose file</label>
                <input
                    type="file"
                    class="form-control"
                    name="input_file"
                    id="input_file"
                    placeholder="Choose Excel file"
                    aria-describedby="fileHelpId" />
                <div id="fileHelpId" class="form-text">
                    الملفات المسموح بها: .xls و .xlsx — يجب أن يحتوي الصف الأول على أسماء الأعمدة (العناوين).
                </div>
            </div>

            <div class="text-danger "> <?php echo $file_err; ?> </div>

            <button type="submit" class="btn btn-primary" name="submit">Import</button>

        </form>
    </div>

</body>

</html>