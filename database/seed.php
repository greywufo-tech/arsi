<?php
declare(strict_types=1);

/**
 * Seed data E-Jastip.
 *
 * Jalankan:
 *   php database/seed.php
 *
 * Menghapus seluruh data lama lalu mengisi data dummy:
 * akun admin/jastiper/pelanggan, penawaran, produk, pesanan,
 * pembayaran, pengiriman, dan riwayat status.
 */

define('BASE_PATH', dirname(__DIR__));

$config = require BASE_PATH . '/config/config.php';
$GLOBALS['config'] = $config;

require_once BASE_PATH . '/src/Database.php';
require_once BASE_PATH . '/src/Status.php';
require_once BASE_PATH . '/src/BusinessRuleException.php';

$db = $config['db'];
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['name'], $db['charset']);

try {
    Database::init($db);
} catch (PDOException $e) {
    fwrite(STDERR, "Gagal terhubung ke database: {$e->getMessage()}\n");
    fwrite(STDERR, "Pastikan MySQL berjalan dan database sudah dibuat (jalankan database/schema.sql lebih dulu).\n");
    exit(1);
}

echo "Mengisi data dummy E-Jastip...\n";

// Urutan penghapusan mengikuti dependensi foreign key (anak lebih dulu).
foreach (['order_status_logs', 'shipments', 'payments', 'order_details', 'orders', 'products', 'jastip_offers', 'users'] as $table) {
    Database::run("DELETE FROM `$table`");
}

/* ------------------------------------------------------------------
 *  USERS
 * ------------------------------------------------------------------ */
function makeUser(string $name, string $email, string $password, string $role, ?string $phone, ?string $address = null, bool $verified = true): int
{
    Database::run(
        'INSERT INTO users (name, email, password_hash, role, phone, address, is_active, verified_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, ?)',
        [
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
            $phone,
            $address,
            $verified ? date('Y-m-d H:i:s', strtotime('-30 days')) : null,
        ]
    );
    return Database::lastId();
}

$adminId = makeUser('Admin E-Jastip', 'admin@ejastip.test', 'admin123', 'ADMIN', '081100000001', 'Kantor E-Jastip, Jakarta');

$jastiperId = makeUser('Budi Santoso', 'jastiper@ejastip.test', 'jastiper123', 'JASTIPER', '081200000001', 'Jl. Kebagusan Raya No. 12, Jakarta');
$jastiper2Id = makeUser('Siti Rahayu', 'jastiper2@ejastip.test', 'jastiper123', 'JASTIPER', '081200000002', 'Jl. Gajah Mada No. 45, Jakarta', false);

$pelangganId = makeUser('Andi Pratama', 'pelanggan@ejastip.test', 'pelanggan123', 'PELANGGAN', '081300000001', 'Jl. Melati No. 7, Bandung');
$pelanggan2Id = makeUser('Dewi Lestari', 'pelanggan2@ejastip.test', 'pelanggan123', 'PELANGGAN', '081300000002', 'Jl. Kenanga No. 3, Surabaya');

echo "  [x] 6 pengguna dibuat\n";

/* ------------------------------------------------------------------
 *  PENAWARAN JASTIP
 *
 *  Mengikuti spesifikasi: periode 1-15 bulan berjalan dengan batas
 *  pemesanan tanggal 12. Bila batas tersebut sudah lewat (mis. seeding
 *  dilakukan tanggal 20), periode digeser ke bulan berikutnya agar demo
 *  tetap dapat diuji.
 * ------------------------------------------------------------------ */
$startDate  = date('Y-m-01');
$deadline   = date('Y-m-12 23:59:59', strtotime($startDate));

if (strtotime($deadline) < time()) {
    $startDate = date('Y-m-01', strtotime('first day of next month'));
    $deadline  = date('Y-m-12 23:59:59', strtotime($startDate));
}

// Tanggal 15 sebagai akhir periode.
$endDate = date('Y-m-15', strtotime($startDate));

