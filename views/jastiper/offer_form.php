<?php
$isEdit = $offer !== null;
$pageTitle = $isEdit ? 'Edit Penawaran' : 'Buat Penawaran';
$defaultStart = $isEdit ? $offer['start_date'] : date('Y-m-d');
$defaultEnd   = $isEdit ? $offer['end_date'] : date('Y-m-d', strtotime('+14 days'));
$defaultDeadline = $isEdit ? date('Y-m-d\TH:i', strtotime($offer['order_deadline'])) : date('Y-m-d\TH:i', strtotime('+7 days'));
$status = $isEdit ? $offer['status'] : 'AKTIF';
?>
<div class="mb-3"><a href="/jastiper/penawaran" class="small">← Kembali ke daftar penawaran</a></div>

<form method="post" action="<?= $isEdit ? '/jastiper/penawaran/' . (int) $offer['id'] : '/jastiper/penawaran' ?>">
    <?= csrfField() ?>

    <div class="grid grid-2" style="align-items:start;">
        <div class="card">
            <div class="card-header"><strong><?= $isEdit ? 'Edit Penawaran' : 'Detail Penawaran' ?></strong></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="title">Judul penawaran *</label>
                    <input type="text" id="title" name="title" required minlength="5" maxlength="150"
                           value="<?= e((string) old('title', $offer['title'] ?? '')) ?>"
                           placeholder="mis. Jastip Jakarta — Oktober 2026">
                </div>

                <div class="form-group">
                    <label for="location">Lokasi asal / tempat pembelian *</label>
                    <input type="text" id="location" name="location" required maxlength="120"
                           value="<?= e((string) old('location', $offer['location'] ?? '')) ?>"
                           placeholder="mis. Jakarta, Manila, Bandung">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Tanggal mulai *</label>
                        <input type="date" id="start_date" name="start_date" required
                               value="<?= e((string) old('start_date', $defaultStart)) ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">Tanggal berakhir *</label>
                        <input type="date" id="end_date" name="end_date" required
                               value="<?= e((string) old('end_date', $defaultEnd)) ?>">
                        <div class="help-text">Tidak boleh lebih awal dari tanggal mulai.</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="order_deadline">Batas waktu pemesanan *</label>
                    <input type="datetime-local" id="order_deadline" name="order_deadline" required
                           value="<?= e((string) old('order_deadline', $defaultDeadline)) ?>">
                    <div class="help-text">Setelah waktu ini pelanggan tidak dapat membuat pesanan baru.</div>
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" rows="4"
                              placeholder="Ceritakan apa saja yang bisa dititipkan…"><?= e((string) old('description', $offer['description'] ?? '')) ?></textarea>
                </div>

                <div class="form-group mb-0">
                    <label for="image_url">URL gambar penawaran (opsional)</label>
                    <input type="text" id="image_url" name="image_url"
                           value="<?= e((string) old('image_url', $offer['image_url'] ?? '')) ?>"
                           placeholder="https://…">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Status Penawaran</strong></div>
            <div class="card-body">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php if (!$isEdit): ?>
                            <option value="AKTIF" <?= $status === 'AKTIF' ? 'selected' : '' ?>>Aktif — langsung tayangkan</option>
                            <option value="DRAFT" <?= $status === 'DRAFT' ? 'selected' : '' ?>>Draft — simpan dulu</option>
                        <?php else: ?>
                            <?php foreach (['AKTIF' => 'Aktif', 'DRAFT' => 'Draft', 'NONAKTIF' => 'Nonaktif', 'SELESAI' => 'Selesai'] as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="alert alert-info small">
                    Setelah penawaran dibuat, tambahkan produk (barang, harga, biaya jastip, dan stok)
                    melalui menu <strong>Produk</strong>.
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <?= $isEdit ? 'Simpan Perubahan' : 'Buat Penawaran' ?>
                </button>
                <a href="/jastiper/penawaran" class="btn btn-ghost btn-block mt-1">Batal</a>
            </div>
        </div>
    </div>
</form>