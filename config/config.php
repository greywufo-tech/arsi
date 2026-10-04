<?php
/**
 * Konfigurasi aplikasi E-Jastip.
 * Nilai dapat dioverride lewat environment variable.
 */
return [
    'app' => [
        'name'  => 'E-Jastip',
        'debug' => getenv('APP_DEBUG') !== '0',
        'url'   => getenv('APP_URL') ?: 'http://localhost:3000',
    ],

    'db' => [
        'host'    => getenv('DB_HOST') ?: '127.0.0.1',
        'port'    => getenv('DB_PORT') ?: '3306',
        'name'    => getenv('DB_NAME') ?: 'e_jastip',
        'user'    => getenv('DB_USER') ?: 'root',
        'pass'    => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
        'charset' => 'utf8mb4',
    ],

    // Ongkos kirim default (dapat ditimpa jastiper saat memproses pesanan)
    'shipping' => [
        'default_cost' => 30000,
    ],

    // Minimal 1 karakter untuk validasi
    'validation' => [
        'min_password_length' => 6,
    ],
];