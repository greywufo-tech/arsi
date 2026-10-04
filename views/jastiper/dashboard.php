<?php $pageTitle = 'Dashboard Jastiper'; ?>

<?php if (($user['verified_at'] ?? null) === null): ?>
    <div class="alert alert-warning">
        <strong>Akun belum diverifikasi.</strong>
        Admin perlu memverifikasi akun Anda sebelum dapat membuat atau mengaktifkan penawaran.
        Hubungi admin E-Jastip.
    </div>
<?php endif; ?>

<div class="grid grid-4 mb-3">
    <div class="stat-card">
        <div class="stat-icon bg-indigo">📢</div>
        <div><div class="stat-value"><?= (int) $stats['active_offers'] ?></div><div class="stat-label">Penawaran Aktif</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-purple">🏷️</div>
        <div><div class="stat-value"><?= (int) $stats['products'] ?></div><div class="stat-label">Produk</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-amber">🆕</div>
        <div><div class="stat-value"><?= (int) $stats['new_orders'] ?></div><div class="stat-label">Pesanan Baru</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-sky">⚙️</div>
        <div><div class="stat-value"><?= (int) $stats['processing'] ?></div><div class="stat-label">Sedang Diproses</div></div>
    </div>
</div>

<div class="grid grid-3 mb-3">
    <div class="stat-card">
        <div class="stat-icon bg-teal">🚚</div>
        <div><div class="stat-value"><?= (int) $stats['shipping'] ?></div><div class="stat-label">Sedang Dikirim</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-red">⏳</div>
        <div><div class="stat-value"><?= (int) $stats['unpaid'] ?></div><div class="stat-label">Menunggu Verifikasi Bayar</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-green">💰</div>
        <div>
            <div class="stat-value" style="font-size:1.25rem;"><?= money($stats['revenue']) ?></div>
            <div class="stat-label">Total Transaksi</div>
        </div>
    </div>
</div>

<div class="flex mb-3" style="flex-wrap:wrap;">
    <a href="/jastiper/penawaran/tambah" class="btn btn-primary">+ Buat Penawaran</a>
    <a href="/jastiper/produk/tambah" class="btn btn-outline">+ Tambah Produk</a>
    <a href="/jastiper/pesanan" class="btn btn-outline">📋 Pesanan Masuk</a>
    <a href="/jastiper/pengiriman" class="btn btn-outline">🚚 Pengiriman</a>
</div>

<div class="card">
    <div class="card-header">
        <strong>Pesanan Terbaru</strong>
        <a href="/jastiper/pesanan" class="btn btn-outline btn-sm">Lihat semua</a>
    </div>
    <div class="card-body" style="padding-top:0;">
        <?php if ($recent === []): ?>
            <?php emptyState('Belum ada pesanan masuk.', '🧾'); ?>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th class="text-right">Total</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent as $o): ?>
                        <tr>
                            <td class="bold small"><?= e($o['order_code']) ?></td>
                            <td class="small"><?= e($o['customer_name']) ?></td>
                            <td class="small"><?= formatDate($o['order_date']) ?></td>
                            <td class="text-right bold"><?= money($o['total']) ?></td>
                            <td><?php paymentBadge($o['payment_status']) ?></td>
                            <td><?php statusBadge($o['status']) ?></td>
                            <td class="actions"><a href="/jastiper/pesanan/<?= (int) $o['id'] ?>" class="btn btn-outline btn-sm">Proses</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>