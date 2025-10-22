<?php
// ==========================
// CONFIG DATABASE
// ==========================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'Sys-ASLPDC-T2B2');

// ==========================
// DEBUG MODE
// ==========================
// Ubah ke `0` kalau sudah live / produksi
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ==========================
// SESSION SETTINGS
if (session_status() === PHP_SESSION_NONE) {
    session_name('Sys-ASLPDC-T2B2_sess'); // set name DULU
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}



// ==========================
// DATABASE CONNECTION (PDO)
// ==========================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Munculkan error (debug)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Hasil query = array asosiatif
            PDO::ATTR_EMULATE_PREPARES => false // Query lebih aman
        ]
    );
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
