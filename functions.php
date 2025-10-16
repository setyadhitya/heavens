<?php
require_once __DIR__ . '/config.php';

// CSRF helpers
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}

// Output escaping
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Simple auth check
function is_logged_in() {
    if (empty($_SESSION['user_id'])) return false;
    // Session timeout (30 minutes)
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // expired
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

// Simple brute-force protection (session based)
function record_failed_login() {
    if (!isset($_SESSION['failed_login'])) {
        $_SESSION['failed_login'] = ['count' => 0, 'last' => 0];
    }
    $_SESSION['failed_login']['count'] += 1;
    $_SESSION['failed_login']['last'] = time();
}

function too_many_failed_logins() {
    if (empty($_SESSION['failed_login'])) return false;
    $fail = $_SESSION['failed_login'];
    if ($fail['count'] >= 5 && (time() - $fail['last']) < 300) {
        return true;
    }
    // reset attempts after 10 minutes
    if ((time() - $fail['last']) > 600) {
        unset($_SESSION['failed_login']);
    }
    return false;
}
