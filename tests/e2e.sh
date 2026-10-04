#!/usr/bin/env bash
# =====================================================================
#  E-Jastip - End-to-end test alur utama
#
#  Menjalankan seluruh alur nyata melalui HTTP:
#  register -> login -> lihat produk -> keranjang -> checkout -> bayar
#  -> jastiper verifikasi & proses -> kirim + resi -> pelanggan terima
#  -> selesai -> cek dashboard admin
#
#  Jalankan: bash tests/e2e.sh
# =====================================================================
set -uo pipefail

BASE="http://localhost:3000"
TMP="$(mktemp -d)"
PASS=0
FAIL=0

cleanup() { rm -rf "$TMP"; }
trap cleanup EXIT

ok()   { PASS=$((PASS+1)); printf '  \033[32mPASS\033[0m %s\n' "$1"; }
bad()  { FAIL=$((FAIL+1)); printf '  \033[31mFAIL\033[0m %s\n' "$1"; }
head2() { printf '\n\033[1m%s\033[0m\n' "$1"; }

# check <label> <actual> <expected>
check() {
  if [ "$2" = "$3" ]; then ok "$1 ($2)"; else bad "$1 — Harapkan '$3', dapat '$2'"; fi
}

# check_contains <label> <file> <needle>
check_contains() {
  if grep -qF -- "$3" "$2"; then ok "$1"; else bad "$1 — teks '$3' tidak ditemukan"; fi
}

check_not_contains() {
  if grep -qF -- "$3" "$2"; then bad "$1 — teks '$3' tidak seharusnya ada"; else ok "$1"; fi
}

# ---------------------------------------------------------------
#  Session per role
# ---------------------------------------------------------------
jar() { echo "$TMP/cookies_$1.txt"; }

# get <jar> <path> -> isi body
get() {
  curl -s -b "$(jar "$1")" -c "$(jar "$1")" "$BASE$2"
}

# status <jar> <path>
status() {
  curl -s -o /dev/null -w '%{http_code}' -b "$(jar "$1")" -c "$(jar "$1")" "$BASE$2"
}

# token <jar> <path> -> nilai field _token
token() {
  get "$1" "$2" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//; s/"$//'
}

# post <jar> <path> <token> <data...> -> HTTP code + Location header
post() {
  local j="$1" path="$2" tok="$3"; shift 3
  local args=(--data-urlencode "_token=$tok")
  local kv
  for kv in "$@"; do args+=(--data-urlencode "$kv"); done
  curl -s -i -b "$(jar "$j")" -c "$(jar "$j")" -X POST "${args[@]}" "$BASE$path"
}

# location dari header respons POST
loc_of() { printf '%s' "$1" | grep -i '^location:' | tail -1 | tr -d '\r' | awk '{print $2}'; }
code_of() { printf '%s' "$1" | head -1 | awk '{print $2}'; }

# login <jar> <email> <password>
login() {
  local j="$1" email="$2" pass="$3" tok loc
  tok="$(token "$j" /login)"
  loc="$(loc_of "$(post "$j" /login "$tok" "email=$email" "password=$pass")")"
  printf '%s' "$loc"
}

head2 "1. Halaman publik"
check "GET /" "$(status guest /)" "200"
get guest / > "$TMP/home.html"
check_contains "landing menampilkan judul E-Jastip" "$TMP/home.html" "E-Jastip"
get guest /jastip > "$TMP/offers.html"
check_contains "daftar jastip menampilkan penawaran Jakarta" "$TMP/offers.html" "Jastip Jakarta"

head2 "2. Registrasi pelanggan baru"
RAND="test$(date +%s)"
NEWMAIL="$RAND@ejastip.test"
TOK="$(token guest /register)"
RESP="$(post guest /register "$TOK" \
  "name=Pelanggan Uji Coba" "email=$NEWMAIL" "phone=081234567890" \
  "address=Jl. Uji Coba No. 1, Jakarta" "role=PELANGGAN" \
  "password=rahasia123" "password_confirmation=rahasia123")"
check "registrasi redirect ke dashboard" "$(code_of "$RESP")" "302"
check "dashboard pelanggan terbuka" "$(status guest /pelanggan/dashboard)" "200"

head2 "3. Pelanggan tidak boleh akses area jastiper/admin"
check "/jastiper/dashboard ditolak (403)" "$(status guest /jastiper/dashboard)" "403"
check "/admin/dashboard ditolak (403)" "$(status guest /admin/dashboard)" "403"

