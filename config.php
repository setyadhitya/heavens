<?php
// config.php - update with your DB credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'php_login');

ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start secure session settings
session_name('php_login_sess');
session_start([
    'cookie_httponly' => true,
    // 'cookie_secure' => true, // uncomment when using HTTPS
    'cookie_samesite' => 'Lax',
]);

// Database connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    die("DB connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
