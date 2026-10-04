<?php
/** Konten detail pesanan (dipakai pelanggan, jastiper, dan admin). */
$orderTotalCheck = (float) $order['subtotal'] + (float) $order['service_fee_total'] + (float) $order['shipping_cost'];
?>
<div class="mb-3">
    <div class="flex-between">
        <div>
            <h2 class="mb-0"><?= e($order['order_code']) ?></h2>
            <div class="small muted">
                Dibuat <?= formatDate($order['order_date'], true) ?> ·
                Penawaran <a href="/jastip/<?= (int) $order['offer_id'] ?>"><?= e($order['offer_title']) ?></a>
            </div>
        </div>
        <div class="text-right">
            <?php statusBadge($order['status']) ?>
            <div class="mt-1"><?php paymentBadge($order['payment_status']) ?></div>
        </div>
    </div>
</div>

<div class="grid grid-split">
    <div>
        <div class="card mb-3">
            <div class="card-header"><strong>Progres Pesanan</strong></div>
            <div class="card-body"><?php orderProgress($order['status']); ?></div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Barang Pesanan</strong></div>
            <div class="card-body" style="padding-top:0;">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-right">Harga</th>
                                <th class="text-right">Jastip</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($details as $d): ?>
                            <tr>
                                <td class="small bold"><?= e($d['product_name']) ?></td>
                                <td class="text-right small"><?= money($d['price']) ?></td>
                                <td class="text-right small"><?= money($d['service_fee']) ?></td>
                                <td class="text-right small"><?= (int) $d['quantity'] ?></td>
                                <td class="text-right small bold"><?= money($d['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($shipment): ?>
            <div class="card mb-3">
                <div class="card-header"><strong>🚚 Informasi Pengiriman</strong></div>
                <div class="card-body">
                    <div class="summary-row"><span class="label">Kurir</span><span><strong><?= e($shipment['courier_name'] ?: '-') ?></strong></span></div>
                    <div class="summary-row"><span class="label">Nomor resi</span><span><strong class="text-primary"><?= e($shipment['tracking_number'] ?: '-') ?></strong></span></div>
                    <div class="summary-row"><span class="label">Tanggal kirim</span><span><?= formatDate($shipment['shipped_at'], true) ?></span></div>
                    <div class="summary-row"><span class="label">Diterima</span><span><?= $shipment['delivered_at'] ? formatDate($shipment['delivered_at'], true) : '-' ?></span></div>
                    <?php if ($shipment['note']): ?>
                        <div class="summary-row"><span class="label">Catatan</span><span><?= e($shipment['note']) ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><strong>Riwayat Status</strong></div>
            <div class="card-body">
                <ul class="timeline">
                    <?php foreach ($logs as $log): ?>
                        <li class="done">
                            <div class="timeline-title"><?= e(Status::label($log['status'])) ?></div>
                            <div class="timeline-meta">
                                <?= formatDate($log['created_at'], true) ?>
                                <?php if ($log['changed_by_name']): ?>· <?= e($log['changed_by_name']) ?><?php endif; ?>
                            </div>
                            <?php if ($log['note']): ?>
                                <div class="small muted"><?= e($log['note']) ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div>
        <div class="card mb-3">
            <div class="card-header"><strong>Rincian Biaya</strong></div>
            <div class="card-body">
                <div class="summary-row">
                    <span class="label">Subtotal barang</span>
                    <span><?= money($order['subtotal']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Biaya jasa titip</span>
                    <span><?= money($order['service_fee_total']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Ongkos kirim</span>
                    <span><?= money($order['shipping_cost']) ?></span>
                </div>
                <div class="summary-row total">
                    <span class="label">Total Pembayaran</span>
                    <span><?= money($order['total']) ?></span>
                </div>
                <?php if (abs($orderTotalCheck - (float) $order['total']) > 0.01): ?>
                    <div class="alert alert-error mt-2 mb-0 small">
                        Perhitungan tidak konsisten (seharusnya <?= money($orderTotalCheck) ?>).
                    </div>
                <?php endif; ?>
                <div class="help-text">Total dihitung sistem saat pesanan dibuat.</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Alamat Pengiriman</strong></div>
            <div class="card-body small">
                <div class="bold"><?= e($order['receiver_name']) ?></div>
                <div class="muted"><?= e($order['phone']) ?></div>
                <div class="mt-1"><?= nl2br(e($order['shipping_address'])) ?></div>
                <?php if ($order['notes']): ?>
                    <div class="divider"></div>
                    <div class="muted">Catatan: <?= e($order['notes']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Pembayaran</strong></div>
            <div class="card-body small">
                <?php if ($payment): ?>
                    <div class="summary-row"><span class="label">Metode</span><span><?= e(paymentMethodLabel($payment['method'])) ?></span></div>
                    <div class="summary-row"><span class="label">Jumlah</span><span><?= money($payment['amount']) ?></span></div>
                    <div class="summary-row"><span class="label">Status</span><span><?php paymentBadge($payment['status']) ?></span></div>
                    <?php if ($payment['reference']): ?>
                        <div class="summary-row"><span class="label">Referensi</span><span><?= e($payment['reference']) ?></span></div>
                    <?php endif; ?>
                    <?php if ($payment['paid_at']): ?>
                        <div class="summary-row"><span class="label">Dikirim</span><span><?= formatDate($payment['paid_at'], true) ?></span></div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="muted mb-0">Belum ada pembayaran untuk pesanan ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>