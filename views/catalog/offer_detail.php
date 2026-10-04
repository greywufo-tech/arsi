<?php
$pageTitle = $offer['title'];
$deadlinePassed = strtotime($offer['order_deadline']) < time();
?>
<div class="mb-2">
    <a href="/jastip" class="small">← Kembali ke daftar jastip</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="flex-between">
            <div>
                <h2 class="mb-1"><?= e($offer['title']) ?></h2>
                <div class="muted small">
                    📍 <?= e($offer['location']) ?> · oleh <strong><?= e($offer['jastiper_name']) ?></strong>
                    <?php if ($offer['jastiper_phone']): ?>
                        · 📞 <?= e($offer['jastiper_phone']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-right">
                <?php
                $statusMap = ['AKTIF' => ['badge-success', 'Aktif'], 'DRAFT' => ['badge-gray', 'Draft'], 'NONAKTIF' => ['badge-danger', 'Nonaktif'], 'SELESAI' => ['badge-gray', 'Selesai']];
                [$cls, $txt] = $statusMap[$offer['status']] ?? ['badge-gray', $offer['status']];
                ?>
                <span class="badge <?= e($cls) ?>"><?= e($txt) ?></span>
            </div>
        </div>

        <?php if ($offer['description']): ?>
            <p class="mt-2 mb-2"><?= nl2br(e($offer['description'])) ?></p>
        <?php endif; ?>

        <div class="grid grid-3">
            <div class="small"><strong>Periode jastip</strong><br>
                <span class="muted"><?= formatDate($offer['start_date']) ?> – <?= formatDate($offer['end_date']) ?></span>
            </div>
            <div class="small"><strong>Batas waktu pemesanan</strong><br>
                <span class="<?= $deadlinePassed ? 'text-danger' : 'text-success' ?>"><?= formatDate($offer['order_deadline'], true) ?></span>
            </div>
            <div class="small"><strong>Jumlah produk</strong><br>
                <span class="muted"><?= count($products) ?> produk</span>
            </div>
        </div>
    </div>
</div>

<?php if (!$canOrder): ?>
    <div class="alert alert-warning">
        Penawaran ini tidak dapat dipesan saat ini
        <?= $deadlinePassed ? 'karena batas waktu pemesanan sudah lewat.' : 'karena belum/ tidak aktif.' ?>
    </div>
<?php endif; ?>

<?php if ($products === []): ?>
    <div class="card"><div class="card-body">
        <?php emptyState('Jastiper belum menambahkan produk untuk penawaran ini.', '🏷️'); ?>
    </div></div>
<?php else: ?>
    <h3>Daftar Barang</h3>
    <div class="grid grid-products mt-2">
        <?php foreach ($products as $p): ?>
            <div class="product-card">
                <a href="/produk/<?= (int) $p['id'] ?>" style="text-decoration:none;color:inherit;">
                    <div class="product-thumb"><?php renderImage($p['image_url'], categoryIcon($p['category'])); ?></div>
                </a>
                <div class="product-info">
                    <div class="product-meta">
                        <?= e($p['category'] ?? 'Umum') ?>
                    </div>
                    <a href="/produk/<?= (int) $p['id'] ?>"><div class="product-name"><?= e($p['name']) ?></div></a>
                    <div class="price"><?= money($p['price']) ?></div>
                    <div class="fee">+ Jastip <?= money($p['service_fee']) ?></div>
                    <div class="product-footer flex-between">
                        <?php if ((int) $p['stock'] <= 0): ?>
                            <span class="stock-out">Stok habis</span>
                        <?php elseif ((int) $p['stock'] < 10): ?>
                            <span class="stock-low">Sisa <?= (int) $p['stock'] ?></span>
                        <?php else: ?>
                            <span class="small muted">Stok <?= (int) $p['stock'] ?></span>
                        <?php endif; ?>
                        <a href="/produk/<?= (int) $p['id'] ?>" class="btn btn-outline btn-sm">Detail</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>