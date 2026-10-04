<?php $pageTitle = 'Akses Ditolak'; ?>
<div class="card"><div class="card-body text-center" style="padding:3rem 1rem;">
    <div style="font-size:3.5rem;">🚫</div>
    <h2>403 — Akses ditolak</h2>
    <p class="muted"><?= e($message ?? 'Anda tidak memiliki izin untuk mengakses halaman ini.') ?></p>
    <a href="<?= e(Auth::homeFor(Auth::role())) ?>" class="btn btn-primary mt-2">Kembali ke Dashboard</a>
</div></div>