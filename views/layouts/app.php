<?php
/**
 * Layout aplikasi (dashboard pelanggan, jastiper, admin).
 * Navigasi menyesuaikan role pengguna.
 */
$currentPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
if ($currentPath === '') { $currentPath = '/'; }
$currentUser = Auth::user();
$role = $currentUser['role'] ?? null;
$pageTitle = $pageTitle ?? 'Dashboard';
$flashes = takeFlash();

/** Item navigasi per role. */
function navItems(?string $role): array
{
    return match ($role) {
        'PELANGGAN' => [
            ['Dashboard',      '/pelanggan/dashboard', '📊'],
            ['Jelajahi Jastip','/jastip',              '🔍'],
            ['Semua Produk',   '/produk',              '🛍️'],
            ['Pesanan Saya',   '/pesanan',             '📦'],
            ['Profil',         '/profil',              '👤'],
        ],
        'JASTIPER' => [
            ['Dashboard',           '/jastiper/dashboard',    '📊'],
            ['Penawaran Jastip',    '/jastiper/penawaran',    '📢'],
            ['Produk',              '/jastiper/produk',       '🏷️'],
            ['Pesanan Masuk',       '/jastiper/pesanan',      '🧾'],
            ['Pengiriman',          '/jastiper/pengiriman',   '🚚'],
            ['Riwayat Transaksi',   '/jastiper/transaksi',    '💰'],
            ['Profil',              '/profil',                '👤'],
        ],
        'ADMIN' => [
            ['Dashboard',    '/admin/dashboard',   '📊'],
            ['Pengguna',     '/admin/pengguna',    '👥'],
            ['Jastiper',     '/admin/jastiper',    '🧑‍💼'],
            ['Penawaran',    '/admin/penawaran',   '📢'],
            ['Pesanan',      '/admin/pesanan',     '📦'],
            ['Transaksi',    '/admin/transaksi',   '💰'],
        ],
        default => [
            ['Beranda', '/',            '🏠'],
            ['Jastip',  '/jastip',      '🔍'],
            ['Produk',  '/produk',      '🛍️'],
        ],
    };
}

function isActive(string $currentPath, string $link): bool
{
    return $currentPath === rtrim($link, '/') || ($link !== '/' && str_starts_with($currentPath, rtrim($link, '/')));
}

$roleLabels = [
    'PELANGGAN' => 'Pelanggan',
    'JASTIPER'  => 'Jastiper',
    'ADMIN'     => 'Administrator',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — E-Jastip</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-shell">

    <aside class="sidebar" id="sidebar">
        <a href="<?= e(Auth::homeFor($role)) ?>" class="brand"><span class="brand-mark">EJ</span> E-Jastip</a>
        <div class="sidebar-section">Menu</div>
        <?php foreach (navItems($role) as [$label, $link, $icon]): ?>
            <a href="<?= e($link) ?>" class="<?= isActive($currentPath, $link) ? 'active' : '' ?>">
                <span class="ico"><?= $icon ?></span> <?= e($label) ?>
            </a>
        <?php endforeach; ?>
        <div style="margin-top:1.5rem;padding:0 .4rem;">
            <form method="post" action="/logout">
                <?= csrfField() ?>
                <button class="btn btn-outline btn-sm btn-block" type="submit" style="background:transparent;color:#cbd5e1;border-color:#475569;">Keluar</button>
            </form>
        </div>
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <div class="flex">
                <button class="btn btn-outline btn-sm menu-toggle" type="button" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
                <h1><?= e($pageTitle) ?></h1>
            </div>
            <div class="topbar-user">
                <?php if ($currentUser): ?>
                    <span class="badge badge-gray"><?= e($roleLabels[$role] ?? $role) ?></span>
                    <span class="avatar"><?= e(strtoupper(mb_substr($currentUser['name'], 0, 1))) ?></span>
                    <span><?= e($currentUser['name']) ?></span>
                <?php else: ?>
                    <a href="/login" class="btn btn-outline btn-sm">Masuk</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="app-content">
            <?php foreach ($flashes as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
            <?php endforeach; ?>
            <?= $content ?>
        </div>
    </div>
</div>
</body>
</html>