<?php
declare(strict_types=1);

final class JastiperOrderController
{
    /** Dashboard jastiper: penawaran aktif, produk, pesanan baru, diproses, total transaksi. */
    public static function dashboard(): void
    {
        Auth::requireRole('JASTIPER');
        $me = (int) Auth::id();

        $stats = [
            'active_offers' => Database::value(
                "SELECT COUNT(*) FROM jastip_offers WHERE jastiper_id = ? AND status = 'AKTIF'", [$me]
            ),
            'products'      => Database::value(
                'SELECT COUNT(*) FROM products p JOIN jastip_offers o ON o.id = p.offer_id
                 WHERE o.jastiper_id = ? AND p.is_active = 1', [$me]
            ),
            'new_orders'    => Database::value(
                'SELECT COUNT(*) FROM orders WHERE jastiper_id = ? AND status = ?', [$me, Status::MENUNGGU_PEMBAYARAN]
            ),
            'processing'    => Database::value(
                'SELECT COUNT(*) FROM orders WHERE jastiper_id = ? AND status IN (?, ?, ?)',
                [$me, Status::PEMBAYARAN_DIKONFIRMASI, Status::SEDANG_DIBELI, Status::BARANG_DIPEROLEH]
            ),
            'shipping'      => Database::value(
                'SELECT COUNT(*) FROM orders WHERE jastiper_id = ? AND status = ?', [$me, Status::SEDANG_DIKIRIM]
            ),
            'revenue'       => Database::value(
                'SELECT COALESCE(SUM(total), 0) FROM orders WHERE jastiper_id = ? AND status <> ?',
                [$me, Status::DIBATALKAN]
            ),
            'unpaid'        => Database::value(
                "SELECT COUNT(*) FROM orders WHERE jastiper_id = ? AND payment_status = 'MENUNGGU'", [$me]
            ),
        ];

        $recent = Database::all(
            'SELECT o.*, u.name AS customer_name FROM orders o
             JOIN users u ON u.id = o.customer_id
             WHERE o.jastiper_id = ? ORDER BY o.order_date DESC LIMIT 5',
            [$me]
        );

        $user = Auth::user();

        view('jastiper/dashboard', ['stats' => $stats, 'recent' => $recent, 'user' => $user], 'app');
    }

