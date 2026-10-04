<?php
$pageTitle = $product['name'];
$stock = (int) $product['stock'];
$deadlinePassed = strtotime($product['order_deadline']) < time();
$unitTotal = (float) $product['price'] + (float) $product['service_fee'];
?>
<div class="mb-2">
    <a href="/jastip/<?= (int) $product['offer_id'] ?>" class="small">← <?= e($product['offer_title']) ?></a>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="product-thumb" style="height:340px;border-radius:var(--radius) var(--radius) 0 0;">
            <?php renderImage($product['image_url'], categoryIcon($product['category'])); ?>
        </div>
    </div>

    <div>
        <div class="card mb-3">
            <div class="card-body">
                <div class="product-meta"><?= e($product['category'] ?? 'Umum') ?> · 📍 <?= e($product['location']) ?></div>
                <h2 class="mb-1"><?= e($product['name']) ?></h2>

                <?php if ($product['description']): ?>
                    <p class="muted"><?= nl2br(e($product['description'])) ?></p>
                <?php endif; ?>

                <div class="divider"></div>

                <div class="summary-row">
                    <span class="label">Harga barang</span>
                    <span><strong><?= money($product['price']) ?></strong></span>
                </div>
                <div class="summary-row">
                    <span class="label">Biaya jasa titip</span>
                    <span><strong><?= money($product['service_fee']) ?></strong></span>
                </div>
                <div class="summary-row total">
                    <span class="label">Per 1 barang</span>
                    <span><?= money($unitTotal) ?></span>
                </div>
                <div class="help-text mb-2">Ongkos kirim ditambahkan saat checkout.</div>

                <div class="summary-row">
                    <span class="label">Stok tersedia</span>
                    <span>
                        <?php if ($stock <= 0): ?>
                            <span class="stock-out">Habis</span>
                        <?php elseif ($stock < 10): ?>
                            <span class="stock-low">Sisa <?= $stock ?></span>
                        <?php else: ?>
                            <?= $stock ?> barang
                        <?php endif; ?>
                    </span>
                </div>
                <div class="summary-row">
                    <span class="label">Jastiper</span>
                    <span><?= e($product['jastiper_name']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Batas pemesanan</span>
                    <span class="<?= $deadlinePassed ? 'text-danger' : '' ?>"><?= formatDate($product['order_deadline'], true) ?></span>
                </div>

                <div class="divider"></div>

                <?php if (!$canOrder): ?>
                    <div class="alert alert-warning mb-0">
                        <?php if ($deadlinePassed): ?>
                            Batas waktu pemesanan sudah lewat.
                        <?php elseif ($stock <= 0): ?>
                            Stok produk ini habis.
                        <?php else: ?>
                            Penawaran ini sedang tidak aktif.
                        <?php endif; ?>
                    </div>
                <?php elseif (Auth::is('PELANGGAN')): ?>
                    <form method="post" action="/keranjang/tambah" class="flex" style="align-items:flex-end;">
                        <?= csrfField() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <div class="form-group mb-0">
                            <label for="quantity">Jumlah</label>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $stock ?>" class="qty-input" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">+ Tambah ke Keranjang</button>
                    </form>
                    <div class="help-text mt-1">Maksimal <?= $stock ?> barang sesuai stok.</div>
                <?php elseif (!Auth::check()): ?>
                    <a href="/login" class="btn btn-primary btn-lg btn-block">Login untuk memesan</a>
                <?php else: ?>
                    <div class="alert alert-info mb-0">Hanya pelanggan yang dapat memesan barang.</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($related)): ?>
            <div class="card">
                <div class="card-header"><strong>Barang lain dari penawaran ini</strong></div>
                <div class="card-body">
                    <div class="grid grid-2">
                        <?php foreach ($related as $r): ?>
                            <a href="/produk/<?= (int) $r['id'] ?>" class="flex" style="color:inherit;text-decoration:none;">
                                <div class="product-thumb" style="width:56px;height:56px;border-radius:8px;font-size:1.5rem;">
                                    <?php renderImage($r['image_url'], categoryIcon($r['category'])); ?>
                                </div>
                                <div>
                                    <div class="small bold"><?= e($r['name']) ?></div>
                                    <div class="small text-primary"><?= money($r['price']) ?> <span class="muted">+ <?= money($r['service_fee']) ?></span></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>