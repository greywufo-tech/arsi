<?php
declare(strict_types=1);

/**
 * Definisi status pesanan, transisi yang diperbolehkan, dan siapa yang boleh mengubah.
 */
final class Status
{
    public const MENUNGGU_PEMBAYARAN     = 'MENUNGGU_PEMBAYARAN';
    public const PEMBAYARAN_DIKONFIRMASI = 'PEMBAYARAN_DIKONFIRMASI';
    public const SEDANG_DIBELI           = 'SEDANG_DIBELI';
    public const BARANG_DIPEROLEH        = 'BARANG_DIPEROLEH';
    public const SEDANG_DIKIRIM          = 'SEDANG_DIKIRIM';
    public const SELESAI                 = 'SELESAI';
    public const DIBATALKAN              = 'DIBATALKAN';

    public const LABELS = [
        self::MENUNGGU_PEMBAYARAN     => 'Menunggu Pembayaran',
        self::PEMBAYARAN_DIKONFIRMASI => 'Pembayaran Dikonfirmasi',
        self::SEDANG_DIBELI           => 'Sedang Dibeli',
        self::BARANG_DIPEROLEH        => 'Barang Diperoleh',
        self::SEDANG_DIKIRIM          => 'Sedang Dikirim',
        self::SELESAI                 => 'Selesai',
        self::DIBATALKAN              => 'Dibatalkan',
    ];

    public const COLORS = [
        self::MENUNGGU_PEMBAYARAN     => 'badge-warning',
        self::PEMBAYARAN_DIKONFIRMASI => 'badge-info',
        self::SEDANG_DIBELI           => 'badge-purple',
        self::BARANG_DIPEROLEH        => 'badge-teal',
        self::SEDANG_DIKIRIM          => 'badge-blue',
        self::SELESAI                 => 'badge-success',
        self::DIBATALKAN              => 'badge-danger',
    ];

    /** Urutan progres linear (untuk progress bar). */
    public const FLOW = [
        self::MENUNGGU_PEMBAYARAN,
        self::PEMBAYARAN_DIKONFIRMASI,
        self::SEDANG_DIBELI,
        self::BARANG_DIPEROLEH,
        self::SEDANG_DIKIRIM,
        self::SELESAI,
    ];

    /** Transisi status yang diperbolehkan. */
    public const NEXT = [
        self::MENUNGGU_PEMBAYARAN     => [self::PEMBAYARAN_DIKONFIRMASI, self::DIBATALKAN],
        self::PEMBAYARAN_DIKONFIRMASI => [self::SEDANG_DIBELI, self::MENUNGGU_PEMBAYARAN, self::DIBATALKAN],
        self::SEDANG_DIBELI           => [self::BARANG_DIPEROLEH, self::DIBATALKAN],
        self::BARANG_DIPEROLEH        => [self::SEDANG_DIKIRIM, self::DIBATALKAN],
        self::SEDANG_DIKIRIM          => [self::SELESAI, self::DIBATALKAN],
        self::SELESAI                 => [],
        self::DIBATALKAN              => [],
    ];

    /** Role yang boleh melakukan transisi ke status tertentu. */
    public const ALLOWED_ROLES = [
        self::PEMBAYARAN_DIKONFIRMASI => ['PELANGGAN', 'ADMIN'],
        self::SEDANG_DIBELI           => ['JASTIPER', 'ADMIN'],
        self::BARANG_DIPEROLEH        => ['JASTIPER', 'ADMIN'],
        self::SEDANG_DIKIRIM          => ['JASTIPER', 'ADMIN'],
        self::SELESAI                 => ['PELANGGAN', 'JASTIPER', 'ADMIN'],
        self::MENUNGGU_PEMBAYARAN     => ['JASTIPER', 'ADMIN'], // penolakan pembayaran
        self::DIBATALKAN              => ['PELANGGAN', 'JASTIPER', 'ADMIN'],
    ];

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }

    public static function color(string $status): string
    {
        return self::COLORS[$status] ?? 'badge-gray';
    }

    public static function canTransition(string $from, string $to, ?string $role): bool
    {
        if (!in_array($to, self::NEXT[$from] ?? [], true)) {
            return false;
        }
        return $role !== null && in_array($role, self::ALLOWED_ROLES[$to] ?? [], true);
    }

    /** Status pesanan yang dianggap "diproses". */
    public static function isProcessing(string $status): bool
    {
        return in_array($status, [
            self::PEMBAYARAN_DIKONFIRMASI,
            self::SEDANG_DIBELI,
            self::BARANG_DIPEROLEH,
        ], true);
    }

    public static function isShipping(string $status): bool
    {
        return $status === self::SEDANG_DIKIRIM;
    }

    /* ------------------------- status pembayaran ------------------------- */
    public const PAYMENT_LABELS = [
        'BELUM'    => 'Belum Bayar',
        'MENUNGGU' => 'Menunggu Verifikasi',
        'LUNAS'    => 'Lunas',
        'GAGAL'    => 'Gagal / Ditolak',
    ];

    public const PAYMENT_COLORS = [
        'BELUM'    => 'badge-gray',
        'MENUNGGU' => 'badge-warning',
        'LUNAS'    => 'badge-success',
        'GAGAL'    => 'badge-danger',
    ];

    public const PAYMENT_METHODS = [
        'TRANSFER_BANK' => 'Transfer Bank',
        'E_WALLET'      => 'E-Wallet',
        'COD'           => 'COD (Bayar di Tempat)',
    ];
}