    public static function index(): void
    {
        Auth::requireRole('JASTIPER');
        $me = (int) Auth::id();
        $status = (string) query('status', '');

        $sql = 'SELECT o.*, u.name AS customer_name, u.email AS customer_email
                FROM orders o JOIN users u ON u.id = o.customer_id
                WHERE o.jastiper_id = ?';
        $params = [$me];

        if ($status !== '' && isset(Status::LABELS[$status])) {
            $sql .= ' AND o.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY o.order_date DESC';

        $orders = Database::all($sql, $params);

        view('jastiper/orders', ['orders' => $orders, 'status' => $status], 'app');
    }

    public static function detail(array $params): void
    {
        Auth::requireLogin();
        $order = CustomerOrderController::load((int) $params['id']);
        Auth::requireOrderAccess((int) $order['customer_id'], (int) $order['jastiper_id']);

        $data = CustomerOrderController::detailData($order);
        $data['nextStatuses'] = self::allowedNextStatuses($order);

        view('jastiper/order_detail', $data, 'app');
    }

    /** Jastiper mengubah status pesanan (mulai beli, barang diperoleh, dll). */
    public static function updateStatus(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('JASTIPER');

        $id = (int) $params['id'];
        $order = CustomerOrderController::load($id);

        if ((int) $order['jastiper_id'] !== Auth::id()) {
            http_response_code(403);
            view('errors/403', ['message' => 'Pesanan ini bukan milik Anda.'], 'app');
            return;
        }

        $to = (string) input('status', '');
        $result = OrderService::changeStatus($id, $to, 'JASTIPER', (int) Auth::id(), trim((string) input('note', '')) ?: null);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('/jastiper/pesanan/' . $id);
    }

    /** Jastiper memverifikasi pembayaran (LUNAS / ditolak). */
    public static function verifyPayment(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('JASTIPER');

        $id = (int) $params['id'];
        $order = CustomerOrderController::load($id);

        if ((int) $order['jastiper_id'] !== Auth::id()) {
            http_response_code(403);
            view('errors/403', ['message' => 'Pesanan ini bukan milik Anda.'], 'app');
            return;
        }

        $action = (string) input('action', 'approve');
        $result = OrderService::verifyPayment($id, (int) Auth::id(), $action === 'approve');
        flash($result['ok'] ? 'success' : 'error', $result['message'] ?? 'Pembayaran diproses.');
        redirect('/jastiper/pesanan/' . $id);
    }

    /** Daftar pesanan yang perlu dikirim. */
    public static function shipments(): void
    {
        Auth::requireRole('JASTIPER');
        $me = (int) Auth::id();

        $orders = Database::all(
            'SELECT o.*, u.name AS customer_name, s.courier_name, s.tracking_number, s.shipped_at
             FROM orders o
             JOIN users u ON u.id = o.customer_id
             LEFT JOIN shipments s ON s.order_id = o.id
             WHERE o.jastiper_id = ? AND o.status IN (?, ?, ?)
             ORDER BY o.order_date DESC',
            [$me, Status::BARANG_DIPEROLEH, Status::SEDANG_DIKIRIM, Status::SELESAI]
        );

        view('jastiper/shipments', compact('orders'), 'app');
    }

    /** Jastiper memasukkan kurir + nomor resi -> pesanan menjadi "Sedang Dikirim". */
    public static function ship(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('JASTIPER');

        $id = (int) $params['id'];
        $order = CustomerOrderController::load($id);

        if ((int) $order['jastiper_id'] !== Auth::id()) {
            http_response_code(403);
            view('errors/403', ['message' => 'Pesanan ini bukan milik Anda.'], 'app');
            return;
        }

        $data = [
            'courier_name'    => trim((string) input('courier_name', '')),
            'tracking_number' => trim((string) input('tracking_number', '')),
            'shipped_at'      => trim((string) input('shipped_at', '')),
            'note'            => trim((string) input('note', '')),
        ];

        $v = new Validator(['courier_name' => 'Nama kurir', 'tracking_number' => 'Nomor resi']);
        $v->validate($data, [
            'courier_name'    => 'required|min:3|max:80',
            'tracking_number' => 'required|min:4|max:80',
            'shipped_at'      => 'required|date',
        ]);

        if ($v->fails()) {
            flash('error', $v->firstError());
            redirect('/jastiper/pesanan/' . $id);
        }

        // Status harus sudah "Barang Diperoleh" sebelum bisa dikirim.
        if (!in_array($order['status'], [Status::BARANG_DIPEROLEH, Status::SEDANG_DIKIRIM], true)) {
            flash('error', 'Barang belum ditandai sudah diperoleh. Ubah status ke "Barang Diperoleh" terlebih dahulu.');
            redirect('/jastiper/pesanan/' . $id);
        }

        OrderService::saveShipment($id, $data);

        if ($order['status'] === Status::BARANG_DIPEROLEH) {
            $result = OrderService::changeStatus($id, Status::SEDANG_DIKIRIM, 'JASTIPER', (int) Auth::id(), 'Barang dikirim dengan resi ' . $data['tracking_number']);
            flash($result['ok'] ? 'success' : 'error', $result['message']);
        } else {
            flash('success', 'Informasi pengiriman diperbarui.');
        }

        redirect('/jastiper/pesanan/' . $id);
    }

    /** Riwayat transaksi jastiper. */
    public static function transactions(): void
    {
        Auth::requireRole('JASTIPER');
        $me = (int) Auth::id();

        $payments = Database::all(
            'SELECT p.*, o.order_code, o.order_date, u.name AS customer_name, o.total
             FROM payments p
             JOIN orders o ON o.id = p.order_id
             JOIN users u ON u.id = o.customer_id
             WHERE o.jastiper_id = ?
             ORDER BY p.created_at DESC',
            [$me]
        );

        $summary = [
            'count'  => count($payments),
            'total'  => array_sum(array_map(fn ($p) => (float) $p['amount'], array_filter($payments, fn ($p) => $p['status'] === 'LUNAS'))),
            'lunas'  => count(array_filter($payments, fn ($p) => $p['status'] === 'LUNAS')),
            'pending'=> count(array_filter($payments, fn ($p) => $p['status'] === 'MENUNGGU')),
        ];

        $monthly = Database::all(
            "SELECT DATE_FORMAT(order_date, '%Y-%m') AS month, COALESCE(SUM(total), 0) AS total, COUNT(*) AS jml
             FROM orders WHERE jastiper_id = ? AND status <> ?
             GROUP BY month ORDER BY month DESC LIMIT 6",
            [$me, Status::DIBATALKAN]
        );

        view('jastiper/transactions', compact('payments', 'summary', 'monthly'), 'app');
    }

    /* ------------------------------------------------------------------
     *  Status berikutnya yang boleh dipilih jastiper
     * ------------------------------------------------------------------ */
    private static function allowedNextStatuses(array $order): array
    {
        $from = $order['status'];
        $allowed = array_filter(
            Status::NEXT[$from] ?? [],
            fn ($s) => in_array('JASTIPER', Status::ALLOWED_ROLES[$s] ?? [], true)
        );

        // JASTIPER tidak boleh mengembalikan status ke Menunggu Pembayaran.
        return array_values(array_filter($allowed, fn ($s) => $s !== Status::MENUNGGU_PEMBAYARAN));
    }
}