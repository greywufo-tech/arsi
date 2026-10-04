<?php $pageTitle = 'Dashboard Pelanggan'; ?>

<div class="grid grid-4 mb-3">
    <div class="stat-card">
        <div class="stat-icon bg-indigo">📦</div>
        <div><div class="stat-value"><?= (int) $counts['total'] ?></div><div class="stat-label">Jumlah Pesanan</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-purple">⚙️</div>
        <div><div class="stat-value"><?= (int) $counts['processing'] ?></div><div class="stat-label">Sedang Diproses</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-sky">🚚</div>
        <div><div class="stat-value"><?= (int) $counts['shipping'] ?></div><div class="stat-label">Sedang Dikirim</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-green">✅</div>
        <div><div class="stat-value"><?= (int) $counts['done'] ?></div><div class="stat-label">Pesanan Selesai</div></div>
    </div>
</div>

<div class="flex mb-3">
    <a href="/jastip" class="btn btn-primary">🔍 Jelajahi Jastip</a>
    <a href="/pesanan" class="btn btn-outline">📦 Pesanan Saya</a>
    <a href="/profil" class="btn btn-outline">👤 Profil</a>
</div>

<?php if ((int) $counts['unpaid'] > 0): ?>
    <div class="alert alert-warning">
        Anda memiliki <strong><?= (int) $counts['unpaid'] ?></strong> pesanan yang belum dibayar.
        <a href="/pesanan?status=MENUNGGU_PEMBAYARAN">Bayar sekarang →</a>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <strong>Pesanan Terbaru</strong>
        <a href="/pesanan" class="btn btn-outline btn-sm">Lihat semua</a>
    </div>
    <div class="card-body" style="padding-top:0;">
        <?php if ($recent === []): ?>
            <?php emptyState('Anda belum memiliki pesanan. Mulai dengan menjelajahi penawaran jastip.', '🛒', '<a href="/jastip" class="btn btn-primary mt-2">Jelajahi Jastip</a>'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode Pesanan</th>
                            <th>Penawaran</th>
                            <th>Tanggal</th>
                            <th class="text-right">Total</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent as $o): ?>
                        <tr>
                            <td class="bold small"><?= e($o['order_code']) ?></td>
                            <td class="small"><?= e($o['offer_title']) ?><br><span class="muted">📍 <?= e($o['location']) ?></span></td>
                            <td class="small"><?= formatDate($o['order_date']) ?></td>
                            <td class="text-right bold"><?= money($o['total']) ?></td>
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