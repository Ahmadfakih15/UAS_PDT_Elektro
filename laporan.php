<?php
require 'config.php';
checkAuth();
if (!in_array($_SESSION['role'], ['direktur', 'admin_pusat'])) redirect('login.php');

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$laporanCabang = $pusat->prepare("SELECT branch_id, COUNT(*) as total_trx, SUM(total_bayar) as total_omzet FROM transaksi_global WHERE DATE(tanggal_transaksi) BETWEEN ? AND ? GROUP BY branch_id");
$laporanCabang->execute([$start_date, $end_date]);
$dataCabang = $laporanCabang->fetchAll();

$grandTotal = $pusat->prepare("SELECT COUNT(*) as total_trx, SUM(total_bayar) as total_omzet FROM transaksi_global WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?");
$grandTotal->execute([$start_date, $end_date]);
$grand = $grandTotal->fetch();

$detailTrx = $pusat->prepare("SELECT kode_transaksi, branch_id, total_bayar, tanggal_transaksi FROM transaksi_global WHERE DATE(tanggal_transaksi) BETWEEN ? AND ? ORDER BY tanggal_transaksi DESC LIMIT 50");
$detailTrx->execute([$start_date, $end_date]);
$details = $detailTrx->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Laporan Konsolidasi</title>
<style>
body{font-family:sans-serif;background:#f4f4f4;margin:0}
.navbar{background:#343a40;color:white;padding:15px 30px;display:flex;justify-content:space-between}
.container{max-width:1200px;margin:30px auto;padding:0 20px}
.card{background:white;padding:25px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);margin-bottom:20px}
.filter-box{background:#e9ecef;padding:15px;border-radius:8px;margin-bottom:20px;display:flex;gap:15px;align-items:flex-end}
.filter-box input{padding:8px;border:1px solid #ccc;border-radius:4px}
.filter-box button{padding:8px 20px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer}
table{width:100%;border-collapse:collapse;margin-top:15px}
th,td{padding:10px;text-align:left;border-bottom:1px solid #ddd}
th{background:#343a40;color:white}
.stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:20px}
.stat-box{background:white;padding:20px;border-radius:8px;text-align:center;box-shadow:0 2px 5px rgba(0,0,0,0.1)}
.stat-box h3{margin:0;font-size:28px;color:#007bff}
.badge{padding:4px 8px;border-radius:3px;color:white;font-size:12px}
.tsk{background:#28a745}.bgr{background:#ffc107;color:black}
</style>
</head>
<body>
<div class="navbar"><h2>📊 Laporan Konsolidasi (GCS)</h2><div><span><?= $_SESSION['name'] ?></span> <a href="logout.php" style="color:white;margin-left:15px">Logout</a></div></div>
<div class="container">
    <div class="filter-box">
        <form method="GET" style="display:flex;gap:15px;align-items:flex-end">
            <div><label>Tanggal Mulai</label><br><input type="date" name="start_date" value="<?= $start_date ?>"></div>
            <div><label>Tanggal Akhir</label><br><input type="date" name="end_date" value="<?= $end_date ?>"></div>
            <button type="submit">🔍 Tampilkan</button>
        </form>
    </div>
    <div class="stat-grid">
        <div class="stat-box"><h3>Rp <?= number_format($grand['total_omzet'] ?? 0, 0, ',', '.') ?></h3><p>Grand Total Omzet</p></div>
        <div class="stat-box"><h3><?= $grand['total_trx'] ?? 0 ?></h3><p>Total Transaksi</p></div>
        <div class="stat-box"><h3>2</h3><p>Cabang Aktif</p></div>
    </div>
    <div class="card">
        <h3>📈 Performa Per Cabang (<?= $start_date ?> s/d <?= $end_date ?>)</h3>
        <table>
            <tr><th>Cabang</th><th>Jumlah Transaksi</th><th>Total Omzet</th></tr>
            <?php foreach($dataCabang as $row): ?>
            <tr><td><span class="badge <?= strtolower($row['branch_id']) ?>"><?= $row['branch_id'] ?></span></td><td><?= $row['total_trx'] ?></td><td>Rp <?= number_format($row['total_omzet'], 0, ',', '.') ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
