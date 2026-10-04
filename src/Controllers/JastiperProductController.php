<?php
declare(strict_types=1);

final class JastiperProductController
{
    public static function index(): void
    {
        Auth::requireRole('JASTIPER');

        $offerId = (int) query('offer', 0);
        $params = [Auth::id()];
        $sql = 'SELECT p.*, o.title AS offer_title, o.status AS offer_status, o.order_deadline
                FROM products p
                JOIN jastip_offers o ON o.id = p.offer_id
                WHERE o.jastiper_id = ?';

        if ($offerId > 0) {
            $sql .= ' AND p.offer_id = ?';
            $params[] = $offerId;
        }
        $sql .= ' ORDER BY p.created_at DESC';

        $products = Database::all($sql, $params);
        $offers = Database::all(
            'SELECT id, title FROM jastip_offers WHERE jastiper_id = ? ORDER BY created_at DESC',
            [Auth::id()]
        );

        view('jastiper/products', compact('products', 'offers', 'offerId'), 'app');
    }

    public static function create(): void
    {
        Auth::requireRole('JASTIPER');

        $offers = Database::all(
            "SELECT id, title FROM jastip_offers WHERE jastiper_id = ? AND status IN ('AKTIF','DRAFT') ORDER BY created_at DESC",
            [Auth::id()]
        );

        if ($offers === []) {
            flash('warning', 'Buat penawaran jastip terlebih dahulu sebelum menambahkan produk.');
            redirect('/jastiper/penawaran/tambah');
        }

        $selected = (int) query('offer', 0) ?: (int) $offers[0]['id'];
        view('jastiper/product_form', ['product' => null, 'offers' => $offers, 'selectedOffer' => $selected], 'app');
    }

    public static function store(): void
    {
        verifyCsrf();
        Auth::requireRole('JASTIPER');

        $data = self::validated();
        if ($data === null) {
            redirect('/jastiper/produk/tambah');
        }

        $offer = self::findOwnedOffer((int) $data['offer_id']);
        if (!$offer) {
            flash('error', 'Penawaran tidak ditemukan atau bukan milik Anda.');
            redirect('/jastiper/produk/tambah');
        }

        Database::run(
            'INSERT INTO products (offer_id, name, description, image_url, price, service_fee, stock, category)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $offer['id'],
                $data['name'],
                $data['description'],
                $data['image_url'] ?: null,
                $data['price'],
                $data['service_fee'],
                $data['stock'],
                $data['category'] ?: null,
            ]
        );

