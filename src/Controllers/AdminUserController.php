<?php
declare(strict_types=1);

final class AdminUserController
{
    /** Daftar seluruh pengguna (pelanggan, jastiper, admin). */
    public static function index(): void
    {
        Auth::requireRole('ADMIN');

        $role   = (string) query('role', '');
        $search = trim((string) query('q', ''));

        $sql = 'SELECT u.*,
                       (SELECT COUNT(*) FROM orders o WHERE o.customer_id = u.id) AS order_count,
                       (SELECT COUNT(*) FROM jastip_offers j WHERE j.jastiper_id = u.id) AS offer_count
                FROM users u WHERE 1=1';
        $params = [];

        if (in_array($role, ['PELANGGAN', 'JASTIPER', 'ADMIN'], true)) {
            $sql .= ' AND u.role = ?';
            $params[] = $role;
        }
        if ($search !== '') {
            $sql .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like);
        }
        $sql .= ' ORDER BY u.created_at DESC';

        $users = Database::all($sql, $params);

        view('admin/users', ['users' => $users, 'role' => $role, 'search' => $search], 'app');
    }

    /** Aktifkan / nonaktifkan pengguna. */
    public static function toggleActive(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('ADMIN');

        $id = (int) $params['id'];
        if ($id === (int) Auth::id()) {
            flash('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
            redirect('/admin/pengguna');
        }

        $user = Database::one('SELECT * FROM users WHERE id = ?', [$id]);
        if (!$user) {
            flash('error', 'Pengguna tidak ditemukan.');
            redirect('/admin/pengguna');
        }

        $newStatus = (int) $user['is_active'] === 1 ? 0 : 1;
        Database::run('UPDATE users SET is_active = ? WHERE id = ?', [$newStatus, $id]);

        flash('success', sprintf('Akun %s berhasil %s.', $user['name'], $newStatus === 1 ? 'diaktifkan' : 'dinonaktifkan'));
        redirect('/admin/pengguna');
    }

    /** Daftar jastiper + verifikasi. */
    public static function jastipers(): void
    {
        Auth::requireRole('ADMIN');

        $jastipers = Database::all(
            'SELECT u.*,
                    (SELECT COUNT(*) FROM jastip_offers o WHERE o.jastiper_id = u.id) AS offer_count,
                    (SELECT COALESCE(SUM(total),0) FROM orders od WHERE od.jastiper_id = u.id AND od.status <> ?) AS revenue
             FROM users u WHERE u.role = ? ORDER BY u.verified_at IS NOT NULL, u.created_at DESC',
            [Status::DIBATALKAN, 'JASTIPER']
        );

        view('admin/jastipers', compact('jastipers'), 'app');
    }

    /** Verifikasi / cabut verifikasi jastiper. */
    public static function verify(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('ADMIN');

        $id = (int) $params['id'];
        $user = Database::one("SELECT * FROM users WHERE id = ? AND role = 'JASTIPER'", [$id]);

        if (!$user) {
            flash('error', 'Jastiper tidak ditemukan.');
            redirect('/admin/jastiper');
        }

        if ($user['verified_at'] === null) {
            Database::run('UPDATE users SET verified_at = NOW(), is_active = 1 WHERE id = ?', [$id]);
            flash('success', $user['name'] . ' berhasil diverifikasi.');
        } else {
            Database::run('UPDATE users SET verified_at = NULL WHERE id = ?', [$id]);
            flash('success', $user['name'] . ' verifikasi dicabut.');
        }

        redirect('/admin/jastiper');
    }
}