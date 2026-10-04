<?php $pageTitle = 'Dashboard Admin'; ?>

<div class="grid grid-4 mb-3">
    <div class="stat-card">
        <div class="stat-icon bg-indigo">👥</div>
        <div><div class="stat-value"><?= (int) $stats['users'] ?></div><div class="stat-label">Total Pelanggan</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-purple">🧑‍💼</div>
        <div><div class="stat-value"><?= (int) $stats['jastipers'] ?></div><div class="stat-label">Total Jastiper</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-teal">📢</div>
        <div><div class="stat-value"><?= (int) $stats['active_offers'] ?></div><div class="stat-label">Penawaran Aktif</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-amber">📦</div>
        <div><div class="stat-value"><?= (int) $stats['orders'] ?></div><div class="stat-label">Total Pesanan</div></div>
    </div>
</div>

<div class="grid grid-3 mb-3">
    <div class="stat-card span-2">
        <div class="stat-icon bg-green">💰</div>
        <div>
            <div class="stat-value"><?= money($stats['revenue']) ?></div>
            <div class="stat-label">Total Transaksi (<?= (int) $stats['transactions'] ?> pembayaran)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-red">⏳</div>
        <div><div class="stat-value"><?= (int) $stats['unverified'] ?></div><div class="stat-label">Jastiper Belum Verifikasi</div></div>
    </div>
</div>

<div class="grid grid-2 mb-3" style="align-items:start;">
    <div class="card">
        <div class="card-header">
            <strong>Pesanan per Bulan</strong>
            <a href="/admin/transaksi" class="btn btn-outline btn-sm">Laporan lengkap</a>
        </div>
        <div class="card-body">
            <?php if ($monthlyOrders === []): ?>
                <?php emptyState('Belum ada data pesanan.', '📊'); ?>
            <?php else: ?>
                <?php $maxVal = max(array_map(fn ($m) => (int) $m['total'], $monthlyOrders)) ?: 1; ?>
                <div class="chart">
                    <?php foreach ($monthlyOrders as $m): ?>
                        <div class="chart-col">
                            <div class="chart-track">
                                <div class="chart-bar" style="height:<?= max(3, ((int) $m['total'] / $maxVal) * 100) ?>%">
                                    <span><?= (int) $m['total'] ?></span>
                                </div>
                            </div>
                            <div class="chart-label"><?= e(date('M Y', strtotime($m['label'] . '-01'))) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Pesanan per Status</strong></div>
        <div class="card-body">
            <?php if ($byStatus === []): ?>
                <?php emptyState('Belum ada pesanan.', '📊'); ?>
            <?php else: ?>
                <table>
                    <tbody>
                    <?php foreach ($byStatus as $s): ?>
                        <tr>
                            <td><?php statusBadge($s['status']) ?></td>
                            <td class="text-right bold"><?= (int) $s['total'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <div class="card-header">
            <strong>Transaksi Terbaru</strong>
            <a href="/admin/transaksi" class="btn btn-outline btn-sm">Lihat semua</a>
        </div>
        <div class="card-body" style="padding-top:0;">
            <?php if ($recentPayments === []): ?>
                <?php emptyState('Belum ada transaksi.', '💰'); ?>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Kode</th><th>Pelanggan</th><th class="text-right">Jumlah</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentPayments as $p): ?>
                            <tr>
                                <td class="bold small"><?= e($p['order_code']) ?></td>
                                <td class="small"><?= e($p['customer_name']) ?></td>
                                <td class="text-right small bold"><?= money($p['amount']) ?></td>
                                <td><?php paymentBadge($p['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Jastiper Teratas</strong></div>
        <div class="card-body" style="padding-top:0;">
            <?php if ($topJastiper === []): ?>
                <?php emptyState('Belum ada transaksi.', '🏆'); ?>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Jastiper</th><th class="text-right">Pesanan</th><th class="text-right">Omzet</th></tr></thead>
                        <tbody>
                        <?php foreach ($topJastiper as $t): ?>
                            <tr>
                                <td class="small bold"><?= e($t['name']) ?></td>
                                <td class="text-right"><?= (int) $t['jumlah'] ?></td>
                                <td class="text-right small bold"><?= money($t['omzet']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>