head2 "4. Pelanggan melihat produk dan menambah ke keranjang"
get guest /jastip > "$TMP/offers.html"
# Cari ID penawaran berdasarkan judulnya (bukan urutan, karena urutan=deadline)
OFFER_JAKARTA="$(grep -o 'href="/jastip/[0-9]*"><div class="product-name">Jastip Jakarta' "$TMP/offers.html" | grep -o '[0-9]*' | head -1)"
OFFER_MANILA="$(grep -o 'href="/jastip/[0-9]*"><div class="product-name">Jastip Manila' "$TMP/offers.html" | grep -o '[0-9]*' | head -1)"
if [ -z "$OFFER_JAKARTA" ] || [ -z "$OFFER_MANILA" ]; then bad "penawaran demo tidak ditemukan"; fi
echo "  info  penawaran Jakarta=#$OFFER_JAKARTA Manila=#$OFFER_MANILA"

# produk dari penawaran Manila
get guest "/jastip/$OFFER_MANILA" > "$TMP/manila.html"
PROD_M="$(grep -o '/produk/[0-9]*' "$TMP/manila.html" | grep -o '[0-9]*' | head -1)"
get guest "/produk/$PROD_M" > "$TMP/pd.html"
check_contains "detail produk menampilkan biaya jastip" "$TMP/pd.html" "Biaya jasa titip"
TOK="$(token guest "/produk/$PROD_M")"
post guest /keranjang/tambah "$TOK" "product_id=$PROD_M" "quantity=1" > /dev/null

get guest /checkout > "$TMP/checkout1.html"
check_contains "keranjang berisi 1 produk" "$TMP/checkout1.html" "Barang Pesanan"

