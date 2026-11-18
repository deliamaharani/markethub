-- ============================================================================
-- MarketHub — database/markethub.sql
-- Impor lewat phpMyAdmin (tab Import). Script ini membuat database "markethub".
-- PERHATIAN: tabel lama bernama sama akan dihapus dan dibuat ulang.
-- ============================================================================
CREATE DATABASE IF NOT EXISTS markethub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE markethub;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS detail_pesanan;
DROP TABLE IF EXISTS keranjang;
DROP TABLE IF EXISTS pesanan;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. users
CREATE TABLE users (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('admin','penjual','pembeli') NOT NULL DEFAULT 'pembeli',
  status     ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif', -- untuk fitur "pengaturan status akun" admin
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. kategori
CREATE TABLE kategori (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama_kategori VARCHAR(100) NOT NULL,
  slug          VARCHAR(120) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kategori_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. produk  (users.id -> produk.penjual_id, kategori.id -> produk.kategori_id)
CREATE TABLE produk (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kategori_id INT UNSIGNED NOT NULL,
  penjual_id  INT UNSIGNED NOT NULL,
  nama_produk VARCHAR(150) NOT NULL,
  deskripsi   TEXT NOT NULL,
  harga       DECIMAL(12,0) NOT NULL,
  stok        INT NOT NULL DEFAULT 0,
  gambar      VARCHAR(255) DEFAULT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_produk_kategori (kategori_id),
  KEY idx_produk_penjual (penjual_id),
  CONSTRAINT fk_produk_kategori FOREIGN KEY (kategori_id) REFERENCES kategori (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_produk_penjual  FOREIGN KEY (penjual_id)  REFERENCES users (id)    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_produk_stok  CHECK (stok >= 0),
  CONSTRAINT chk_produk_harga CHECK (harga >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. keranjang  (users.id -> keranjang.user_id, produk.id -> keranjang.produk_id; TIDAK berelasi langsung ke pesanan)
CREATE TABLE keranjang (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  produk_id  INT UNSIGNED NOT NULL,
  jumlah     INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_keranjang_user_produk (user_id, produk_id),
  KEY idx_keranjang_produk (produk_id),
  CONSTRAINT fk_keranjang_user   FOREIGN KEY (user_id)   REFERENCES users (id)  ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_keranjang_produk FOREIGN KEY (produk_id) REFERENCES produk (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_keranjang_jumlah CHECK (jumlah >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. pesanan  (users.id -> pesanan.user_id)
CREATE TABLE pesanan (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id           INT UNSIGNED NOT NULL,
  total_harga       DECIMAL(12,0) NOT NULL,
  status_pesanan    ENUM('pending','diproses','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'pending',
  alamat_pengiriman TEXT NOT NULL,
  tanggal_pesanan   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pesanan_user (user_id),
  KEY idx_pesanan_status (status_pesanan),
  CONSTRAINT fk_pesanan_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. detail_pesanan  (pesanan.id -> pesanan_id, produk.id -> produk_id)
CREATE TABLE detail_pesanan (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pesanan_id   INT UNSIGNED NOT NULL,
  produk_id    INT UNSIGNED NOT NULL,
  jumlah       INT NOT NULL,
  harga_satuan DECIMAL(12,0) NOT NULL,  -- histori harga saat transaksi
  subtotal     DECIMAL(12,0) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_detail_pesanan (pesanan_id),
  KEY idx_detail_produk (produk_id),
  CONSTRAINT fk_detail_pesanan FOREIGN KEY (pesanan_id) REFERENCES pesanan (id) ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT fk_detail_produk  FOREIGN KEY (produk_id)  REFERENCES produk (id)  ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_detail_jumlah CHECK (jumlah >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- DATA AWAL
-- Admin : admin@markethub.test  / Admin123!
-- Penjual & pembeli contoh : (email di bawah) / Password123!
-- ============================================================================
INSERT INTO users (id, nama, email, password, role, status, created_at) VALUES
(1, 'Admin MarketHub',  'admin@markethub.test',  '$2y$10$zETKNvt8QaTfBFUxBdJlN.mwyJje8o5u9j9inRLmzb7egqwKst3y6', 'admin',   'aktif', '2026-10-01 08:00:00'),
(2, 'Sari Batik',       'sari@markethub.test',   '$2y$10$qW32QzdsiuYf/sZrmzKutODn2Ax5c0FTseYwLUXeL/awe8hS5Axs.', 'penjual', 'aktif', '2026-10-02 09:00:00'),
(3, 'Tani Gayo',        'tani@markethub.test',   '$2y$10$qW32QzdsiuYf/sZrmzKutODn2Ax5c0FTseYwLUXeL/awe8hS5Axs.', 'penjual', 'aktif', '2026-10-02 09:10:00'),
(4, 'Rajut Lestari',    'rajut@markethub.test',  '$2y$10$qW32QzdsiuYf/sZrmzKutODn2Ax5c0FTseYwLUXeL/awe8hS5Axs.', 'penjual', 'aktif', '2026-10-02 09:20:00'),
(5, 'Dapur Bu Ratna',   'ratna@markethub.test',  '$2y$10$qW32QzdsiuYf/sZrmzKutODn2Ax5c0FTseYwLUXeL/awe8hS5Axs.', 'penjual', 'aktif', '2026-10-03 10:00:00'),
(6, 'Gerabah Jaya',     'gerabah@markethub.test','$2y$10$qW32QzdsiuYf/sZrmzKutODn2Ax5c0FTseYwLUXeL/awe8hS5Axs.', 'penjual', 'aktif', '2026-10-03 10:30:00'),
(7, 'Dewi Lestari',     'dewi@markethub.test',   '$2y$10$qW32QzdsiuYf/sZrmzKutODn2Ax5c0FTseYwLUXeL/awe8hS5Axs.', 'pembeli', 'aktif', '2026-10-05 11:00:00'),
(8, 'Rizky Pratama',    'rizky@markethub.test',  '$2y$10$qW32QzdsiuYf/sZrmzKutODn2Ax5c0FTseYwLUXeL/awe8hS5Axs.', 'pembeli', 'aktif', '2026-10-06 12:00:00'),
(9, 'Maya Anggraini',   'maya@markethub.test',   '$2y$10$qW32QzdsiuYf/sZrmzKutODn2Ax5c0FTseYwLUXeL/awe8hS5Axs.', 'pembeli', 'aktif', '2026-10-07 13:00:00');

INSERT INTO kategori (id, nama_kategori, slug) VALUES
(1, 'Fashion', 'fashion'),
(2, 'Makanan dan minuman', 'makanan-minuman'),
(3, 'Kerajinan', 'kerajinan'),
(4, 'Dekorasi rumah', 'dekorasi-rumah'),
(5, 'Pertanian', 'pertanian'),
(6, 'Aksesoris', 'aksesoris');

INSERT INTO produk (id, kategori_id, penjual_id, nama_produk, deskripsi, harga, stok, created_at) VALUES
(1, 1, 2, 'Batik Tulis Pekalongan', 'Kain batik tulis asli Pekalongan dengan motif buketan klasik. Dikerjakan manual oleh pengrajin lokal menggunakan pewarna yang tahan lama. Bahan katun primisima, adem dipakai, cocok untuk acara formal maupun harian. Ukuran kain 2,5 x 1,15 meter.', 285000, 11, '2026-11-02 08:00:00'),
(2, 1, 2, 'Selendang Songket Palembang', 'Selendang songket tenun tangan khas Palembang dengan benang emas. Cocok sebagai pelengkap kebaya atau busana adat. Panjang 180 cm, lebar 60 cm.', 450000, 4, '2026-11-02 08:10:00'),
(3, 1, 2, 'Kemeja Batik Cap Solo', 'Kemeja batik cap motif parang dari Solo. Bahan katun halus, jahitan rapi, nyaman untuk kerja maupun acara santai. Tersedia ukuran M hingga XL.', 215000, 17, '2026-11-03 08:00:00'),
(4, 2, 3, 'Kopi Gayo Arabika 250g', 'Biji kopi arabika Gayo single origin, disangrai medium dengan aroma cokelat dan buah. Dikemas dalam kantong kedap udara berkatup, berat bersih 250 gram.', 68000, 22, '2026-11-03 09:00:00'),
(5, 5, 3, 'Madu Hutan Jambi 500ml', 'Madu hutan murni dari lebah liar di kawasan hutan Jambi. Tanpa campuran gula atau pengawet, dikemas dalam botol kaca 500 ml.', 98000, 37, '2026-11-04 08:00:00'),
(6, 3, 4, 'Tas Anyaman Pandan', 'Tas anyaman daun pandan dengan pegangan kulit sintetis dan lapisan kain di dalam. Ringan, kuat, dan cocok untuk dibawa berbelanja atau berlibur.', 125000, 7, '2026-11-04 09:00:00'),
(7, 6, 4, 'Dompet Rajut Etnik', 'Dompet rajut tangan bermotif etnik dengan resleting kuat dan dua sekat kartu. Ukuran 19 x 10 cm, tersedia beberapa pilihan warna.', 55000, 30, '2026-11-05 08:00:00'),
(8, 2, 5, 'Keripik Tempe Renyah 200g', 'Keripik tempe tipis renyah dengan bumbu bawang dan ketumbar. Digoreng dengan minyak baru, dikemas rapat sehingga tetap renyah. Berat bersih 200 gram.', 24000, 57, '2026-11-05 09:00:00'),
(9, 2, 5, 'Bumbu Rendang 100g', 'Bumbu rendang racikan resep Minang yang siap pakai. Cukup tambahkan daging dan santan. Tanpa pengawet buatan, berat bersih 100 gram.', 32000, 73, '2026-11-06 08:00:00'),
(10, 4, 6, 'Vas Gerabah Kasongan', 'Vas bunga dari tanah liat gerabah Kasongan, dibakar manual dan dihias ukiran sederhana. Tinggi 30 cm, cocok untuk dekorasi ruang tamu.', 175000, 5, '2026-11-06 09:00:00'),
(11, 4, 6, 'Piring Hias Keramik', 'Piring hias keramik berglasur untuk dinding atau meja pajang. Diameter 25 cm, dilengkapi pengait di bagian belakang.', 85000, 15, '2026-11-07 08:00:00'),
(12, 6, 4, 'Gelang Kayu Sonokeling', 'Gelang kayu sonokeling dengan serat alami yang indah, diamplas halus dan dipoles minyak alami. Ukuran dapat disesuaikan.', 45000, 22, '2026-11-07 09:00:00');

-- Pesanan contoh (status awal pending sesuai flowchart; sisanya riwayat)
INSERT INTO pesanan (id, user_id, total_harga, status_pesanan, alamat_pengiriman, tanggal_pesanan) VALUES
(1, 7, 493000, 'pending',    'Dewi Lestari|081234567890\nJl. Sultan Thaha No. 24, RT 05, Kelurahan Telanaipura, Kota Jambi, Jambi 36122', '2026-11-16 09:32:00'),
(2, 7, 285000, 'diproses',   'Dewi Lestari|081234567890\nJl. Sultan Thaha No. 24, RT 05, Kelurahan Telanaipura, Kota Jambi, Jambi 36122', '2026-11-10 14:05:00'),
(3, 7, 175000, 'dikirim',    'Dewi Lestari|081234567890\nJl. Sultan Thaha No. 24, RT 05, Kelurahan Telanaipura, Kota Jambi, Jambi 36122', '2026-11-04 10:20:00'),
(4, 7, 162000, 'selesai',    'Dewi Lestari|081234567890\nJl. Sultan Thaha No. 24, RT 05, Kelurahan Telanaipura, Kota Jambi, Jambi 36122', '2026-10-28 16:40:00'),
(5, 7, 450000, 'selesai',    'Dewi Lestari|081234567890\nJl. Sultan Thaha No. 24, RT 05, Kelurahan Telanaipura, Kota Jambi, Jambi 36122', '2026-10-15 11:15:00'),
(6, 7,  98000, 'dibatalkan', 'Dewi Lestari|081234567890\nJl. Sultan Thaha No. 24, RT 05, Kelurahan Telanaipura, Kota Jambi, Jambi 36122', '2026-10-02 09:00:00'),
(7, 8, 215000, 'pending',    'Rizky Pratama|082198765432\nJl. Diponegoro No. 8, Kota Palembang, Sumatera Selatan 30126', '2026-11-16 08:10:00'),
(8, 9, 355000, 'diproses',   'Maya Anggraini|085711223344\nJl. Merdeka Raya No. 17, Kota Bandung, Jawa Barat 40115', '2026-11-15 13:25:00'),
(9, 8,  98000, 'dikirim',    'Rizky Pratama|082198765432\nJl. Diponegoro No. 8, Kota Palembang, Sumatera Selatan 30126', '2026-11-14 15:50:00'),
(10, 9, 450000, 'selesai',   'Maya Anggraini|085711223344\nJl. Merdeka Raya No. 17, Kota Bandung, Jawa Barat 40115', '2026-11-12 10:00:00');

INSERT INTO detail_pesanan (pesanan_id, produk_id, jumlah, harga_satuan, subtotal) VALUES
(1, 1, 1, 285000, 285000), (1, 4, 2, 68000, 136000), (1, 8, 3, 24000, 72000),
(2, 1, 1, 285000, 285000),
(3, 10, 1, 175000, 175000),
(4, 5, 1, 98000, 98000), (4, 9, 2, 32000, 64000),
(5, 2, 1, 450000, 450000),
(6, 5, 1, 98000, 98000),
(7, 3, 1, 215000, 215000),
(8, 6, 1, 125000, 125000), (8, 10, 1, 175000, 175000), (8, 7, 1, 55000, 55000),
(9, 5, 1, 98000, 98000),
(10, 2, 1, 450000, 450000);

-- Contoh isi keranjang untuk pembeli Dewi
INSERT INTO keranjang (user_id, produk_id, jumlah) VALUES (7, 3, 1), (7, 12, 2);
