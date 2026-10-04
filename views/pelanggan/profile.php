<?php $pageTitle = 'Profil Saya'; ?>

<div class="grid grid-2" style="align-items:start;">
    <div class="card">
        <div class="card-header"><strong>Informasi Akun</strong></div>
        <div class="card-body">
            <form method="post" action="/profil">
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="name">Nama lengkap</label>
                    <input type="text" id="name" name="name" value="<?= e((string) old('name', $user['name'])) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?= e($user['email']) ?>" disabled>
                    <div class="help-text">Email tidak dapat diubah.</div>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <input type="text" value="<?= e($user['role']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="phone">Nomor telepon</label>
                    <input type="tel" id="phone" name="phone" value="<?= e((string) old('phone', $user['phone'])) ?>" required>
                </div>
                <div class="form-group">
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address" rows="3"><?= e((string) old('address', $user['address'])) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Ubah Password</strong></div>
        <div class="card-body">
            <form method="post" action="/profil/password">
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="current_password">Password saat ini</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Password baru</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <div class="help-text">Minimal <?= (int) config('validation.min_password_length', 6) ?> karakter.</div>
                </div>
                <div class="form-group">
                    <label for="new_password_confirmation">Konfirmasi password baru</label>
                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" required>
                </div>
                <button type="submit" class="btn btn-outline">Ubah Password</button>
            </form>
        </div>
    </div>
</div>