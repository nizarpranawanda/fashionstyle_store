-- =============================================
-- DATABASE: fashionstyle_store
-- FashionStyle Store - UMKM Toko Baju Fashion Modern
-- PHP Native + MySQL/phpMyAdmin
-- Import lewat: phpMyAdmin -> Import -> pilih file ini
-- =============================================

CREATE DATABASE IF NOT EXISTS fashionstyle_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fashionstyle_store;

-- =============================================
-- TABEL ADMIN
-- =============================================
CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TABEL PELANGGAN
-- =============================================
CREATE TABLE pelanggan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    no_hp VARCHAR(20) NOT NULL,
    alamat TEXT NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TABEL KATEGORI
-- =============================================
CREATE TABLE kategori (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(50) NOT NULL UNIQUE,
    deskripsi TEXT,
    gambar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TABEL PRODUK
-- =============================================
CREATE TABLE produk (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kategori_id INT NOT NULL,
    nama VARCHAR(150) NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    harga_asli DECIMAL(12,2) NOT NULL,
    diskon INT DEFAULT 0,
    deskripsi TEXT,
    gambar VARCHAR(255),
    stok INT DEFAULT 0,
    ukuran_tersedia VARCHAR(50) DEFAULT 'M,L,XL,XXL',
    rating DECIMAL(2,1) DEFAULT 0.0,
    terjual INT DEFAULT 0,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE CASCADE,
    INDEX idx_produk_kategori (kategori_id),
    INDEX idx_produk_status (status)
) ENGINE=InnoDB;

-- =============================================
-- TABEL KERANJANG (1 keranjang aktif per pelanggan)
-- =============================================
CREATE TABLE keranjang (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pelanggan_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE,
    UNIQUE(pelanggan_id)
) ENGINE=InnoDB;

-- =============================================
-- TABEL DETAIL KERANJANG (ukuran M / L / XL / XXL disimpan di sini)
-- =============================================
CREATE TABLE detail_keranjang (
    id INT PRIMARY KEY AUTO_INCREMENT,
    keranjang_id INT NOT NULL,
    produk_id INT NOT NULL,
    ukuran VARCHAR(5) NOT NULL DEFAULT 'M',
    jumlah INT NOT NULL DEFAULT 1,
    harga DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (keranjang_id) REFERENCES keranjang(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_item (keranjang_id, produk_id, ukuran),
    INDEX idx_dk_produk (produk_id)
) ENGINE=InnoDB;

-- =============================================
-- TABEL KURIR (opsi pengiriman di halaman checkout)
-- =============================================
CREATE TABLE kurir (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode VARCHAR(30) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    estimasi VARCHAR(100) NOT NULL,
    biaya DECIMAL(10,2) NOT NULL,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TABEL VOUCHER (kode diskon di halaman checkout)
-- =============================================
CREATE TABLE voucher (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode VARCHAR(30) NOT NULL UNIQUE,
    tipe ENUM('persen','nominal','ongkir_gratis') NOT NULL,
    nilai DECIMAL(10,2) DEFAULT 0,
    maksimal_diskon DECIMAL(10,2) DEFAULT NULL,
    kuota INT DEFAULT NULL,
    berlaku_sampai DATE DEFAULT NULL,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- TABEL TRANSAKSI
-- =============================================
CREATE TABLE transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pelanggan_id INT NOT NULL,
    voucher_id INT DEFAULT NULL,
    kurir_id INT DEFAULT NULL,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    nama_penerima VARCHAR(100) NOT NULL,
    hp_penerima VARCHAR(20) NOT NULL,
    alamat_kirim TEXT NOT NULL,
    catatan TEXT,
    subtotal DECIMAL(12,2) NOT NULL,
    ongkir DECIMAL(10,2) DEFAULT 0,
    diskon DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,
    status_bayar ENUM('belum','lunas','gagal') DEFAULT 'belum',
    status_kirim ENUM('pending','dikirim','diterima','batal') DEFAULT 'pending',
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE,
    FOREIGN KEY (voucher_id) REFERENCES voucher(id) ON DELETE SET NULL,
    FOREIGN KEY (kurir_id) REFERENCES kurir(id) ON DELETE SET NULL,
    INDEX idx_transaksi_pelanggan (pelanggan_id),
    INDEX idx_transaksi_invoice (invoice_no)
) ENGINE=InnoDB;

-- =============================================
-- TABEL DETAIL TRANSAKSI (ukuran ikut tersimpan agar riwayat akurat)
-- =============================================
CREATE TABLE detail_transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaksi_id INT NOT NULL,
    produk_id INT NOT NULL,
    nama_produk VARCHAR(150) NOT NULL,
    ukuran VARCHAR(5) NOT NULL DEFAULT 'M',
    jumlah INT NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE,
    INDEX idx_dt_transaksi (transaksi_id)
) ENGINE=InnoDB;

-- =============================================
-- TABEL PEMBAYARAN
-- =============================================
CREATE TABLE pembayaran (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaksi_id INT NOT NULL,
    metode VARCHAR(50) NOT NULL,
    kode_bayar VARCHAR(100),
    jumlah DECIMAL(12,2) NOT NULL,
    batas_waktu TIMESTAMP DEFAULT NULL,
    status ENUM('menunggu','berhasil','gagal') DEFAULT 'menunggu',
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- DATA AWAL / SEED
-- =============================================

-- Admin default -> username: admin | password: admin123
INSERT INTO admin (nama, username, password, email) VALUES
('Super Admin', 'admin', '$2y$12$9jLC0wSFSmV2vvdtb/Wn8.Ci7M619suOGsF4/mTGbRahY3UYom/bC', 'admin@fashionstyle.com');

-- Kategori
INSERT INTO kategori (nama, deskripsi, gambar) VALUES
('Jas', 'Koleksi jas premium pria dan wanita', 'jas.jpg'),
('Kemeja', 'Kemeja elegan untuk berbagai acara', 'kemeja.jpg'),
('Celana Jeans', 'Celana jeans modern dan stylish', 'jeans.jpg'),
('Celana Bahan', 'Celana bahan formal dan casual', 'bahan.jpg'),
('Hoodie & Jaket', 'Hoodie dan jaket kekinian', 'hoodie.jpg'),
('Kaos', 'Kaos casual dan oversize', 'kaos.jpg');

-- Produk (nama file gambar mengikuti website asli, taruh file gambar di assets/images/products/)
INSERT INTO produk (kategori_id, nama, harga, harga_asli, diskon, deskripsi, gambar, stok, ukuran_tersedia, rating, terjual, status) VALUES
(1, 'Jas Premium Slim Fit', 450000, 450000, 0, 'Jas premium dengan bahan wool blend berkualitas tinggi, desain slim fit modern.', 'jas1.jpg', 25, 'M,L,XL,XXL', 4.8, 150, 'aktif'),
(1, 'Jas Slim Fit Elegant', 500000, 500000, 0, 'Jas slim fit dengan detail jahitan premium, cocok untuk acara formal.', 'jas2.jpg', 18, 'M,L,XL,XXL', 4.7, 120, 'aktif'),
(1, 'Jas Wedding Mewah', 650000, 650000, 0, 'Jas pernikahan mewah dengan aksen brokat dan detail premium.', 'jas3.jpg', 10, 'M,L,XL,XXL', 4.9, 80, 'aktif'),
(2, 'Kemeja Putih Classic', 170000, 170000, 0, 'Kemeja putih polos bahan cotton premium, nyaman dipakai sehari-hari.', 'kemeja1.jpg', 50, 'M,L,XL,XXL', 4.5, 200, 'aktif'),
(2, 'Kemeja Linen Casual', 200000, 250000, 20, 'Kemeja linen breathable untuk tampilan casual yang stylish.', 'kemeja2.jpg', 35, 'M,L,XL,XXL', 4.6, 180, 'aktif'),
(3, 'Jeans Slim Fit Dark', 280000, 280000, 0, 'Celana jeans slim fit warna dark blue, bahan denim premium stretch.', 'jeans1.jpg', 40, 'M,L,XL,XXL', 4.7, 250, 'aktif'),
(3, 'Jeans Modern Light', 320000, 400000, 20, 'Celana jeans modern light wash dengan detail distressed.', 'jeans2.jpg', 30, 'M,L,XL,XXL', 4.5, 190, 'aktif'),
(4, 'Celana Chino Khaki', 240000, 240000, 0, 'Celana chino khaki dengan bahan twill yang nyaman dan stylish.', 'chino1.jpg', 45, 'M,L,XL,XXL', 4.6, 220, 'aktif'),
(4, 'Celana Formal Hitam', 250000, 250000, 0, 'Celana formal hitam untuk ke kantor dan acara resmi.', 'formal1.jpg', 30, 'M,L,XL,XXL', 4.4, 160, 'aktif'),
(5, 'Hoodie Premium Grey', 300000, 300000, 0, 'Hoodie premium bahan fleece tebal, nyaman dan hangat.', 'hoodie1.jpg', 20, 'M,L,XL,XXL', 4.8, 170, 'aktif'),
(6, 'Kaos Oversize Black', 150000, 150000, 0, 'Kaos oversize cotton combed 30s, nyaman untuk daily wear.', 'kaos1.jpg', 60, 'M,L,XL,XXL', 4.5, 300, 'aktif'),
(5, 'Jaket Bomber Green', 350000, 350000, 0, 'Jaket bomber warna army green, bahan parasut waterproof.', 'bomber1.jpg', 15, 'M,L,XL,XXL', 4.7, 90, 'aktif');

-- Pelanggan contoh -> password semua: password
INSERT INTO pelanggan (nama, username, password, email, no_hp, alamat) VALUES
('Budi Santoso', 'budi', '$2b$12$JqyKAN6CGWsg/dIHuBjQcONwM.anD6K6BEIKGjRcPIOLmmvYp6uMS', 'budi@email.com', '081234567890', 'Jl. Merdeka No. 10, Jakarta Selatan'),
('Siti Rahayu', 'siti', '$2b$12$JqyKAN6CGWsg/dIHuBjQcONwM.anD6K6BEIKGjRcPIOLmmvYp6uMS', 'siti@email.com', '082345678901', 'Jl. Sudirman No. 25, Bandung'),
('Ahmad Fauzi', 'ahmad', '$2b$12$JqyKAN6CGWsg/dIHuBjQcONwM.anD6K6BEIKGjRcPIOLmmvYp6uMS', 'ahmad@email.com', '083456789012', 'Jl. Gatot Subroto No. 5, Surabaya');

-- Kurir (samakan dengan opsi pengiriman di halaman checkout)
INSERT INTO kurir (kode, nama, estimasi, biaya) VALUES
('jne_reg', 'JNE Reguler', '2-3 hari', 15000),
('jne_hemat', 'JNE Hemat Kargo', '5-7 hari', 8000),
('jnt', 'J&T Express', '2-3 hari', 18000),
('sicepat', 'SiCepat Reguler', '3-4 hari', 12000),
('gosend', 'GoSend Instant (khusus dalam kota)', 'Same day', 25000);

-- Voucher (samakan dengan kode voucher di halaman checkout: HEMAT10, ONGKIRGRATIS)
INSERT INTO voucher (kode, tipe, nilai, maksimal_diskon, kuota, status) VALUES
('HEMAT10', 'persen', 10, 50000, 1000, 'aktif'),
('ONGKIRGRATIS', 'ongkir_gratis', 0, NULL, 1000, 'aktif');
