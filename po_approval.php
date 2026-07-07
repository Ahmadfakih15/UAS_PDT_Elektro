<?php
require 'config.php';
checkAuth();
if ($_SESSION['role'] != 'direktur') redirect('login.php');

if (isset($_POST['action'])) {
    $poId = $_POST['po_id'];
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';
    $newStatus = ($action == 'approve') ? 'approved' : 'rejected';
    
    $pusat->prepare("UPDATE purchase_orders SET status=?, approved_by=?, approval_notes=? WHERE po_id=?")
          ->execute([$newStatus, $_SESSION['name'], $notes, $poId]);
    
    header("Location: po_approval.php");
    exit();
}

$pending = $pusat->query("SELECT * FROM purchase_orders WHERE status='pending_approval' ORDER BY created_at DESC")->fetchAll();
$all = $pusat->query("SELECT * FROM purchase_orders ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Approval PO - Direktur</title>
<style>
body{font-family:sans-serif;background:#f4f4f4;margin:0}
.navbar{background:#343a40;color:white;padding:15px 30px;display:flex;justify-content:space-between}
.container{max-width:1200px;margin:30px auto;padding:0 20px}
.card{background:white;padding:25px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);margin-bottom:20px}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;text-align:left;border-bottom:1px solid #ddd}
th{background:#343a40;color:white}
.btn{padding:8px 15px;border:none;border-radius:4px;cursor:pointer;color:white;margin:2px}
.btn-approve{background:#28a745}.btn-reject{background:#dc3545}
.badge{padding:4px 8px;border-radius:3px;font-size:12px;color:white}
.b-pending{background:#ffc107;color:black}.b-approved{background:#28a745}.b-rejected{background:#dc3545}
.alert{background:#fff3cd;color:#856404;padding:15px;border-radius:4px;margin-bottom:20px}
</style>
</head>
<body>
<div class="navbar"><h2>👔 Approval Purchase Order (Direktur)</h2><div><span><?= $_SESSION['name'] ?></span> <a href="logout.php" style="color:white;margin-left:15px">Logout</a></div></div>
<div class="container">
    <?php if(count($pending) > 0): ?>
    <div class="alert">⚠️ Ada <b><?= count($pending) ?> PO</b> yang menunggu approval Anda (nilai > Rp 50.000.000)</div>
    <?php endif; ?>
    
    <div class="card">
        <h3>📋 PO Menunggu Approval</h3>
        <table>
            <tr><th>PO ID</th><th>Supplier</th><th>Total Nilai</th><th>Dibuat Oleh</th><th>Items</th><th>Aksi</th></tr>
            <?php foreach($pending as $po): 
                $items = json_decode($po['items_json'], true);
            ?>
            <tr>
                <td><b><?= $po['po_id'] ?></b></td>
                <td><?= $po['supplier_name'] ?></td>
                <td><b style="color:#dc3545">Rp <?= number_format($po['total_value'],0,',','.') ?></b></td>
                <td><?= $po['created_by'] ?></td>
                <td>
                    <?php foreach($items as $item): ?>
                    <div><?= $item['barang_id'] ?> x<?= $item['qty'] ?> @Rp<?= number_format($item['harga']) ?></div>
                    <?php endforeach; ?>
                </td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="po_id" value="<?= $po['po_id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button class="btn btn-approve">✓ Approve</button>
                    </form>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="po_id" value="<?= $po['po_id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="text" name="notes" placeholder="Alasan tolak" style="width:150px">
                        <button class="btn btn-reject">✗ Reject</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($pending)): ?>
            <tr><td colspan="6" style="text-align:center">Tidak ada PO yang menunggu approval</td></tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="card">
        <h3>📚 Riwayat Semua PO</h3>
        <table>
            <tr><th>PO ID</th><th>Supplier</th><th>Total</th><th>Status</th><th>Approved By</th></tr>
            <?php foreach($all as $po): ?>
            <tr>
                <td><?= $po['po_id'] ?></td>
                <td><?= $po['supplier_name'] ?></td>
                <td>Rp <?= number_format($po['total_value'],0,',','.') ?></td>
                <td><span class="badge b-<?= str_replace('_','-',$po['status']) ?>"><?= strtoupper(str_replace('_',' ',$po['status'])) ?></span></td>
                <td><?= $po['approved_by'] ?: '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