$monthName = date('F', strtotime($startDate));   // contoh: Oktober
$year      = date('Y', strtotime($startDate));

$offer1Id = null;
Database::run(
    'INSERT INTO jastip_offers (jastiper_id, title, location, description, start_date, end_date, order_deadline, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
    [
        $jastiperId,
        "Jastip Jakarta — $monthName $year",
        'Jakarta',
        "Belanja barangaturedipusat Jakarta seperti Plaza Indonesia, Grand Indonesia, dan Parry. "
        . "Barang dikirim langsung ke alamat Anda di luar Jakarta. Cocok untuk nonstop, fashion, dan elektronik.",
        $startDate,
        $endDate,
        $deadline,
        'AKTIF',
    ]
);
$offer1Id = Database::lastId();

Database::run(
    'INSERT INTO jastip_offers (jastiper_id, title, location, description, start_date, end_date, order_deadline, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
    [
        $jastiper2Id,
        "Jastip Manila — $monthName $year",
        'Manila',
        'Titip belanja di Manila, Filipina: kosmetik, makanan, dan oleh-oleh. '
        . 'Harga sudah termasuk biaya jastip untuk pembelian di toko.',
        $startDate,
        $endDate,
        date('Y-m-11 23:59:59', strtotime($startDate)),   // batas pemesanan sehari lebih awal
        'AKTIF',
    ]
);
$offer2Id = Database::lastId();

// Penawaran lama (nonaktif) sebagai contoh riwayat.
Database::run(
    'INSERT INTO jastip_offers (jastiper_id, title, location, description, start_date, end_date, order_deadline, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
    [
        $jastiperId,
        "Jastip Bandung — " . date('F', strtotime('-2 months')) . ' ' . date('Y', strtotime('-2 months')),
        'Bandung',
        'Penawaran sudah selesai dan tidak dapat dipesan lagi.',
        date('Y-m-d', strtotime('-2 months')),
        date('Y-m-d', strtotime('-2 months +13 days')),
        date('Y-m-d 23:59:59', strtotime('-2 months +10 days')),
        'SELESAI',
    ]
);

echo "  [x] 3 penawaran jastip dibuat\n";

/* ------------------------------------------------------------------
 *  PRODUK
 * ------------------------------------------------------------------ */
function makeProduct(int $offerId, string $name, string $description, float $price, float $serviceFee, int $stock, string $category): int
{
    Database::run(
        'INSERT INTO products (offer_id, name, description, price, service_fee, stock, category)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$offerId, $name, $description, $price, $serviceFee, $stock, $category]
    );
    return Database::lastId();
}

// Penawaran Jakarta (sesuai contoh pada spesifikasi)
$sepatuId = makeProduct($offer1Id, 'Sepatu Nike Air Max 90', 'Sepatu lari original, ukuran 40-44. Warna putih/black.', 800000, 50000, 10, 'Sepatu');
$tasId   = makeProduct($offer1Id, 'Tas Kanvas E-Jastip', 'Tas ransel kanvas resistant, muat laptop 14"', 500000, 30000, 15, 'Tas');
$bajuId  = makeProduct($offer1Id, 'Kemeja Flanel Premium', 'Kemeja flanel tebal, tersedia warna navy dan merah marun.', 200000, 20000, 20, 'Baju');
$sepatu2Id = makeProduct($offer1Id, 'Sandal Kulit Pria', 'Sandal kulit asli, sole karet. Size 40-43.', 450000, 40000, 8, 'Sepatu');
$baju2Id = makeProduct($offer1Id, 'Hoodie Cotton Oversize', 'Hoodie katun tebal, unisex, ukuran L/XL.', 275000, 25000, 12, 'Baju');
$tas2Id  = makeProduct($offer1Id, 'Tas Ransel Laptop 15"', 'Ransel laptop 15 inci, compartment organizer.', 375000, 30000, 0, 'Tas');

