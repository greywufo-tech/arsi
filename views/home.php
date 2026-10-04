<?php $pageTitle = 'Beranda'; ?>
<section class="hero">
    <div class="container">
        <h1>Belanja dari Manila &amp; Jakarta, tanpa ribet</h1>
        <p>
            E-Jastip mempertemukan Anda dengan jastiper lokal. Pilih penawaran,
            pesan barang yang Anda inginkan, dan pantau status pengiriman sampai
            barang tiba di tangan Anda.
        </p>
        <div class="hero-actions">
            <a href="/jastip" class="btn btn-primary btn-lg">Jelajahi Penawaran</a>
            <a href="/register" class="btn btn-outline btn-lg" style="background:transparent;color:#fff;border-color:#6366f1;">Daftar Gratis</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="grid grid-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-indigo">📢</div>
                <div>
                    <div class="stat-value"><?= (int) $stats['offers'] ?></div>
                    <div class="stat-label">Penawaran Aktif</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green">🧑‍💼</div>
                <div>
                    <div class="stat-value"><?= (int) $stats['jastiper'] ?></div>
                    <div class="stat-label">Jastiper Terdaftar</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-amber">📦</div>
                <div>
                    <div class="stat-value"><?= (int) $stats['orders'] ?></div>
                    <div class="stat-label">Pesanan Diproses</div>
                </div>
            </div>
        </div>

        <div class="section-head">
            <div>
                <h2>Penawaran Terbaru</h2>
                <p class="muted mb-0">Perjalanan titip yang sedang dibuka oleh jastiper.</p>
            </div>
            <a href="/jastip" class="btn btn-outline btn-sm">Lihat semua →</a>
        </div>

        <?php if ($offers === []): ?>
            <div class="card"><div class="card-body">
                <?php emptyState('Belum ada penawaran aktif saat ini.', '📭'); ?>
            </div></div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($offers as $o): ?>
                    <a href="/jastip/<?= (int) $o['id'] ?>" style="text-decoration:none;">
                        <div class="product-card">
                            <div class="product-thumb"><?php renderImage($o['image_url'], '🧳'); ?></div>
                            <div class="product-info">
                                <div class="product-name"><?= e($o['title']) ?></div>
                                <div class="product-meta">📍 <?= e($o['location']) ?> · <?= e($o['jastiper_name']) ?></div>
                                <div class="small muted mt-1">
                                    <?= (int) $o['product_count'] ?> produk ·
                                    Deadline <?= formatDate($o['order_deadline']) ?>
                                </div>
                                <div class="product-footer">
                                    <?php if ($o['min_price'] !== null): ?>
                                        <span class="price"><?= money($o['min_price']) ?></span>
                                        <span class="muted small">mulai</span>
                                    <?php else: ?>
                                        <span class="muted small">Belum ada produk</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section" style="background:#fff;border-top:1px solid var(--gray-200);">
    <div class="container">
        <h2>Cara Kerja</h2>
        <div class="grid grid-4 mt-2">
            <div class="card"><div class="card-body">
                <div class="stat-icon bg-indigo mb-1">1️⃣</div>
                <h3>Pilih Penawaran</h3>
                <p class="muted small mb-0">Lihat lokasi, periode, dan daftar barang dari jastiper.</p>
            </div></div>
            <div class="card"><div class="card-body">
                <div class="stat-icon bg-purple mb-1">2️⃣</div>
                <h3>Memesan</h3>
                <p class="muted small mb-0">Tentukan jumlah, isi alamat, dan sistem menghitung total otomatis.</p>
            </div></div>
            <div class="card"><div class="card-body">
                <div class="stat-icon bg-amber mb-1">3️⃣</div>
                <h3>Bayar</h3>
                <p class="muted small mb-0">Transfer bank, e-wallet, atau COD. Pembayaran diverifikasi jastiper.</p>
            </div></div>
            <div class="card"><div class="card-body">
                <div class="stat-icon bg-green mb-1">4️⃣</div>
                <h3>Pantau &amp; Terima</h3>
                <p class="muted small mb-0">Ikuti status pesanan sampai nomor resi dan barang diterima.</p>
            </div></div>
        </div>
    </div>
</section>