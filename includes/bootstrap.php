<?php
declare(strict_types=1);

session_start();

define('ROOT_PATH', dirname(__DIR__));

$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
$rootPath = str_replace('\\', '/', realpath(ROOT_PATH) ?: ROOT_PATH);
$baseUrl = $docRoot && str_starts_with($rootPath, $docRoot)
    ? rtrim(str_replace($docRoot, '', $rootPath), '/')
    : rtrim(dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '')), '/');
if (str_ends_with($baseUrl, '/admin')) {
    $baseUrl = dirname($baseUrl);
}
if ($baseUrl === '/' || $baseUrl === '.') {
    $baseUrl = '';
}
define('BASE_URL', $baseUrl);
define('IS_ADMIN', str_contains(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/admin/'));

$config = require ROOT_PATH . '/config/app.php';
$dbConfig = require ROOT_PATH . '/config/database.php';

require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/Database.php';

try {
    $pdo = Database::connect($dbConfig);
} catch (PDOException $e) {
    if (file_exists(ROOT_PATH . '/install.php')) {
        header('Location: ' . url('install.php'));
        exit;
    }
    die('Database connection failed. Please check config/database.php');
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
