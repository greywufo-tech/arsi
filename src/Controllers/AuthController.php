<?php
declare(strict_types=1);

final class AuthController
{
    public static function showLogin(): void
    {
        if (Auth::check()) {
            redirect(Auth::homeFor(Auth::role()));
        }
        view('auth/login', [], 'public');
    }

    public static function login(): void
    {
        verifyCsrf();

        $email    = trim((string) input('email', ''));
        $password = (string) input('password', '');

        $v = new Validator(['email' => 'Email', 'password' => 'Password']);
        $v->validate(['email' => $email, 'password' => $password], [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($v->fails()) {
            keepOld(['email' => $email]);
            flash('error', $v->firstError());
            redirect('/login');
        }

        $user = Database::one('SELECT * FROM users WHERE email = ?', [strtolower($email)]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('error', 'Email atau password salah.');
            keepOld(['email' => $email]);
            redirect('/login');
        }
        if ((int) $user['is_active'] !== 1) {
            flash('error', 'Akun Anda dinonaktifkan oleh admin. Hubungi admin untuk informasi lebih lanjut.');
            redirect('/login');
        }

        Auth::login((int) $user['id']);

        // Jastiper belum diverifikasi -> beri tahu tapi tetap boleh login.
        if ($user['role'] === 'JASTIPER' && $user['verified_at'] === null) {
            flash('warning', 'Akun jastiper Anda belum diverifikasi admin, sehingga belum dapat membuat penawaran.');
        }

        $intended = $_SESSION['intended'] ?? null;
        unset($_SESSION['intended']);
        redirect($intended ?: Auth::homeFor($user['role']));
    }

    public static function showRegister(): void
    {
        if (Auth::check()) {
            redirect(Auth::homeFor(Auth::role()));
        }
        view('auth/register', ['oldRole' => old('role', 'PELANGGAN')], 'public');
    }

    public static function register(): void
    {
        verifyCsrf();

        $data = [
            'name'     => trim((string) input('name', '')),
            'email'    => trim((string) input('email', '')),
            'phone'    => trim((string) input('phone', '')),
            'address'  => trim((string) input('address', '')),
            'role'     => (string) input('role', 'PELANGGAN'),
            'password' => (string) input('password', ''),
            // Wajib ikut disertakan agar aturan "confirmed" bisa membandingkannya.
            'password_confirmation' => (string) input('password_confirmation', ''),
        ];

        // Jangan izinkan pendaftaran sebagai admin lewat form.
        if (!in_array($data['role'], ['PELANGGAN', 'JASTIPER'], true)) {
            $data['role'] = 'PELANGGAN';
        }

        $v = new Validator([
            'name'     => 'Nama lengkap',
            'email'    => 'Email',
            'phone'    => 'Nomor telepon',
            'password' => 'Password',
        ]);

        $v->validate($data, [
            'name'     => 'required|min:3|max:100',
            'email'    => 'required|email',
            'phone'    => 'required|min:8|max:30',
            'password' => 'required|min:' . (int) config('validation.min_password_length', 6) . '|confirmed',
        ]);

        if ($v->fails()) {
            keepOld($data);
            flash('error', $v->firstError());
            redirect('/register');
        }

        if (Database::value('SELECT 1 FROM users WHERE email = ?', [strtolower($data['email'])])) {
            keepOld($data);
            flash('error', 'Email sudah terdaftar. Silakan login.');
            redirect('/register');
        }

        $userId = Auth::register($data);
        Auth::login($userId);

        if ($data['role'] === 'JASTIPER') {
            flash('success', 'Registrasi berhasil. Akun jastiper perlu diverifikasi admin sebelum dapat membuat penawaran.');
        } else {
            flash('success', 'Registrasi berhasil. Selamat datang di E-Jastip!');
        }

        redirect(Auth::homeFor($data['role']));
    }

    public static function logout(): void
    {
        verifyCsrf();
        Auth::logout();
        flash('success', 'Anda telah keluar.');
        redirect('/login');
    }
}