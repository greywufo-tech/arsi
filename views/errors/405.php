<?php $pageTitle = 'Metode Tidak Diizinkan'; ?>
<div class="card"><div class="card-body text-center" style="padding:3rem 1rem;">
    <div style="font-size:3.5rem;">⚠️</div>
    <h2>405 — Metode permintaan tidak diizinkan</h2>
    <p class="muted">Permintaan ini tidak tersedia untuk halaman tersebut.</p>
    <a href="<?= e(Auth::homeFor(Auth::role())) ?>" class="btn btn-primary mt-2">Kembali</a>
</div></div>