<?php
require 'config.php';
checkAuth();
if ($_SESSION['role'] != 'admin_pusat') redirect('login.php');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $poId = $_POST['po_id'];
        $verifiedBy = $_SESSION['name'] ?? 'Admin Pusat';
        $receiptId = 'GR-' . date('YmdHis');
        
        // Cek apakah PO sudah pernah di-receipt
        $cekReceipt = $pusat->prepare("SELECT receipt_id FROM goods_receipts WHERE po_id = ?");
        $cekReceipt->execute([$poId]);
        
        if ($cekReceipt->fetchColumn()) {
            throw new Exception("PO ini sudah pernah diterima! Tidak bisa diterima ulang.");
        }
        
        // Insert ke goods_receipts
        $stmt = $pusat->prepare("INSERT INTO goods_receipts (receipt_id, po_id, received_date, verified_by, status, created_at) VALUES (?, ?, NOW(), ?, 'verified', NOW())");
        $stmt->execute([$receiptId, $poId, $verifiedBy]);
        
        // Update status PO menjadi 'received'
        $pusat->prepare("UPDATE purchase_orders SET status='received' WHERE po_id=?")->execute([$poId]);
        
        $message = "✅ Penerimaan barang berhasil! PO $poId telah diterima.";
        $messageType = 'success';
        
    } catch(Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Ambil PO yang approved/sent DAN belum di-receive
try {
    $approvedPOs = $pusat->query("
        SELECT po.* 
        FROM purchase_orders po 
        WHERE po.status IN ('approved', 'sent') 
        AND NOT EXISTS (
            SELECT 1 FROM goods_receipts gr WHERE gr.po_id = po.po_id
        )
        ORDER BY po.created_at DESC
    ")->fetchAll();
    
    $receipts = $pusat->query("
        SELECT gr.*, po.supplier_name 
        FROM goods_receipts gr 
        LEFT JOIN purchase_orders po ON gr.po_id = po.po_id 
        ORDER BY gr.created_at DESC
    ")->fetchAll();
    
} catch(Exception $e) {
    $approvedPOs = [];
    $receipts = [];
    $debugError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goods Receipt</title>
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

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #555;
    }

    .form-group select {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 1rem;
        font-family: 'Inter', sans-serif;
    }

    .form-group select:focus {
        outline: none;
        border-color: #11998e;
    }

    .btn-primary {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(17, 153, 142, 0.4);
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

    .badge-verified {
        background: linear-gradient(135deg, #28a745 0%, #4caf50 100%);
        color: white;
    }

    .badge-pending {
        background: linear-gradient(135deg, #ffc107 0%, #ffdb6a 100%);
        color: #000;
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

    .alert-warning {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border-left: 5px solid #f7b733;
        color: #856404;
    }

    .alert-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        border-left: 5px solid #17a2b8;
        color: #0c5460;
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #718096;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
        display: block;
    }

    .po-info {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 0.5rem;
    }

    .po-info b {
        color: #11998e;
    }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h2><i class="fas fa-download"></i> Goods Receipt</h2>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?= $_SESSION['name'] ?></span>
                <a href="dashboard_admin_pusat.php" class="logout-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <h3><i class="fas fa-clipboard-check"></i> Terima Barang dari Supplier</h3>

            <?php if(empty($approvedPOs)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Tidak ada PO yang siap diterima!</strong><br>
                <small>
                    <?php 
                    $totalPO = $pusat->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn();
                    $receivedPO = $pusat->query("SELECT COUNT(*) FROM goods_receipts")->fetchColumn();
                    echo "Total PO: $totalPO | Sudah diterima: $receivedPO<br>";
                    echo "Pastikan ada PO dengan status 'approved' atau 'sent' yang belum pernah diterima.";
                    ?>
                </small>
            </div>
            <?php else: ?>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-file-alt"></i> Pilih Purchase Order (Approved/Sent):</label>
                    <select name="po_id" required>
                        <option value="">-- Pilih PO --</option>
                        <?php foreach($approvedPOs as $po): ?>
                        <option value="<?= htmlspecialchars($po['po_id']) ?>">
                            <?= htmlspecialchars($po['po_id']) ?> - <?= htmlspecialchars($po['supplier_name'] ?? '-') ?>
                            - Rp <?= number_format($po['total_value'] ?? 0, 0, ',', '.') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Verifikasi Penerimaan
                </button>
            </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3><i class="fas fa-history"></i> Riwayat Penerimaan</h3>
            <div class="table-container">
                <?php if(empty($receipts)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Belum ada penerimaan barang</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Receipt ID</th>
                            <th>PO ID</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th>Diverifikasi Oleh</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($receipts as $r): ?>
                        <tr>
                            <td><b><?= htmlspecialchars($r['receipt_id']) ?></b></td>
                            <td><?= htmlspecialchars($r['po_id']) ?></td>
                            <td><?= htmlspecialchars($r['supplier_name'] ?? '-') ?></td>
                            <td><span class="badge badge-<?= $r['status'] ?>"><?= strtoupper($r['status']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($r['verified_by'] ?? '-') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
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