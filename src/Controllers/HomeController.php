<?php
declare(strict_types=1);

final class HomeController
{
    public static function index(): void
    {
        if (Auth::check()) {
            redirect(Auth::homeFor(Auth::role()));
        }

        $offers = Database::all(
            "SELECT o.*, u.name AS jastiper_name,
                    (SELECT COUNT(*) FROM products p WHERE p.offer_id = o.id AND p.is_active = 1) AS product_count,
                    (SELECT MIN(p.price) FROM products p WHERE p.offer_id = o.id AND p.is_active = 1) AS min_price
             FROM jastip_offers o
             JOIN users u ON u.id = o.jastiper_id
             WHERE o.status = 'AKTIF'
             ORDER BY o.order_deadline ASC
             LIMIT 6"
        );

        $stats = [
            'offers'   => Database::value("SELECT COUNT(*) FROM jastip_offers WHERE status = 'AKTIF'"),
            'jastiper' => Database::value("SELECT COUNT(*) FROM users WHERE role = 'JASTIPER' AND is_active = 1"),
            'orders'   => Database::value('SELECT COUNT(*) FROM orders'),
        ];

        view('home', compact('offers', 'stats'), 'public');
    }
}