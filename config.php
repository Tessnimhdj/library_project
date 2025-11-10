<?php


$server_name = "localhost";
$username = "root";
$password = "";
$db_name = "library";

// إذا كنا على localhost، لا نحول إلى HTTPS
/*if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('Location: ' . $httpsUrl);
    exit;
}*/


try {
    $dsn = "mysql:host=$server_name;dbname=$db_name;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage(), 3, __DIR__ . '/logs/errors.log');
    exit('Database connection failed.');
}


ini_set('display_errors', 0);
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
define('ALLOWED_MIMES', [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel'
]);
define('UPLOAD_DIR', __DIR__ . '/uploads/');

if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0750, true);
}
