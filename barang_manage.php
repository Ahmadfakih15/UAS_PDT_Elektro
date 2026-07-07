<?php
require 'config.php';
checkAuth();
if ($_SESSION['role'] != 'admin_pusat') redirect('login.php');

// Proses Tambah Barang
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'tambah') {
        $barangId = $_POST['barang_id'];
        $nama = $_POST['nama_barang'];
        $kategori = $_POST['kategori'];
        $hargaBeli = $_POST['harga_beli'];
        $hargaJual = $_POST['harga_jual'];
        $stok = $_POST['stok_awal'];
        
        try {
            $pusat->prepare("INSERT INTO barang (barang_id, nama_barang, kategori, harga_beli, harga_jual, status) VALUES (?, ?, ?, ?, ?, 'aktif')")
                  ->execute([$barangId, $nama, $kategori, $hargaBeli, $hargaJual]);
            
            // Tambah stok di semua cabang
            foreach (['tasik' => $tasik, 'bogor' => $bogor] as $branch => $db) {
                $db->prepare("INSERT INTO stok_lokal (barang_id, jumlah_stok) VALUES (?, ?)")
                   ->execute([$barangId, $stok]);
            }
            $success = "Barang $nama berhasil ditambahkan!";
        } catch(Exception $e) {
            $error = "Gagal: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'update') {
        $id = $_POST['barang_id'];
        $nama = $_POST['nama_barang'];
        $kategori = $_POST['kategori'];
        $hargaBeli = $_POST['harga_beli'];
        $hargaJual = $_POST['harga_jual'];
        
        $pusat->prepare("UPDATE barang SET nama_barang=?, kategori=?, harga_beli=?, harga_jual=? WHERE barang_id=?")
              ->execute([$nama, $kategori, $hargaBeli, $hargaJual, $id]);
        $success = "Barang berhasil diupdate!";
    }
    
    if ($_POST['action'] == 'nonaktif') {
        $id = $_POST['barang_id'];
        $pusat->prepare("UPDATE barang SET status='nonaktif' WHERE barang_id=?")->execute([$id]);
        $success = "Barang dinonaktifkan!";
    }
}

$barangs = $pusat->query("SELECT * FROM barang ORDER BY barang_id")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Kelola Barang - Admin Pusat</title>
<style>
body{font-family:sans-serif;background:#f4f4f4;margin:0}
.navbar{background:#007bff;color:white;padding:15px 30px;display:flex;justify-content:space-between}
.container{max-width:1200px;margin:30px auto;padding:0 20px}
.card{background:white;padding:25px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);margin-bottom:20px}
table{width:100%;border-collapse:collapse;margin-top:15px}
th,td{padding:10px;text-align:left;border-bottom:1px solid #ddd}
th{background:#007bff;color:white}
input,select{padding:8px;margin:5px 0;box-sizing:border-box}
button{padding:8px 15px;border:none;border-radius:4px;cursor:pointer;color:white;margin:2px}
.btn-add{background:#28a745}.btn-edit{background:#ffc107;color:black}.btn-del{background:#dc3545}
.success{background:#d4edda;color:#155724;padding:15px;border-radius:4px;margin-bottom:20px}
.error{background:#f8d7da;color:#721c24;padding:15px;border-radius:4px;margin-bottom:20px}
.badge-aktif{background:#28a745;color:white;padding:3px 8px;border-radius:3px;font-size:12px}
.badge-nonaktif{background:#6c757d;color:white;padding:3px 8px;border-radius:3px;font-size:12px}
</style>
</head>
<body>
<div class="navbar"><h2> Kelola Barang Master</h2><div><span><?= $_SESSION['name'] ?></span> <a href="logout.php" style="color:white;margin-left:15px">Logout</a></div></div>
<div class="container">
    <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
    
    <div class="card">
        <h3>➕ Tambah Barang Baru</h3>
        <form method="POST" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
            <input type="hidden" name="action" value="tambah">
            <input type="text" name="barang_id" placeholder="Kode Barang (BRG006)" required>
            <input type="text" name="nama_barang" placeholder="Nama Barang" required>
            <select name="kategori" required>
                <option value="">-- Kategori --</option>
                <option value="Kabel">Kabel</option>
                <option value="MCB">MCB</option>
                <option value="Lampu">Lampu</option>
                <option value="Saklar">Saklar</option>
                <option value="Stop Kontak">Stop Kontak</option>
                <option value="Panel Listrik">Panel Listrik</option>
            </select>
            <input type="number" name="harga_beli" placeholder="Harga Beli" required>
            <input type="number" name="harga_jual" placeholder="Harga Jual" required>
            <input type="number" name="stok_awal" placeholder="Stok Awal per Cabang" required>
            <button type="submit" class="btn-add" style="grid-column:span 3">💾 Simpan Barang</button>
        </form>
    </div>
    
    <div class="card">
        <h3>📋 Daftar Barang Master (<?= count($barangs) ?> item)</h3>
        <table>
            <tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Harga Beli</th><th>Harga Jual</th><th>Status</th><th>Aksi</th></tr>
            <?php foreach($barangs as $b): ?>
            <tr>
                <td><b><?= $b['barang_id'] ?></b></td>
                <td><?= $b['nama_barang'] ?></td>
                <td><?= $b['kategori'] ?></td>
                <td>Rp <?= number_format($b['harga_beli']) ?></td>
                <td>Rp <?= number_format($b['harga_jual']) ?></td>
                <td><span class="badge-<?= $b['status'] ?? 'aktif' ?>"><?= strtoupper($b['status'] ?? 'AKTIF') ?></span></td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="nonaktif">
                        <input type="hidden" name="barang_id" value="<?= $b['barang_id'] ?>">
                        <button type="submit" class="btn-del" onclick="return confirm('Nonaktifkan barang ini?')"> Nonaktifkan</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
