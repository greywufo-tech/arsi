<?php
declare(strict_types=1);

/**
 * Logika bisnis pesanan: pembuatan pesanan dari keranjang, pembayaran,
 * transisi status, dan pengiriman.
 *
 * Semua perhitungan uang selalu dihitung ulang di server dari data database.
 * Nilai total / harga yang dikirim browser tidak pernah dipercaya.
 */
final class OrderService
{
    /** Kode pesanan mudah dibaca, mis. EJS-20261004-8F3A1C */
    public static function generateOrderCode(): string
    {
        do {
            $code = 'EJS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $exists = Database::value('SELECT 1 FROM orders WHERE order_code = ?', [$code]);
        } while ($exists);

        return $code;
    }

    /** Ongkos kirim: biaya tetap dari konfigurasi. */
    public static function defaultShippingCost(): float
    {
        return (float) config('shipping.default_cost', 30000);
    }

    /* ==============================================================
     *  BUAT PESANAN DARI KERANJANG
     * ============================================================== */
    public static function createFromCart(int $customerId, array $data): array
    {
        $user = Database::one('SELECT * FROM users WHERE id = ?', [$customerId]);
        if (!$user || $user['role'] !== 'PELANGGAN') {
            return ['ok' => false, 'message' => 'Hanya pelanggan yang dapat membuat pesanan.'];
        }

        if (Cart::isEmpty()) {
            return ['ok' => false, 'message' => 'Keranjang belanja kosong. Tambahkan barang terlebih dahulu.'];
        }

        $mixed = Cart::mixedOfferId();
        if ($mixed === -1) {
            return ['ok' => false, 'message' => 'Keranjang hanya boleh berisi barang dari satu penawaran. Kosongkan dulu barang dari penawaran lain.'];
        }

        $items = Cart::items();

        // Validasi stok
        foreach ($items as $item) {
            if ($item['exceeds_stock']) {
                return ['ok' => false, 'message' => sprintf(
                    'Jumlah pesanan "%s" (%d) melebihi stok tersedia (%d).',
                    $item['product']['name'],
                    $item['quantity'],
                    $item['available']
                )];
            }
        }

        // Validasi penawaran masih aktif & belum lewat batas pemesanan
        foreach ($items as $item) {
            $p = $item['product'];
            if (!$item['valid']) {
                $reason = $p['offer_status'] !== 'AKTIF'
                    ? 'penawaran sudah tidak aktif'
                    : 'batas waktu pemesanan sudah lewat';
                return ['ok' => false, 'message' => sprintf('Barang "%s" tidak dapat dipesan karena %s.', $p['name'], $reason)];
            }
        }

        $offerId   = (int) $items[0]['product']['offer_id'];
        $offer     = Database::one('SELECT * FROM jastip_offers WHERE id = ?', [$offerId]);
        $jastiperId = (int) $offer['jastiper_id'];

        $shippingCost = self::defaultShippingCost();
        $totals = Cart::totals($shippingCost);

        try {
            $orderId = Database::transaction(function () use ($items, $customerId, $jastiperId, $offerId, $data, $totals) {
                // 1. Kunci baris produk (FOR UPDATE) agar stok tidak bentrok
                //    ketika dua pelanggan checkout bersamaan.
                $productIds = array_map(fn ($i) => (int) $i['product']['id'], $items);
                $ph = implode(',', array_fill(0, count($productIds), '?'));
                $locked = Database::all("SELECT id, stock FROM products WHERE id IN ($ph) FOR UPDATE", $productIds);

                $stocks = [];
                foreach ($locked as $row) {
                    $stocks[(int) $row['id']] = (int) $row['stock'];
                }

                // 2. Validasi stok terbaru lalu kurangi
                foreach ($items as $item) {
                    $pid  = (int) $item['product']['id'];
                    $qty  = (int) $item['quantity'];
                    $stock = $stocks[$pid] ?? 0;

                    if ($qty > $stock) {
                        throw new BusinessRuleException(sprintf(
                            'Stok "%s" tidak mencukupi. Tersedia %d, diminta %d.',
                            $item['product']['name'],
                            $stock,
                            $qty
                        ));
                    }
                    Database::run('UPDATE products SET stock = stock - ? WHERE id = ?', [$qty, $pid]);
                    $stocks[$pid] = $stock - $qty;
                }

                // 3. Simpan header pesanan (total dihitung server)
                Database::run(
                    'INSERT INTO orders
                        (order_code, customer_id, jastiper_id, offer_id, subtotal,
                         service_fee_total, shipping_cost, total, payment_status, status,
                         receiver_name, phone, shipping_address, notes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        self::generateOrderCode(),
                        $customerId,
                        $jastiperId,
                        $offerId,
                        $totals['subtotal'],
                        $totals['service_fee'],
                        $totals['shipping'],
                        $totals['total'],
                        'BELUM',
                        Status::MENUNGGU_PEMBAYARAN,
                        $data['receiver_name'],
                        $data['phone'],
                        $data['shipping_address'],
                        $data['notes'] ?: null,
                    ]
                );
                $orderId = Database::lastId();

                // 4. Simpan detail pesanan (harga di-snapshot saat transaksi)
                foreach ($items as $item) {
                    Database::run(
                        'INSERT INTO order_details
                            (order_id, product_id, product_name, price, service_fee, quantity, subtotal)
                         VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [
                            $orderId,
                            (int) $item['product']['id'],
                            $item['product']['name'],
                            $item['price'],
                            $item['service_fee'],
                            $item['quantity'],
                            $item['item_total'],
                        ]
                    );
                }

                // 5. Catat status awal
                self::logStatus($orderId, Status::MENUNGGU_PEMBAYARAN, 'Pesanan dibuat.', $customerId);

                return $orderId;
            });
        } catch (BusinessRuleException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        // Hapus keranjang hanya setelah transaksi berhasil
        Cart::clear();

        return ['ok' => true, 'message' => 'Pesanan berhasil dibuat.', 'order_id' => $orderId];
    }

    /* ==============================================================
     *  PEMBAYARAN
     * ============================================================== */

    /** Pelanggan menekan tombol "Konfirmasi Pembayaran". */
    public static function submitPayment(int $orderId, int $customerId, string $method, ?string $reference): array
    {
        $order = self::findOrderForCustomer($orderId, $customerId);
        if (!$order) {
            return ['ok' => false, 'message' => 'Pesanan tidak ditemukan.'];
        }
        if ($order['status'] !== Status::MENUNGGU_PEMBAYARAN) {
            return ['ok' => false, 'message' => 'Pesanan ini tidak dalam status menunggu pembayaran.'];
        }

        try {
            Database::transaction(function () use ($order, $method, $reference, $customerId) {
                Database::run(
                    'INSERT INTO payments (order_id, method, amount, status, reference, paid_at)
                     VALUES (?, ?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE
                        method = VALUES(method),
                        amount = VALUES(amount),
                        status = \'MENUNGGU\',
                        reference = VALUES(reference),
                        paid_at = NOW(),
                        verified_by = NULL,
                        verified_at = NULL',
                    [$order['id'], $method, $order['total'], 'MENUNGGU', $reference]
                );

                Database::run('UPDATE orders SET payment_status = ? WHERE id = ?', ['MENUNGGU', $order['id']]);

                self::setStatus($order['id'], Status::PEMBAYARAN_DIKONFIRMASI, 'Pembayaran dikonfirmasi oleh pelanggan.', $customerId);
            });
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Gagal menyimpan pembayaran: ' . $e->getMessage()];
        }

        return ['ok' => true, 'message' => 'Pembayaran dikirim. Menunggu verifikasi jastiper/admin.'];
    }

    /** Jastiper/admin memverifikasi (LUNAS) atau menolak (GAGAL) pembayaran. */
    public static function verifyPayment(int $orderId, int $verifierId, bool $approve): array
    {
        $order = Database::one('SELECT * FROM orders WHERE id = ?', [$orderId]);
        if (!$order) {
            return ['ok' => false, 'message' => 'Pesanan tidak ditemukan.'];
        }
        $payment = Database::one('SELECT * FROM payments WHERE order_id = ?', [$orderId]);
        if (!$payment) {
            return ['ok' => false, 'message' => 'Belum ada pembayaran untuk pesanan ini.'];
        }
        if ($payment['status'] !== 'MENUNGGU') {
            return ['ok' => false, 'message' => 'Pembayaran ini sudah diproses.'];
        }

        return Database::transaction(function () use ($order, $payment, $verifierId, $approve) {
            if ($approve) {
                Database::run(
                    'UPDATE payments SET status = \'LUNAS\', verified_by = ?, verified_at = NOW() WHERE id = ?',
                    [$verifierId, $payment['id']]
                );
                Database::run('UPDATE orders SET payment_status = \'LUNAS\' WHERE id = ?', [$order['id']]);
                self::logStatus($order['id'], $order['status'], 'Pembayaran diverifikasi lunas.', $verifierId);
            } else {
                Database::run(
                    'UPDATE payments SET status = \'GAGAL\', verified_by = ?, verified_at = NOW() WHERE id = ?',
                    [$verifierId, $payment['id']]
                );
                Database::run('UPDATE orders SET payment_status = \'BELUM\' WHERE id = ?', [$order['id']]);
                self::setStatus($order['id'], Status::MENUNGGU_PEMBAYARAN, 'Pembayaran ditolak, mohon ulangi.', $verifierId);
            }
            return ['ok' => true];
        });
    }

    /* ==============================================================
     *  STATUS PESANAN
     * ============================================================== */
    public static function changeStatus(int $orderId, string $to, ?string $role, ?int $actorId, ?string $note = null): array
    {
        $order = Database::one('SELECT * FROM orders WHERE id = ?', [$orderId]);
        if (!$order) {
            return ['ok' => false, 'message' => 'Pesanan tidak ditemukan.'];
        }

        $from = $order['status'];
        if (!Status::canTransition($from, $to, $role)) {
            return ['ok' => false, 'message' => sprintf(
                'Perubahan status "%s" ke "%s" tidak diperbolehkan.',
                Status::label($from),
                Status::label($to)
            )];
        }

        // Membatalkan pesanan: stok dikembalikan.
        if ($to === Status::DIBATALKAN) {
            Database::transaction(function () use ($orderId, $to, $actorId, $note) {
                $details = Database::all('SELECT product_id, quantity FROM order_details WHERE order_id = ?', [$orderId]);
                foreach ($details as $d) {
                    Database::run('UPDATE products SET stock = stock + ? WHERE id = ?', [(int) $d['quantity'], (int) $d['product_id']]);
                }
                self::setStatus($orderId, $to, $note ?: 'Pesanan dibatalkan, stok dikembalikan.', $actorId);
            });
        } else {
            self::setStatus($orderId, $to, $note, $actorId);
        }

        return ['ok' => true, 'message' => 'Status pesanan diperbarui menjadi "' . Status::label($to) . '".'];
    }

    public static function setStatus(int $orderId, string $status, ?string $note, ?int $actorId): void
    {
        Database::run('UPDATE orders SET status = ? WHERE id = ?', [$status, $orderId]);
        self::logStatus($orderId, $status, $note, $actorId);
    }

    public static function logStatus(int $orderId, string $status, ?string $note, ?int $actorId): void
    {
        Database::run(
            'INSERT INTO order_status_logs (order_id, status, note, changed_by) VALUES (?, ?, ?, ?)',
            [$orderId, $status, $note, $actorId]
        );
    }

    /* ==============================================================
     *  PENGIRIMAN
     * ============================================================== */
    public static function saveShipment(int $orderId, array $data): void
    {
        Database::run(
            'INSERT INTO shipments (order_id, courier_name, tracking_number, shipped_at, note)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                courier_name    = VALUES(courier_name),
                tracking_number = VALUES(tracking_number),
                shipped_at      = VALUES(shipped_at),
                note            = VALUES(note)',
            [
                $orderId,
                $data['courier_name'] ?: null,
                $data['tracking_number'] ?: null,
                $data['shipped_at'] ?: null,
                $data['note'] ?: null,
            ]
        );
    }

    public static function markDelivered(int $orderId): void
    {
        Database::run('UPDATE shipments SET delivered_at = NOW() WHERE order_id = ?', [$orderId]);
    }

    /* ==============================================================
     *  BANTUAN
     * ============================================================== */
    public static function findOrderForCustomer(int $orderId, int $customerId): ?array
    {
        return Database::one('SELECT * FROM orders WHERE id = ? AND customer_id = ?', [$orderId, $customerId]);
    }

    /** Hitung ulang total pesanan dari detail (untuk audit). */
    public static function recalculate(int $orderId): array
    {
        $row = Database::one(
            'SELECT COALESCE(SUM(subtotal), 0) AS item_total FROM order_details WHERE order_id = ?',
            [$orderId]
        );
        $order = Database::one('SELECT * FROM orders WHERE id = ?', [$orderId]);
        $subtotal = 0.0;
        $fee = 0.0;
        foreach (Database::all('SELECT price, service_fee, quantity FROM order_details WHERE order_id = ?', [$orderId]) as $d) {
            $subtotal += (float) $d['price'] * (int) $d['quantity'];
            $fee      += (float) $d['service_fee'] * (int) $d['quantity'];
        }
        $shipping = (float) $order['shipping_cost'];
        return [
            'subtotal' => $subtotal,
            'service_fee' => $fee,
            'shipping' => $shipping,
            'total' => $subtotal + $fee + $shipping,
        ];
    }
}