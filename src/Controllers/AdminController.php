<?php
declare(strict_types=1);

final class AdminController
{
    /** Dashboard admin: total pengguna, jastiper, penawaran, pesanan, transaksi. */
    public static function dashboard(): void
    {
        Auth::requireRole('ADMIN');

        $stats = [
            'users'          => Database::value('SELECT COUNT(*) FROM users WHERE role = ?', ['PELANGGAN']),
            'jastipers'      => Database::value('SELECT COUNT(*) FROM users WHERE role = ?', ['JASTIPER']),
            'unverified'     => Database::value("SELECT COUNT(*) FROM users WHERE role = 'JASTIPER' AND verified_at IS NULL"),
            'active_offers'  => Database::value("SELECT COUNT(*) FROM jastip_offers WHERE status = 'AKTIF'"),
            'orders'         => Database::value('SELECT COUNT(*) FROM orders'),
            'revenue'        => Database::value('SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> ?', [Status::DIBATALKAN]),
            'transactions'   => Database::value('SELECT COUNT(*) FROM payments'),
        ];

        $recentPayments = Database::all(
            'SELECT pay.*, o.order_code, o.order_date, u.name AS customer_name
             FROM payments pay
             JOIN orders o ON o.id = pay.order_id
             JOIN users u ON u.id = o.customer_id
             ORDER BY pay.created_at DESC LIMIT 10'
        );

        // Grafik: jumlah pesanan per bulan (6 bulan terakhir)
        $monthlyOrders = Database::all(
            "SELECT DATE_FORMAT(order_date, '%Y-%m') AS label, COUNT(*) AS total
             FROM orders
             WHERE order_date >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
             GROUP BY label ORDER BY label"
        );

        // Grafik: status pesanan
        $byStatus = Database::all(
            'SELECT status, COUNT(*) AS total FROM orders GROUP BY status ORDER BY total DESC'
        );

        $topJastiper = Database::all(
            "SELECT u.name, COUNT(o.id) AS jumlah, COALESCE(SUM(o.total),0) AS omzet
             FROM orders o JOIN users u ON u.id = o.jastiper_id
             WHERE o.status <> ?
             GROUP BY u.id ORDER BY omzet DESC LIMIT 5",
            [Status::DIBATALKAN]
        );

        view('admin/dashboard', compact('stats', 'recentPayments', 'monthlyOrders', 'byStatus', 'topJastiper'), 'app');
    }

    /** Daftar seluruh pesanan. */
    public static function orders(): void
    {
        Auth::requireRole('ADMIN');

        $status = (string) query('status', '');
        $search = trim((string) query('q', ''));

        $sql = 'SELECT o.*, u.name AS customer_name, j.name AS jastiper_name
                FROM orders o
                JOIN users u ON u.id = o.customer_id
                JOIN users j ON j.id = o.jastiper_id
                WHERE 1=1';
        $params = [];

        if ($status !== '' && isset(Status::LABELS[$status])) {
            $sql .= ' AND o.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $sql .= ' AND (o.order_code LIKE ? OR u.name LIKE ? OR j.name LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like);
        }
        $sql .= ' ORDER BY o.order_date DESC';

        $orders = Database::all($sql, $params);

        view('admin/orders', ['orders' => $orders, 'status' => $status, 'search' => $search], 'app');
    }

    public static function orderDetail(array $params): void
    {
        Auth::requireRole('ADMIN');
        $order = CustomerOrderController::load((int) $params['id']);
        $data = CustomerOrderController::detailData($order);
        view('admin/order_detail', $data, 'app');
    }

    public static function verifyPayment(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('ADMIN');

        $id = (int) $params['id'];
        $action = (string) query('action', 'approve');
        $result = OrderService::verifyPayment($id, (int) Auth::id(), $action !== 'reject');
        flash($result['ok'] ? 'success' : 'error', $result['message'] ?? 'Pembayaran diproses.');
        redirect('/admin/pesanan/' . $id);
    }

    public static function updateStatus(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('ADMIN');

        $id = (int) $params['id'];
        $to = (string) input('status', '');
        $result = OrderService::changeStatus($id, $to, 'ADMIN', (int) Auth::id(), trim((string) input('note', '')) ?: null);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('/admin/pesanan/' . $id);
    }

    /** Moderasi penawaran jastip. */
    public static function offers(): void
    {
        Auth::requireRole('ADMIN');

        $offers = Database::all(
            'SELECT o.*, u.name AS jastiper_name, u.verified_at,
                    (SELECT COUNT(*) FROM products p WHERE p.offer_id = o.id) AS product_count,
                    (SELECT COUNT(*) FROM orders od WHERE od.offer_id = o.id) AS order_count
             FROM jastip_offers o
             JOIN users u ON u.id = o.jastiper_id
             ORDER BY o.created_at DESC'
        );

        view('admin/offers', compact('offers'), 'app');
    }

    public static function updateOfferStatus(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('ADMIN');

        $id = (int) $params['id'];
        $status = (string) input('status', '');

        if (!in_array($status, ['DRAFT', 'AKTIF', 'NONAKTIF', 'SELESAI'], true)) {
            flash('error', 'Status penawaran tidak valid.');
            redirect('/admin/penawaran');
        }

        Database::run('UPDATE jastip_offers SET status = ? WHERE id = ?', [$status, $id]);
        flash('success', 'Status penawaran diperbarui menjadi ' . $status . '.');
        redirect('/admin/penawaran');
    }
}