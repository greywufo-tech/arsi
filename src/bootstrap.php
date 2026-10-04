<?php
declare(strict_types=1);

/**
 * Bootstrap aplikasi: memuat konfigurasi, membuka session aman,
 * memasukkan seluruh class, lalu menyambungkan ke database.
 */

define('BASE_PATH', dirname(__DIR__));

$config = require BASE_PATH . '/config/config.php';
$GLOBALS['config'] = $config;

ini_set('display_errors', $config['app']['debug'] ? '1' : '0');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

// ------------------------------------------------------------------
// Session
// ------------------------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('EJASTIPSESSID');
    session_start();
}

// ------------------------------------------------------------------
// Class & helper
// ------------------------------------------------------------------
require_once BASE_PATH . '/src/Database.php';
require_once BASE_PATH . '/src/helpers.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Cart.php';
require_once BASE_PATH . '/src/Status.php';
require_once BASE_PATH . '/src/BusinessRuleException.php';
require_once BASE_PATH . '/src/Validator.php';
require_once BASE_PATH . '/src/OrderService.php';

// ------------------------------------------------------------------
// Database
// ------------------------------------------------------------------
try {
    Database::init($config['db']);
} catch (PDOException $e) {
    http_response_code(503);
    $message = $config['app']['debug']
        ? $e->getMessage()
        : 'Database tidak dapat diakses. Pastikan MySQL berjalan dan konfigurasi benar.';
    exit('<h1>503 - Database tidak tersedia</h1><pre>' . htmlspecialchars($message, ENT_QUOTES) . '</pre>');
}