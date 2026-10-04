<?php
declare(strict_types=1);

/**
 * Autentikasi + role based access control.
 */
final class Auth
{
    private const SESSION_KEY = 'user_id';

    private static ?array $cached = null;

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::one('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);

        // Password selalu di-hash; plaintext tidak pernah disimpan/disimpan.
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        if ((int) $user['is_active'] !== 1) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = (int) $user['id'];
        self::$cached = $user;

        // Sinkronkan isi keranjang dengan user id
        if (isset($_SESSION['cart'])) {
            $_SESSION['cart_' . self::id()] = $_SESSION['cart'];
            unset($_SESSION['cart']);
        }

        return true;
    }

    public static function register(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);

        Database::run(
            'INSERT INTO users (name, email, password_hash, role, phone, address)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['name'],
                strtolower($data['email']),
                $hash,
                $data['role'],
                $data['phone'] ?: null,
                $data['address'] ?: null,
            ]
        );

        return Database::lastId();
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $userId;
        self::$cached = null;
    }

    public static function logout(): void
    {
        $user = self::user();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        self::$cached = null;
        unset($user);
    }

    public static function id(): ?int
    {
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        return $id === null ? null : (int) $id;
    }

    public static function user(): ?array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }
        $id = self::id();
        if ($id === null) {
            return null;
        }
        $user = Database::one('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
        if (!$user || (int) $user['is_active'] !== 1) {
            return null;
        }
        return self::$cached = $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        $u = self::user();
        return $u['role'] ?? null;
    }

    public static function is(string ...$roles): bool
    {
        $role = self::role();
        return $role !== null && in_array($role, $roles, true);
    }

    /** Halaman awal sesuai role. */
    public static function homeFor(?string $role): string
    {
        return match ($role) {
            'ADMIN'    => '/admin/dashboard',
            'JASTIPER' => '/jastiper/dashboard',
            'PELANGGAN'=> '/pelanggan/dashboard',
            default    => '/login',
        };
    }

    /* ----------------------------------------------------------------
     * Guard: hanya role tertentu yang boleh mengakses
     * ---------------------------------------------------------------- */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? null;
            flash('error', 'Silakan login terlebih dahulu.');
            redirect('/login');
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!self::is(...$roles)) {
            http_response_code(403);
            view('errors/403');
            exit;
        }
    }

    /** Hanya jastiper (atau admin) yang boleh mengubah produk/pesanan. */
    public static function requireJastiperOwner(int $ownerId): void
    {
        self::requireRole('JASTIPER', 'ADMIN');
        if (self::is('JASTIPER') && self::id() !== $ownerId) {
            http_response_code(403);
            view('errors/403', ['message' => 'Anda bukan pemilik penawaran/pesanan ini.']);
            exit;
        }
    }

    /** Pelanggan hanya boleh melihat pesanannya sendiri; jastiper hanya pesanannya sendiri. */
    public static function requireOrderAccess(int $customerId, int $jastiperId): void
    {
        self::requireLogin();
        $me = self::id();
        if (self::is('ADMIN')) {
            return;
        }
        if (self::is('PELANGGAN') && $me === $customerId) {
            return;
        }
        if (self::is('JASTIPER') && $me === $jastiperId) {
            return;
        }
        http_response_code(403);
        view('errors/403', ['message' => 'Pesanan ini bukan milik Anda.']);
        exit;
    }
}