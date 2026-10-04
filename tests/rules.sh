#!/usr/bin/env bash
# =====================================================================
#  E-Jastip - Tes integrasi tambahan
#
#  1. Memindai SELURUH halaman untuk mencari warning/notice/fatal error PHP
#  2. Menguji CRUD jastiper (buat/ubah/hapus penawaran & produk)
#  3. Menguji validasi harga/stok
#  4. Menguji batas stok & penawaran yang sudah berakhir
#  5. Menguji pembatalan pesanan mengembalikan stok
#  6. Menguji rumus biaya sesuai spesifikasi
#
#  Jalankan: bash tests/rules.sh
# =====================================================================
set -uo pipefail

BASE="http://localhost:3000"
TMP="$(mktemp -d)"
PASS=0; FAIL=0
trap 'rm -rf "$TMP"' EXIT

ok()  { PASS=$((PASS+1)); printf '  \033[32mPASS\033[0m %s\n' "$1"; }
bad() { FAIL=$((FAIL+1)); printf '  \033[31mFAIL\033[0m %s\n' "$1"; }
h2()  { printf '\n\033[1m%s\033[0m\n' "$1"; }
check(){ if [ "$2" = "$3" ]; then ok "$1 ($2)"; else bad "$1 — harap '$3', dapat '$2'"; fi; }
has()  { if grep -qF -- "$3" "$2"; then ok "$1"; else bad "$1 — '$3' tidak ditemukan"; fi; }

