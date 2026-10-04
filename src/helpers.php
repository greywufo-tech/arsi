<?php
declare(strict_types=1);

/** Ambil nilai konfigurasi dengan notasi titik: config('db.host'). */
function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['config'] ?? [];
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

/** Escape output HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Bangun URL internal. */
function url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

/** Redirect + hentikan eksekusi. */
function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/* ------------------------------------------------------------------
 * Flash message
 * ---------------------------------------------------------------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function takeFlash(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/* ------------------------------------------------------------------
 * Form input helpers
 * ---------------------------------------------------------------- */
function input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

function query(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

/** Nilai input sebelumnya setelah validasi gagal. */
function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function keepOld(array $data): void
{
    unset($data['password'], $data['password_confirmation']);
    $_SESSION['_old'] = $data;
}

function clearOld(): void
{
    unset($_SESSION['_old']);
}

/* ------------------------------------------------------------------
 * CSRF
 * ---------------------------------------------------------------- */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Sesi kedaluwarsa. Silakan kembali dan coba lagi.');
    }
}

/* ------------------------------------------------------------------
 * Format
 * ---------------------------------------------------------------- */
function money(float|int|string $amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

function formatDate(?string $date, bool $withTime = false): string
{
    if (empty($date)) {
        return '-';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '-';
    }
    return date($withTime ? 'd M Y H:i' : 'd M Y', $ts);
}

/* ------------------------------------------------------------------
 * View
 * ---------------------------------------------------------------- */
function view(string $template, array $data = [], string $layout = 'app'): void
{
    // Partial berisi helper tampilan (badge, ikon, timeline).
    require_once BASE_PATH . '/views/partials/badges.php';

    $data['__template'] = $template;
    extract($data, EXTR_SKIP);

    ob_start();
    require BASE_PATH . '/views/' . $template . '.php';
    $content = ob_get_clean();

    require BASE_PATH . '/views/layouts/' . $layout . '.php';
}

/* ------------------------------------------------------------------
 * Nilai POST berupa angka (validasi "harus berupa angka")
 * ---------------------------------------------------------------- */
function toInt(mixed $value): int
{
    return is_numeric($value) ? (int) $value : 0;
}