<?php
require 'config.php';
checkAuth();
if ($_SESSION['role'] != 'admin_cabang') redirect('login.php');

$branchId = $_SESSION['branch_id'];
$db = ($_SESSION['db_name'] == 'tasik') ? $tasik : $bogor;

try {
    $pendingSync = $db->query("SELECT COUNT(*) FROM transaksi_lokal WHERE sync_status='pending'")->fetchColumn();
    $stokItems = $db->query("SELECT * FROM stok_lokal ORDER BY jumlah_stok ASC")->fetchAll();
    $incomingDist = $pusat->query("SELECT COUNT(*) FROM distributions WHERE to_branch='$branchId' AND status='in_transit'")->fetchColumn();
    $stokMenipis = $db->query("SELECT COUNT(*) FROM stok_lokal WHERE jumlah_stok < 20")->fetchColumn();
    $stokMenipisList = $db->query("SELECT s.barang_id, b.nama_barang, s.jumlah_stok FROM stok_lokal s LEFT JOIN barang b ON s.barang_id=b.barang_id WHERE s.jumlah_stok < 20 ORDER BY s.jumlah_stok ASC")->fetchAll();
} catch(Exception $e) {
    $pendingSync = 0; $stokItems = []; $incomingDist = 0; $stokMenipis = 0; $stokMenipisList = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Cabang <?= $branchId ?></title>
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
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        min-height: 100vh;
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
        color: #11998e;
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
    }

    .alert-warning {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border-left: 5px solid #f7b733;
        color: #856404;
    }

    .alert-danger {
        background: linear-gradient(135deg, #f8d7da 0%, #ffcccc 100%);
        border-left: 5px solid #fc4a1a;
        color: #721c24;
    }

    .alert-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #a8e6cf 100%);
        border-left: 5px solid #11998e;
        color: #0c5460;
    }

    .alert a {
        color: inherit;
        font-weight: 700;
        text-decoration: underline;
    }

    .card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
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

    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .menu-item {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 2.5rem;
        border-radius: 15px;
        text-align: center;
        text-decoration: none;
        color: #333;
        transition: all 0.3s;
        cursor: pointer;
    }

    .menu-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(17, 153, 142, 0.3);
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }

    .menu-item:hover h4,
    .menu-item:hover p {
        color: white;
    }

    .menu-icon {
        font-size: 3.5rem;
        margin-bottom: 1rem;
    }

    .menu-item h4 {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .menu-item p {
        font-size: 0.9rem;
        color: #718096;
    }

    .count {
        background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        margin-left: 10px;
    }

    .table-container {
        overflow-x: auto;
        border-radius: 15px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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

    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }

    .badge-danger {
        background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%);
        color: white;
    }

    .badge-success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }

    .stok-list {
        background: white;
        padding: 1rem;
        border-radius: 10px;
        margin-top: 1rem;
        border: 2px dashed #fc4a1a;
    }

    .stok-item {
        padding: 0.75rem;
        border-bottom: 1px dashed #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .stok-item:last-child {
        border-bottom: none;
    }

    .stok-item b {
        color: #fc4a1a;
    }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h2><i class="fas fa-store"></i> Dashboard Admin Cabang <?= $branchId ?></h2>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?= $_SESSION['name'] ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if($pendingSync > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <b>Ada <?= $pendingSync ?> transaksi pending.</b><br>
            <small>Segera sinkronisasi untuk mengirim data ke pusat.</small>
            <a href="dashboard_kasir.php?action=sync" style="margin-left:auto;">Sinkronisasi Sekarang →</a>
        </div>
        <?php endif; ?>

        <?php if($incomingDist > 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-truck"></i>
            <b>Ada <?= $incomingDist ?> kiriman dari pusat menunggu konfirmasi!</b><br>
            <small>Segera konfirmasi penerimaan barang.</small>
            <a href="distribution_receive.php" style="margin-left:auto;">Konfirmasi Sekarang →</a>
        </div>
        <?php endif; ?>

        <?php if($stokMenipis > 0): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div style="flex:1;">
                <b>PERINGATAN STOK MENIPIS!</b><br>
                <small>Ada <?= $stokMenipis ?> barang dengan stok di bawah 20 unit.</small>
                <div class="stok-list">
                    <?php foreach($stokMenipisList as $item): ?>
                    <div class="stok-item">
                        <span><i class="fas fa-box"></i> <b><?= $item['nama_barang'] ?: $item['barang_id'] ?></b>
                            (<?= $item['barang_id'] ?>)</span>
                        <span>Sisa: <b><?= $item['jumlah_stok'] ?> unit</b></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p style="margin:1rem 0 0 0;font-size:0.9rem;">
                    <i class="fas fa-info-circle"></i> Segera ajukan permintaan pengadaan ke Admin Pusat!
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3><i class="fas fa-th-large"></i> Menu Manajemen Cabang <?= $branchId ?></h3>
            <div class="menu-grid">
                <div class="menu-item" onclick="window.location.href='dashboard_kasir.php'">
                    <div class="menu-icon">🛒</div>
                    <h4>Transaksi Penjualan</h4>
                    <p>Point of Sale (POS)</p>
                </div>
                <div class="menu-item" onclick="window.location.href='distribution_receive.php'">
                    <div class="menu-icon">📥</div>
                    <h4>Terima Distribusi</h4>
                    <p>Dari Pusat</p>
                    <?php if($incomingDist>0): ?><span class="count"><?= $incomingDist ?></span><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-boxes"></i> Status Stok Lokal (Fragmentasi Horizontal)</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-barcode"></i> Barang ID</th>
                            <th><i class="fas fa-tag"></i> Nama Barang</th>
                            <th><i class="fas fa-cubes"></i> Stok</th>
                            <th><i class="fas fa-check-circle"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stokItems as $item): 
                            $nama = $db->query("SELECT nama_barang FROM barang WHERE barang_id='{$item['barang_id']}'")->fetchColumn();
                            $status = $item['jumlah_stok'] < 20 ? '<span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> MENIPIS</span>' : '<span class="badge badge-success"><i class="fas fa-check"></i> Aman</span>';
                        ?>
                        <tr>
                            <td><b><?= $item['barang_id'] ?></b></td>
                            <td><?= $nama ?: '-' ?></td>
                            <td><?= $item['jumlah_stok'] ?></td>
                            <td><?= $status ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>