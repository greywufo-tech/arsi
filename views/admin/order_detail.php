<?php $pageTitle = 'Pesanan ' . $order['order_code']; ?>

<div class="mb-3"><a href="/admin/pesanan" class="small">← Kembali ke daftar pesanan</a></div>

<?php require BASE_PATH . '/views/partials/order_detail_body.php'; ?>

<div class="grid grid-2 mt-3" style="align-items:start;">
    <div class="card">
        <div class="card-header"><strong>Aksi Admin</strong></div>
        <div class="card-body">
            <?php if ($payment && $payment['status'] === 'MENUNGGU'): ?>
                <div class="alert alert-warning small">
                    Pembayaran <?= money($payment['amount']) ?> via
                    <strong><?= e(paymentMethodLabel($payment['method'])) ?></strong>
                    menunggu verifikasi.
                </div>
                <div class="flex">
                    <form method="post" action="/admin/pesanan/<?= (int) $order['id'] ?>/verifikasi?action=approve" class="inline-form">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-success">✓ Verifikasi Lunas</button>
                    </form>
                    <form method="post" action="/admin/pesanan/<?= (int) $order['id'] ?>/verifikasi?action=reject" class="inline-form">
                        <?= csrfField() ?>
                        <button type="submit" class="btn btn-danger">✕ Tolak</button>
                    </form>
                </div>
                <div class="divider"></div>
            <?php endif; ?>

            <?php
            $allowed = array_filter(
                Status::NEXT[$order['status']] ?? [],
                fn ($s) => in_array('ADMIN', Status::ALLOWED_ROLES[$s] ?? [], true)
            );
            ?>
            <?php if ($allowed === []): ?>
                <p class="muted mb-0">Tidak ada perubahan status yang tersedia untuk status saat ini.</p>
            <?php else: ?>
                <form method="post" action="/admin/pesanan/<?= (int) $order['id'] ?>/status">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label for="status">Ubah status menjadi</label>
                        <select id="status" name="status" required>
                            <?php foreach ($allowed as $s): ?>
                                <option value="<?= e($s) ?>"><?= e(Status::label($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="note">Catatan</label>
                        <input type="text" id="note" name="note" placeholder="Alasan perubahan status">
                    </div>
                    <button type="submit" class="btn btn-primary">Perbarui Status</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Informasi Pesanan</strong></div>
        <div class="card-body small">
            <div class="summary-row"><span class="label">Pelanggan</span><span><?= e($order['customer_name']) ?></span></div>
            <div class="summary-row"><span class="label">Email</span><span><?= e($order['customer_email']) ?></span></div>
            <div class="summary-row"><span class="label">Jastiper</span><span><?= e($order['jastiper_name']) ?></span></div>
            <div class="summary-row"><span class="label">Penawaran</span><span><?= e($order['offer_title']) ?></span></div>
            <div class="summary-row"><span class="label">Lokasi</span><span>📍 <?= e($order['location']) ?></span></div>
            <div class="summary-row"><span class="label">Penerima</span><span><?= e($order['receiver_name']) ?></span></div>
            <div class="summary-row"><span class="label">Telepon</span><span><?= e($order['phone']) ?></span></div>
        </div>
    </div>
</div>