// Penawaran Manila
makeProduct($offer2Id, 'Lipstick Matte Maybelline', 'Lipstick matte warna merah dan nude, original.', 175000, 60000, 20, 'Kosmetik');
$serumId   = makeProduct($offer2Id, 'Serum Vitamin C 30ml', 'Serum wajah vitamin C 30ml, BPOM.', 320000, 75000, 10, 'Kosmetik');
$mangoId   = makeProduct($offer2Id, 'Dried Mango Philippines', 'Mangga kering Premium, kemasan 500gr.', 95000, 40000, 30, 'Makanan');
makeProduct($offer2Id, 'Dry Mango seasoned', 'Mangga kering seasoned isi 100gr.', 45000, 30000, 25, 'Oleh-oleh');
makeProduct($offer2Id, 'Pastel Chocolate Box', 'Kotak cokelat premium oleh-oleh.', 250000, 60000, 6, 'Oleh-oleh');

echo "  [x] 11 produk dibuat\n";

/* ------------------------------------------------------------------
 *  PESANAN CONTOH
 * ------------------------------------------------------------------ */

/**
 * Membuat pesanan beserta detail, pembayaran, pengiriman, dan log status.
 */
function makeOrder(array $spec): int
{
    $items = $spec['items'];
    $subtotal = 0.0;
    $feeTotal = 0.0;
    foreach ($items as $it) {
        $subtotal += $it['price'] * $it['quantity'];
        $feeTotal += $it['service_fee'] * $it['quantity'];
    }
    $shipping = $spec['shipping'];
    $total = $subtotal + $feeTotal + $shipping;

    $code = 'EJS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    Database::run(
        'INSERT INTO orders (order_code, customer_id, jastiper_id, offer_id, order_date, subtotal,
                             service_fee_total, shipping_cost, total, payment_status, status,
                             receiver_name, phone, shipping_address, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $code,
            $spec['customer_id'],
            $spec['jastiper_id'],
            $spec['offer_id'],
            $spec['order_date'],
            $subtotal,
            $feeTotal,
            $shipping,
            $total,
            $spec['payment_status'],
            $spec['status'],
            $spec['receiver_name'],
            $spec['phone'],
            $spec['shipping_address'],
            $spec['notes'] ?? null,
        ]
    );
    $orderId = Database::lastId();

    foreach ($items as $it) {
        Database::run(
            'INSERT INTO order_details (order_id, product_id, product_name, price, service_fee, quantity, subtotal)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $orderId,
                $it['product_id'],
                $it['name'],
                $it['price'],
                $it['service_fee'],
                $it['quantity'],
                ($it['price'] + $it['service_fee']) * $it['quantity'],
            ]
        );
    }

    foreach ($spec['timeline'] as $entry) {
        Database::run(
            'INSERT INTO order_status_logs (order_id, status, note, changed_by, created_at)
             VALUES (?, ?, ?, ?, ?)',
            [$orderId, $entry['status'], $entry['note'], $entry['actor'], $entry['at']]
        );
    }

    return $orderId;
}

