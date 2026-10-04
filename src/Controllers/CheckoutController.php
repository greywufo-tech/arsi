<?php
declare(strict_types=1);

final class CheckoutController
{
    public static function index(): void
    {
        Auth::requireRole('PELANGGAN', 'ADMIN');

        $items = Cart::items();
        $totals = Cart::totals(OrderService::defaultShippingCost());
        $user  = Auth::user();

        view('catalog/checkout', [
            'items'       => $items,
            'totals'      => $totals,
            'user'        => $user,
            'stockIssues' => Cart::stockIssues(),
            'invalid'     => Cart::invalidItems(),
        ]);
    }

    public static function store(): void
    {
        verifyCsrf();
        Auth::requireRole('PELANGGAN', 'ADMIN');

        $data = [
            'receiver_name'    => trim((string) input('receiver_name', '')),
            'phone'            => trim((string) input('phone', '')),
            'shipping_address' => trim((string) input('shipping_address', '')),
            'notes'            => trim((string) input('notes', '')),
        ];

        $v = new Validator([
            'receiver_name'    => 'Nama penerima',
            'phone'            => 'Nomor telepon',
            'shipping_address' => 'Alamat pengiriman',
        ]);

        $v->validate($data, [
            'receiver_name'    => 'required|min:3|max:100',
            'phone'            => 'required|min:8|max:30',
            'shipping_address' => 'required|min:10|max:500',
        ]);

        if ($v->fails()) {
            keepOld($data);
            flash('error', $v->firstError());
            redirect('/checkout');
        }

        $result = OrderService::createFromCart((int) Auth::id(), $data);

        if (!$result['ok']) {
            keepOld($data);
            flash('error', $result['message']);
            redirect('/checkout');
        }

        clearOld();
        flash('success', $result['message'] . ' Silakan lakukan pembayaran.');
        redirect('/pesanan/' . $result['order_id']);
    }
}