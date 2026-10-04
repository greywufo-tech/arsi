<?php
declare(strict_types=1);

/**
 * Front controller E-Jastip.
 * Semua request (kecuali file statis) melewati file ini.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require BASE_PATH . '/src/Router.php';

require BASE_PATH . '/src/Controllers/HomeController.php';
require BASE_PATH . '/src/Controllers/AuthController.php';
require BASE_PATH . '/src/Controllers/CatalogController.php';
require BASE_PATH . '/src/Controllers/CartController.php';
require BASE_PATH . '/src/Controllers/CheckoutController.php';
require BASE_PATH . '/src/Controllers/CustomerOrderController.php';
require BASE_PATH . '/src/Controllers/ProfileController.php';
require BASE_PATH . '/src/Controllers/JastiperOfferController.php';
require BASE_PATH . '/src/Controllers/JastiperProductController.php';
require BASE_PATH . '/src/Controllers/JastiperOrderController.php';
require BASE_PATH . '/src/Controllers/AdminController.php';
require BASE_PATH . '/src/Controllers/AdminUserController.php';
require BASE_PATH . '/src/Controllers/AdminReportController.php';

$router = new Router();

/* ==================================================================
 *  UMUM
 * ================================================================== */
$router->get('/',            [HomeController::class, 'index']);
$router->get('/login',       [AuthController::class, 'showLogin']);
$router->post('/login',      [AuthController::class, 'login']);
$router->get('/register',    [AuthController::class, 'showRegister']);
$router->post('/register',   [AuthController::class, 'register']);
$router->post('/logout',     [AuthController::class, 'logout']);

/* ==================================================================
 *  KATALOG (publik / untuk pelanggan)
 * ================================================================== */
$router->get('/jastip',                [CatalogController::class, 'offers']);
$router->get('/jastip/{id}',           [CatalogController::class, 'offerDetail']);
$router->get('/produk/{id}',           [CatalogController::class, 'productDetail']);
$router->get('/produk',                [CatalogController::class, 'products']);

/* ==================================================================
 *  KERANJANG
 * ================================================================== */
$router->post('/keranjang/tambah',     [CartController::class, 'add']);
$router->post('/keranjang/ubah',       [CartController::class, 'update']);
$router->post('/keranjang/hapus',      [CartController::class, 'remove']);
$router->post('/keranjang/kosongkan',  [CartController::class, 'clear']);

/* ==================================================================
 *  CHECKOUT & PESANAN PELANGGAN
 * ================================================================== */
$router->get('/checkout',                      [CheckoutController::class, 'index']);
$router->post('/checkout',                     [CheckoutController::class, 'store']);
$router->get('/pelanggan/dashboard',           [CustomerOrderController::class, 'dashboard']);
$router->get('/pesanan',                       [CustomerOrderController::class, 'index']);
$router->get('/pesanan/{id}',                  [CustomerOrderController::class, 'detail']);
$router->post('/pesanan/{id}/bayar',           [CustomerOrderController::class, 'pay']);
$router->post('/pesanan/{id}/terima',          [CustomerOrderController::class, 'receive']);
$router->post('/pesanan/{id}/batal',           [CustomerOrderController::class, 'cancel']);
$router->get('/profil',                        [ProfileController::class, 'index']);
$router->post('/profil',                       [ProfileController::class, 'update']);
$router->post('/profil/password',              [ProfileController::class, 'updatePassword']);

/* ==================================================================
 *  JASTIPER
 * ================================================================== */
$router->get('/jastiper/dashboard',                    [JastiperOrderController::class, 'dashboard']);

$router->get('/jastiper/penawaran',                    [JastiperOfferController::class, 'index']);
$router->get('/jastiper/penawaran/tambah',             [JastiperOfferController::class, 'create']);
$router->post('/jastiper/penawaran',                   [JastiperOfferController::class, 'store']);
$router->get('/jastiper/penawaran/{id}/edit',          [JastiperOfferController::class, 'edit']);
$router->post('/jastiper/penawaran/{id}',              [JastiperOfferController::class, 'update']);
$router->post('/jastiper/penawaran/{id}/hapus',        [JastiperOfferController::class, 'destroy']);

$router->get('/jastiper/produk',                       [JastiperProductController::class, 'index']);
$router->get('/jastiper/produk/tambah',                [JastiperProductController::class, 'create']);
$router->post('/jastiper/produk',                      [JastiperProductController::class, 'store']);
$router->get('/jastiper/produk/{id}/edit',             [JastiperProductController::class, 'edit']);
$router->post('/jastiper/produk/{id}',                 [JastiperProductController::class, 'update']);
$router->post('/jastiper/produk/{id}/hapus',           [JastiperProductController::class, 'destroy']);

$router->get('/jastiper/pesanan',                      [JastiperOrderController::class, 'index']);
$router->get('/jastiper/pesanan/{id}',                 [JastiperOrderController::class, 'detail']);
$router->post('/jastiper/pesanan/{id}/status',         [JastiperOrderController::class, 'updateStatus']);
$router->post('/jastiper/pesanan/{id}/verifikasi',     [JastiperOrderController::class, 'verifyPayment']);
$router->get('/jastiper/pengiriman',                   [JastiperOrderController::class, 'shipments']);
$router->post('/jastiper/pesanan/{id}/kirim',          [JastiperOrderController::class, 'ship']);
$router->get('/jastiper/transaksi',                    [JastiperOrderController::class, 'transactions']);

/* ==================================================================
 *  ADMIN
 * ================================================================== */
$router->get('/admin/dashboard',       [AdminController::class, 'dashboard']);
$router->get('/admin/pesanan',         [AdminController::class, 'orders']);
$router->get('/admin/pesanan/{id}',    [AdminController::class, 'orderDetail']);
$router->get('/admin/pesanan/{id}/verifikasi', [AdminController::class, 'verifyPayment']);
$router->post('/admin/pesanan/{id}/status',    [AdminController::class, 'updateStatus']);
$router->get('/admin/penawaran',       [AdminController::class, 'offers']);
$router->post('/admin/penawaran/{id}/status', [AdminController::class, 'updateOfferStatus']);
$router->get('/admin/pengguna',        [AdminUserController::class, 'index']);
$router->post('/admin/pengguna/{id}/toggle', [AdminUserController::class, 'toggleActive']);
$router->get('/admin/jastiper',        [AdminUserController::class, 'jastipers']);
$router->post('/admin/jastiper/{id}/verifikasi', [AdminUserController::class, 'verify']);
$router->get('/admin/transaksi',       [AdminReportController::class, 'index']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');