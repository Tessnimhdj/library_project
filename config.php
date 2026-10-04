<?php

ini_set('display_errors', '0');

$server_name = 'localhost';
$username = 'root';
$password = '';
$db_name = 'library';

define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
define('MAX_INVENTORY_LENGTH', 64);
define('MAX_TITLE_LENGTH', 255);
define('MAX_AUTHOR_LENGTH', 255);
define('ALLOWED_MIMES', [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'application/vnd.ms-office',
    'application/zip',
    'application/x-zip-compressed',
    'application/octet-stream',
    'application/CDFV2',
]);
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('LOG_FILE', __DIR__ . '/logs/errors.log');

foreach ([__DIR__ . '/logs', UPLOAD_DIR] as $dir) {
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        exit('Storage directory is not available.');
    }
}

try {
    $dsn = "mysql:host=$server_name;dbname=$db_name;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log(date('c') . ' ' . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
    exit('Database connection failed.');
}
