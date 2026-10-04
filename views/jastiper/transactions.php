<?php $pageTitle = 'Riwayat Transaksi'; ?>

<div class="grid grid-4 mb-3">
    <div class="stat-card">
        <div class="stat-icon bg-indigo">🧾</div>
        <div><div class="stat-value"><?= (int) $summary['count'] ?></div><div class="stat-label">Total Transaksi</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-green">💰</div>
        <div>
            <div class="stat-value" style="font-size:1.25rem;"><?= money($summary['total']) ?></div>
            <div class="stat-label">Nilai Lunas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-teal">✅</div>
        <div><div class="stat-value"><?= (int) $summary['lunas'] ?></div><div class="stat-label">Lunas</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-amber">⏳</div>
        <div><div class="stat-value"><?= (int) $summary['pending'] ?></div><div class="stat-label">Menunggu Verifikasi</div></div>
    </div>
</div>

<?php if ($monthly !== []): ?>
    <div class="card mb-3">
        <div class="card-header"><strong>Omzet 6 Bulan Terakhir</strong></div>
        <div class="card-body">
            <?php
            $maxVal = max(array_map(fn ($m) => (float) $m['total'], $monthly)) ?: 1;
            ?>
            <div class="chart">
                <?php foreach (array_reverse($monthly) as $m): ?>
                    <div class="chart-col">
                        <div class="chart-track">
                            <div class="chart-bar" style="height:<?= max(3, ((float) $m['total'] / $maxVal) * 100) ?>%">
                                <span><?= money($m['total']) ?></span>
                            </div>
                        </div>
                        <div class="chart-label"><?= e(date('M Y', strtotime($m['month'] . '-01'))) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><strong>Daftar Transaksi</strong></div>
    <div class="card-body" style="padding-top:0;">
        <?php if ($payments === []): ?>
            <?php emptyState('Belum ada transaksi.', '💰'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Metode</th>
                            <th class="text-right">Jumlah</th>
                            <th>Status</th>
                            <th>Diverifikasi</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td class="bold small"><?= e($p['order_code']) ?></td>
                            <td class="small"><?= e($p['customer_name']) ?></td>
                            <td class="small"><?= formatDate($p['order_date']) ?></td>
                            <td class="small"><?= e(paymentMethodLabel($p['method'])) ?></td>
                            <td class="text-right bold"><?= money($p['amount']) ?></td>
                            <td><?php paymentBadge($p['status']) ?></td>
                            <td class="small muted"><?= formatDate($p['verified_at'], true) ?></td>
                            <td class="actions"><a href="/jastiper/pesanan/<?= (int) $p['order_id'] ?>" class="btn btn-outline btn-sm">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>