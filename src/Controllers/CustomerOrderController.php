<?php
declare(strict_types=1);

final class CustomerOrderController
{
    /** Dashboard pelanggan: jumlah pesanan, diproses, dikirim, selesai. */
    public static function dashboard(): void
    {
        Auth::requireRole('PELANGGAN');
        $me = (int) Auth::id();

        $counts = [
            'total'      => Database::value('SELECT COUNT(*) FROM orders WHERE customer_id = ?', [$me]),
            'processing' => Database::value(
                'SELECT COUNT(*) FROM orders WHERE customer_id = ? AND status IN (?, ?, ?)',
                [$me, Status::PEMBAYARAN_DIKONFIRMASI, Status::SEDANG_DIBELI, Status::BARANG_DIPEROLEH]
            ),
            'shipping'   => Database::value(
                'SELECT COUNT(*) FROM orders WHERE customer_id = ? AND status = ?',
                [$me, Status::SEDANG_DIKIRIM]
            ),
            'done'       => Database::value(
                'SELECT COUNT(*) FROM orders WHERE customer_id = ? AND status = ?',
                [$me, Status::SELESAI]
            ),
            'unpaid'     => Database::value(
                'SELECT COUNT(*) FROM orders WHERE customer_id = ? AND status = ?',
                [$me, Status::MENUNGGU_PEMBAYARAN]
            ),
        ];

        // jt.title AS offer_title dipakai oleh view dashboard
        $recent = Database::all(
            'SELECT o.*, jt.title AS offer_title, jt.location, j.name AS jastiper_name
             FROM orders o
             JOIN jastip_offers jt ON jt.id = o.offer_id
             JOIN users j ON j.id = o.jastiper_id
             WHERE o.customer_id = ? ORDER BY o.order_date DESC LIMIT 5',
            [$me]
        );

        view('pelanggan/dashboard', compact('counts', 'recent'), 'app');
    }

    public static function index(): void
    {
        Auth::requireRole('PELANGGAN');
        $me = (int) Auth::id();
        $status = (string) query('status', '');

        // j.name AS jastiper_name dipakai oleh view daftar pesanan
        $sql = 'SELECT o.*, jt.title AS offer_title, jt.location, j.name AS jastiper_name
                FROM orders o
                JOIN jastip_offers jt ON jt.id = o.offer_id
                JOIN users j ON j.id = o.jastiper_id
                WHERE o.customer_id = ?';
        $params = [$me];

        if ($status !== '' && isset(Status::LABELS[$status])) {
            $sql .= ' AND o.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY o.order_date DESC';

        $orders = Database::all($sql, $params);

        view('pelanggan/orders', ['orders' => $orders, 'status' => $status], 'app');
    }

    public static function detail(array $params): void
    {
        Auth::requireLogin();

        $id = (int) $params['id'];
        $order = self::load($id);
        Auth::requireOrderAccess((int) $order['customer_id'], (int) $order['jastiper_id']);

        view('pelanggan/order_detail', self::detailData($order), 'app');
    }

    /** Pelanggan mengonfirmasi pembayaran (simulasi). */
    public static function pay(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('PELANGGAN');

        $id = (int) $params['id'];
        $order = self::load($id);
        if ((int) $order['customer_id'] !== Auth::id()) {
            http_response_code(403);
            view('errors/403', ['message' => 'Pesanan ini bukan milik Anda.'], 'app');
            return;
        }

        $method = (string) input('method', '');
        if (!array_key_exists($method, Status::PAYMENT_METHODS)) {
            flash('error', 'Metode pembayaran tidak valid.');
            redirect('/pesanan/' . $id);
        }

        $result = OrderService::submitPayment($id, (int) Auth::id(), $method, trim((string) input('reference', '')));
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('/pesanan/' . $id);
    }

    /** Pelanggan menandai barang sudah diterima -> pesanan selesai. */
    public static function receive(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('PELANGGAN');

        $id = (int) $params['id'];
        $order = self::load($id);
        if ((int) $order['customer_id'] !== Auth::id()) {
            http_response_code(403);
            view('errors/403', ['message' => 'Pesanan ini bukan milik Anda.'], 'app');
            return;
        }

        $result = OrderService::changeStatus($id, Status::SELESAI, 'PELANGGAN', (int) Auth::id(), 'Barang diterima pelanggan.');
        if ($result['ok']) {
            OrderService::markDelivered($id);
        }
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('/pesanan/' . $id);
    }

    public static function cancel(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('PELANGGAN');

        $id = (int) $params['id'];
        $order = self::load($id);
        if ((int) $order['customer_id'] !== Auth::id()) {
            http_response_code(403);
            view('errors/403', ['message' => 'Pesanan ini bukan milik Anda.'], 'app');
            return;
        }

        $result = OrderService::changeStatus($id, Status::DIBATALKAN, 'PELANGGAN', (int) Auth::id(), 'Dibatalkan oleh pelanggan.');
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('/pesanan/' . $id);
    }

    /* ------------------------------------------------------------------
     *  BantuAN
     * ------------------------------------------------------------------ */
    public static function load(int $id): array
    {
        $order = Database::one(
            'SELECT o.*, c.name AS customer_name, c.email AS customer_email,
                    j.name AS jastiper_name, j.phone AS jastiper_phone,
                    jt.title AS offer_title, jt.location, jt.end_date AS offer_end_date
             FROM orders o
             JOIN users c ON c.id = o.customer_id
             JOIN users j ON j.id = o.jastiper_id
             JOIN jastip_offers jt ON jt.id = o.offer_id
             WHERE o.id = ?',
            [$id]
        );

        if (!$order) {
            http_response_code(404);
            view('errors/404', ['message' => 'Pesanan tidak ditemukan.'], 'app');
            exit;
        }

        return $order;
    }

    /** Susunan data untuk halaman detail pesanan. */
    public static function detailData(array $order): array
    {
        $id = (int) $order['id'];

        return [
            'order'    => $order,
            'details'  => Database::all('SELECT * FROM order_details WHERE order_id = ? ORDER BY id', [$id]),
            'payment'  => Database::one('SELECT * FROM payments WHERE order_id = ?', [$id]),
            'shipment' => Database::one('SELECT * FROM shipments WHERE order_id = ?', [$id]),
            'logs'     => Database::all(
                'SELECT l.*, u.name AS changed_by_name FROM order_status_logs l
                 LEFT JOIN users u ON u.id = l.changed_by
                 WHERE l.order_id = ? ORDER BY l.created_at ASC, l.id ASC',
                [$id]
            ),
        ];
    }
}