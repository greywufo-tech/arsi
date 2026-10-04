<?php $pageTitle = 'Laporan Transaksi'; ?>

<div class="grid grid-4 mb-3">
    <div class="stat-card">
        <div class="stat-icon bg-indigo">🧾</div>
        <div><div class="stat-value"><?= (int) $summary['count'] ?></div><div class="stat-label">Jumlah Transaksi</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-green">💰</div>
        <div>
            <div class="stat-value" style="font-size:1.25rem;"><?= money($summary['total_lunas']) ?></div>
            <div class="stat-label">Nilai Transaksi Lunas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-amber">⏳</div>
        <div><div class="stat-value"><?= (int) $summary['pending'] ?></div><div class="stat-label">Menunggu Verifikasi</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-red">✕</div>
        <div><div class="stat-value"><?= (int) $summary['failed'] ?></div><div class="stat-label">Ditolak</div></div>
    </div>
</div>

<div class="grid grid-2 mb-3" style="align-items:start;">
    <div class="card">
        <div class="card-header"><strong>Omzet per Bulan</strong></div>
        <div class="card-body">
            <?php if ($monthly === []): ?>
                <?php emptyState('Belum ada data.', '📊'); ?>
            <?php else: ?>
                <?php $maxVal = max(array_map(fn ($m) => (float) $m['omzet'], $monthly)) ?: 1; ?>
                <div class="chart">
                    <?php foreach (array_reverse($monthly) as $m): ?>
                        <div class="chart-col">
                            <div class="chart-track">
                                <div class="chart-bar" style="height:<?= max(3, ((float) $m['omzet'] / $maxVal) * 100) ?>%">
                                    <span><?= money($m['omzet']) ?></span>
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
        <div class="card-header"><strong>Omzet per Metode Pembayaran</strong></div>
        <div class="card-body" style="padding-top:0;">
            <?php if ($perMethod === []): ?>
                <?php emptyState('Belum ada data.', '💳'); ?>
            <?php else: ?>
                <table>
                    <thead><tr><th>Metode</th><th class="text-right">Jumlah</th><th class="text-right">Omzet</th></tr></thead>
                    <tbody>
                    <?php foreach ($perMethod as $m): ?>
                        <tr>
                            <td class="small"><?= e(paymentMethodLabel($m['method'])) ?></td>
                            <td class="text-right"><?= (int) $m['jml'] ?></td>
                            <td class="text-right small bold"><?= money($m['omzet']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Omzet per Jastiper</strong></div>
    <div class="card-body" style="padding-top:0;">
        <?php if ($perJastiper === []): ?>
            <?php emptyState('Belum ada data.', '🧑‍💼'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Jastiper</th><th class="text-right">Transaksi</th><th class="text-right">Omzet Lunas</th></tr></thead>
                    <tbody>
                    <?php foreach ($perJastiper as $j): ?>
                        <tr>
                            <td class="small bold"><?= e($j['name']) ?></td>
                            <td class="text-right"><?= (int) $j['jml'] ?></td>
                            <td class="text-right small bold"><?= money($j['omzet']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<form method="get" action="/admin/transaksi" class="card mb-3">
    <div class="card-body">
        <div class="filters">
            <div class="form-group">
                <label>Dari tanggal</label>
                <input type="date" name="dari" value="<?= e($from) ?>">
            </div>
            <div class="form-group">
                <label>Sampai tanggal</label>
                <input type="date" name="sampai" value="<?= e($to) ?>">
            </div>
            <div class="form-group">
                <label>Status pembayaran</label>
                <select name="status">
                    <option value="">Semua</option>
                    <?php foreach (['MENUNGGU' => 'Menunggu', 'LUNAS' => 'Lunas', 'GAGAL' => 'Ditolak'] as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a href="/admin/transaksi" class="btn btn-outline">Reset</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header"><strong>Rincian Transaksi (<?= count($payments) ?>)</strong></div>
    <div class="card-body" style="padding-top:0;">
        <?php if ($payments === []): ?>
            <?php emptyState('Tidak ada transaksi pada filter ini.', '💰'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode Pesanan</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Jastiper</th>
                            <th>Metode</th>
                            <th class="text-right">Jumlah</th>
                            <th>Status</th>
                            <th>Status Pesanan</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td class="bold small"><?= e($p['order_code']) ?></td>
                            <td class="small"><?= formatDate($p['order_date']) ?></td>
                            <td class="small"><?= e($p['customer_name']) ?></td>
                            <td class="small"><?= e($p['jastiper_name']) ?></td>
                            <td class="small"><?= e(paymentMethodLabel($p['method'])) ?></td>
                            <td class="text-right bold"><?= money($p['amount']) ?></td>
                            <td><?php paymentBadge($p['status']) ?></td>
                            <td><?php statusBadge($p['order_status']) ?></td>
                            <td class="actions"><a href="/admin/pesanan/<?= (int) $p['order_id'] ?>" class="btn btn-outline btn-sm">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>