<?php
require 'config.php';
checkAuth();
if ($_SESSION['role'] != 'admin_pusat') redirect('login.php');

try {
    $barangCount = $pusat->query("SELECT COUNT(*) FROM barang")->fetchColumn();
    $userCount = $pusat->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $poPending = $pusat->query("SELECT COUNT(*) FROM purchase_orders WHERE status='pending_approval'")->fetchColumn();
    $distInTransit = $pusat->query("SELECT COUNT(*) FROM distributions WHERE status='in_transit'")->fetchColumn();
    $syncLogs = $pusat->query("SELECT * FROM sync_log ORDER BY created_at DESC LIMIT 10")->fetchAll();
    
    // Query SEMUA transaksi individual hari ini (TANPA GROUP BY)
    $allTransaksi = $pusat->query("
        SELECT 
            id,
            kode_transaksi,
            branch_id,
            kasir_name,
            total_bayar,
            tanggal_transaksi,
            synced_at
        FROM transaksi_global 
        WHERE DATE(synced_at) = CURDATE()
        ORDER BY synced_at DESC
    ")->fetchAll();
    
    // Ambil detail barang untuk setiap transaksi
    $transaksiWithItems = [];
    foreach ($allTransaksi as $trans) {
        $branchId = $trans['branch_id'];
        $dbCabang = ($branchId == 'TSK') ? $tasik : $bogor;
        
        try {
            // Ambil items dari transaksi_lokal di cabang
            $itemsQuery = $dbCabang->prepare("
                SELECT b.nama_barang, tl.jumlah, tl.barang_id
                FROM transaksi_lokal tl 
                LEFT JOIN barang b ON tl.barang_id = b.barang_id 
                WHERE tl.kode_transaksi = ?
            ");
            $itemsQuery->execute([$trans['kode_transaksi']]);
            $items = $itemsQuery->fetchAll();
            
            $transaksiWithItems[] = [
                'transaksi' => $trans,
                'items' => $items
            ];
        } catch(Exception $e) {
            // Skip jika error
        }
    }
} catch(Exception $e) {
    $barangCount = 0; $userCount = 0; $poPending = 0; $distInTransit = 0; $syncLogs = [];
    $transaksiWithItems = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Pusat</title>
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        color: #667eea;
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

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }

    .stat-icon.blue {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stat-icon.green {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
    }

    .stat-icon.orange {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-icon.purple {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-info h3 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 0.25rem;
    }

    .stat-info p {
        color: #718096;
        font-size: 0.9rem;
        font-weight: 500;
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
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .menu-item {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 2rem;
        border-radius: 15px;
        text-align: center;
        text-decoration: none;
        color: #333;
        transition: all 0.3s;
        cursor: pointer;
    }

    .menu-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .menu-item:hover h4,
    .menu-item:hover p {
        color: white;
    }

    .menu-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .menu-item h4 {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .menu-item p {
        font-size: 0.85rem;
        color: #718096;
    }

    .menu-item:hover p {
        color: rgba(255, 255, 255, 0.9);
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        margin-top: 0.5rem;
    }

    .badge-red {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    .badge-blue {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }

    .badge-info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

    .status-success {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h2><i class="fas fa-building"></i> Dashboard Admin Pusat</h2>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?= $_SESSION['name'] ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-box"></i></div>
                <div class="stat-info">
                    <h3><?= $barangCount ?></h3>
                    <p>Total Barang</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?= $userCount ?></h3>
                    <p>Total Pengguna</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-file-alt"></i></div>
                <div class="stat-info">
                    <h3><?= $poPending ?></h3>
                    <p>PO Pending</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-truck"></i></div>
                <div class="stat-info">
                    <h3><?= $distInTransit ?></h3>
                    <p>Dalam Pengiriman</p>
                </div>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-cubes"></i> Menu Manajemen</h3>
            <div class="menu-grid">
                <a href="po_list.php" class="menu-item">
                    <div class="menu-icon">📝</div>
                    <h4>Purchase Order</h4>
                    <p>Kelola pengadaan barang</p>
                    <?php if($poPending > 0): ?><span class="badge badge-red"><?= $poPending ?>
                        Pending</span><?php endif; ?>
                </a>
                <a href="distribution.php" class="menu-item">
                    <div class="menu-icon">🚚</div>
                    <h4>Distribusi Barang</h4>
                    <p>Kirim barang ke cabang</p>
                    <?php if($distInTransit > 0): ?><span class="badge badge-blue"><?= $distInTransit ?>
                        Transit</span><?php endif; ?>
                </a>
                <a href="goods_receipt.php" class="menu-item">
                    <div class="menu-icon">📥</div>
                    <h4>Goods Receipt</h4>
                    <p>Terima dari Supplier</p>
                </a>
                <a href="barang_manage.php" class="menu-item">
                    <div class="menu-icon">📦</div>
                    <h4>Kelola Barang</h4>
                    <p>Master data barang</p>
                </a>
                <a href="user_manage.php" class="menu-item">
                    <div class="menu-icon">👥</div>
                    <h4>Kelola User (RBAC)</h4>
                    <p>Manajemen pengguna</p>
                </a>
                <a href="laporan.php" class="menu-item">
                    <div class="menu-icon">📊</div>
                    <h4>Laporan Konsolidasi</h4>
                    <p>Filter & ekspor data</p>
                </a>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-sync-alt"></i> Transaksi Hari Ini dari Cabang</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-clock"></i> Waktu</th>
                            <th><i class="fas fa-store"></i> Cabang</th>
                            <th><i class="fas fa-box"></i> Barang Terjual</th>
                            <th><i class="fas fa-money-bill"></i> Total Nominal</th>
                            <th><i class="fas fa-user"></i> Kasir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($transaksiWithItems)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;padding:2rem;color:#718096;">
                                Belum ada transaksi hari ini
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($transaksiWithItems as $transData): 
                                $trans = $transData['transaksi'];
                                $items = $transData['items'];
                                
                                // Build barang text
                                $barangText = '';
                                if (!empty($items)) {
                                    $barangText = implode(', ', array_map(function($item) {
                                        return ($item['nama_barang'] ?: $item['barang_id']) . ' x' . $item['jumlah'];
                                    }, $items));
                                } else {
                                    $barangText = '-';
                                }
                            ?>
                        <tr>
                            <td><?= date('d M Y H:i', strtotime($trans['synced_at'] ?? $trans['tanggal_transaksi'])) ?>
                            </td>
                            <td><b><?= htmlspecialchars($trans['branch_id']) ?></b></td>
                            <td style="font-size:0.85rem;"><?= htmlspecialchars($barangText) ?></td>
                            <td><b>Rp <?= number_format($trans['total_bayar'] ?? 0, 0, ',', '.') ?></b></td>
                            <td><?= htmlspecialchars($trans['kasir_name'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>