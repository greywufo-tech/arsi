<?php $pageTitle = 'Kelola Penawaran'; ?>

<h2 class="mb-3">Semua Penawaran Jastip</h2>

<div class="card">
    <div class="card-body" style="padding-top:0;">
        <?php if ($offers === []): ?>
            <?php emptyState('Belum ada penawaran.', '📢'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Jastiper</th>
                            <th>Lokasi</th>
                            <th>Periode</th>
                            <th class="text-right">Produk</th>
                            <th class="text-right">Pesanan</th>
                            <th>Status</th>
                            <th>Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($offers as $o):
                        $statusMap = [
                            'AKTIF'    => ['badge-success', 'Aktif'],
                            'DRAFT'    => ['badge-gray', 'Draft'],
                            'NONAKTIF' => ['badge-danger', 'Nonaktif'],
                            'SELESAI'  => ['badge-info', 'Selesai'],
                        ];
                        [$cls, $txt] = $statusMap[$o['status']] ?? ['badge-gray', $o['status']]; ?>
                        <tr>
                            <td>
                                <div class="bold small"><?= e($o['title']) ?></div>
                                <a class="small muted" href="/jastip/<?= (int) $o['id'] ?>">lihat →</a>
                            </td>
                            <td class="small">
                                <?= e($o['jastiper_name']) ?>
                                <?php if ($o['verified_at'] === null): ?>
                                    <span class="badge badge-warning">Belum verifikasi</span>
                                <?php endif; ?>
                            </td>
                            <td class="small">📍 <?= e($o['location']) ?></td>
                            <td class="small"><?= formatDate($o['start_date']) ?> – <?= formatDate($o['end_date']) ?></td>
                            <td class="text-right"><?= (int) $o['product_count'] ?></td>
                            <td class="text-right"><?= (int) $o['order_count'] ?></td>
                            <td><span class="badge <?= e($cls) ?>"><?= e($txt) ?></span></td>
                            <td>
                                <form method="post" action="/admin/penawaran/<?= (int) $o['id'] ?>/status" class="flex">
                                    <?= csrfField() ?>
                                    <select name="status" class="qty-input" style="width:120px;">
                                        <?php foreach (['AKTIF' => 'Aktif', 'DRAFT' => 'Draft', 'NONAKTIF' => 'Nonaktif', 'SELESAI' => 'Selesai'] as $k => $v): ?>
                                            <option value="<?= e($k) ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-outline btn-sm">Simpan</button>
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