jar() { echo "$TMP/c_$1"; }
get()  { curl -s -b "$(jar "$1")" -c "$(jar "$1")" "$BASE$2"; }
st()   { curl -s -o /dev/null -w '%{http_code}' -b "$(jar "$1")" -c "$(jar "$1")" "$BASE$2"; }
tok()  { get "$1" "$2" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//; s/"$//'; }
post() {
  local j="$1" p="$2" t="$3"; shift 3
  local a=(--data-urlencode "_token=$t"); local kv
  for kv in "$@"; do a+=(--data-urlencode "$kv"); done
  curl -s -i -b "$(jar "$j")" -c "$(jar "$j")" -X POST "${a[@]}" "$BASE$p"
}
loc() { printf '%s' "$1" | grep -i '^location:' | tail -1 | tr -d '\r' | awk '{print $2}'; }
login() { local t; t="$(tok "$1" /login)"; loc "$(post "$1" /login "$t" "email=$2" "password=$3")" > /dev/null; }

ERRPAT='Fatal error|Warning:|Notice:|Deprecated:|Uncaught|Parse error'
scan() { # scan <jar> <path> <label>
  local code f
  f="$TMP/last.html"
  code=$(get "$1" "$2" > "$f"; st "$1" "$2")
  if grep -qE "$ERRPAT" "$f"; then
    bad "PHP error di $3 (HTTP $code)"
    grep -oE "$ERRPAT[^<]{0,140}" "$f" | head -2 | sed 's/^/        /'
  else
    ok "bersih: $3 (HTTP $code)"
  fi
}

MYSQL="/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql"
q() { "$MYSQL" -u root -h 127.0.0.1 -N -B -e "USE e_jastip; $1"; }

# Tag unik per eksekusi supaya skrip aman dijalankan berulang kali.
RUNTAG="uji$(date +%s)"
OFFER_NAME="Jastip Surabaya Uji $RUNTAG"
OLD_OFFER_NAME="Jastip Kadaluarsa Uji $RUNTAG"
PROD_NAME="Produk Uji $RUNTAG"
BAD_PRICE_NAME="Produk Harga Salah $RUNTAG"
BAD_STOCK_NAME="Produk Stok Salah $RUNTAG"
OLD_PROD_NAME="Produk Kadaluarsa $RUNTAG"

# =====================================================================
h2 "1. Pindai semua halaman untuk error PHP"
# ---------- tamu ----------
scan guest /            "beranda"
scan guest /login       "login"
scan guest /register    "registrasi"
scan guest /jastip      "daftar jastip"
scan guest /produk      "daftar produk"
scan guest /halaman-tidak-ada "404"

OFFER_JAKARTA="$(get guest /jastip | grep -o 'href="/jastip/[0-9]*"><div class="product-name">Jastip Jakarta' | grep -o '[0-9]*' | head -1)"
PROD_J1="$(get guest "/jastip/$OFFER_JAKARTA" | grep -o '/produk/[0-9]*' | grep -o '[0-9]*' | head -1)"
scan guest "/jastip/$OFFER_JAKARTA" "detail penawaran"
scan guest "/produk/$PROD_J1"       "detail produk"

# ---------- pelanggan ----------
login pel "pelanggan@ejastip.test" "pelanggan123"
for p in /pelanggan/dashboard /pesanan /profil /checkout /jastip /produk; do
  scan pel "$p" "pelanggan $p"
done
ORDER_PEL="$(q "SELECT MIN(id) FROM orders WHERE status NOT IN ('DIBATALKAN')")"
scan pel "/pesanan/$ORDER_PEL" "pelanggan detail pesanan"

# ---------- jastiper ----------
login jas "jastiper@ejastip.test" "jastiper123"
for p in /jastiper/dashboard /jastiper/penawaran /jastiper/penawaran/tambah \
         /jastiper/produk /jastiper/produk/tambah /jastiper/pesanan \
         /jastiper/pengiriman /jastiper/transaksi /profil; do
  scan jas "$p" "jastiper $p"
done
OFFER_PILIH="$(q "SELECT MIN(id) FROM jastip_offers WHERE jastiper_id = (SELECT id FROM users WHERE email='jastiper@ejastip.test')")"
PROD_PILIH="$(q "SELECT MIN(id) FROM products WHERE offer_id=$OFFER_PILIH")"
scan jas "/jastiper/penawaran/$OFFER_PILIH/edit" "jastiper edit penawaran"
scan jas "/jastiper/produk/$PROD_PILIH/edit"    "jastiper edit produk"
ORDER_JAS="$(q "SELECT MIN(id) FROM orders WHERE jastiper_id = (SELECT id FROM users WHERE email='jastiper@ejastip.test')")"
scan jas "/jastiper/pesanan/$ORDER_JAS" "jastiper detail pesanan"

# ---------- admin ----------
login adm "admin@ejastip.test" "admin123"
for p in /admin/dashboard /admin/pengguna /admin/jastiper /admin/penawaran \
         /admin/pesanan /admin/transaksi /admin/pesanan/$ORDER_JAS; do
  scan adm "$p" "admin $p"
done

# Cek positif: kolom hasil JOIN yang dipakai view harus benar-benar tampil.
# (Lengkap dari sekadar "tidak ada teks error": kolom yang hilang bisa
#  membuat halaman tetap 200 sambil menampilkan kolom kosong.)
h2 "1b. Kolom hasil JOIN tampil di halaman daftar"
# Judul offer mengandung em dash; ambil dua kata pertamanya saja agar
# pencocokan tidak terganggu encoding console MySQL.
OFFER_TITLE="$("$MYSQL" -u root -h 127.0.0.1 -N -B -e "USE e_jastip; SELECT title FROM jastip_offers WHERE status='AKTIF' ORDER BY id LIMIT 1" | awk '{print $1" "$2}')"
JASTIPER_NAME="$("$MYSQL" -u root -h 127.0.0.1 -N -B -e "USE e_jastip; SELECT name FROM users WHERE role='JASTIPER' ORDER BY id LIMIT 1")"
CUSTOMER_NAME="$("$MYSQL" -u root -h 127.0.0.1 -N -B -e "USE e_jastip; SELECT name FROM users WHERE role='PELANGGAN' ORDER BY id LIMIT 1")"
LOCATION="$("$MYSQL" -u root -h 127.0.0.1 -N -B -e "USE e_jastip; SELECT location FROM jastip_offers WHERE status='AKTIF' ORDER BY id LIMIT 1")"

assert_contains() { # assert_contains <jar> <path> <needle> <label>
  local f="$TMP/assert.html"
  get "$1" "$2" > "$f"
  if grep -qF -- "$3" "$f"; then ok "$4"; else bad "$4 — '$3' tidak tampil di $2"; fi
}

assert_contains pel /pelanggan/dashboard "$OFFER_TITLE" "dashboard pelanggan menampilkan judul penawaran (offer_title)"
assert_contains pel /pelanggan/dashboard "$LOCATION"    "dashboard pelanggan menampilkan lokasi"
assert_contains pel /pesanan            "$OFFER_TITLE" "daftar pesanan menampilkan judul penawaran"
assert_contains pel /pesanan            "$JASTIPER_NAME" "daftar pesanan menampilkan nama jastiper"
assert_contains jas /jastiper/pesanan   "$CUSTOMER_NAME" "pesanan masuk menampilkan nama pelanggan"
assert_contains adm /admin/pesanan      "$JASTIPER_NAME" "admin/pesanan menampilkan nama jastiper"
assert_contains adm /admin/pesanan      "$CUSTOMER_NAME" "admin/pesanan menampilkan nama pelanggan"
assert_contains adm /admin/transaksi    "$JASTIPER_NAME" "laporan transaksi menampilkan nama jastiper"
assert_contains adm /admin/penawaran    "$OFFER_TITLE"  "admin/penawaran menampilkan judul penawaran"
# Halaman pesanan jastiper menampilkan kode pesanan (bukan judul penawaran),
# jadi yang diperiksa di sini adalah kode pesanan yang benar-benar dirender.
J_ORDER_CODE="$("$MYSQL" -u root -h 127.0.0.1 -N -B -e "USE e_jastip; SELECT o.order_code FROM orders o JOIN users j ON j.id=o.jastiper_id WHERE j.email='jastiper@ejastip.test' ORDER BY o.id DESC LIMIT 1")"
assert_contains jas /jastiper/pesanan   "$J_ORDER_CODE" "pesanan masuk menampilkan kode pesanan"
assert_contains adm /admin/pengguna     "$CUSTOMER_NAME" "admin/pengguna menampilkan nama pengguna"

# Nama kolom tidak boleh kosong di tabel mana pun yang dirender
get pel /pesanan > "$TMP/pel.html"
if grep -qE '<td[^>]*>[[:space:]]*</td>' "$TMP/pel.html"; then
  bad "daftar pesanan memiliki sel kosong yang seharusnya terisi"
else
  ok "tidak ada sel kosong di daftar pesanan"
fi

# =====================================================================
h2 "2. Jastiper membuat penawaran baru"
T="$(tok jas /jastiper/penawaran/tambah)"
LOCN="$(loc "$(post jas /jastiper/penawaran "$T" \
  "title=$OFFER_NAME" "location=Surabaya" \
  "description=Penawaran uji otomatis" \
  "start_date=$(date +%Y-%m-%d)" "end_date=$(date -d '+13 days' +%Y-%m-%d)" \
  "order_deadline=$(date -d '+10 days' +%Y-%m-%d)T23:59" "status=AKTIF")")"
NEW_OFFER_ID="$(q "SELECT id FROM jastip_offers WHERE title='$OFFER_NAME' ORDER BY id DESC LIMIT 1")"
if [ -n "$NEW_OFFER_ID" ]; then
  ok "penawaran baru tersimpan (id $NEW_OFFER_ID)"
else
  bad "penawaran baru tidak tersimpan"
fi

h2 "3. Jastiper menambahkan produk ke penawaran itu"
T="$(tok jas /jastiper/produk/tambah)"
post jas /jastiper/produk "$T" \
  "offer_id=$NEW_OFFER_ID" "name=$PROD_NAME" "category=Tas" \
  "description=Produk untuk pengujian" \
  "price=123000" "service_fee=7000" "stock=4" > /dev/null
NEW_PROD_ID="$(q "SELECT id FROM products WHERE name='$PROD_NAME' ORDER BY id DESC LIMIT 1")"
if [ -n "$NEW_PROD_ID" ]; then ok "produk tersimpan (id $NEW_PROD_ID)"; else bad "produk tidak tersimpan"; fi
check "harga produk tersimpan benar" "$(q "SELECT price FROM products WHERE id=$NEW_PROD_ID")" "123000.00"
check "biaya jastip tersimpan benar" "$(q "SELECT service_fee FROM products WHERE id=$NEW_PROD_ID")" "7000.00"
check "stok tersimpan benar" "$(q "SELECT stock FROM products WHERE id=$NEW_PROD_ID")" "4"

h2 "4. Produk baru langsung terlihat di halaman publik"
get guest "/jastip/$NEW_OFFER_ID" > "$TMP/no.html"
has "penawaran baru tampil di halaman publik" "$TMP/no.html" "$OFFER_NAME"
has "produk baru tampil di katalog publik" "$TMP/no.html" "$PROD_NAME"

h2 "5. Validasi: harga & stok harus berupa angka"
T="$(tok jas /jastiper/produk/tambah)"
post jas /jastiper/produk "$T" "offer_id=$NEW_OFFER_ID" "name=$BAD_PRICE_NAME" \
  "price=abc" "service_fee=0" "stock=1" > /dev/null
get jas /jastiper/produk/tambah > "$TMP/v1.html"
has "harga non-numerik ditolak" "$TMP/v1.html" "harus berupa angka"
if [ -z "$(q "SELECT id FROM products WHERE name='$BAD_PRICE_NAME'")" ]; then
  ok "produk dengan harga salah tidak tersimpan"
else
  bad "produk dengan harga salah tersimpan"
fi

T="$(tok jas /jastiper/produk/tambah)"
post jas /jastiper/produk "$T" "offer_id=$NEW_OFFER_ID" "name=$BAD_STOCK_NAME" \
  "price=1000" "service_fee=0" "stock=-5" > /dev/null
get jas /jastiper/produk/tambah > "$TMP/v2.html"
if grep -qF "Stok" "$TMP/v2.html" && grep -qE "minimal 0|bilangan bulat" "$TMP/v2.html"; then
  ok "stok negatif ditolak"
else
  bad "stok negatif ditolak — pesan validasi tidak ditemukan"
fi
if [ -z "$(q "SELECT id FROM products WHERE name='$BAD_STOCK_NAME'")" ]; then
  ok "produk dengan stok salah tidak tersimpan"
else
  bad "produk dengan stok salah tersimpan"
fi

h2 "6. Jastiper mengedit produk"
T="$(tok jas "/jastiper/produk/$NEW_PROD_ID/edit")"
post jas "/jastiper/produk/$NEW_PROD_ID" "$T" "offer_id=$NEW_OFFER_ID" \
  "name=$PROD_NAME" "category=Tas" "description=Deskripsi baru" \
  "price=200000" "service_fee=10000" "stock=6" > /dev/null
check "harga terupdate" "$(q "SELECT price FROM products WHERE id=$NEW_PROD_ID")" "200000.00"
check "stok terupdate" "$(q "SELECT stock FROM products WHERE id=$NEW_PROD_ID")" "6"

h2 "7. Pelanggan dibatasi jumlah pesan melebihi stok"
# produk uji stok 6; coba pesan 99. Pakak akun pelanggan yang sudah login.
T="$(tok pel /checkout)"
post pel /keranjang/kosongkan "$T" > /dev/null
T="$(tok pel "/produk/$NEW_PROD_ID")"
post pel /keranjang/tambah "$T" "product_id=$NEW_PROD_ID" "quantity=99" > /dev/null
# atribut input spans beberapa baris -> ratakan dulu
CART_QTY="$(get pel /checkout | tr '\n' ' ' | grep -o "name=\"quantities\[$NEW_PROD_ID\]\"[^>]*value=\"[0-9]*\"" | grep -o 'value="[0-9]*"' | grep -o '[0-9]*')"
check "jumlah dibatasi menjadi stok (6)" "$CART_QTY" "6"

