<?php
declare(strict_types=1);

final class CatalogController
{
    /** Daftar penawaran jastip yang aktif. */
    public static function offers(): void
    {
        $search   = trim((string) query('q', ''));
        $location = trim((string) query('lokasi', ''));

        $sql = "SELECT o.*, u.name AS jastiper_name,
                       (SELECT COUNT(*) FROM products p WHERE p.offer_id = o.id AND p.is_active = 1) AS product_count,
                       (SELECT MIN(p.price) FROM products p WHERE p.offer_id = o.id AND p.is_active = 1) AS min_price
                FROM jastip_offers o
                JOIN users u ON u.id = o.jastiper_id
                WHERE o.status = 'AKTIF' AND u.is_active = 1";
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (o.title LIKE ? OR o.location LIKE ? OR o.description LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like);
        }
        if ($location !== '') {
            $sql .= ' AND o.location LIKE ?';
            $params[] = '%' . $location . '%';
        }

        $sql .= ' ORDER BY o.order_deadline ASC';
        $offers = Database::all($sql, $params);

        $locations = Database::all(
            "SELECT DISTINCT location FROM jastip_offers WHERE status = 'AKTIF' ORDER BY location"
        );

        view('catalog/offers', compact('offers', 'search', 'location', 'locations'));
    }

    /** Detail penawaran + daftar produk di dalamnya. */
    public static function offerDetail(array $params): void
    {
        $id = (int) $params['id'];

        $offer = Database::one(
            'SELECT o.*, u.name AS jastiper_name, u.phone AS jastiper_phone
             FROM jastip_offers o
             JOIN users u ON u.id = o.jastiper_id
             WHERE o.id = ?',
            [$id]
        );

        if (!$offer) {
            http_response_code(404);
            view('errors/404', ['message' => 'Penawaran tidak ditemukan.']);
            return;
        }

        $products = Database::all(
            'SELECT * FROM products WHERE offer_id = ? AND is_active = 1 ORDER BY category, name',
            [$id]
        );

        $canOrder = $offer['status'] === 'AKTIF' && strtotime($offer['order_deadline']) >= time();

        view('catalog/offer_detail', compact('offer', 'products', 'canOrder'));
    }

    /** Daftar seluruh produk pada penawaran aktif. */
    public static function products(): void
    {
        $search   = trim((string) query('q', ''));
        $category = trim((string) query('kategori', ''));

        $sql = "SELECT p.*, o.title AS offer_title, o.location, o.status AS offer_status, o.order_deadline
                FROM products p
                JOIN jastip_offers o ON o.id = p.offer_id
                WHERE p.is_active = 1 AND o.status = 'AKTIF'";
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (p.name LIKE ? OR p.description LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like);
        }
        if ($category !== '') {
            $sql .= ' AND p.category = ?';
            $params[] = $category;
        }

        $sql .= ' ORDER BY p.created_at DESC';
        $products = Database::all($sql, $params);

        $categories = Database::all(
            "SELECT DISTINCT p.category FROM products p
             JOIN jastip_offers o ON o.id = p.offer_id
             WHERE p.is_active = 1 AND o.status = 'AKTIF' AND p.category IS NOT NULL
             ORDER BY p.category"
        );

        view('catalog/products', compact('products', 'search', 'category', 'categories'));
    }

    /** Detail produk. */
    public static function productDetail(array $params): void
    {
        $id = (int) $params['id'];

        $product = Database::one(
            'SELECT p.*, o.title AS offer_title, o.location, o.status AS offer_status,
                    o.order_deadline, o.end_date, u.name AS jastiper_name
             FROM products p
             JOIN jastip_offers o ON o.id = p.offer_id
             JOIN users u ON u.id = o.jastiper_id
             WHERE p.id = ?',
            [$id]
        );

        if (!$product || (int) $product['is_active'] !== 1) {
            http_response_code(404);
            view('errors/404', ['message' => 'Produk tidak ditemukan.']);
            return;
        }

        $related = Database::all(
            'SELECT * FROM products WHERE offer_id = ? AND id <> ? AND is_active = 1 LIMIT 4',
            [$product['offer_id'], $id]
        );

        $canOrder = $product['offer_status'] === 'AKTIF'
            && strtotime($product['order_deadline']) >= time()
            && (int) $product['stock'] > 0;

        view('catalog/product_detail', compact('product', 'related', 'canOrder'));
    }
}