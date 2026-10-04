<?php $pageTitle = 'Penawaran Jastip'; ?>

<div class="flex-between mb-3">
    <h2 class="mb-0">Penawaran Saya</h2>
    <a href="/jastiper/penawaran/tambah" class="btn btn-primary">+ Buat Penawaran</a>
</div>

<div class="card">
    <div class="card-body" style="padding-top:0;">
        <?php if ($offers === []): ?>
            <?php emptyState('Belum ada penawaran. Buat penawaran pertama Anda.', '📢', '<a href="/jastiper/penawaran/tambah" class="btn btn-primary mt-2">Buat Penawaran</a>'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Lokasi</th>
                            <th>Periode</th>
                            <th>Deadline Order</th>
                            <th>Status</th>
                            <th class="text-right">Produk</th>
                            <th class="text-right">Pesanan</th>
                            <th class="text-right">Omzet</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($offers as $o):
                        $statusMap = [
                            'AKTIF'   => ['badge-success', 'Aktif'],
                            'DRAFT'   => ['badge-gray', 'Draft'],
                            'NONAKTIF'=> ['badge-danger', 'Nonaktif'],
                            'SELESAI' => ['badge-info', 'Selesai'],
                        ];
                        [$cls, $txt] = $statusMap[$o['status']] ?? ['badge-gray', $o['status']]; ?>
                        <tr>
                            <td>
                                <div class="bold small"><?= e($o['title']) ?></div>
                                <a class="small muted" href="/jastip/<?= (int) $o['id'] ?>">lihat halaman publik →</a>
                            </td>
                            <td class="small">📍 <?= e($o['location']) ?></td>
                            <td class="small"><?= formatDate($o['start_date']) ?> – <?= formatDate($o['end_date']) ?></td>
                            <td class="small <?= strtotime($o['order_deadline']) < time() ? 'text-danger' : '' ?>">
                                <?= formatDate($o['order_deadline'], true) ?>
                            </td>
                            <td><span class="badge <?= e($cls) ?>"><?= e($txt) ?></span></td>
                            <td class="text-right"><?= (int) $o['product_count'] ?></td>
                            <td class="text-right"><?= (int) $o['order_count'] ?></td>
                            <td class="text-right small"><?= money($o['revenue']) ?></td>
                            <td class="actions">
                                <a href="/jastiper/produk?offer=<?= (int) $o['id'] ?>" class="btn btn-ghost btn-sm">Produk</a>
                                <a href="/jastiper/penawaran/<?= (int) $o['id'] ?>/edit" class="btn btn-outline btn-sm">Edit</a>
                                <form method="post" action="/jastiper/penawaran/<?= (int) $o['id'] ?>/hapus" class="inline-form"
                                      onsubmit="return confirm('Hapus penawaran ini beserta seluruh produknya?')">
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