h2 "8. Checkout: total dihitung server sesuai rumus spesifikasi"
# keranjang dikosongkan lalu diisi tepat 1 barang agar rumusnya deterministik
T="$(tok pel /checkout)"
post pel /keranjang/kosongkan "$T" > /dev/null
T="$(tok pel "/produk/$NEW_PROD_ID")"
post pel /keranjang/tambah "$T" "product_id=$NEW_PROD_ID" "quantity=1" > /dev/null
T="$(tok pel /checkout)"
ORDER_LOC="$(loc "$(post pel /checkout "$T" \
  "receiver_name=Penguji Rumus" "phone=081234567890" \
  "shipping_address=Jl. Rumus No. 1, Jakarta 10110")")"
RUMUS_ID="${ORDER_LOC##*/}"
if [ -n "$RUMUS_ID" ] && [ "$RUMUS_ID" != "$ORDER_LOC" ]; then
  # 1 x 200.000 + 1 x 10.000 + 30.000 = 240.000
  SUB="$(q "SELECT subtotal FROM orders WHERE id=$RUMUS_ID")"
  FEE="$(q "SELECT service_fee_total FROM orders WHERE id=$RUMUS_ID")"
  SHP="$(q "SELECT shipping_cost FROM orders WHERE id=$RUMUS_ID")"
  TOT="$(q "SELECT total FROM orders WHERE id=$RUMUS_ID")"
  check "subtotal = harga x jumlah"      "$SUB" "200000.00"
  check "biaya jasa titip sesuai"        "$FEE" "10000.00"
  check "ongkos kirim"                   "$SHP" "30000.00"
  check "total = subtotal + jastip + ongkir" "$TOT" "240000.00"
  CALC="$(awk "BEGIN{printf \"%.2f\", $SUB+$FEE+$SHP}")"
  check "total konsisten dengan penjumlahan" "$TOT" "$CALC"
