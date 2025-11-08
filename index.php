<?php
require 'vendor/autoload.php';

include 'config.php';

$file_err = $err_msg = "";
$valid_ext = array("xls", "xlsx");
if (isset($_POST['submit'])) {
    if ($_FILES['input_file']['name'] == '') {
        $file_err = "Please Select File";
    } else {
        //proced for upload
        $file_name = $_FILES['input_file']['name'];
        $tmp_name = $_FILES['input_file']['tmp_name'];

        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (in_array($ext, $valid_ext)) {
            $new_file = time() . basename($file_name);

            try {
                move_uploaded_file($tmp_name, "uploads/" . $new_file);
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                $spreadsheet = $reader->load("uploads/" . $new_file);
                $worksheet = $spreadsheet->getActiveSheet();
                $data = $worksheet->toArray();
                unset($data[0]);
                foreach ($data as $row) {
                    $inventory_number = $row[0];
                    $title = $row[1];
                    $author = $row[2];
                    $notes = $row[3];

                    $sql = "SELECT * FROM books WHERE inventory_number = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $inventory_number);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows == 0) {
                        $sql = "INSERT INTO books (inventory_number, title, author, notes) VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssss", $inventory_number, $title, $author, $notes);
                        $stmt->execute();
                    }
                }
            } catch (Exception $e) {
                $err_msg = "Error uploading file: " . $e->getMessage();
            }
        } else {
            $err_msg = "Invalid File Extension";
        }
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
        <?php
        if (!empty($err_msg)) {
            echo '<div class="alert alert-danger" role="alert">' . $err_msg . '</div>';
        }

        ?>

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