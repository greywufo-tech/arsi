<?php $pageTitle = 'Daftar'; ?>
<div class="auth-wrap">
    <div class="auth-card wide">
        <div class="text-center mb-3">
            <div class="brand justify-content-center"><span class="brand-mark">EJ</span> E-Jastip</div>
        </div>

        <h1 style="font-size:1.35rem;">Buat Akun Baru</h1>
        <p class="muted small mb-3">Pilih peran Anda di E-Jastip.</p>

        <form method="post" action="/register">
            <?= csrfField() ?>

            <div class="form-group">
                <label>Saya adalah</label>
                <div class="flex">
                    <label class="checkbox flex-1">
                        <input type="radio" name="role" value="PELANGGAN" <?= ($oldRole ?? '') === 'PELANGGAN' ? 'checked' : '' ?>>
                        <span><strong>Pelanggan</strong><br><span class="small muted">Ingin titip barang</span></span>
                    </label>
                    <label class="checkbox flex-1">
                        <input type="radio" name="role" value="JASTIPER" <?= ($oldRole ?? '') === 'JASTIPER' ? 'checked' : '' ?>>
                        <span><strong>Jastiper</strong><br><span class="small muted">Menyediakan jasa titip</span></span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="name">Nama lengkap</label>
                <input type="text" id="name" name="name" value="<?= e((string) old('name')) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e((string) old('email')) ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Nomor telepon</label>
                <input type="tel" id="phone" name="phone" value="<?= e((string) old('phone')) ?>"
                       placeholder="08xxxxxxxxxx" required>
            </div>

            <div class="form-group">
                <label for="address">Alamat (opsional)</label>
                <textarea id="address" name="address" rows="2"><?= e((string) old('address')) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <div class="help-text">Minimal 6 karakter.</div>
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Daftar Sekarang</button>
        </form>

        <p class="text-center small mt-3 mb-0">
            Sudah punya akun? <a href="/login">Masuk</a>
        </p>
    </div>
</div>