else
  bad "checkout gagal, pesanan tidak dibuat"
fi

# Cek contoh spesifikasi: 800.000 + 50.000 + 30.000 = 880.000
SEP="$(q "SELECT id FROM products WHERE name='Sepatu Nike Air Max 90' LIMIT 1")"
SEP_PRICE="$(q "SELECT price FROM products WHERE id=$SEP")"
SEP_FEE="$(q "SELECT service_fee FROM products WHERE id=$SEP")"
SPEC_TOTAL="$(awk "BEGIN{printf \"%.2f\", $SEP_PRICE*1+$SEP_FEE*1+30000}")"
check "contoh spesifikasi 800.000+50.000+30.000" "$SPEC_TOTAL" "880000.00"

h2 "9. Penawaran yang batas pemesanannya lewat tidak bisa dipesan"
LOCX="$(loc "$(post jas /jastiper/penawaran "$T" 2>/dev/null)" 2>/dev/null)"
T="$(tok jas /jastiper/penawaran/tambah)"
post jas /jastiper/penawaran "$T" \
  "title=$OLD_OFFER_NAME" "location=Bekasi" "description=uji kedaluwarsa" \
  "start_date=$(date -d '-5 days' +%Y-%m-%d)" "end_date=$(date -d '+5 days' +%Y-%m-%d)" \
  "order_deadline=$(date -d '-1 days' +%Y-%m-%d)T23:59" "status=AKTIF" > /dev/null
