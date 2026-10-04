<?php
/** Layout halaman publik (landing, login, register). */
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$flashes = takeFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'E-Jastip') ?> — E-Jastip</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="site-header">
    <div class="container inner">
        <a href="/" class="brand"><span class="brand-mark">EJ</span> E-Jastip</a>
        <nav class="nav-public">
            <?php if (Auth::check()): ?>
                <a href="<?= e(Auth::homeFor(Auth::role())) ?>">Dashboard</a>
                <form method="post" action="/logout" class="inline-form">
                    <?= csrfField() ?>
                    <button class="btn btn-outline btn-sm" type="submit">Keluar</button>
                </form>
            <?php else: ?>
                <a href="/jastip" class="<?= $currentPath === '/jastip' ? 'active' : '' ?> hide-mobile">Cari Jastip</a>
                <a href="/login" class="<?= $currentPath === '/login' ? 'active' : '' ?>">Masuk</a>
                <a href="/register" class="btn btn-primary btn-sm">Daftar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main>
    <?php foreach ($flashes as $f): ?>
        <div class="container" style="margin-top:1rem;">
            <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        </div>
    <?php endforeach; ?>
    <?= $content ?>
</main>

<footer style="background:var(--gray-900);color:var(--gray-400);padding:1.5rem 0;margin-top:3rem;">
    <div class="container flex-between">
        <span>&copy; <?= date('Y') ?> E-Jastip — Sistem Informasi Jasa Titip</span>
        <span class="small">PHP <?= PHP_VERSION ?> · MySQL</span>
    </div>
</footer>
</body>
</html>