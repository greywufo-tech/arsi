<?php $pageTitle = 'Produk'; ?>
<h2 class="mb-0">Semua Produk</h2>
<p class="muted small"><?= count($products) ?> produk dari penawaran aktif</p>

<form method="get" action="/produk" class="card mt-2 mb-3">
    <div class="card-body">
        <div class="filters">
            <div class="form-group">
                <label>Cari produk</label>
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Nama atau deskripsi…">
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori">
                    <option value="">Semua kategori</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= e($c['category']) ?>" <?= $category === $c['category'] ? 'selected' : '' ?>>
                            <?= e($c['category']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Cari</button>
            <a href="/produk" class="btn btn-outline">Reset</a>
        </div>
    </div>
</form>

<?php if ($products === []): ?>
    <div class="card"><div class="card-body">
        <?php emptyState('Tidak ada produk yang cocok.', '🔍'); ?>
    </div></div>
<?php else: ?>
    <div class="grid grid-products">
        <?php foreach ($products as $p):
            $deadlinePassed = strtotime($p['order_deadline']) < time(); ?>
            <div class="product-card">
                <a href="/produk/<?= (int) $p['id'] ?>" style="text-decoration:none;color:inherit;">
                    <div class="product-thumb"><?php renderImage($p['image_url'], categoryIcon($p['category'])); ?></div>
                </a>
                <div class="product-info">
                    <div class="product-meta"><?= e($p['category'] ?? 'Umum') ?> · 📍 <?= e($p['location']) ?></div>
                    <a href="/produk/<?= (int) $p['id'] ?>"><div class="product-name"><?= e($p['name']) ?></div></a>
                    <div class="small muted"><?= e($p['offer_title']) ?></div>
                    <div class="price mt-1"><?= money($p['price']) ?></div>
                    <div class="fee">+ Jastip <?= money($p['service_fee']) ?></div>
                    <div class="product-footer flex-between">
                        <?php if ($deadlinePassed): ?>
                            <span class="stock-out">Deadline lewat</span>
                        <?php elseif ((int) $p['stock'] <= 0): ?>
                            <span class="stock-out">Stok habis</span>
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