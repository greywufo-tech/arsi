<?php
declare(strict_types=1);

final class AdminReportController
{
    /** Laporan transaksi: daftar pembayaran + ringkasan + grafik. */
    public static function index(): void
    {
        Auth::requireRole('ADMIN');

        $from  = trim((string) query('dari', ''));
        $to    = trim((string) query('sampai', ''));
        $status = (string) query('status', '');

        $sql = 'SELECT pay.*, o.order_code, o.order_date, o.total AS order_total, o.status AS order_status,
                       u.name AS customer_name, j.name AS jastiper_name
                FROM payments pay
                JOIN orders o ON o.id = pay.order_id
                JOIN users u ON u.id = o.customer_id
                JOIN users j ON j.id = o.jastiper_id
                WHERE 1=1';
        $params = [];

        if ($from !== '' && strtotime($from) !== false) {
            $sql .= ' AND DATE(o.order_date) >= ?';
            $params[] = date('Y-m-d', strtotime($from));
        }
        if ($to !== '' && strtotime($to) !== false) {
            $sql .= ' AND DATE(o.order_date) <= ?';
            $params[] = date('Y-m-d', strtotime($to));
        }
        if (in_array($status, ['MENUNGGU', 'LUNAS', 'GAGAL'], true)) {
            $sql .= ' AND pay.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY o.order_date DESC';

        $payments = Database::all($sql, $params);

        $lunas = array_values(array_filter($payments, fn ($p) => $p['status'] === 'LUNAS'));
        $summary = [
            'count'       => count($payments),
            'lunas'       => count($lunas),
            'pending'     => count(array_filter($payments, fn ($p) => $p['status'] === 'MENUNGGU')),
            'failed'      => count(array_filter($payments, fn ($p) => $p['status'] === 'GAGAL')),
            'total_lunas' => array_sum(array_map(fn ($p) => (float) $p['amount'], $lunas)),
        ];

        // Grafik omzet per bulan
        $monthly = Database::all(
            "SELECT DATE_FORMAT(o.order_date, '%Y-%m') AS label,
                    COALESCE(SUM(CASE WHEN pay.status = 'LUNAS' THEN pay.amount ELSE 0 END), 0) AS omzet,
                    COUNT(*) AS jml
             FROM payments pay JOIN orders o ON o.id = pay.order_id
             WHERE o.status <> ?
             GROUP BY label ORDER BY label DESC LIMIT 12",
            [Status::DIBATALKAN]
        );

        // Omzet per jastiper
        $perJastiper = Database::all(
            "SELECT j.name,
                    COALESCE(SUM(CASE WHEN pay.status = 'LUNAS' THEN pay.amount ELSE 0 END), 0) AS omzet,
                    COUNT(pay.id) AS jml
             FROM payments pay
             JOIN orders o ON o.id = pay.order_id
             JOIN users j ON j.id = o.jastiper_id
             GROUP BY j.id ORDER BY omzet DESC"
        );

        // Omzet per metode pembayaran
        $perMethod = Database::all(
            "SELECT pay.method, COUNT(*) AS jml, COALESCE(SUM(pay.amount),0) AS omzet
             FROM payments pay GROUP BY pay.method"
        );

        view('admin/transactions', compact('payments', 'summary', 'monthly', 'perJastiper', 'perMethod', 'from', 'to', 'status'), 'app');
    }
}