OLD_OFFER="$(q "SELECT id FROM jastip_offers WHERE title='$OLD_OFFER_NAME' ORDER BY id DESC LIMIT 1")"
T="$(tok jas /jastiper/produk/tambah)"
post jas /jastiper/produk "$T" "offer_id=$OLD_OFFER" "name=$OLD_PROD_NAME" \
  "price=50000" "service_fee=5000" "stock=3" > /dev/null
OLD_PROD="$(q "SELECT id FROM products WHERE name='$OLD_PROD_NAME' ORDER BY id DESC LIMIT 1")"

# Pelanggan baru agar keranjang bersih
T="$(tok guest /register)"
NEWMAIL="rules$(date +%s)@t.test"
post guest /register "$T" "name=Penguji Kedaluwarsa" "email=$NEWMAIL" \
  "phone=081200000011" "address=Jl Uji" "role=PELANGGAN" \
  "password=rahasia123" "password_confirmation=rahasia123" > /dev/null

get guest "/produk/$OLD_PROD" > "$TMP/oldp.html"
has "halaman produk menandai batas pemesanan lewat" "$TMP/oldp.html" "Batas waktu pemesanan sudah lewat"
if grep -qF "Tambah ke Keranjang" "$TMP/oldp.html"; then
  bad "form tambah ke keranjang masih tampil padahal deadline lewat"
else
  ok "form tambah ke keranjang tidak ditampilkan"
