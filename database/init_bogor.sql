-- ============================================
-- DATABASE CABANG TASIKMALAYA
-- Local Autonomy
-- ============================================

-- Tabel Barang (Replikasi dari Pusat)
CREATE TABLE IF NOT EXISTS barang (
    barang_id VARCHAR(20) PRIMARY KEY,
    nama_barang VARCHAR(100),
    kategori VARCHAR(50),
    harga_jual DECIMAL(15,2)
);

INSERT INTO barang VALUES 
('BRG001', 'Kabel NYM 3x2.5mm', 'Kabel', 25000.00),
('BRG002', 'MCB 6A 1 Phase', 'MCB', 35000.00),
('BRG003', 'Lampu LED 9W', 'Lampu', 18000.00),
('BRG004', 'Saklar Tunggal', 'Saklar', 12000.00),
('BRG005', 'Stop Kontak 16A', 'Stop Kontak', 15000.00);

-- Tabel Stok Lokal (Fragmentasi)
CREATE TABLE IF NOT EXISTS stok_lokal (
    barang_id VARCHAR(20) PRIMARY KEY,
    jumlah_stok INT DEFAULT 0
);

INSERT INTO stok_lokal VALUES 
('BRG001', 100),
('BRG002', 50),
('BRG003', 200),
('BRG004', 150),
('BRG005', 120);

-- Tabel Transaksi Lokal
CREATE TABLE IF NOT EXISTS transaksi_lokal (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(50) UNIQUE,
    barang_id VARCHAR(20),
    jumlah INT,
    total_harga DECIMAL(15,2),
    tanggal_transaksi DATETIME,
    sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending'
);

-- Tabel Users (Local)
CREATE TABLE IF NOT EXISTS users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('direktur', 'admin_pusat', 'admin_cabang', 'kasir'),
    branch_id VARCHAR(10)
);

INSERT INTO users (name, email, password, role, branch_id) VALUES
('Admin Cabang Bogor', 'admin.bogor@elektro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin_cabang', 'BGR'),
('Kasir Bogor', 'kasir.bogor@elektro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kasir', 'BGR');