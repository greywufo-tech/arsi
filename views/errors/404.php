<?php $pageTitle = 'Halaman Tidak Ditemukan'; ?>
<div class="card"><div class="card-body text-center" style="padding:3rem 1rem;">
    <div style="font-size:3.5rem;">🔍</div>
    <h2>404 — Halaman tidak ditemukan</h2>
    <p class="muted"><?= e($message ?? 'Alamat yang Anda tuju tidak tersedia atau sudah dipindahkan.') ?></p>
    <a href="<?= e(Auth::homeFor(Auth::role())) ?>" class="btn btn-primary mt-2">Kembali</a>
</div></div>