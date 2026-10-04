<?php $pageTitle = 'Pengiriman'; ?>

<h2 class="mb-3">Daftar Pengiriman</h2>

<div class="card">
    <div class="card-body" style="padding-top:0;">
        <?php if ($orders === []): ?>
            <?php emptyState('Belum ada pesanan yang perlu dikirim.', '🚚'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pelanggan</th>
                            <th>Alamat</th>
                            <th>Kurir</th>
                            <th>No. Resi</th>
                            <th>Tanggal Kirim</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="bold small"><?= e($o['order_code']) ?></td>
                            <td class="small"><?= e($o['customer_name']) ?></td>
                            <td class="small muted" style="max-width:220px;"><?= e(mb_strimwidth((string) $o['shipping_address'], 0, 60, '…')) ?></td>
                            <td class="small"><?= e($o['courier_name'] ?: '-') ?></td>
                            <td class="small"><?= $o['tracking_number'] ? '<strong class="text-primary">' . e($o['tracking_number']) . '</strong>' : '-' ?></td>
                            <td class="small"><?= formatDate($o['shipped_at']) ?></td>
                            <td><?php statusBadge($o['status']) ?></td>
                            <td class="actions">
                                <a href="/jastiper/pesanan/<?= (int) $o['id'] ?>" class="btn btn-outline btn-sm">Kelola</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>