fi

# Menolak ditambahkan ke keranjang
T="$(tok guest "/produk/$OLD_PROD")"
post guest /keranjang/tambah "$T" "product_id=$OLD_PROD" "quantity=1" > /dev/null
get guest /checkout > "$TMP/old.html"
has "penolakan penjelasan batas pemesanan tampil" "$TMP/old.html" "Batas waktu pemesanan untuk penawaran ini sudah lewat"

# Uji penjaga sisi server: pasang barang ke keranjang saat masih aktif,
# lalu buat-deadline-nya lewat sebelum checkout.
q "UPDATE jastip_offers SET order_deadline = DATE_ADD(NOW(), INTERVAL 2 DAY) WHERE id=$OLD_OFFER" > /dev/null
T="$(tok guest /produk)"
add_from_offer() {
  local t; t="$(tok guest "/produk/$1")"
  post guest /keranjang/tambah "$t" "product_id=$1" "quantity=1" > /dev/null
}
add_from_offer "$OLD_PROD"
q "UPDATE jastip_offers SET order_deadline = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE id=$OLD_OFFER" > /dev/null
get guest /checkout > "$TMP/old2.html"
if grep -qF "$OLD_PROD_NAME" "$TMP/old2.html"; then
  ok "barang ada di keranjang (sebelum deadline diubah)"
  if grep -qF "tidak bisa dipesan" "$TMP/old2.html"; then
    ok "keranjang menandai barang sebagai tidak bisa dipesan"
  else
    bad "keranjang tidak menandai barang sebagai tidak bisa dipesan"
  fi
  T="$(tok guest /checkout)"
  CANCEL_LOC="$(loc "$(post guest /checkout "$T" "receiver_name=Uji Deadline" \
    "phone=081200000013" "shipping_address=Jl. Deadline No. 3, Jakarta 10110")")"
  if [ "$CANCEL_LOC" = "/checkout" ]; then
    ok "server menolak checkout karena deadline lewat"
  else
    bad "server TERIMA checkout padahal deadline lewat"
  fi
else
  bad "barang tidak masuk keranjang saat masih aktif"
fi

h2 "10. Pembatalan pesanan mengembalikan stok"
# set stok awal yang pasti supaya Assertions deterministik
q "UPDATE products SET stock = 10 WHERE id=$NEW_PROD_ID" > /dev/null
STOK_AWAL="$(q "SELECT stock FROM products WHERE id=$NEW_PROD_ID")"
# buat pesanan 3 lalu batalkan
T="$(tok pel /checkout)"
post pel /keranjang/kosongkan "$T" > /dev/null
T="$(tok pel "/produk/$NEW_PROD_ID")"
post pel /keranjang/tambah "$T" "product_id=$NEW_PROD_ID" "quantity=3" > /dev/null
T="$(tok pel /checkout)"
CANCEL_LOC="$(loc "$(post pel /checkout "$T" "receiver_name=Penguji Batal" \
  "phone=081200000012" "shipping_address=Jl. Batal No. 2, Jakarta 10110")")"
CANCEL_ID="${CANCEL_LOC##*/}"
if [ -z "$CANCEL_ID" ] || [ "$CANCEL_ID" = "$CANCEL_LOC" ]; then
  bad "checkout untuk uji pembatalan gagal"
  CANCEL_ID=0
fi
STOK_SETELAH_BELI="$(q "SELECT stock FROM products WHERE id=$NEW_PROD_ID")"
if [ "$STOK_SETELAH_BELI" -lt "$STOK_AWAL" ]; then
  ok "stok berkurang setelah order ($STOK_AWAL -> $STOK_SETELAH_BELI)"
else
  bad "stok tidak berkurang setelah order"
fi
T="$(tok pel "/pesanan/$CANCEL_ID")"
post pel "/pesanan/$CANCEL_ID/batal" "$T" > /dev/null
STOK_SESUDAH_BATAL="$(q "SELECT stock FROM products WHERE id=$NEW_PROD_ID")"
check "stok kembali setelah dibatalkan" "$STOK_SESUDAH_BATAL" "$STOK_AWAL"
check "status pesanan menjadi Dibatalkan" "$(q "SELECT status FROM orders WHERE id=$CANCEL_ID")" "DIBATALKAN"

