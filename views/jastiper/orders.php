<?php $pageTitle = 'Pesanan Masuk'; ?>

<h2 class="mb-3">Pesanan Masuk</h2>

<div class="flex mb-3" style="flex-wrap:wrap;">
    <a href="/jastiper/pesanan" class="btn <?= $status === '' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Semua</a>
    <?php foreach (Status::LABELS as $key => $label): ?>
        <a href="/jastiper/pesanan?status=<?= e($key) ?>" class="btn <?= $status === $key ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-body" style="padding-top:0;">
        <?php if ($orders === []): ?>
            <?php emptyState('Belum ada pesanan pada filter ini.', '🧾'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th class="text-right">Item</th>
                            <th class="text-right">Total</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o):
                        $items = Database::value('SELECT COALESCE(SUM(quantity),0) FROM order_details WHERE order_id = ?', [$o['id']]); ?>
                        <tr>
                            <td class="bold small"><?= e($o['order_code']) ?></td>
                            <td class="small">
                                <?= e($o['customer_name']) ?>
                                <div class="muted"><?= e($o['customer_email']) ?></div>
                            </td>
                            <td class="small"><?= formatDate($o['order_date']) ?></td>
                            <td class="text-right"><?= (int) $items ?></td>
                            <td class="text-right bold"><?= money($o['total']) ?></td>
                            <td><?php paymentBadge($o['payment_status']) ?></td>
                            <td><?php statusBadge($o['status']) ?></td>
                            <td class="actions">
                                <a href="/jastiper/pesanan/<?= (int) $o['id'] ?>" class="btn btn-primary btn-sm">Proses</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>