head2 "5. Keranjang hanya boleh satu penawaran"
get guest "/jastip/$OFFER_JAKARTA" > "$TMP/jakarta.html"
PROD_J="$(grep -o '/produk/[0-9]*' "$TMP/jakarta.html" | grep -o '[0-9]*' | head -1)"
TOK="$(token guest "/produk/$PROD_J")"
post guest /keranjang/tambah "$TOK" "product_id=$PROD_J" "quantity=1" > "$TMP/r2.txt"
get guest /checkout > "$TMP/checkout2.html"
ITEMS2="$(grep -c 'name="quantities\[' "$TMP/checkout2.html")"
check "keranjang diganti ke penawaran lain (1 item)" "$ITEMS2" "1"

head2 "6. Validasi: checkout tanpa barang / data tidak valid"
TOK="$(token guest /checkout)"
RESP="$(post guest /checkout "$TOK" "receiver_name=A" "phone=1" "shipping_address=x")"
check "checkout dengan data tidak lengkap ditolak" "$(code_of "$RESP")" "302"
get guest /checkout > "$TMP/bad.html"
check_contains "pesan validasi ditampilkan" "$TMP/bad.html" "minimal 3"

head2 "7. Checkout dengan 2 produk dari penawaran yang sama"
# kosongkan keranjang (tanpa logout), lalu tambah 2 produk dari penawaran Jakarta
TOK="$(token guest /checkout)"
post guest /keranjang/kosongkan "$TOK" > /dev/null
get guest "/jastip/$OFFER_JAKARTA" > "$TMP/od.html"
IDS=($(grep -o '/produk/[0-9]*' "$TMP/od.html" | grep -o '[0-9]*' | head -2))
check "penawaran Jakarta punya >= 2 produk" "${#IDS[@]}" "2"

get guest "/produk/${IDS[0]}" > /dev/null
TOK="$(token guest "/produk/${IDS[0]}")"
post guest /keranjang/tambah "$TOK" "product_id=${IDS[0]}" "quantity=1" > /dev/null
get guest "/produk/${IDS[1]}" > /dev/null
TOK="$(token guest "/produk/${IDS[1]}")"
post guest /keranjang/tambah "$TOK" "product_id=${IDS[1]}" "quantity=2" > /dev/null

get guest /checkout > "$TMP/checkout.html"
check_contains "checkout menampilkan subtotal" "$TMP/checkout.html" "Subtotal barang"
check_contains "checkout menampilkan biaya jasa titip" "$TMP/checkout.html" "Biaya jasa titip"
check_contains "checkout menampilkan ongkos kirim" "$TMP/checkout.html" "Ongkos kirim"
check_contains "checkout menampilkan total pembayaran" "$TMP/checkout.html" "Total Pembayaran"

head2 "8. Pelanggan membuat pesanan (total dihitung server)"
TOK="$(token guest /checkout)"
RESP="$(post guest /checkout "$TOK" \
  "receiver_name=Pelanggan Uji Coba" "phone=081234567890" \
  "shipping_address=Jl. Uji Coba No. 1, Jakarta 10110" "notes=Testing otomatis")"
ORDER_URL="$(loc_of "$RESP")"
ORDER_ID="${ORDER_URL##*/}"
if [ -z "$ORDER_ID" ] || [ "$ORDER_ID" = "$ORDER_URL" ]; then bad "gagal membuat pesanan"; else ok "pesanan dibuat (#$ORDER_ID)"; fi

get guest "$ORDER_URL" > "$TMP/order.html"
check_contains "status awal 'Menunggu Pembayaran'" "$TMP/order.html" "Menunggu Pembayaran"
check_contains "halaman detail menampilkan rincian biaya" "$TMP/order.html" "Rincian Biaya"

TOTAL_TEXT="$(grep -A3 'Total Pembayaran' "$TMP/order.html" | grep -o 'Rp[0-9.,]*' | tail -1)"
echo "  info  total pesanan: $TOTAL_TEXT"

head2 "9. Pelanggan melakukan konfirmasi pembayaran"
TOK="$(token guest "$ORDER_URL")"
RESP="$(post guest "$ORDER_URL/bayar" "$TOK" "method=E_WALLET" "reference=OVO-TEST-123")"
get guest "$ORDER_URL" > "$TMP/order2.html"
check_contains "status menjadi 'Pembayaran Dikonfirmasi'" "$TMP/order2.html" "Pembayaran Dikonfirmasi"
check_contains "status pembayaran menunggu verifikasi" "$TMP/order2.html" "Menunggu Verifikasi"

head2 "10. Pelanggan lain tidak bisa membuka pesanan ini"
login lain "pelanggan2@ejastip.test" "pelanggan123" > /dev/null
check "pesanan milik orang lain ditolak (403)" "$(status lain "$ORDER_URL")" "403"

head2 "11. Jastiper memverifikasi pembayaran"
LOC="$(login jastiper "jastiper@ejastip.test" "jastiper123")"
check "login jastiper ke dashboard" "$(status jastiper /jastiper/dashboard)" "200"
check "dashboard jastiper terbuka" "$(status jastiper /jastiper/dashboard)" "200"

# jastiper harus memiliki pesanan ini: cari lewat daftar pesanan
JORDER="/jastiper/pesanan/$ORDER_ID"
get jastiper /jastiper/pesanan > "$TMP/jorders.html"
check_contains "pesanan masuk ke jastiper" "$TMP/jorders.html" "$JORDER"

TOK="$(token jastiper "$JORDER")"
post jastiper "$JORDER/verifikasi" "$TOK" "action=approve" > /dev/null
get jastiper "$JORDER" > "$TMP/jorder.html"
check_contains "pembayaran terverifikasi lunas" "$TMP/jorder.html" "Lunas"

head2 "12. Jastiper mengubah status: Sedang Dibeli -> Barang Diperoleh"
TOK="$(token jastiper "$JORDER")"
post jastiper "$JORDER/status" "$TOK" "status=SEDANG_DIBELI" "note=Mulai belanja" > /dev/null
get jastiper "$JORDER" > "$TMP/jorder.html"
check_contains "status 'Sedang Dibeli'" "$TMP/jorder.html" "Sedang Dibeli"

TOK="$(token jastiper "$JORDER")"
post jastiper "$JORDER/status" "$TOK" "status=BARANG_DIPEROLEH" "note=Barang sudah dibeli" > /dev/null
get jastiper "$JORDER" > "$TMP/jorder.html"
check_contains "status 'Barang Diperoleh'" "$TMP/jorder.html" "Barang Diperoleh"

head2 "13. Jastiper memasukkan kurir dan nomor resi"
TOK="$(token jastiper "$JORDER")"
post jastiper "$JORDER/kirim" "$TOK" \
  "courier_name=JNE Reguler" "tracking_number=TEST123456789" "shipped_at=$(date +%Y-%m-%dT%H:%M)" "note=Kotak_Test" > /dev/null
get jastiper "$JORDER" > "$TMP/jorder.html"
check_contains "nomor resi tersimpan" "$TMP/jorder.html" "TEST123456789"
get jastiper "$JORDER" > "$TMP/jorder.html"
STATUS_TAGS="$(grep -o 'badge badge-[a-z]*">Sedang Dikirim' "$TMP/jorder.html" | head -1)"
if [ -n "$STATUS_TAGS" ]; then ok "status badge 'Sedang Dikirim' tampil"; else bad "status badge 'Sedang Dikirim' tidak tampil"; fi

head2 "14. Pelanggan melihat pengiriman lalu menandai diterima"
get guest "$ORDER_URL" > "$TMP/order3.html"
check_contains "pelanggan melihat nomor resi" "$TMP/order3.html" "TEST123456789"
check_contains "pelanggan melihat nama kurir" "$TMP/order3.html" "JNE Reguler"

TOK="$(token guest "$ORDER_URL")"
post guest "$ORDER_URL/terima" "$TOK" > /dev/null
get guest "$ORDER_URL" > "$TMP/order4.html"
check_contains "status akhir 'Selesai'" "$TMP/order4.html" "Selesai"

head2 "15. Stok berkurang sesuai jumlah pesanan"
MYSQLBIN="/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql"
stok_sepatu() { "$MYSQLBIN" -u root -h 127.0.0.1 -N -B -e "SELECT stock FROM e_jastip.products WHERE id = ${IDS[0]}"; }
qty_sepatu()  { "$MYSQLBIN" -u root -h 127.0.0.1 -N -B -e "SELECT quantity FROM e_jastip.order_details WHERE order_id = $ORDER_ID AND product_id = ${IDS[0]}"; }
QTY_DIAMBIL="$(qty_sepatu)"
STOK_SESUDAH="$(stok_sepatu)"
if [ -n "$QTY_DIAMBIL" ] && [ "$STOK_SESUDAH" -lt 20 ]; then
  ok "stok produk berkurang (sisa $STOK_SESUDAH, dipesan $QTY_DIAMBIL)"
else
  bad "stok tidak berkurang (sisa=$STOK_SESUDAH, dipesan=$QTY_DIAMBIL)"
fi

head2 "16. Dashboard admin & laporan transaksi"
LOC="$(login admin "admin@ejastip.test" "admin123")"
check "login admin" "$(status admin /admin/dashboard)" "200"
get admin /admin/dashboard > "$TMP/admin.html"
check_contains "admin melihat statistik pengguna" "$TMP/admin.html" "Total Pelanggan"
check_contains "admin melihat total transaksi" "$TMP/admin.html" "Total Transaksi"

for p in /admin/pengguna /admin/jastiper /admin/penawaran /admin/pesanan /admin/transaksi; do
  check "halaman $p terbuka" "$(status admin "$p")" "200"
done

get admin /admin/transaksi > "$TMP/trans.html"
check_contains "laporan menampilkan kode pesanan uji" "$TMP/trans.html" "EJS-"
check_contains "grafik omzet bulanan ditampilkan" "$TMP/trans.html" "Omzet per Bulan"

head2 "17. Admin dapat memverifikasi jastiper baru"
get admin /admin/jastiper > "$TMP/jastipers.html"
if grep -q 'Belum Verifikasi' "$TMP/jastipers.html"; then
  ok "daftar jastiper menampilkan status verifikasi"
  TOK="$(token admin /admin/jastiper)"
  POST_ACTION="$(grep -o '/admin/jastiper/[0-9]*/verifikasi' "$TMP/jastipers.html" | head -1)"
  if [ -n "$POST_ACTION" ]; then
    post admin "$POST_ACTION" "$TOK" > /dev/null
    get admin /admin/jastiper > "$TMP/jastipers2.html"
    if [ "$(grep -c 'Belum Verifikasi' "$TMP/jastipers2.html")" -lt "$(grep -c 'Belum Verifikasi' "$TMP/jastipers.html")" ]; then
      ok "verifikasi jastiper berhasil"
    else
      bad "verifikasi jastiper tidak mengubah status"
    fi
  else
    bad "tombol verifikasi tidak ditemukan"
  fi
else
  bad "jastiper belum verifikasi tidak ditemukan"
fi

head2 "18. Keamanan"
# Akses langsung ke halaman admin tanpa login
rm -f "$(jar stranger)"
check "anonim tidak bisa buka /admin/dashboard" "$(status stranger /admin/dashboard)" "302"
check "anonim tidak bisa buka /pesanan" "$(status stranger /pesanan)" "302"

# CSRF ditolak
CODE="$(curl -s -o /dev/null -w '%{http_code}' -b "$(jar guest)" -X POST -d "_token=token-palsu&product_id=1&quantity=1" "$BASE/keranjang/tambah")"
check "CSRF token palsu ditolak (419)" "$CODE" "419"

# Password tidak tersimpan plaintext
HASH="$(curl -s -o /dev/null "$BASE/" && /c/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql -u root -h 127.0.0.1 -N -B -e "SELECT password_hash FROM e_jastip.users WHERE email='$NEWMAIL'")"
if printf '%s' "$HASH" | grep -q '^\$2'; then ok "password disimpan sebagai bcrypt hash"; else bad "password bukan hash bcrypt: $HASH"; fi

# Total tidak bisa dimanipulasi dari sisi klien
check_not_contains "halaman pesanan tidak menampilkan input edit total" "$TMP/order4.html" 'name="total"'

# ---------------------------------------------------------------
printf '\n\033[1m===============================\033[0m\n'
printf '  Lulus: %d   Gagal: %d\n' "$PASS" "$FAIL"
printf '\033[1m===============================\033[0m\n'
[ "$FAIL" -eq 0 ]