        flash('success', 'Produk berhasil ditambahkan.');
        redirect('/jastiper/produk');
    }

    public static function edit(array $params): void
    {
        Auth::requireRole('JASTIPER');
        $product = self::findOwnedProduct((int) $params['id']);

        $offers = Database::all(
            'SELECT id, title FROM jastip_offers WHERE jastiper_id = ? ORDER BY created_at DESC',
            [Auth::id()]
        );

        view('jastiper/product_form', [
            'product'       => $product,
            'offers'        => $offers,
            'selectedOffer' => (int) $product['offer_id'],
        ], 'app');
    }

    public static function update(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('JASTIPER');

        $id = (int) $params['id'];
        $product = self::findOwnedProduct($id);

        $data = self::validated();
        if ($data === null) {
            redirect('/jastiper/produk/' . $id . '/edit');
        }

        if (!self::findOwnedOffer((int) $data['offer_id'])) {
            flash('error', 'Penawaran tujuan bukan milik Anda.');
            redirect('/jastiper/produk/' . $id . '/edit');
        }

        // Stok tidak boleh turun di bawah stok yang sudah terpakai oleh pesanan aktif.
        $reserved = Database::value(
            'SELECT COALESCE(SUM(quantity), 0) FROM order_details WHERE product_id = ? AND order_id IN
                (SELECT id FROM orders WHERE status NOT IN (?, ?))',
            [$id, Status::SELESAI, Status::DIBATALKAN]
        );
        if ((int) $data['stock'] < (int) $reserved) {
            flash('error', "Stok minimal {$reserved} karena sudah ada pesanan aktif.");
            redirect('/jastiper/produk/' . $id . '/edit');
        }

        Database::run(
            'UPDATE products SET offer_id = ?, name = ?, description = ?, image_url = ?,
                    price = ?, service_fee = ?, stock = ?, category = ?
             WHERE id = ?',
            [
                (int) $data['offer_id'],
                $data['name'],
                $data['description'],
                $data['image_url'] ?: null,
                $data['price'],
                $data['service_fee'],
                $data['stock'],
                $data['category'] ?: null,
                $id,
            ]
        );

        flash('success', 'Produk berhasil diperbarui.');
        redirect('/jastiper/produk');
    }

    public static function destroy(array $params): void
    {
        verifyCsrf();
        Auth::requireRole('JASTIPER');

        $id = (int) $params['id'];
        $product = self::findOwnedProduct($id);

        $used = Database::value('SELECT COUNT(*) FROM order_details WHERE product_id = ?', [$id]);
        if ($used > 0) {
            // Produk yang sudah pernah dipesan tidak dihapus agar riwayat tetap utuh.
            Database::run('UPDATE products SET is_active = 0 WHERE id = ?', [$id]);
            flash('success', 'Produk telah dinonaktifkan karena sudah pernah dipesan.');
        } else {
            Database::run('DELETE FROM products WHERE id = ?', [$id]);
            flash('success', 'Produk berhasil dihapus.');
        }

        unset($product);
        redirect('/jastiper/produk');
    }

    /* ------------------------------------------------------------------
     *  BantuAN
     * ------------------------------------------------------------------ */
    private static function findOwnedOffer(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        return Database::one('SELECT * FROM jastip_offers WHERE id = ? AND jastiper_id = ?', [$id, Auth::id()]);
    }

    private static function findOwnedProduct(int $id): array
    {
        $product = Database::one(
            'SELECT p.*, o.jastiper_id FROM products p
             JOIN jastip_offers o ON o.id = p.offer_id
             WHERE p.id = ?',
            [$id]
        );

        if (!$product) {
            http_response_code(404);
            view('errors/404', ['message' => 'Produk tidak ditemukan.'], 'app');
            exit;
        }
        if ((int) $product['jastiper_id'] !== Auth::id() && !Auth::is('ADMIN')) {
            http_response_code(403);
            view('errors/403', ['message' => 'Anda bukan pemilik produk ini.'], 'app');
            exit;
        }

        return $product;
    }

    /** Validasi input produk: harga & stok harus angka. */
    private static function validated(): ?array
    {
        $data = [
            'offer_id'    => toInt(input('offer_id')),
            'name'        => trim((string) input('name', '')),
            'description' => trim((string) input('description', '')),
            'image_url'   => trim((string) input('image_url', '')),
            'price'       => trim((string) input('price', '')),
            'service_fee' => trim((string) input('service_fee', '0')),
            'stock'       => trim((string) input('stock', '')),
            'category'    => trim((string) input('category', '')),
        ];

        $v = new Validator([
            'name'        => 'Nama produk',
            'price'       => 'Harga barang',
            'service_fee' => 'Biaya jasa titip',
            'stock'       => 'Stok',
        ]);

        $v->validate($data, [
            'offer_id'    => 'required|gte:1',
            'name'        => 'required|min:3|max:150',
            'price'       => 'required|numeric|gte:0',
            'service_fee' => 'required|numeric|gte:0',
            'stock'       => 'required|integer|gte:0|lte:100000',
            'description' => 'max:1000',
        ]);

        if ($v->fails()) {
            keepOld($data);
            flash('error', $v->firstError());
            return null;
        }

        // Normalisasi ke format DECIMAL tanpa pemisah ribuan.
        $data['price']       = (float) str_replace(['.', ',', ' '], '', $data['price']);
        $data['service_fee'] = (float) str_replace(['.', ',', ' '], '', $data['service_fee']);

        return $data;
    }
}