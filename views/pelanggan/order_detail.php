<?php $pageTitle = 'Detail Pesanan ' . $order['order_code']; ?>

<div class="mb-3">
    <a href="/pesanan" class="small">← Kembali ke daftar pesanan</a>
</div>

<?php require BASE_PATH . '/views/partials/order_detail_body.php'; ?>

<div class="card mt-3">
    <div class="card-header"><strong>Aksi Pelanggan</strong></div>
    <div class="card-body">

        <?php if ($order['status'] === Status::MENUNGGU_PEMBAYARAN): ?>
            <h3>Konfirmasi Pembayaran</h3>
            <p class="muted small">
                Pilih metode pembayaran lalu konfirmasi. Pembayaran akan diverifikasi oleh jastiper/admin.
                (Simulasi — tidak terhubung ke payment gateway sungguhan.)
            </p>

            <?php if ($payment && $payment['status'] === 'MENUNGGU'): ?>
                <div class="alert alert-warning">
                    Pembayaran Anda sedang menunggu verifikasi (<?= e(paymentMethodLabel($payment['method'])) ?>).
                </div>
            <?php else: ?>
                <form method="post" action="/pesanan/<?= (int) $order['id'] ?>/bayar">
                    <?= csrfField() ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="method">Metode pembayaran</label>
                            <select id="method" name="method" required>
                                <?php foreach (Status::PAYMENT_METHODS as $value => $label): ?>
                                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reference">Referensi / bukti transfer (opsional)</label>
                            <input type="text" id="reference" name="reference" placeholder="mis. TRX-12345 / 0812…">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Konfirmasi Pembayaran <?= money($order['total']) ?></button>
                </form>
            <?php endif; ?>

            <div class="divider"></div>
            <form method="post" action="/pesanan/<?= (int) $order['id'] ?>/batal" class="inline-form">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-outline btn-danger">Batalkan Pesanan</button>
                <span class="help-text">Stok akan dikembalikan secara otomatis.</span>
            </form>

        <?php elseif ($order['status'] === Status::SEDANG_DIKIRIM): ?>
            <form method="post" action="/pesanan/<?= (int) $order['id'] ?>/terima">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-success">✅ Barang sudah saya terima</button>
            </form>

        <?php elseif ($order['status'] === Status::SELESAI): ?>
            <div class="alert alert-success mb-0">Pesanan ini sudah selesai. Terima kasih telah-belanja! 🎉</div>

        <?php elseif ($order['status'] === Status::DIBATALKAN): ?>
            <div class="alert alert-danger mb-0">
                Pesanan dibatalkan<?= $order['cancel_reason'] ? ' — ' . e($order['cancel_reason']) : '' ?>.
            </div>

        <?php else: ?>
            <p class="muted mb-0">
                Pesanan sedang diproses oleh <strong><?= e($order['jastiper_name']) ?></strong>.
                Anda akan melihat pembaruan status di halaman ini.
            </p>
        <?php endif; ?>
    </div>
</div>