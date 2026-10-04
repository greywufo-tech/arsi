<?php $pageTitle = 'Pesanan'; ?>

<div class="flex-between mb-3">
    <h2 class="mb-0">Seluruh Pesanan</h2>
    <span class="muted small"><?= count($orders) ?> pesanan</span>
</div>

<form method="get" action="/admin/pesanan" class="card mb-3">
    <div class="card-body">
        <div class="filters">
            <div class="form-group">
                <label>Cari</label>
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Kode pesanan, nama…">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">Semua status</option>
                    <?php foreach (Status::LABELS as $k => $label): ?>
                        <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a href="/admin/pesanan" class="btn btn-outline">Reset</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body" style="padding-top:0;">
        <?php if ($orders === []): ?>
            <?php emptyState('Tidak ada pesanan.', '📦'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pelanggan</th>
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
                            <td class="small"><?= e($o['customer_name']) ?></td>
                            <td class="small"><?= e($o['jastiper_name']) ?></td>
                            <td class="small"><?= formatDate($o['order_date']) ?></td>
                            <td class="text-right bold"><?= money($o['total']) ?></td>
                            <td><?php paymentBadge($o['payment_status']) ?></td>
                            <td><?php statusBadge($o['status']) ?></td>
                            <td class="actions"><a href="/admin/pesanan/<?= (int) $o['id'] ?>" class="btn btn-outline btn-sm">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>