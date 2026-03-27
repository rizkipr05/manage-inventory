-- Buat Database
CREATE DATABASE IF NOT EXISTS manage_inventory_db;
USE manage_medical_db;

-- Tabel untuk Admin/Pemilik
CREATE TABLE IF NOT EXISTS admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk Supplier
CREATE TABLE IF NOT EXISTS supplier (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_supplier VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    telepon VARCHAR(15),
    email VARCHAR(100),
    alamat TEXT,
    kota VARCHAR(50),
    provinsi VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel untuk Barang/Material Medis
CREATE TABLE IF NOT EXISTS barang (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_barang VARCHAR(100) NOT NULL,
    kode_barang VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    kategori VARCHAR(50),
    stok_awal INT DEFAULT 0,
    stok_masuk INT DEFAULT 0,
    stok_keluar INT DEFAULT 0,
    stok_akhir INT DEFAULT 0,
    stok_minimum INT DEFAULT 10,
    harga_unit DECIMAL(10, 2),
    total_harga DECIMAL(12, 2),
    tanggal_masuk DATE,
    tanggal_kadaluarsa DATE,
    satuan VARCHAR(20),
    status VARCHAR(20) DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES supplier(id) ON DELETE CASCADE
);

-- Tabel untuk Riwayat Transaksi (Masuk/Keluar Barang)
CREATE TABLE IF NOT EXISTS transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    barang_id INT NOT NULL,
    tipe_transaksi ENUM('masuk', 'keluar') NOT NULL,
    jumlah INT NOT NULL,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    keterangan TEXT,
    FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE
);

-- Insert Admin Default
INSERT INTO admin (username, password, email) VALUES 
('admin', SHA2('admin123', 256), 'admin@managemenbarang.com');
