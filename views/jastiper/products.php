<?php $pageTitle = 'Produk Saya'; ?>

<div class="flex-between mb-3">
    <h2 class="mb-0">Produk Saya</h2>
    <a href="/jastiper/produk/tambah" class="btn btn-primary">+ Tambah Produk</a>
</div>

<form method="get" action="/jastiper/produk" class="card mb-3">
    <div class="card-body">
        <div class="filters">
            <div class="form-group">
                <label>Tampilkan</label>
                <select name="offer" onchange="this.form.submit()">
                    <option value="0">Semua penawaran</option>
                    <?php foreach ($offers as $o): ?>
                        <option value="<?= (int) $o['id'] ?>" <?= $offerId === (int) $o['id'] ? 'selected' : '' ?>>
                            <?= e($o['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a href="/jastiper/produk" class="btn btn-outline">Semua</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body" style="padding-top:0;">
        <?php if ($products === []): ?>
            <?php emptyState('Belum ada produk.', '🏷️', '<a href="/jastiper/produk/tambah" class="btn btn-primary mt-2">Tambah Produk</a>'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Penawaran</th>
                            <th>Kategori</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Jastip</th>
                            <th class="text-right">Stok</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <div class="bold small"><?= e($p['name']) ?></div>
                                <div class="small muted"><?= e(mb_strimwidth((string) ($p['description'] ?? ''), 0, 60, '…')) ?></div>
                            </td>
                            <td class="small"><?= e($p['offer_title']) ?></td>
                            <td class="small"><span class="tag"><?= e($p['category'] ?? 'Umum') ?></span></td>
                            <td class="text-right small"><?= money($p['price']) ?></td>
                            <td class="text-right small"><?= money($p['service_fee']) ?></td>
                            <td class="text-right">
                                <?php if ((int) $p['stock'] <= 0): ?>
                                    <span class="stock-out">0</span>
                                <?php elseif ((int) $p['stock'] < 10): ?>
                                    <span class="stock-low"><?= (int) $p['stock'] ?></span>
                                <?php else: ?>
                                    <?= (int) $p['stock'] ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) $p['is_active'] === 1): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="/produk/<?= (int) $p['id'] ?>" class="btn btn-ghost btn-sm">Lihat</a>
                                <a href="/jastiper/produk/<?= (int) $p['id'] ?>/edit" class="btn btn-outline btn-sm">Edit</a>
                                <form method="post" action="/jastiper/produk/<?= (int) $p['id'] ?>/hapus" class="inline-form"
                                      onsubmit="return confirm('Hapus produk ini?')">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>