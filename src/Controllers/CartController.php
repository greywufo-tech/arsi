<?php
declare(strict_types=1);

final class CartController
{
    public static function add(): void
    {
        verifyCsrf();
        Auth::requireRole('PELANGGAN', 'ADMIN');

        $productId = toInt(input('product_id'));
        $qty       = max(1, toInt(input('quantity', 1)));

        $product = Database::one(
            'SELECT p.*, o.status AS offer_status, o.order_deadline, o.title AS offer_title
             FROM products p JOIN jastip_offers o ON o.id = p.offer_id
             WHERE p.id = ?',
            [$productId]
        );

        if (!$product || (int) $product['is_active'] !== 1) {
            flash('error', 'Produk tidak ditemukan atau sudah tidak tersedia.');
            redirect('/produk');
        }
        if ($product['offer_status'] !== 'AKTIF') {
            flash('error', 'Penawaran "'.$product['offer_title'].'" sudah tidak aktif.');
            redirect('/produk/' . $productId);
        }
        if (strtotime($product['order_deadline']) < time()) {
            flash('error', 'Batas waktu pemesanan untuk penawaran ini sudah lewat.');
            redirect('/produk/' . $productId);
        }

        // Keranjang hanya boleh satu penawaran: ganti jika berbeda.
        $currentOffer = Cart::mixedOfferId();
        if ($currentOffer !== null && $currentOffer !== (int) $product['offer_id']) {
            Cart::clear();
            flash('warning', 'Keranjang diganti karena hanya dapat berisi barang dari satu penawaran.');
        }

        if ((int) $product['stock'] <= 0) {
            flash('error', 'Stok produk ini habis.');
            redirect('/produk/' . $productId);
        }

        $desired = Cart::quantityOf($productId) + $qty;
        $newQty  = min($desired, (int) $product['stock']);

        if ($newQty < $desired) {
            flash('warning', 'Jumlah dibatasi sesuai stok yang tersedia (' . $product['stock'] . ').');
        }

        Cart::setQty($productId, $newQty);

        flash('success', '"' . $product['name'] . '" ditambahkan ke keranjang.');
        redirect('/checkout');
    }

    public static function update(): void
    {
        verifyCsrf();
        Auth::requireRole('PELANGGAN', 'ADMIN');

        foreach ((array) input('quantities', []) as $productId => $qty) {
            $pid = (int) $productId;
            $q   = max(0, (int) $qty);

            $product = Database::one('SELECT stock FROM products WHERE id = ?', [$pid]);
            if (!$product) {
                Cart::remove($pid);
                continue;
            }
            if ($q > (int) $product['stock']) {
                $q = (int) $product['stock'];
                flash('warning', 'Jumlah "' . $product['stock'] . '" untuk produk #'.$pid.' dibatasi sesuai stok.');
            }
            Cart::setQty($pid, $q);
        }

        flash('success', 'Keranjang diperbarui.');
        redirect('/checkout');
    }

    public static function remove(): void
    {
        verifyCsrf();
        Auth::requireRole('PELANGGAN', 'ADMIN');

        Cart::remove(toInt(input('product_id')));
        flash('success', 'Produk dihapus dari keranjang.');
        redirect('/checkout');
    }

    public static function clear(): void
    {
        verifyCsrf();
        Auth::requireRole('PELANGGAN', 'ADMIN');

        Cart::clear();
        flash('success', 'Keranjang dikosongkan.');
        redirect('/checkout');
    }
}