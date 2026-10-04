<?php
declare(strict_types=1);

/**
 * Router script untuk PHP built-in server:
 *   php -S localhost:3000 -t public public/router.php
 *
 * File statis (css/js/gambar) dilayani langsung, selebihnya diteruskan
 * ke front controller public/index.php.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

// Cegah path traversal
$root = realpath(__DIR__);
$real = realpath($file);
if ($real !== false && $root !== false && str_starts_with($real, $root) && is_file($real)) {
    return false; // biarkan PHP built-in server melayani file statis
}

require __DIR__ . '/index.php';