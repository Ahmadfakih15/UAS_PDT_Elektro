<?php
require 'config.php';
checkAuth();
if ($_SESSION['role'] != 'direktur') redirect('login.php');

$totalPenjualan = $pusat->query("SELECT SUM(total_bayar) as total FROM transaksi_global")->fetch()['total'] ?? 0;
$totalTransaksi = $pusat->query("SELECT COUNT(*) as total FROM transaksi_global")->fetch()['total'];
$perCabang = $pusat->query("SELECT branch_id, COUNT(*) as jumlah, SUM(total_bayar) as omzet FROM transaksi_global GROUP BY branch_id")->fetchAll();
$poPending = $pusat->query("SELECT COUNT(*) FROM purchase_orders WHERE status='pending_approval'")->fetchColumn();
$totalPOValue = $pusat->query("SELECT SUM(total_value) FROM purchase_orders WHERE status='pending_approval'")->fetchColumn() ?? 0;
?>
<!DOCTYPE html>
<html>
<head><title>Dashboard Direktur</title>
<style>
body{font-family:sans-serif;background:#f4f4f4;margin:0}
.navbar{background:#343a40;color:white;padding:15px 30px;display:flex;justify-content:space-between}
.container{max-width:1200px;margin:30px auto;padding:0 20px}
.card{background:white;padding:25px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);margin-bottom:20px}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:20px}
.stat-box{color:white;padding:30px;border-radius:8px;text-align:center}
.stat-box h3{margin:0;font-size:36px}
.stat-box p{margin:10px 0 0 0;opacity:0.9}
table{width:100%;border-collapse:collapse;margin-top:15px}
th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd}
th{background:#007bff;color:white}
.badge{padding:5px 10px;border-radius:3px;color:white;font-weight:bold}
.tsk{background:#28a745}.bgr{background:#ffc107;color:black}
.menu-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px}
.menu-item{background:#f8f9fa;padding:20px;border-radius:8px;text-align:center;text-decoration:none;color:#333;border:2px solid #dee2e6}
.menu-item:hover{border-color:#343a40;background:#e9ecef}
.menu-item h3{margin:0 0 10px 0;font-size:32px}
.alert{background:#fff3cd;color:#856404;padding:15px;border-radius:4px;margin-bottom:20px}
</style>
</head>
<body>
<div class="navbar"><h2>📊 Dashboard Direktur</h2><div><span><?= $_SESSION['name'] ?></span> <a href="logout.php" style="color:white;margin-left:15px">Logout</a></div></div>
<div class="container">
    <?php if($poPending > 0): ?>
    <div class="alert">⚠️ Ada <b><?= $poPending ?> PO</b> menunggu approval Anda dengan total nilai <b>Rp <?= number_format($totalPOValue,0,',','.') ?></b></div>
    <?php endif; ?>
    
    <div class="menu-grid">
        <a href="po_approval.php" class="menu-item" style="<?php if($poPending>0) echo 'border-color:#dc3545;background:#f8d7da'; ?>">
            <h3>👔</h3>
            <div>Approval PO<?php if($poPending>0): ?><br><b style="color:#dc3545"><?= $poPending ?> pending!</b><?php endif; ?></div>
        </a>
        <a href="#" class="menu-item">
            <h3>📈</h3>
            <div>Laporan Keuangan<br><small>Konsolidasi</small></div>
        </a>
    </div>
    
    <div class="card">
        <h3>🏢 Global Conceptual Schema - Konsolidasi Seluruh Cabang</h3>
        <div class="stats">
            <div class="stat-box" style="background:linear-gradient(135deg,#667eea,#764ba2)"><h3>Rp <?= number_format($totalPenjualan,0,',','.') ?></h3><p>Total Omzet</p></div>
            <div class="stat-box" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><h3><?= $totalTransaksi ?></h3><p>Total Transaksi</p></div>
            <div class="stat-box" style="background:linear-gradient(135deg,#4facfe,#00f2fe)"><h3>3</h3><p>Node Aktif</p></div>
        </div>
        
        <h4>📈 Performa Per Cabang</h4>
        <table>
            <tr><th>Cabang</th><th>Jumlah Transaksi</th><th>Total Omzet</th></tr>
            <?php foreach($perCabang as $row): ?>
            <tr>
                <td><span class="badge <?= strtolower($row['branch_id']) ?>"><?= $row['branch_id'] ?></span></td>
                <td><?= $row['jumlah'] ?></td>
                <td>Rp <?= number_format($row['omzet'],0,',','.') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
