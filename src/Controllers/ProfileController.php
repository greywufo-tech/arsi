<?php
declare(strict_types=1);

final class ProfileController
{
    public static function index(): void
    {
        Auth::requireLogin();
        view('pelanggan/profile', ['user' => Auth::user()], 'app');
    }

    public static function update(): void
    {
        verifyCsrf();
        Auth::requireLogin();

        $data = [
            'name'    => trim((string) input('name', '')),
            'phone'   => trim((string) input('phone', '')),
            'address' => trim((string) input('address', '')),
        ];

        $v = new Validator(['name' => 'Nama lengkap', 'phone' => 'Nomor telepon']);
        $v->validate($data, [
            'name'  => 'required|min:3|max:100',
            'phone' => 'required|min:8|max:30',
        ]);

        if ($v->fails()) {
            keepOld($data);
            flash('error', $v->firstError());
            redirect('/profil');
        }

        Database::run(
            'UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?',
            [$data['name'], $data['phone'], $data['address'] ?: null, Auth::id()]
        );

        clearOld();
        flash('success', 'Profil berhasil diperbarui.');
        redirect('/profil');
    }

    public static function updatePassword(): void
    {
        verifyCsrf();
        Auth::requireLogin();

        $current = (string) input('current_password', '');
        $new     = (string) input('new_password', '');
        $confirm = (string) input('new_password_confirmation', '');

        $user = Auth::user();

        $v = new Validator([
            'current_password' => 'Password saat ini',
            'new_password'     => 'Password baru',
        ]);
        $v->validate(
            [
                'current_password'           => $current,
                'new_password'               => $new,
                'new_password_confirmation'  => $confirm,
            ],
            [
                'current_password' => 'required',
                'new_password'     => 'required|min:' . (int) config('validation.min_password_length', 6) . '|confirmed',
            ]
        );

        if ($v->fails()) {
            flash('error', $v->firstError());
            redirect('/profil');
        }

        if (!password_verify($current, $user['password_hash'])) {
            flash('error', 'Password saat ini salah.');
            redirect('/profil');
        }

        Database::run(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [password_hash($new, PASSWORD_DEFAULT), Auth::id()]
        );

        flash('success', 'Password berhasil diubah.');
        redirect('/profil');
    }
}