<?php $pageTitle = 'Pesanan ' . $order['order_code']; ?>

<div class="mb-3"><a href="/jastiper/pesanan" class="small">← Kembali ke daftar pesanan</a></div>

<?php require BASE_PATH . '/views/partials/order_detail_body.php'; ?>

<div class="grid grid-2 mt-3" style="align-items:start;">

    <div class="card">
        <div class="card-header"><strong>Ubah Status Pesanan</strong></div>
        <div class="card-body">
            <?php if (empty($nextStatuses)): ?>
                <p class="muted mb-0">
                    Status saat ini <strong><?= e(Status::label($order['status'])) ?></strong>
                    tidak memiliki tindakan lanjutan untuk jastiper.
                </p>
            <?php else: ?>
                <form method="post" action="/jastiper/pesanan/<?= (int) $order['id'] ?>/status">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label for="status">Status baru</label>
                        <select id="status" name="status" required>
                            <?php foreach ($nextStatuses as $s): ?>
                                <option value="<?= e($s) ?>"><?= e(Status::label($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="note">Catatan (opsional)</label>
                        <input type="text" id="note" name="note" placeholder="mis. Barang sudah dibeli di Shopee">
                    </div>
                    <button type="submit" class="btn btn-primary">Perbarui Status</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Verifikasi Pembayaran</strong></div>
        <div class="card-body">
            <?php if (!$payment): ?>
                <p class="muted mb-0">Pelanggan belum melakukan konfirmasi pembayaran.</p>
            <?php elseif ($payment['status'] !== 'MENUNGGU'): ?>
                <div class="summary-row"><span class="label">Metode</span><span><?= e(paymentMethodLabel($payment['method'])) ?></span></div>
                <div class="summary-row"><span class="label">Status</span><span><?php paymentBadge($payment['status']) ?></span></div>
                <p class="muted small mt-2 mb-0">Pembayaran ini sudah diproses.</p>
            <?php else: ?>
                <div class="alert alert-warning small">
                    Pelanggan mengkonfirmasi pembayaran sebesar
                    <strong><?= money($payment['amount']) ?></strong> via
                    <strong><?= e(paymentMethodLabel($payment['method'])) ?></strong>.
                    <?php if ($payment['reference']): ?>
                        <br>Referensi: <code><?= e($payment['reference']) ?></code>
                    <?php endif; ?>
                    <br>Dikirim: <?= formatDate($payment['paid_at'], true) ?>
                </div>
                <div class="flex">
                    <form method="post" action="/jastiper/pesanan/<?= (int) $order['id'] ?>/verifikasi" class="inline-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success">✓ Verifikasi Lunas</button>
                    </form>
                    <form method="post" action="/jastiper/pesanan/<?= (int) $order['id'] ?>/verifikasi" class="inline-form"
                          onsubmit="return confirm('Tolak pembayaran ini?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-danger">✕ Tolak</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><strong>🚚 Informasi Pengiriman</strong></div>
    <div class="card-body">
        <?php if (!in_array($order['status'], [Status::BARANG_DIPEROLEH, Status::SEDANG_DIKIRIM, Status::SELESAI], true)): ?>
            <p class="muted mb-0">
                Pengiriman dapat diisi setelah status pesanan diubah menjadi
                <strong>Barang Diperoleh</strong>.
            </p>
        <?php else: ?>
            <form method="post" action="/jastiper/pesanan/<?= (int) $order['id'] ?>/kirim">
                <?= csrfField() ?>
                <div class="form-row-3">
                    <div class="form-group">
                        <label for="courier_name">Nama kurir *</label>
                        <input type="text" id="courier_name" name="courier_name" required minlength="3"
                               value="<?= e((string) ($shipment['courier_name'] ?? '')) ?>" placeholder="JNE / SiCepat / J&T">
                    </div>
                    <div class="form-group">
                        <label for="tracking_number">Nomor resi *</label>
                        <input type="text" id="tracking_number" name="tracking_number" required minlength="4"
                               value="<?= e((string) ($shipment['tracking_number'] ?? '')) ?>" placeholder="JNE123456789">
                    </div>
                    <div class="form-group">
                        <label for="shipped_at">Tanggal pengiriman *</label>
                        <input type="datetime-local" id="shipped_at" name="shipped_at" required
                               value="<?= e($shipment && $shipment['shipped_at'] ? date('Y-m-d\TH:i', strtotime($shipment['shipped_at'])) : date('Y-m-d\TH:i')) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="note">Catatan (opsional)</label>
                    <input type="text" id="note" name="note" value="<?= e((string) ($shipment['note'] ?? '')) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Simpan Info Pengiriman</button>
            </form>
        <?php endif; ?>
    </div>
</div>