<?php
require 'config.php';
checkAuth();
if ($_SESSION['role'] != 'admin_cabang') redirect('login.php');

$branchId = $_SESSION['branch_id'];
$db = ($_SESSION['db_name'] == 'tasik') ? $tasik : $bogor;

$message = '';
$messageType = '';

// Proses terima distribusi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dist_id'])) {
    try {
        $distId = $_POST['dist_id'];
        $receivedBy = $_SESSION['name'] ?? 'Admin Cabang';
        
        // Ambil data distribusi dari pusat
        $dist = $pusat->prepare("SELECT * FROM distributions WHERE dist_id = ? AND to_branch = ? AND status = 'in_transit'");
        $dist->execute([$distId, $branchId]);
        $distData = $dist->fetch();
        
        if (!$distData) {
            throw new Exception("Distribusi tidak ditemukan atau sudah diterima");
        }
        
        // Parse items dari JSON
        $items = json_decode($distData['items_json'], true);
        
        if (empty($items)) {
            throw new Exception("Data barang tidak valid");
        }
        
        // Update stok di database cabang untuk setiap barang
        foreach ($items as $item) {
            $barangId = $item['barang_id'];
            $qty = $item['qty'];
            
            // Cek apakah barang sudah ada di stok_lokal
            $cekStok = $db->prepare("SELECT jumlah_stok FROM stok_lokal WHERE barang_id = ?");
            $cekStok->execute([$barangId]);
            $stokSekarang = $cekStok->fetchColumn();
            
            if ($stokSekarang === false) {
                // Barang belum ada, insert baru
                $db->prepare("INSERT INTO stok_lokal (barang_id, jumlah_stok) VALUES (?, ?)")
                   ->execute([$barangId, $qty]);
            } else {
                // Barang sudah ada, update stok (tambah)
                $db->prepare("UPDATE stok_lokal SET jumlah_stok = jumlah_stok + ? WHERE barang_id = ?")
                   ->execute([$qty, $barangId]);
            }
        }
        
        // Update status distribusi di pusat
        $pusat->prepare("UPDATE distributions SET status = 'received', received_by = ?, received_at = NOW() WHERE dist_id = ?")
              ->execute([$receivedBy, $distId]);
        
        $message = "✅ Distribusi $distId berhasil diterima! Stok cabang telah diperbarui.";
        $messageType = 'success';
        
    } catch(Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Ambil distribusi yang menunggu konfirmasi
try {
    $incomingDist = $pusat->prepare("SELECT * FROM distributions WHERE to_branch = ? AND status = 'in_transit' ORDER BY created_at DESC");
    $incomingDist->execute([$branchId]);
    $incomingDist = $incomingDist->fetchAll();
    
    // Ambil riwayat distribusi yang sudah diterima
    $receivedDist = $pusat->prepare("SELECT * FROM distributions WHERE to_branch = ? AND status = 'received' ORDER BY received_at DESC");
    $receivedDist->execute([$branchId]);
    $receivedDist = $receivedDist->fetchAll();
} catch(Exception $e) {
    $incomingDist = [];
    $receivedDist = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terima Distribusi - Cabang <?= $branchId ?></title>
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

    .alert {
        padding: 1rem 1.5rem;
        border-radius: 15px;
        margin-bottom: 1.5rem;
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border-left: 5px solid #28a745;
        color: #155724;
    }

    .alert-error {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        border-left: 5px solid #dc3545;
        color: #721c24;
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

    .badge-received {
        background: linear-gradient(135deg, #28a745 0%, #4caf50 100%);
        color: white;
    }

    .badge-transit {
        background: linear-gradient(135deg, #ffc107 0%, #ffdb6a 100%);
        color: #000;
    }

    .btn-terima {
        background: linear-gradient(135deg, #28a745 0%, #4caf50 100%);
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-terima:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #718096;
    }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h2><i class="fas fa-download"></i> Terima Distribusi - Cabang <?= $branchId ?></h2>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?= $_SESSION['name'] ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <h3><i class="fas fa-truck-loading"></i> Kiriman Masuk (Menunggu Konfirmasi)</h3>
            <div class="table-container">
                <?php if(empty($incomingDist)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox" style="font-size:3rem;margin-bottom:1rem;display:block;opacity:0.5;"></i>
                    <p>Tidak ada kiriman masuk</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Dist ID</th>
                            <th>Dari</th>
                            <th>Items</th>
                            <th>Tanggal Kirim</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($incomingDist as $d): 
                            $items = json_decode($d['items_json'], true) ?? [];
                            $itemsText = implode(', ', array_map(function($item) {
                                return $item['barang_id'] . ' x' . $item['qty'];
                            }, $items));
                        ?>
                        <tr>
                            <td><b><?= htmlspecialchars($d['dist_id']) ?></b></td>
                            <td><?= htmlspecialchars($d['from_branch']) ?></td>
                            <td><?= htmlspecialchars($itemsText) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="dist_id" value="<?= htmlspecialchars($d['dist_id']) ?>">
                                    <button type="submit" class="btn-terima">
                                        <i class="fas fa-check"></i> Terima
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-history"></i> Riwayat Distribusi ke Cabang Ini</h3>
            <div class="table-container">
                <?php if(empty($receivedDist)): ?>
                <div class="empty-state">
                    <p>Belum ada riwayat distribusi</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Dist ID</th>
                            <th>Dari</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Diterima Oleh</th>
                            <th>Tanggal Terima</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($receivedDist as $d): 
                            $items = json_decode($d['items_json'], true) ?? [];
                            $itemsText = implode(', ', array_map(function($item) {
                                return $item['barang_id'] . ' x' . $item['qty'];
                            }, $items));
                        ?>
                        <tr>
                            <td><b><?= htmlspecialchars($d['dist_id']) ?></b></td>
                            <td><?= htmlspecialchars($d['from_branch']) ?></td>
                            <td><?= htmlspecialchars($itemsText) ?></td>
                            <td><span class="badge badge-received"><?= strtoupper($d['status']) ?></span></td>
                            <td><?= htmlspecialchars($d['received_by'] ?? '-') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($d['received_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>