// 1) Pesanan selesai (pembayaran lunas, sudah dikirim & diterima)
$orderDoneId = makeOrder([
    'customer_id'      => $pelangganId,
    'jastiper_id'      => $jastiperId,
    'offer_id'         => $offer1Id,
    'order_date'       => date('Y-m-d H:i:s', strtotime('-20 days')),
    'shipping'         => 30000,
    'payment_status'   => 'LUNAS',
    'status'           => Status::SELESAI,
    'receiver_name'    => 'Andi Pratama',
    'phone'            => '081300000001',
    'shipping_address' => 'Jl. Melati No. 7, Bandung, Jawa Barat 40123',
    'items' => [
        ['product_id' => $sepatuId, 'name' => 'Sepatu Nike Air Max 90', 'price' => 800000, 'service_fee' => 50000, 'quantity' => 1],
        ['product_id' => $tasId,   'name' => 'Tas Kanvas E-Jastip',   'price' => 500000, 'service_fee' => 30000, 'quantity' => 1],
    ],
    'timeline' => [
        ['status' => Status::MENUNGGU_PEMBAYARAN, 'note' => 'Pesanan dibuat.', 'actor' => $pelangganId, 'at' => date('Y-m-d H:i:s', strtotime('-20 days'))],
        ['status' => Status::PEMBAYARAN_DIKONFIRMASI, 'note' => 'Pembayaran dikonfirmasi oleh pelanggan.', 'actor' => $pelangganId, 'at' => date('Y-m-d H:i:s', strtotime('-20 days +1 hour'))],
        ['status' => Status::SEDANG_DIBELI, 'note' => 'Pembelian di Plaza Indonesia.', 'actor' => $jastiperId, 'at' => date('Y-m-d H:i:s', strtotime('-18 days'))],
        ['status' => Status::BARANG_DIPEROLEH, 'note' => 'Barang sudah dibeli dan dicek.', 'actor' => $jastiperId, 'at' => date('Y-m-d H:i:s', strtotime('-17 days'))],
        ['status' => Status::SEDANG_DIKIRIM, 'note' => 'Dikirim via JNE dengan resi JNE9988776655.', 'actor' => $jastiperId, 'at' => date('Y-m-d H:i:s', strtotime('-16 days'))],
        ['status' => Status::SELESAI, 'note' => 'Barang diterima pelanggan.', 'actor' => $pelangganId, 'at' => date('Y-m-d H:i:s', strtotime('-12 days'))],
    ],
]);

Database::run(
    'INSERT INTO payments (order_id, method, amount, status, reference, paid_at, verified_by, verified_at, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [
        $orderDoneId, 'TRANSFER_BANK',
        (800000 * 1 + 50000 * 1) + (500000 * 1 + 30000 * 1) + 30000,
        'LUNAS', 'BCA-TRX-55001',
        date('Y-m-d H:i:s', strtotime('-20 days +1 hour')),
        $jastiperId, date('Y-m-d H:i:s', strtotime('-20 days +3 hour')),
        date('Y-m-d H:i:s', strtotime('-20 days +1 hour')),
    ]
);

Database::run(
    'INSERT INTO shipments (order_id, courier_name, tracking_number, shipped_at, delivered_at, note)
     VALUES (?, ?, ?, ?, ?, ?)',
    [
        $orderDoneId, 'JNE Reguler', 'JNE9988776655',
        date('Y-m-d H:i:s', strtotime('-16 days')),
        date('Y-m-d H:i:s', strtotime('-12 days')),
        'Barang dikemas bubble wrap tebal.',
    ]
);

// 2) Pesanan sedang dikirim
$orderShipId = makeOrder([
    'customer_id'      => $pelanggan2Id,
    'jastiper_id'      => $jastiperId,
    'offer_id'         => $offer1Id,
    'order_date'       => date('Y-m-d H:i:s', strtotime('-8 days')),
    'shipping'         => 30000,
    'payment_status'   => 'LUNAS',
    'status'           => Status::SEDANG_DIKIRIM,
    'receiver_name'    => 'Dewi Lestari',
    'phone'            => '081300000002',
    'shipping_address' => 'Jl. Kenanga No. 3, Surabaya, Jawa Timur 60271',
    'items' => [
        ['product_id' => $bajuId, 'name' => 'Kemeja Flanel Premium', 'price' => 200000, 'service_fee' => 20000, 'quantity' => 2],
    ],
    'timeline' => [
        ['status' => Status::MENUNGGU_PEMBAYARAN, 'note' => 'Pesanan dibuat.', 'actor' => $pelanggan2Id, 'at' => date('Y-m-d H:i:s', strtotime('-8 days'))],
        ['status' => Status::PEMBAYARAN_DIKONFIRMASI, 'note' => 'Pembayaran dikonfirmasi oleh pelanggan.', 'actor' => $pelanggan2Id, 'at' => date('Y-m-d H:i:s', strtotime('-8 days +2 hour'))],
        ['status' => Status::SEDANG_DIBELI, 'note' => 'Sedang belanja di Jakarta.', 'actor' => $jastiperId, 'at' => date('Y-m-d H:i:s', strtotime('-6 days'))],
        ['status' => Status::BARANG_DIPEROLEH, 'note' => 'Barang sudah dibeli.', 'actor' => $jastiperId, 'at' => date('Y-m-d H:i:s', strtotime('-5 days'))],
        ['status' => Status::SEDANG_DIKIRIM, 'note' => 'Dikirim via SiCepat resi SCP123456789.', 'actor' => $jastiperId, 'at' => date('Y-m-d H:i:s', strtotime('-3 days'))],
    ],
]);

