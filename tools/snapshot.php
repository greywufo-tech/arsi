<?php
declare(strict_types=1);

/**
 * Alat bantu pengembangan: merender halaman yang butuh login ke file HTML
 * agar dapat diambil screenshot-nya. Tidak mengubah kode aplikasi.
 *
 * Contoh: php tools/snapshot.php admin /admin/dashboard /tmp/admin.html
 */
if ($argc < 4) {
    fwrite(STDERR, "Pakai: php tools/snapshot.php <email> <path> <keluaran.html>\n");
    exit(1);
}

[, $email, $path, $outFile] = $argv;

require dirname(__DIR__) . '/src/bootstrap.php';

$user = Database::one('SELECT * FROM users WHERE email = ?', [$email]);
if (!$user) {
    fwrite(STDERR, "Pengguna tidak ditemukan: {$email}\n");
    exit(1);
}

// WAJIB setelah bootstrap: session_start() akan mengosongkan $_SESSION.
$_SESSION['user_id'] = (int) $user['id'];

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = $path;

ob_start();
require BASE_PATH . '/public/index.php';
$html = (string) ob_get_clean();

// Path absolut (/assets/...) tidak resolve saat dibuka lewat file://
$html = str_replace('href="/assets/', 'href="http://localhost:3000/assets/', $html);

file_put_contents($outFile, $html);
echo "Tersimpan: {$outFile} (" . strlen($html) . " byte)\n";