h2 "11. Jastiper lain tidak bisa mengubah produk milik orang"
OTHER="$(q "SELECT id FROM users WHERE email='jastiper2@ejastip.test'")"
OTHER_PROD="$(q "SELECT MIN(id) FROM products WHERE offer_id IN (SELECT id FROM jastip_offers WHERE jastiper_id=$OTHER)")"
BEFORE_PRICE="$(q "SELECT price FROM products WHERE id=$OTHER_PROD")"
T="$(tok jas "/jastiper/produk/$OTHER_PROD/edit")"
CODE="$(curl -s -o /dev/null -w '%{http_code}' -b "$(jar jas)" -X POST \
  --data-urlencode "_token=$T" --data-urlencode "offer_id=$OFFER_PILIH" \
  --data-urlencode "name=Diretas" --data-urlencode "price=1" \
  --data-urlencode "service_fee=0" --data-urlencode "stock=99" \
  "$BASE/jastiper/produk/$OTHER_PROD")"
check "edit produk orang lain ditolak (403)" "$CODE" "403"
check "harga produk tidak berubah" "$(q "SELECT price FROM products WHERE id=$OTHER_PROD")" "$BEFORE_PRICE"

h2 "12. Pelanggan tidak bisa membuka offer form jastiper"
check "GET /jastiper/penawaran/tambah ditolak" "$(st pel /jastiper/penawaran/tambah)" "403"
check "POST /jastiper/pesanan/1/status ditolak" \
  "$(curl -s -o /dev/null -w '%{http_code}' -b "$(jar pel)" -X POST -d "_token=$(tok pel /pesanan)" "$BASE/jastiper/pesanan/1/status")" "403"

h2 "13. Penawaran yang sudah punya pesanan tidak boleh dihapus"
T="$(tok jas /jastiper/penawaran)"
post jas "/jastiper/penawaran/$NEW_OFFER_ID/hapus" "$T" > /dev/null
if [ -n "$(q "SELECT id FROM jastip_offers WHERE id=$NEW_OFFER_ID")" ]; then
  ok "penawaran yang sudah ada pesanannya tidak terhapus"
else
  bad "penawaran yang sudah ada pesanannya ikut terhapus"
fi

h2 "14. Jastiper menghapus penawaran tanpa pesanan (berserta produknya)"
T="$(tok jas /jastiper/penawaran/tambah)"
post jas /jastiper/penawaran "$T" \
  "title=$OFFER_NAME" "location=Surabaya" "description=offer kosong untuk uji hapus" \
  "start_date=$(date +%Y-%m-%d)" "end_date=$(date -d '+13 days' +%Y-%m-%d)" \
  "order_deadline=$(date -d '+10 days' +%Y-%m-%d)T23:59" "status=DRAFT" > /dev/null
TMP_OFFER="$(q "SELECT id FROM jastip_offers WHERE title='$OFFER_NAME' ORDER BY id DESC LIMIT 1")"
T="$(tok jas /jastiper/produk/tambah)"
post jas /jastiper/produk "$T" "offer_id=$TMP_OFFER" "name=$PROD_NAME" \
  "price=10000" "service_fee=1000" "stock=2" > /dev/null
TMP_PROD="$(q "SELECT id FROM products WHERE offer_id=$TMP_OFFER ORDER BY id DESC LIMIT 1")"
if [ -n "$TMP_PROD" ]; then ok "produk sementara dibuat (id $TMP_PROD)"; else bad "produk sementara gagal dibuat"; fi
T="$(tok jas /jastiper/penawaran)"
post jas "/jastiper/penawaran/$TMP_OFFER/hapus" "$T" > /dev/null
if [ -z "$(q "SELECT id FROM jastip_offers WHERE id=$TMP_OFFER")" ]; then
  ok "penawaran kosong terhapus"
else
  bad "penawaran kosong tidak terhapus"
fi
if [ -z "$(q "SELECT id FROM products WHERE id=$TMP_PROD")" ]; then
  ok "produk ikut terhapus (cascade)"
else
  bad "produk tidak terhapus"
fi