Database::run(
    'INSERT INTO payments (order_id, method, amount, status, reference, paid_at, verified_by, verified_at, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [
        $orderShipId, 'E_WALLET',
        (200000 * 2) + (20000 * 2) + 30000,
        'LUNAS', 'OVO-778899',
        date('Y-m-d H:i:s', strtotime('-8 days +2 hour')),
        $jastiperId, date('Y-m-d H:i:s', strtotime('-8 days +4 hour')),
        date('Y-m-d H:i:s', strtotime('-8 days +2 hour')),
    ]
);

Database::run(
    'INSERT INTO shipments (order_id, courier_name, tracking_number, shipped_at, note)
     VALUES (?, ?, ?, ?, ?)',
    [$orderShipId, 'SiCepat BEST', 'SCP123456789', date('Y-m-d H:i:s', strtotime('-3 days')), 'Estimasi tiba 1 hari kerja.']
);

// 3) Pesanan menunggu pembayaran
makeOrder([
    'customer_id'      => $pelangganId,
    'jastiper_id'      => $jastiperId,
    'offer_id'         => $offer1Id,
    'order_date'       => date('Y-m-d H:i:s', strtotime('-4 hours')),
    'shipping'         => 30000,
    'payment_status'   => 'BELUM',
    'status'           => Status::MENUNGGU_PEMBAYARAN,
    'receiver_name'    => 'Andi Pratama',
    'phone'            => '081300000001',
    'shipping_address' => 'Jl. Melati No. 7, Bandung, Jawa Barat 40123',
    'notes'            => 'Mohon dipilih warna navy untuk kemeja.',
    'items' => [
        ['product_id' => $baju2Id, 'name' => 'Hoodie Cotton Oversize', 'price' => 275000, 'service_fee' => 25000, 'quantity' => 1],
    ],
    'timeline' => [
        ['status' => Status::MENUNGGU_PEMBAYARAN, 'note' => 'Pesanan dibuat.', 'actor' => $pelangganId, 'at' => date('Y-m-d H:i:s', strtotime('-4 hours'))],
    ],
]);

// 4) Pesanan menunggu verifikasi pembayaran
$orderVerifyId = makeOrder([
    'customer_id'      => $pelanggan2Id,
    'jastiper_id'      => $jastiperId,
    'offer_id'         => $offer1Id,
    'order_date'       => date('Y-m-d H:i:s', strtotime('-2 days')),
    'shipping'         => 30000,
    'payment_status'   => 'MENUNGGU',
    'status'           => Status::PEMBAYARAN_DIKONFIRMASI,
    'receiver_name'    => 'Dewi Lestari',
    'phone'            => '081300000002',
    'shipping_address' => 'Jl. Kenanga No. 3, Surabaya, Jawa Timur 60271',
    'items' => [
        ['product_id' => $sepatu2Id, 'name' => 'Sandal Kulit Pria', 'price' => 450000, 'service_fee' => 40000, 'quantity' => 1],
    ],
    'timeline' => [
        ['status' => Status::MENUNGGU_PEMBAYARAN, 'note' => 'Pesanan dibuat.', 'actor' => $pelanggan2Id, 'at' => date('Y-m-d H:i:s', strtotime('-2 days'))],
        ['status' => Status::PEMBAYARAN_DIKONFIRMASI, 'note' => 'Pembayaran dikonfirmasi oleh pelanggan.', 'actor' => $pelanggan2Id, 'at' => date('Y-m-d H:i:s', strtotime('-2 days +1 hour'))],
    ],
]);

