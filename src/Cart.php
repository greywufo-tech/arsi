<?php
declare(strict_types=1);

/**
 * Keranjang belanja berbasis session.
 *
 * PENTING: seluruh perhitungan harga (harga produk, biaya jastip, total)
 * selalu dihitung ulang dari database di server. Nilai dari client tidak
 * pernah dipercaya.
 */
final class Cart
{
    private static function key(): string
    {
        $id = Auth::id();
        return $id === null ? 'cart' : 'cart_' . $id;
    }

    private static function raw(): array
    {
        return $_SESSION[self::key()] ?? [];
    }

    private static function save(array $cart): void
    {
        $_SESSION[self::key()] = $cart;
    }

    public static function add(int $productId, int $qty = 1): void
    {
        $qty = max(1, $qty);
        $cart = self::raw();
        $cart[$productId] = ($cart[$productId] ?? 0) + $qty;
        self::save($cart);
    }

    public static function setQty(int $productId, int $qty): void
    {
        $cart = self::raw();
        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $qty;
        }
        self::save($cart);
    }

    public static function remove(int $productId): void
    {
        $cart = self::raw();
        unset($cart[$productId]);
        self::save($cart);
    }

    public static function clear(): void
    {
        unset($_SESSION[self::key()]);
    }

    /** Jumlah item tertentu yang ada di keranjang. */
    public static function quantityOf(int $productId): int
    {
        return (int) (self::raw()[$productId] ?? 0);
    }

    public static function count(): int
    {
        return array_sum(self::raw());
    }

    public static function isEmpty(): bool
    {
        return self::raw() === [];
    }

    /**
     * Ambil item keranjang yang valid beserta perhitungannya.
     * Produk yang sudah dihapus / offer berakhir / tidak aktif otomatis dibuang.
     */
    public static function items(): array
    {
        $cart = self::raw();
        if ($cart === []) {
            return [];
        }

        $ids = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $products = Database::all(
            "SELECT p.*, o.title AS offer_title, o.location, o.start_date, o.end_date,
                    o.order_deadline, o.status AS offer_status, o.jastiper_id
             FROM products p
             JOIN jastip_offers o ON o.id = p.offer_id
             WHERE p.id IN ($placeholders)",
            $ids
        );

        $items = [];
        foreach ($products as $p) {
            $qty = (int) $cart[$p['id']];

            // Validasi: produk aktif, offer aktif, belum lewat batas pemesanan
            $isActive = (int) $p['is_active'] === 1 && $p['offer_status'] === 'AKTIF';
            $canOrder = strtotime($p['order_deadline']) >= time();

            $items[] = [
                'product'       => $p,
                'quantity'      => $qty,
                'price'         => (float) $p['price'],
                'service_fee'   => (float) $p['service_fee'],
                'item_subtotal' => (float) $p['price'] * $qty,
                'item_fee'      => (float) $p['service_fee'] * $qty,
                'item_total'    => ((float) $p['price'] + (float) $p['service_fee']) * $qty,
                'available'     => (int) $p['stock'],
                'exceeds_stock' => $qty > (int) $p['stock'],
                'valid'         => $isActive && $canOrder,
            ];
        }

        return $items;
    }

    /**
     * Keranjang hanya boleh berisi satu penawaran pada satu waktu.
     * Returns ID penawaran yang sudah dipakai, atau null.
     */
    public static function mixedOfferId(): ?int
    {
        $ids = [];
        foreach (self::items() as $item) {
            $ids[] = (int) $item['product']['offer_id'];
        }
        $ids = array_unique($ids);
        return count($ids) > 1 ? -1 : ($ids[0] ?? null);
    }

    /**
     * Total dihitung server-side:
     *   subtotal = Σ (harga produk × jumlah)
     *   feeTotal = Σ (biaya jastip × jumlah)
     *   shipping = ongkos kirim
     *   total    = subtotal + feeTotal + shipping
     */
    public static function totals(float $shippingCost = 0.0): array
    {
        $subtotal = 0.0;
        $feeTotal = 0.0;
        foreach (self::items() as $item) {
            $subtotal += $item['item_subtotal'];
            $feeTotal += $item['item_fee'];
        }
        $total = $subtotal + $feeTotal + $shippingCost;

        return [
            'subtotal'    => $subtotal,
            'service_fee' => $feeTotal,
            'shipping'    => $shippingCost,
            'total'       => $total,
        ];
    }

    /** Produk dalam keranjang yang jumlahnya melebihi stok. */
    public static function stockIssues(): array
    {
        return array_values(array_filter(self::items(), fn ($i) => $i['exceeds_stock']));
    }

    /** Produk dalam keranjang yang offer-nya sudah tidak bisa dipesan. */
    public static function invalidItems(): array
    {
        return array_values(array_filter(self::items(), fn ($i) => !$i['valid']));
    }
}