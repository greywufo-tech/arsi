# E-Jastip — Sistem Informasi Jasa Titip

Aplikasi web marketplace jasa titip yang mempertemukan **pelanggan** dengan **jastiper**.
Jastiper membuka penawaran (lokasi + periode), menambahkan barang, pelanggan memesan dan
membayar, lalu jastiper memproses, membeli, dan mengirim hingga pesanan selesai.

Aplikasi ini **dapat dijalankan dan diuji** — bukan prototype tampilan.

---

## 1. Teknologi

| Komponen | Versi | Keterangan |
|----------|-------|------------|
| PHP | 8.3 | Native (tanpa framework, tanpa Composer) |
| MySQL | 8.4 | Database relasional |
| Server | PHP built-in | `php -S localhost:3000` |

Tidak ada dependensi yang perlu diunduh lewat internet — semuanya memakai PHP & MySQL
yang sudah ada di mesin ini (Laragon).

---

## 2. Cara Menjalankan

### Langkah 1 — Pastikan MySQL jalan

```bash
# dari Git Bash di direktori proyek
"/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqld" \
  --datadir="C:/laragon/data/mysql-8.4" --port=3306 --bind-address=127.0.0.1 --console
```

Atau jalankan **MySQL** dari aplikasi Laragon.

### Langkah 2 — Siapkan database (sekali saja)

```bash
/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql -u root -h 127.0.0.1 < database/schema.sql
```

### Langkah 3 — Isi data dummy

```bash
/c/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe database/seed.php
```

### Langkah 4 — Jalankan server

```bash
/c/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe -S localhost:3000 -t public public/router.php
```

Buka **http://localhost:3000**

> Jika PHP/MySQL Anda berada di lokasi lain, jalankan `php` dan `mysql` seperti biasa;
> konfigurasi koneksi ada di `config/config.php` (bisa dioverride lewat env `DB_HOST`,
> `DB_USER`, `DB_PASS`, `DB_NAME`).

---

## 3. Akun Demo

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@ejastip.test` | `admin123` |
| Jastiper | `jastiper@ejastip.test` | `jastiper123` |
| Pelanggan | `pelanggan@ejastip.test` | `pelanggan123` |

Tersedia juga akun tambahan: `jastiper2@ejastip.test` (belum diverifikasi admin) dan
`pelanggan2@ejastip.test`.

### Data dummy

* **Jastip Jakarta — Oktober 2026** — periode 1–15 Oktober 2026, batas pemesanan 12 Oktober
  2026. Sepatu Rp800.000 (jastip Rp50.000), Tas Rp500.000 (jastip Rp30.000),
  Kemeja Rp200.000 (jastip Rp20.000), dan lainnya.
* **Jastip Manila — Oktober 2026** — kosmetik, makanan, oleh-oleh.
* 5 pesanan contoh pada berbagai status (menunggu bayar, menunggu verifikasi, dalam proses,
  sedang dikirim, selesai) beserta pembayaran, pengiriman, dan riwayat status.

Periode mengikuti spesifikasi (1–15, batas pemesanan tanggal 12). Bila tanggal tersebut sudah
lewat saat seed dijalankan, periode digeser ke bulan berikutnya agar demo tetap dapat diuji.

---

## 4. Struktur Proyek

```
config/config.php          konfigurasi DB & aplikasi
database/
  schema.sql               DDL: 8 tabel + foreign key + check constraint
  seed.php                 data dummy (akun, penawaran, produk, pesanan)
public/
  index.php                front controller + definisi route
  router.php               router script untuk PHP built-in server
  assets/css/app.css       seluruh gaya tampilan
src/
  bootstrap.php            session, autoload, koneksi DB
  Database.php             wrapper PDO
  Auth.php                 login, register, RBAC
  Cart.php                 keranjang + perhitungan
  OrderService.php         logika bisnis pesanan/pembayaran/pengiriman
  Status.php               definisi status & transisi yang diizinkan
  Validator.php            validasi input berbasis aturan
  BusinessRuleException.php
  Router.php               router sederhana
  Controllers/             13 controller
views/
  layouts/                 app.php (dashboard), public.php (publik)
  catalog/                 beranda katalog, detail, checkout
  pelanggan/  jastiper/  admin/  errors/
  partials/                badge status, timeline, isi detail pesanan
tests/
  e2e.sh                   52 pengujian alur utama end-to-end
  rules.sh                 71 pengujian aturan, validasi, keamanan
