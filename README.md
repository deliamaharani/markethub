# MarketHub — Marketplace PHP Native

PHP Native + MySQLi (prepared statements) + Bootstrap 5 + Plus Jakarta Sans.

## Cara menjalankan (XAMPP)
1. Ekstrak ZIP, pindahkan folder `markethub` ke `C:\xampp\htdocs\` (hasil: `C:\xampp\htdocs\markethub\`).
2. Nyalakan **Apache** dan **MySQL** di XAMPP Control Panel.
3. Buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/markethub.sql` → **Go**.
   (File ini membuat database `markethub` sekaligus 6 tabel + data awal. Mengimpor ulang akan mereset seluruh data.)
4. Cek `config/database.php` bila perlu. Bawaan XAMPP: host `localhost`, user `root`, password kosong, database `markethub`.
5. Buka `http://localhost/markethub/` (landing) dan `http://localhost/markethub/beranda.php` (katalog).
   Butuh internet untuk memuat Bootstrap, Bootstrap Icons, dan Google Fonts dari CDN.

## Akun uji
| Role    | Email                  | Password      |
|---------|------------------------|---------------|
| Admin   | admin@markethub.test   | Admin123!     |
| Penjual | sari@markethub.test    | Password123!  |
| Penjual | tani@markethub.test    | Password123!  |
| Pembeli | dewi@markethub.test    | Password123!  |
| Pembeli | rizky@markethub.test   | Password123!  |

Admin tidak bisa mendaftar lewat registrasi publik. Registrasi hanya untuk pembeli dan penjual.

## API (JSON) — `http://localhost/markethub/api/`
Dokumentasi ringkas: `GET /api/index.php`.
Autentikasi: sesi login web (kirim header `X-CSRF-Token` untuk POST/PUT/DELETE) atau HTTP Basic Auth.
```
curl -u admin@markethub.test:Admin123! http://localhost/markethub/api/pesanan.php
curl http://localhost/markethub/api/produk.php?q=batik
curl -u dewi@markethub.test:Password123! -X POST -H "Content-Type: application/json" \
  -d '{"nama_penerima":"Dewi","telepon":"081234567890","alamat":"Jl. Contoh No. 1 Jambi"}' \
  http://localhost/markethub/api/pesanan.php
```
Endpoint: `kategori.php`, `produk.php`, `pesanan.php` (GET/POST/PUT/DELETE sesuai role dan aturan bisnis).

## Catatan
- Satu penambahan skema: kolom `users.status` (aktif/nonaktif) untuk fitur aktif/nonaktifkan akun oleh admin.
- Produk yang sudah punya histori transaksi tidak bisa dihapus (ubah stok menjadi 0).
- Upload gambar produk: JPG/PNG/WEBP, maks 2 MB, disimpan di `uploads/produk/`.
- Jika folder diletakkan bukan di `/markethub`, URL dasar dihitung otomatis.
- Keamanan: file `.htaccess` memblokir akses langsung ke `config/`, `includes/`, `database/` dan mencegah eksekusi skrip di `uploads/` (perlu Apache dengan `AllowOverride All`, bawaan XAMPP sudah aktif).
