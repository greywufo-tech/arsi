<?php
$isEdit = $product !== null;
$pageTitle = $isEdit ? 'Edit Produk' : 'Tambah Produk';
$action = $isEdit ? '/jastiper/produk/' . (int) $product['id'] : '/jastiper/produk';
$status = $product['status'] ?? '';
?>
<div class="mb-3"><a href="/jastiper/produk" class="small">← Kembali ke daftar produk</a></div>

<form method="post" action="<?= e($action) ?>">
    <?= csrfField() ?>

    <div class="grid grid-2" style="align-items:start;">
        <div class="card">
            <div class="card-header"><strong><?= $isEdit ? 'Edit Produk' : 'Detail Produk' ?></strong></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="offer_id">Masuk ke penawaran *</label>
                    <select id="offer_id" name="offer_id" required>
                        <?php foreach ($offers as $o): ?>
                            <option value="<?= (int) $o['id'] ?>" <?= $selectedOffer === (int) $o['id'] ? 'selected' : '' ?>>
                                <?= e($o['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="name">Nama produk *</label>
                    <input type="text" id="name" name="name" required minlength="3" maxlength="150"
                           value="<?= e((string) old('name', $product['name'] ?? '')) ?>"
                           placeholder="mis. Sepatu Nike Air Max">
                </div>

                <div class="form-group">
                    <label for="category">Kategori</label>
                    <select id="category" name="category">
                        <?php foreach (['', 'Sepatu', 'Tas', 'Baju', 'Kosmetik', 'Makanan', 'Oleh-oleh'] as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= (string) old('category', $product['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                <?= $cat === '' ? '— Pilih kategori —' : e($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" rows="3"><?= e((string) old('description', $product['description'] ?? '')) ?></textarea>
                </div>

                <div class="form-group mb-0">
                    <label for="image_url">URL gambar (opsional)</label>
                    <input type="text" id="image_url" name="image_url"
                           value="<?= e((string) old('image_url', $product['image_url'] ?? '')) ?>"
                           placeholder="https://…">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Harga &amp; Stok</strong></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="price">Harga barang (Rp) *</label>
                    <input type="text" id="price" name="price" inputmode="numeric" required
                           value="<?= e((string) old('price', isset($product['price']) ? (int) $product['price'] : '')) ?>"
                           placeholder="800000">
                    <div class="help-text">Harga barang per 1 pcs (tanpa biaya jastip).</div>
                </div>

                <div class="form-group">
                    <label for="service_fee">Biaya jasa titip (Rp) *</label>
                    <input type="text" id="service_fee" name="service_fee" inputmode="numeric" required
                           value="<?= e((string) old('service_fee', isset($product['service_fee']) ? (int) $product['service_fee'] : 0)) ?>"
                           placeholder="50000">
                    <div class="help-text">Biaya jasa per 1 pcs, ditambahkan ke harga barang.</div>
                </div>

                <div class="form-group">
                    <label for="stock">Stok *</label>
                    <input type="number" id="stock" name="stock" min="0" max="100000" required
                           value="<?= e((string) old('stock', $product['stock'] ?? 10)) ?>">
                </div>

                <div class="total-highlight">
                    <div class="small muted">Harga pelanggan per 1 barang</div>
                    <div class="amount" id="unitTotalPreview"><?= money(((float) ($product['price'] ?? 0)) + ((float) ($product['service_fee'] ?? 0))) ?></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">
                    <?= $isEdit ? 'Simpan Perubahan' : 'Tambah Produk' ?>
                </button>
                <a href="/jastiper/produk" class="btn btn-ghost btn-block mt-1">Batal</a>
                <?php unset($status); ?>
            </div>
        </div>
    </div>
</form>

<script>
    (function () {
        const price = document.getElementById('price');
        const fee = document.getElementById('service_fee');
        const out = document.getElementById('unitTotalPreview');
        const parse = (v) => parseInt(String(v || '0').replace(/[^\d]/g, ''), 10) || 0;
        const fmt = (n) => 'Rp' + n.toLocaleString('id-ID');
        function update() { out.textContent = fmt(parse(price.value) + parse(fee.value)); }
        price.addEventListener('input', update);
        fee.addEventListener('input', update);
    })();
</script>