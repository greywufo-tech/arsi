<?php $pageTitle = 'Checkout'; ?>

<?php if ($items === []): ?>
    <div class="card"><div class="card-body">
        <?php emptyState('Keranjang Anda masih kosong.', '🛒', '<a href="/jastip" class="btn btn-primary mt-2">Jelajahi Penawaran</a>'); ?>
    </div></div>
<?php else: ?>

    <?php if ($stockIssues !== []): ?>
        <div class="alert alert-error">
            <strong>Jumlah melebihi stok:</strong>
            <?= e(implode(', ', array_map(fn ($i) => $i['product']['name'] . ' (' . $i['quantity'] . '/' . $i['available'] . ')', $stockIssues))) ?>
        </div>
    <?php endif; ?>

    <?php if ($invalid !== []): ?>
        <div class="alert alert-warning">
            <strong>Tidak dapat dipesan:</strong>
            <?= e(implode(', ', array_map(fn ($i) => $i['product']['name'], $invalid))) ?>
            — penawaran sudah tidak aktif atau batas pemesanan lewat.
        </div>
    <?php endif; ?>

    <form method="post" action="/checkout">
        <?= csrfField() ?>

        <div class="grid grid-checkout">
            <div>
                <div class="card mb-3">
                    <div class="card-header">
                        <strong>Barang Pesanan</strong>
                        <span class="muted small"><?= Cart::count() ?> item</span>
                    </div>
                    <div class="card-body" style="padding-top:0;padding-bottom:0;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th style="width:90px;">Jumlah</th>
                                    <th class="text-right">Harga</th>
                                    <th class="text-right">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($items as $item):
                                $p = $item['product']; ?>
                                <tr>
                                    <td>
                                        <div class="bold small"><?= e($p['name']) ?></div>
                                        <div class="muted small">
                                            <?= money($item['price']) ?> + jastip <?= money($item['service_fee']) ?>
                                            · stok <?= $item['available'] ?>
                                        </div>
                                        <?php if ($item['exceeds_stock']): ?>
                                            <span class="badge badge-danger">melebihi stok</span>
                                        <?php elseif (!$item['valid']): ?>
                                            <span class="badge badge-warning">tidak bisa dipesan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="number" name="quantities[<?= (int) $p['id'] ?>]"
                                               value="<?= (int) $item['quantity'] ?>" min="0" max="<?= $item['available'] ?>"
                                               class="qty-input">
                                    </td>
                                    <td class="text-right small"><?= money($item['price']) ?></td>
                                    <td class="text-right bold"><?= money($item['item_subtotal']) ?></td>
                                    <td class="actions">
                                        <button class="btn btn-ghost btn-sm" title="Hapus">✕</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer flex-between">
                        <button type="submit" class="btn btn-outline btn-sm" formaction="/keranjang/ubah">Perbarui jumlah</button>
                        <a href="/produk" class="btn btn-ghost btn-sm">Lanjut belanja →</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><strong>Informasi Pengiriman</strong></div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="receiver_name">Nama penerima</label>
                                <input type="text" id="receiver_name" name="receiver_name"
                                       value="<?= e((string) old('receiver_name', $user['name'])) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Nomor telepon</label>
                                <input type="tel" id="phone" name="phone"
                                       value="<?= e((string) old('phone', $user['phone'])) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="shipping_address">Alamat lengkap pengiriman</label>
                            <textarea id="shipping_address" name="shipping_address" rows="3" required><?= e((string) old('shipping_address', $user['address'])) ?></textarea>
                            <div class="help-text">Cantumkan nama jalan, nomor rumah, kota, dan kode pos.</div>
                        </div>
                        <div class="form-group mb-0">
                            <label for="notes">Catatan untuk jastiper (opsional)</label>
                            <textarea id="notes" name="notes" rows="2"><?= e((string) old('notes')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="position:sticky;top:80px;" data-sticky>
                <div class="card-header"><strong>Ringkasan Pembayaran</strong></div>
                <div class="card-body">
                    <div class="summary-row">
                        <span class="label">Subtotal barang</span>
                        <span><?= money($totals['subtotal']) ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Biaya jasa titip</span>
                        <span><?= money($totals['service_fee']) ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Ongkos kirim</span>
                        <span><?= money($totals['shipping']) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span class="label">Total Pembayaran</span>
                        <span><?= money($totals['total']) ?></span>
                    </div>

                    <div class="total-highlight text-center">
                        <div class="small muted">Total yang harus dibayar</div>
                        <div class="amount"><?= money($totals['total']) ?></div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg mt-2"
                            <?= ($stockIssues !== [] || $invalid !== []) ? 'disabled' : '' ?>>
                        Buat Pesanan
                    </button>
                    <div class="help-text text-center mt-1">
                        Total dihitung oleh sistem dan tidak dapat diubah.
                    </div>
                </div>
            </div>
        </div>
    </form>
<?php endif; ?>