Database::run(
    'INSERT INTO payments (order_id, method, amount, status, reference, paid_at, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?)',
    [
        $orderVerifyId, 'TRANSFER_BANK',
        450000 + 40000 + 30000,
        'MENUNGGU', 'BCA-TRX-90002',
        date('Y-m-d H:i:s', strtotime('-2 days +1 hour')),
        date('Y-m-d H:i:s', strtotime('-2 days +1 hour')),
    ]
);

// 5) Pesanan dari jastiper Manila (untuk laporan admin)
$orderManilaId = makeOrder([
    'customer_id'      => $pelangganId,
    'jastiper_id'      => $jastiper2Id,
    'offer_id'         => $offer2Id,
    'order_date'       => date('Y-m-d H:i:s', strtotime('-10 days')),
    'shipping'         => 30000,
    'payment_status'   => 'LUNAS',
    'status'           => Status::BARANG_DIPEROLEH,
    'receiver_name'    => 'Andi Pratama',
    'phone'            => '081300000001',
    'shipping_address' => 'Jl. Melati No. 7, Bandung, Jawa Barat 40123',
    'items' => [
        ['product_id' => $serumId, 'name' => 'Serum Vitamin C 30ml', 'price' => 320000, 'service_fee' => 75000, 'quantity' => 1],
        ['product_id' => $mangoId, 'name' => 'Dried Mango Philippines', 'price' => 95000, 'service_fee' => 40000, 'quantity' => 2],
    ],
    'timeline' => [
        ['status' => Status::MENUNGGU_PEMBAYARAN, 'note' => 'Pesanan dibuat.', 'actor' => $pelangganId, 'at' => date('Y-m-d H:i:s', strtotime('-10 days'))],
        ['status' => Status::PEMBAYARAN_DIKONFIRMASI, 'note' => 'Pembayaran dikonfirmasi oleh pelanggan.', 'actor' => $pelangganId, 'at' => date('Y-m-d H:i:s', strtotime('-10 days +1 hour'))],
        ['status' => Status::SEDANG_DIBELI, 'note' => 'Belanja di Manila.', 'actor' => $jastiper2Id, 'at' => date('Y-m-d H:i:s', strtotime('-9 days'))],
        ['status' => Status::BARANG_DIPEROLEH, 'note' => 'Barang sudah dibeli.', 'actor' => $jastiper2Id, 'at' => date('Y-m-d H:i:s', strtotime('-7 days'))],
    ],
]);

Database::run(
    'INSERT INTO payments (order_id, method, amount, status, reference, paid_at, verified_by, verified_at, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [
        $orderManilaId, 'TRANSFER_BANK',
        (320000 * 1) + (75000 * 1) + (95000 * 2) + (40000 * 2) + 30000,
        'LUNAS', 'BCA-TRX-44110',
        date('Y-m-d H:i:s', strtotime('-10 days +1 hour')),
        $jastiper2Id, date('Y-m-d H:i:s', strtotime('-10 days +2 hour')),
        date('Y-m-d H:i:s', strtotime('-10 days +1 hour')),
    ]
);

echo "  [x] 5 pesanan contoh dibuat (dengan pembayaran, pengiriman, dan riwayat status)\n\n";

echo "Seed selesai. Akun demo:\n";
echo "  Admin     : admin@ejastip.test / admin123\n";
echo "  Jastiper  : jastiper@ejastip.test / jastiper123\n";
echo "  Pelanggan : pelanggan@ejastip.test / pelanggan123\n";