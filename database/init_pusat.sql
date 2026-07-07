-- ============================================
-- DATABASE PUSAT (JAKARTA)
-- Global Conceptual Schema
-- ============================================

-- Tabel Branches
CREATE TABLE IF NOT EXISTS branches (
    branch_id VARCHAR(10) PRIMARY KEY,
    branch_name VARCHAR(50),
    branch_city VARCHAR(50)
);

INSERT INTO branches VALUES 
('PST', 'Kantor Pusat', 'Jakarta'),
('TSK', 'Cabang Tasikmalaya', 'Tasikmalaya'),
('BGR', 'Cabang Bogor', 'Bogor');

-- Tabel Barang Master (Replikasi Penuh)
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

-- Tabel Transaksi Global (Konsolidasi)
CREATE TABLE IF NOT EXISTS transaksi_global (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(50) UNIQUE,
    branch_id VARCHAR(10),
    kasir_name VARCHAR(100),
    total_bayar DECIMAL(15,2),
    tanggal_transaksi DATETIME,
    synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('direktur', 'admin_pusat', 'admin_cabang', 'kasir'),
    branch_id VARCHAR(10)
);

-- Password default: password (bcrypt hash)
INSERT INTO users (name, email, password, role, branch_id) VALUES
('Direktur Utama', 'direktur@elektro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'direktur', NULL),
('Admin Pusat', 'admin.pusat@elektro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin_pusat', NULL);

-- Tabel Sync Log
CREATE TABLE IF NOT EXISTS sync_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    branch_id VARCHAR(10),
    jumlah_data INT,
    status ENUM('success', 'failed'),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);