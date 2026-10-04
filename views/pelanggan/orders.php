<?php $pageTitle = 'Pesanan Saya'; ?>

<div class="flex-between mb-3">
    <h2 class="mb-0">Pesanan Saya</h2>
    <a href="/jastip" class="btn btn-primary btn-sm">+ Pesan Barang</a>
</div>

<div class="flex mb-3" style="flex-wrap:wrap;">
    <a href="/pesanan" class="btn <?= $status === '' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Semua</a>
    <?php foreach (Status::LABELS as $key => $label): ?>
        <a href="/pesanan?status=<?= e($key) ?>" class="btn <?= $status === $key ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-body" style="padding-top:0;">
        <?php if ($orders === []): ?>
            <?php emptyState('Belum ada pesanan pada filter ini.', '📦', '<a href="/jastip" class="btn btn-primary mt-2">Jelajahi Jastip</a>'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Penawaran</th>
                            <th>Jastiper</th>
                            <th>Tanggal</th>
                            <th class="text-right">Total</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="bold small"><?= e($o['order_code']) ?></td>
                            <td class="small"><?= e($o['offer_title']) ?><br><span class="muted">📍 <?= e($o['location']) ?></span></td>
                            <td class="small"><?= e($o['jastiper_name']) ?></td>
                            <td class="small"><?= formatDate($o['order_date']) ?></td>
                            <td class="text-right bold"><?= money($o['total']) ?></td>
                            <td><?php paymentBadge($o['payment_status']) ?></td>
                            <td><?php statusBadge($o['status']) ?></td>
                            <td class="actions"><a href="/pesanan/<?= (int) $o['id'] ?>" class="btn btn-outline btn-sm">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>