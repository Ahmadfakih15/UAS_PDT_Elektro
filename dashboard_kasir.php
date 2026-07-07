<?php
require 'config.php';
checkAuth();
if (!in_array($_SESSION['role'], ['kasir', 'admin_cabang'])) redirect('login.php');

$branchId = $_SESSION['branch_id'];
$db = ($_SESSION['db_name'] == 'tasik') ? $tasik : $bogor;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode = $branchId . '-' . date('YmdHis');
    $barangId = $_POST['barang_id'];
    $jumlah = (int)$_POST['jumlah'];
    
    $cekStok = $db->prepare("SELECT jumlah_stok FROM stok_lokal WHERE barang_id = ?");
    $cekStok->execute([$barangId]);
    $stokTersedia = $cekStok->fetchColumn();
    
    if ($stokTersedia < $jumlah) {
        $error = "Stok tidak mencukupi! Tersedia: $stokTersedia unit";
    } else {
        $stmt = $db->prepare("SELECT harga_jual FROM barang WHERE barang_id = ?");
        $stmt->execute([$barangId]);
        $harga = $stmt->fetchColumn();
        $total = $harga * $jumlah;
        
        $db->prepare("INSERT INTO transaksi_lokal (kode_transaksi, barang_id, jumlah, total_harga, sync_status, tanggal_transaksi) VALUES (?, ?, ?, ?, 'pending', NOW())")->execute([$kode, $barangId, $jumlah, $total]);
        $db->prepare("UPDATE stok_lokal SET jumlah_stok = jumlah_stok - ? WHERE barang_id = ?")->execute([$jumlah, $barangId]);
        
        $success = "✅ Transaksi berhasil! Kode: $kode | Total: Rp " . number_format($total, 0, ',', '.');
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'sync') {
    $pending = $db->query("SELECT * FROM transaksi_lokal WHERE sync_status='pending'")->fetchAll();
    $jumlahBerhasil = 0;
    
    foreach ($pending as $row) {
        $cek = $pusat->prepare("SELECT id FROM transaksi_global WHERE kode_transaksi = ?");
        $cek->execute([$row['kode_transaksi']]);
        
        if ($cek->rowCount() == 0) {
            $pusat->prepare("INSERT INTO transaksi_global (kode_transaksi, branch_id, total_bayar, tanggal_transaksi) VALUES (?, ?, ?, NOW())")->execute([$row['kode_transaksi'], $branchId, $row['total_harga']]);
            $jumlahBerhasil++;
        }
        
        $db->prepare("UPDATE transaksi_lokal SET sync_status='synced' WHERE kode_transaksi = ?")->execute([$row['kode_transaksi']]);
    }
    
    if ($jumlahBerhasil > 0) {
        $pusat->prepare("INSERT INTO sync_log (branch_id, jumlah_data, status, message, created_at) VALUES (?, ?, 'success', ?, NOW())")
              ->execute([$branchId, $jumlahBerhasil, "Berhasil sinkronisasi $jumlahBerhasil transaksi dari cabang $branchId"]);
        $syncSuccess = "🔄 Berhasil sinkronisasi $jumlahBerhasil transaksi ke pusat!";
    }
}

$barangs = $db->query("SELECT b.*, s.jumlah_stok FROM barang b JOIN stok_lokal s ON b.barang_id = s.barang_id WHERE b.status='aktif' OR b.status IS NULL")->fetchAll();
$transaksiHariIni = $db->query("SELECT * FROM transaksi_lokal WHERE DATE(tanggal_transaksi) = CURDATE() ORDER BY id DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Kasir - Cabang <?= $branchId ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        min-height: 100vh;
        color: #333;
    }

    .navbar {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        padding: 1rem 2rem;
    }

    .navbar-content {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .navbar h2 {
        color: #f093fb;
        font-size: 1.5rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .user-info span {
        color: #555;
        font-weight: 500;
    }

    .back-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 8px 20px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
        margin-right: 10px;
    }

    .logout-btn {
        background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%);
        color: white;
        padding: 8px 20px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    .alert {
        padding: 1rem 1.5rem;
        border-radius: 15px;
        margin-bottom: 1.5rem;
        animation: slideIn 0.5s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border-left: 5px solid #28a745;
        color: #155724;
    }

    .alert-danger {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        border-left: 5px solid #dc3545;
        color: #721c24;
    }

    .alert-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        border-left: 5px solid #17a2b8;
        color: #0c5460;
    }

    .grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .card h3 {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    form label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #555;
    }

    form select,
    form input {
        width: 100%;
        padding: 12px 15px;
        margin-bottom: 1.5rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    form select:focus,
    form input:focus {
        outline: none;
        border-color: #f093fb;
    }

    button[type="submit"] {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    button[type="submit"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(86, 171, 47, 0.4);
    }

    .btn-sync {
        display: inline-block;
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        text-align: center;
        text-decoration: none;
        border-radius: 10px;
        font-weight: 700;
        margin-top: 1rem;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .btn-sync:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(79, 172, 254, 0.4);
    }

    .table-container {
        overflow-x: auto;
        border-radius: 10px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
    }

    td {
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }

    tbody tr:hover {
        background: #f7fafc;
    }

    .status-pending {
        color: #f5576c;
        font-weight: 700;
    }

    .status-synced {
        color: #56ab2f;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h2><i class="fas fa-cash-register"></i> POS Kasir - Cabang <?= $branchId ?></h2>
            <div class="user-info">
                <?php if($_SESSION['role'] == 'admin_cabang'): ?>
                <a href="dashboard_admin_cabang.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
                <?php endif; ?>
                <span><i class="fas fa-user-circle"></i> <?= $_SESSION['name'] ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <?php if(isset($syncSuccess)) echo "<div class='alert alert-info'>$syncSuccess</div>"; ?>

        <div class="grid">
            <div class="card">
                <h3><i class="fas fa-shopping-cart"></i> Transaksi Baru (Local Autonomy)</h3>
                <form method="POST">
                    <label><i class="fas fa-box"></i> Pilih Barang:</label>
                    <select name="barang_id" required>
                        <?php foreach($barangs as $b): ?>
                        <option value="<?= $b['barang_id'] ?>">
                            <?= $b['nama_barang'] ?> - Rp <?= number_format($b['harga_jual']) ?> (Stok:
                            <?= $b['jumlah_stok'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label><i class="fas fa-hashtag"></i> Jumlah:</label>
                    <input type="number" name="jumlah" min="1" value="1" required>

                    <button type="submit">
                        <i class="fas fa-credit-card"></i> Bayar & Simpan Lokal
                    </button>
                </form>

                <a href="?action=sync" class="btn-sync">
                    <i class="fas fa-sync-alt"></i> Sync ke Pusat
                </a>
            </div>

            <div class="card">
                <h3><i class="fas fa-receipt"></i> Transaksi Hari Ini</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($transaksiHariIni as $t): ?>
                            <tr>
                                <td><b><?= $t['kode_transaksi'] ?></b></td>
                                <td>Rp <?= number_format($t['total_harga']) ?></td>
                                <td class="status-<?= $t['sync_status'] ?>">
                                    <?= strtoupper($t['sync_status']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($transaksiHariIni)): ?>
                            <tr>
                                <td colspan="3" style="text-align:center;padding:2rem;color:#718096;">
                                    Belum ada transaksi hari ini
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>