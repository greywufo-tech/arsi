-- =============================================================
--  E-JASTIP - Database Schema (MySQL 8+)
--  Jalankan: mysql -u root -p < database/schema.sql
-- =============================================================

CREATE DATABASE IF NOT EXISTS e_jastip
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE e_jastip;

-- -------------------------------------------------------------
-- 1. USERS (pelanggan, jastiper, admin)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('PELANGGAN','JASTIPER','ADMIN') NOT NULL DEFAULT 'PELANGGAN',
  phone         VARCHAR(30) NULL,
  address       TEXT NULL,
  avatar_url    VARCHAR(255) NULL,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  -- jastiper belum boleh membuat penawaran sebelum diverifikasi admin
  verified_at   DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_active (is_active)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 2. JASTIP_OFFERS (penawaran jasa titip milik jastiper)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jastip_offers (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jastiper_id     INT UNSIGNED NOT NULL,
  title           VARCHAR(150) NOT NULL,
  location        VARCHAR(120) NOT NULL,
  description     TEXT NULL,
  image_url       VARCHAR(255) NULL,
  start_date      DATE NOT NULL,
  end_date        DATE NOT NULL,
  -- batas waktu pemesanan (pelanggan tidak bisa order setelah lewat)
  order_deadline  DATETIME NOT NULL,
  status          ENUM('DRAFT','AKTIF','NONAKTIF','SELESAI') NOT NULL DEFAULT 'DRAFT',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_offer_jastiper FOREIGN KEY (jastiper_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_offer_dates CHECK (end_date >= start_date),
  INDEX idx_offer_jastiper (jastiper_id),
  INDEX idx_offer_status (status),
  INDEX idx_offer_period (start_date, end_date)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 3. PRODUCTS (barang di dalam sebuah penawaran)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  offer_id     INT UNSIGNED NOT NULL,
  name         VARCHAR(150) NOT NULL,
  description  TEXT NULL,
  image_url    VARCHAR(255) NULL,
  price        DECIMAL(12,2) NOT NULL,          -- harga barang
  service_fee  DECIMAL(12,2) NOT NULL DEFAULT 0, -- biaya jasa titip per item
  stock        INT NOT NULL DEFAULT 0,
  category     VARCHAR(50) NULL,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_product_offer FOREIGN KEY (offer_id) REFERENCES jastip_offers(id) ON DELETE CASCADE,
  CONSTRAINT chk_product_price CHECK (price >= 0),
  CONSTRAINT chk_product_fee CHECK (service_fee >= 0),
  CONSTRAINT chk_product_stock CHECK (stock >= 0),
  INDEX idx_product_offer (offer_id),
  INDEX idx_product_category (category)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 4. ORDERS
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_code       VARCHAR(25) NOT NULL UNIQUE,
  customer_id      INT UNSIGNED NOT NULL,
  jastiper_id      INT UNSIGNED NOT NULL,
  offer_id         INT UNSIGNED NOT NULL,
  order_date       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  subtotal         DECIMAL(14,2) NOT NULL DEFAULT 0, -- jumlah (harga x qty)
  service_fee_total DECIMAL(14,2) NOT NULL DEFAULT 0, -- jumlah biaya jastip
  shipping_cost    DECIMAL(14,2) NOT NULL DEFAULT 0, -- ongkos kirim
  total            DECIMAL(14,2) NOT NULL DEFAULT 0, -- subtotal + fee + ongkir
  payment_status   ENUM('BELUM','MENUNGGU','LUNAS','GAGAL') NOT NULL DEFAULT 'BELUM',
  status           ENUM('MENUNGGU_PEMBAYARAN',
                        'PEMBAYARAN_DIKONFIRMASI',
                        'SEDANG_DIBELI',
                        'BARANG_DIPEROLEH',
                        'SEDANG_DIKIRIM',
                        'SELESAI',
                        'DIBATALKAN') NOT NULL DEFAULT 'MENUNGGU_PEMBAYARAN',
  receiver_name    VARCHAR(100) NOT NULL,
  phone            VARCHAR(30) NOT NULL,
  shipping_address TEXT NOT NULL,
  notes            TEXT NULL,
  cancel_reason    VARCHAR(255) NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_order_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_order_jastiper FOREIGN KEY (jastiper_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_order_offer    FOREIGN KEY (offer_id)    REFERENCES jastip_offers(id) ON DELETE RESTRICT,
  INDEX idx_order_customer (customer_id),
  INDEX idx_order_jastiper (jastiper_id),
  INDEX idx_order_status (status),
  INDEX idx_order_date (order_date)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 5. ORDER_DETAILS (barang yang dipesan)
--    harga/fee disimpan sebagai snapshot agar riwayat tetap akurat
--    walaupun jastiper mengubah harga produk di kemudian hari.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_details (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id     INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NOT NULL,
  product_name VARCHAR(150) NOT NULL,
  price        DECIMAL(12,2) NOT NULL,
  service_fee  DECIMAL(12,2) NOT NULL DEFAULT 0,
  quantity     INT NOT NULL,
  subtotal     DECIMAL(14,2) NOT NULL, -- (price + service_fee) * quantity
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_detail_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  CONSTRAINT fk_detail_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  CONSTRAINT chk_detail_qty CHECK (quantity > 0),
  INDEX idx_detail_order (order_id),
  INDEX idx_detail_product (product_id)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 6. PAYMENTS (simulasi pembayaran, tanpa payment gateway)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id      INT UNSIGNED NOT NULL UNIQUE,
  method        ENUM('TRANSFER_BANK','E_WALLET','COD') NOT NULL,
  amount        DECIMAL(14,2) NOT NULL,
  status        ENUM('MENUNGGU','LUNAS','GAGAL') NOT NULL DEFAULT 'MENUNGGU',
  reference     VARCHAR(120) NULL,  -- bukti transfer / catatan pelanggan
  paid_at       DATETIME NULL,      -- waktu pelanggan menekan "Konfirmasi Pembayaran"
  verified_by   INT UNSIGNED NULL,  -- admin/jastiper yang memverifikasi
  verified_at   DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_payment_user  FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_payment_status (status)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 7. SHIPMENTS (informasi pengiriman)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shipments (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id        INT UNSIGNED NOT NULL UNIQUE,
  courier_name    VARCHAR(80) NULL,
  tracking_number VARCHAR(80) NULL,
  shipped_at      DATETIME NULL,
  delivered_at    DATETIME NULL,
  note            VARCHAR(255) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_shipment_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  INDEX idx_shipment_tracking (tracking_number)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 8. ORDER_STATUS_LOGS (riwayat perubahan status pesanan)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_status_logs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id    INT UNSIGNED NOT NULL,
  status      VARCHAR(40) NOT NULL,
  note        VARCHAR(255) NULL,
  changed_by  INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_log_user  FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_log_order (order_id)
) ENGINE=InnoDB;