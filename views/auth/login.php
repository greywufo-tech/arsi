<?php $pageTitle = 'Masuk'; ?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="text-center mb-3">
            <div class="brand justify-content-center">
                <span class="brand-mark">EJ</span> E-Jastip
            </div>
        </div>

        <h1 style="font-size:1.35rem;">Masuk ke Akun Anda</h1>
        <p class="muted small mb-3">Gunakan email dan password yang terdaftar.</p>

        <form method="post" action="/login">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e((string) old('email')) ?>"
                       placeholder="nama@email.com" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Masuk</button>
        </form>

        <p class="text-center small mt-3 mb-0">
            Belum punya akun? <a href="/register">Daftar sekarang</a>
        </p>

        <div class="divider"></div>
        <div class="alert alert-info small mb-0">
            <strong>Akun demo</strong><br>
            Admin: <code>admin@ejastip.test</code> / <code>admin123</code><br>
            Jastiper: <code>jastiper@ejastip.test</code> / <code>jastiper123</code><br>
            Pelanggan: <code>pelanggan@ejastip.test</code> / <code>pelanggan123</code>
        </div>
    </div>
</div>