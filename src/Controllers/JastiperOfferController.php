<?php
declare(strict_types=1);

final class JastiperOfferController
{
    public static function index(): void
    {
        Auth::requireRole('JASTIPER');

        $offers = Database::all(
            'SELECT o.*,
                    (SELECT COUNT(*) FROM products p WHERE p.offer_id = o.id) AS product_count,
                    (SELECT COUNT(*) FROM orders od WHERE od.offer_id = o.id) AS order_count,
                    (SELECT COALESCE(SUM(total), 0) FROM orders od WHERE od.offer_id = o.id AND od.status <> ?) AS revenue
             FROM jastip_offers o
             WHERE o.jastiper_id = ?
             ORDER BY o.created_at DESC',
            [Status::DIBATALKAN, Auth::id()]
        );

        view('jastiper/offers', compact('offers'), 'app');
    }

    public static function create(): void
    {
        Auth::requireRole('JASTIPER');
        self::requireVerified();
        view('jastiper/offer_form', ['offer' => null], 'app');
    }

    public static function store(): void
    {
        verifyCsrf();
        Auth::requireRole('JASTIPER');
        self::requireVerified();

        $data = self::validated();
        if ($data === null) {
            redirect('/jastiper/penawaran/tambah');
        }

        $status = (string) input('status', 'AKTIF');
        if (!in_array($status, ['DRAFT', 'AKTIF'], true)) {
            $status = 'AKTIF';
        }

        Database::run(
            'INSERT INTO jastip_offers (jastiper_id, title, location, description, image_url,
                                        start_date, end_date, order_deadline, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                Auth::id(),
                $data['title'],
                $data['location'],
                $data['description'],
                $data['image_url'] ?: null,
                $data['start_date'],
                $data['end_date'],
                $data['order_deadline'],
                $status,
            ]
        );

        flash('success', 'Penawaran jastip berhasil dibuat. Tambahkan produk ke dalamnya.');
        redirect('/jastiper/produk/tambah?offer=' . Database::lastId());
    }

    public static function edit(array $params): void
    {
        Auth::requireRole('JASTIPER');
        $offer = self::findOwned((int) $params['id']);
        view('jastiper/offer_form', ['offer' => $offer], 'app');
    }

    public static function update(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('JASTIPER');

        $id = (int) $params['id'];
        $offer = self::findOwned($id);

        $data = self::validated($offer);
        if ($data === null) {
            redirect('/jastiper/penawaran/' . $id . '/edit');
        }

        $status = (string) input('status', $offer['status']);
        if (!in_array($status, ['DRAFT', 'AKTIF', 'NONAKTIF', 'SELESAI'], true)) {
            $status = $offer['status'];
        }

        Database::run(
            'UPDATE jastip_offers
             SET title = ?, location = ?, description = ?, image_url = ?,
                 start_date = ?, end_date = ?, order_deadline = ?, status = ?
             WHERE id = ? AND jastiper_id = ?',
            [
                $data['title'],
                $data['location'],
                $data['description'],
                $data['image_url'] ?: null,
                $data['start_date'],
                $data['end_date'],
                $data['order_deadline'],
                $status,
                $id,
                Auth::id(),
            ]
        );

        flash('success', 'Penawaran berhasil diperbarui.');
        redirect('/jastiper/penawaran');
    }

    public static function destroy(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('JASTIPER');

        $id = (int) $params['id'];
        $offer = self::findOwned($id);

        $hasOrders = Database::value('SELECT COUNT(*) FROM orders WHERE offer_id = ?', [$id]);
        if ($hasOrders > 0) {
            flash('error', 'Penawaran yang sudah memiliki pesanan tidak dapat dihapus. Nonaktifkan saja.');
            redirect('/jastiper/penawaran');
        }

        Database::run('DELETE FROM jastip_offers WHERE id = ? AND jastiper_id = ?', [$id, Auth::id()]);

        flash('success', 'Penawaran beserta produknya berhasil dihapus.');
        redirect('/jastiper/penawaran');
    }

    /* ------------------------------------------------------------------
     *  BantuAN
     * ------------------------------------------------------------------ */
    private static function requireVerified(): void
    {
        $user = Auth::user();
        if ($user['verified_at'] === null) {
            flash('error', 'Akun jastiper Anda belum diverifikasi admin, sehingga belum dapat membuat atau mengubah penawaran.');
            redirect('/jastiper/dashboard');
        }
    }

    /** Hanya pemilik penawaran yang boleh mengelola. */
    private static function findOwned(int $id): array
    {
        $offer = Database::one('SELECT * FROM jastip_offers WHERE id = ?', [$id]);
        if (!$offer) {
            http_response_code(404);
            view('errors/404', ['message' => 'Penawaran tidak ditemukan.'], 'app');
            exit;
        }
        if ((int) $offer['jastiper_id'] !== Auth::id() && !Auth::is('ADMIN')) {
            http_response_code(403);
            view('errors/403', ['message' => 'Anda bukan pemilik penawaran ini.'], 'app');
            exit;
        }
        return $offer;
    }

    private static function validated(?array $offer = null): ?array
    {
        $data = [
            'title'          => trim((string) input('title', '')),
            'location'       => trim((string) input('location', '')),
            'description'    => trim((string) input('description', '')),
            'image_url'      => trim((string) input('image_url', '')),
            'start_date'     => trim((string) input('start_date', '')),
            'end_date'       => trim((string) input('end_date', '')),
            'order_deadline' => trim((string) input('order_deadline', '')),
        ];

        $v = new Validator([
            'title'          => 'Judul penawaran',
            'location'       => 'Lokasi',
            'start_date'     => 'Tanggal mulai',
            'end_date'       => 'Tanggal berakhir',
            'order_deadline' => 'Batas waktu pemesanan',
        ]);

        $v->validate($data, [
            'title'          => 'required|min:5|max:150',
            'location'       => 'required|min:3|max:120',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'order_deadline' => 'required|date',
        ]);

        if ($v->fails()) {
            keepOld($data);
            flash('error', $v->firstError());
            return null;
        }

        // Batas pemesanan harus berada dalam periode penawaran.
        if (strtotime($data['order_deadline']) < strtotime($data['start_date'] . ' 00:00:00')) {
            keepOld($data);
            flash('error', 'Batas waktu pemesanan tidak boleh sebelum tanggal mulai.');
            return null;
        }

        return $data;
    }
}