tools/snapshot.php         alat bantu render halaman untuk screenshot
```

---

## 5. Database

8 tabel relasional dengan foreign key dan check constraint:

| Tabel | Isi | Relasi |
|-------|-----|--------|
| `users` | pelanggan, jastiper, admin | — |
| `jastip_offers` | penawaran jastip | → `users` (jastiper) |
| `products` | barang dalam penawaran | → `jastip_offers` |
| `orders` | header pesanan | → `users`, `jastip_offers` |
| `order_details` | detail barang pesanan | → `orders`, `products` |
| `payments` | pembayaran (simulasi) | → `orders` (unik), `users` |
| `shipments` | kurir & nomor resi | → `orders` (unik) |
| `order_status_logs` | riwayat perubahan status | → `orders`, `users` |

Harga dan biaya jastip pada `order_details` disimpan sebagai **snapshot**, sehingga riwayat
pesanan tetap akurat walaupun jastiper mengubah harga produk di kemudian hari.

---

## 6. Fitur per Role

### Pelanggan
Dashboard (jumlah pesanan, diproses, dikirim, selesai) · Jelajahi jastip · Detail penawaran ·
Detail produk · Keranjang · Checkout · Konfirmasi pembayaran (Transfer Bank / E-Wallet / COD) ·
Riwayat pesanan · Detail pesanan dengan progres status · Informasi pengiriman (kurir + resi) ·
Tandai barang diterima · Profil + ubah password

### Jastiper
Dashboard (penawaran aktif, produk, pesanan baru, diproses, total transaksi) ·
Buat/ubah/hapus penawaran (lokasi, periode, batas pemesanan, status) ·
Kelola produk (harga, biaya jastip, stok, kategori, gambar) · Pesanan masuk ·
Ubah status pesanan · Verifikasi/tolak pembayaran · Input kurir & nomor resi ·
Riwayat transaksi dengan grafik omzet

### Admin
Dashboard (total pengguna, jastiper, penawaran aktif, pesanan, transaksi) + grafik ·
Kelola pengguna (aktifkan/nonaktifkan) · Verifikasi/cabut verifikasi jastiper ·
Moderasi penawaran · Pantau seluruh pesanan · Verifikasi pembayaran ·
Laporan transaksi (filter tanggal & status, omzet per bulan/jastiper/metode)

---

## 7. Status Pesanan

```
Menunggu Pembayaran → Pembayaran Dikonfirmasi → Sedang Dibeli
→ Barang Diperoleh → Sedang Dikirim → Selesai
                 ↘ (mana saja) → Dibatalkan
```

Setiap transisi hanya boleh dilakukan oleh role tertentu (`src/Status.php`), dan perubahan
status selalu dicatat di `order_status_logs`.

---

## 8. Perhitungan Biaya

```
Total = (Harga Produk × Jumlah) + (Biaya Jasa Titip × Jumlah) + Ongkos Kirim
```

Seluruh perhitungan dilakukan **di server** (`OrderService::createFromCart`) dari data
database. Nilai total yang dikirim browser tidak pernah dipercaya, dan tidak ada input
yang memungkinkan pelanggan mengubah total. Gambar/`order_detail_body` juga menampilkan
peringatan bila total tidak konsisten dengan penjumlahan detail.

Contoh sesuai spesifikasi: Rp800.000 + Rp50.000 + Rp30.000 = **Rp880.000** (diuji otomatis).

---

## 9. Validasi & Keamanan

* Email harus valid, password minimal 6 karakter dan wajib dikonfirmasi
* Semua field wajib tidak boleh kosong
* Harga harus angka (`numeric`), stok harus bilangan bulat
* Jumlah pesanan tidak boleh melebihi stok (dibatasi di UI **dan** dicek ulang di server
  dengan `SELECT ... FOR UPDATE` agar tidak bentrok saat checkout bersamaan)
* Pelanggan tidak dapat checkout jika keranjang kosong
* Pelanggan tidak dapat memesan penawaran yang sudah lewat batas pemesanan
* **Hanya jastiper pemilik** penawaran/produk/pesanan yang boleh mengubahnya
* Pelanggan tidak dapat mengakses dashboard jastiper maupun admin

Keamanan:

* Password di-hash dengan `password_hash()` (bcrypt), diverifikasi dengan `password_verify()`
* Autentikasi berbasis session + `session_regenerate_id()` saat login
* Role-based access control di setiap route (`Auth::requireRole`)
* Token CSRF pada seluruh form POST (`verifyCsrf()`)
* Cookie session `HttpOnly` + `SameSite=Lax`
* Seluruh output di-escape dengan `e()` (htmlspecialchars)
* Query memakai prepared statement PDO

---

## 10. Menjalankan Pengujian

Server harus sudah berjalan di `localhost:3000`.

```bash
bash tests/e2e.sh     # 52 pemeriksaan: alur utama 3 role dari daftar sampai selesai
bash tests/rules.sh   # 95 pemeriksaan: aturan, validasi, keamanan, error PHP
```

`tests/rules.sh` juga memindai seluruh halaman sebagai ketiga role dan memastikan tidak ada
`Fatal error` / `Warning` / `Notice` PHP, serta menguji:

* kolom hasil JOIN benar-benar tampil di setiap halaman daftar (offer title, nama jastiper,
  nama pelanggan) — mencegah bug "kolom hilang tapi halaman tetap 200"
* CRUD penawaran & produk oleh jastiper
* harga non-numerik dan stok negatif ditolak
* jumlah dibatasi oleh stok
* server menolak checkout bila deadline sudah lewat
* pembatalan pesanan mengembalikan stok
* jastiper lain tidak bisa mengubah produk milik orang (403)
* update profil dan ganti password (termasuk penolakan password lama yang salah,
  konfirmasi yang tidak sama, dan akun nonaktif)
* password tersimpan sebagai hash bcrypt

Hasil terakhir: **52/52** dan **95/95** lulus.

---

## 11. Catatan

* Pembayaran adalah **simulasi** (tanpa payment gateway sungguhan), sesuai requirement.
* Ongkos kirim memakai biaya tetap yang diatur di `config/config.php`
  (`shipping.default_cost`, default Rp30.000).
* Gambar produk memakai URL opsional; jika kosong ditampilkan ikon kategori.