h2 "15. Profil dan ganti password"
# Memakai session BARU (belum login) agar /register bisa diakses, dan
# memakai akun uji baru agar akun demo tidak ikut berubah.
PROFJAR="prof"
T="$(tok "$PROFJAR" /register)"
PROFMAIL="profil$(date +%s)@t.test"
post "$PROFJAR" /register "$T" "name=Penguji Profil" "email=$PROFMAIL" \
  "phone=081200000021" "address=Jl. Awal" "role=PELANGGAN" \
  "password=lama12345" "password_confirmation=lama12345" > /dev/null
PROF_ID="$(q "SELECT id FROM users WHERE email='$PROFMAIL'")"
if [ -n "$PROF_ID" ]; then ok "akun uji profil terdaftar"; else bad "akun uji profil gagal dibuat"; fi

# login_loc <email> <password> <jar> -> tujuan redirect (/login jika gagal)
login_loc() { local t; t="$(tok "$3" /login)"; loc "$(post "$3" /login "$t" "email=$1" "password=$2")"; }

# --- ubah profil ---
T="$(tok "$PROFJAR" /profil)"
post "$PROFJAR" /profil "$T" "name=Profil Baru" "phone=081299999999" "address=Jl. Uji Profil 9" > /dev/null
get "$PROFJAR" /profil > "$TMP/prof.html"
has "nama baru tampil di halaman profil" "$TMP/prof.html" "Profil Baru"
check "nama tersimpan di database" "$(q "SELECT name FROM users WHERE id=$PROF_ID")" "Profil Baru"
check "telepon tersimpan di database" "$(q "SELECT phone FROM users WHERE id=$PROF_ID")" "081299999999"

# --- ganti password ---
HASH_AWAL="$(q "SELECT password_hash FROM users WHERE id=$PROF_ID")"
T="$(tok "$PROFJAR" /profil)"
post "$PROFJAR" /profil/password "$T" "current_password=salahsaja" \
  "new_password=baru12345" "new_password_confirmation=baru12345" > /dev/null
check "password lama salah tidak mengubah hash" "$(q "SELECT password_hash FROM users WHERE id=$PROF_ID")" "$HASH_AWAL"

T="$(tok "$PROFJAR" /profil)"
post "$PROFJAR" /profil/password "$T" "current_password=lama12345" \
  "new_password=baru12345" "new_password_confirmation=beda12345" > /dev/null
get "$PROFJAR" /profil > "$TMP/pw.html"
has "konfirmasi password tidak sama ditolak" "$TMP/pw.html" "Konfirmasi Password baru tidak sama"
check "hash tetap sama setelah konfirmasi salah" "$(q "SELECT password_hash FROM users WHERE id=$PROF_ID")" "$HASH_AWAL"

T="$(tok "$PROFJAR" /profil)"
post "$PROFJAR" /profil/password "$T" "current_password=lama12345" \
  "new_password=baru12345" "new_password_confirmation=baru12345" > /dev/null
HASH_BARU="$(q "SELECT password_hash FROM users WHERE id=$PROF_ID")"
if [ "$HASH_BARU" != "$HASH_AWAL" ]; then ok "password berhasil diganti (hash berubah)"; else bad "hash password tidak berubah"; fi
if printf '%s' "$HASH_BARU" | grep -q '^\$2'; then ok "hash baru tetap bcrypt"; else bad "hash baru bukan bcrypt"; fi

check "password lama sudah tidak berlaku" "$(login_loc "$PROFMAIL" "lama12345" u1)" "/login"
check "password baru berhasil login"      "$(login_loc "$PROFMAIL" "baru12345" u2)" "/pelanggan/dashboard"

# --- akun nonaktif tidak bisa login ---
q "UPDATE users SET is_active = 0 WHERE id=$PROF_ID" > /dev/null
check "akun nonaktif ditolak saat login" "$(login_loc "$PROFMAIL" "baru12345" u3)" "/login"

printf '\n\033[1m===============================\033[0m\n'
printf '  Lulus: %d   Gagal: %d\n' "$PASS" "$FAIL"
printf '\033[1m===============================\033[0m\n'
[ "$FAIL" -eq 0 ]