<?php $pageTitle = 'Daftar Jastip'; ?>
<div class="flex-between mb-3">
    <div>
        <h2 class="mb-0">Daftar Penawaran Jastip</h2>
        <p class="muted small mb-0"><?= count($offers) ?> penawaran tersedia</p>
    </div>
</div>

<form method="get" action="/jastip" class="card mb-3">
    <div class="card-body">
        <div class="filters">
            <div class="form-group">
                <label>Cari</label>
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Judul, lokasi, deskripsi…">
            </div>
            <div class="form-group">
                <label>Lokasi</label>
                <select name="lokasi">
                    <option value="">Semua lokasi</option>
                    <?php foreach ($locations as $l): ?>
                        <option value="<?= e($l['location']) ?>" <?= $location === $l['location'] ? 'selected' : '' ?>>
                            <?= e($l['location']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a href="/jastip" class="btn btn-outline">Reset</a>
        </div>
    </div>
</form>

<?php if ($offers === []): ?>
    <div class="card"><div class="card-body">
        <?php emptyState('Tidak ada penawaran yang cocok dengan pencarian Anda.', '🔍'); ?>
    </div></div>
<?php else: ?>
    <div class="grid grid-3">
        <?php foreach ($offers as $o): ?>
            <div class="product-card">
                <a href="/jastip/<?= (int) $o['id'] ?>" style="text-decoration:none;color:inherit;">
                    <div class="product-thumb"><?php renderImage($o['image_url'], '🧳'); ?></div>
                </a>
                <div class="product-info">
                    <a href="/jastip/<?= (int) $o['id'] ?>"><div class="product-name"><?= e($o['title']) ?></div></a>
                    <div class="product-meta">📍 <?= e($o['location']) ?> · oleh <?= e($o['jastiper_name']) ?></div>
                    <div class="small muted mt-1">
                        🗓 <?= formatDate($o['start_date']) ?> – <?= formatDate($o['end_date']) ?>
                    </div>
                    <div class="small">
                        ⏰ Deadline order: <strong><?= formatDate($o['order_deadline'], true) ?></strong>
                    </div>
                    <div class="small muted"><?= (int) $o['product_count'] ?> produk tersedia</div>
                    <div class="product-footer flex-between">
                        <?php if ($o['min_price'] !== null): ?>
                            <span class="price"><?= money($o['min_price']) ?></span>
                        <?php else: ?>
                            <span class="muted small">Belum ada produk</span>
                        <?php endif; ?>
                        <a href="/jastip/<?= (int) $o['id'] ?>" class="btn btn-primary btn-sm">Lihat Detail</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>