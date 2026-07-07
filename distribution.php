<?php
require 'config.php';
checkAuth();
if ($_SESSION['role'] != 'admin_pusat') redirect('login.php');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $toBranch = $_POST['to_branch'];
        $barangId = $_POST['barang_id'];
        $qty = (int)$_POST['qty'];
        $createdBy = $_SESSION['name'] ?? 'Admin';
        
        // Generate dist_id
        $distId = 'DIST-' . date('YmdHis');
        
        // Buat items_json
        $items = json_encode([['barang_id' => $barangId, 'qty' => $qty]]);
        
        // Insert ke distributions
        $stmt = $pusat->prepare("INSERT INTO distributions (dist_id, from_branch, to_branch, items_json, status, created_by, created_at) VALUES (?, 'PST', ?, ?, 'in_transit', ?, NOW())");
        $stmt->execute([$distId, $toBranch, $items, $createdBy]);
        
        $message = "✅ Distribusi berhasil! $qty unit $barangId dikirim ke $toBranch";
        $messageType = 'success';
    } catch(Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Ambil data
$branches = $pusat->query("SELECT * FROM branches")->fetchAll();
$barangs = $pusat->query("SELECT * FROM barang WHERE status='aktif'")->fetchAll();
$distributions = $pusat->query("SELECT * FROM distributions ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Distribusi Barang</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    h2 {
        color: #333;
        margin-bottom: 10px;
    }

    .nav {
        margin-bottom: 20px;
    }

    .nav a {
        color: #667eea;
        text-decoration: none;
        margin-right: 15px;
    }

    .nav a:hover {
        text-decoration: underline;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
    }

    select,
    input {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
    }

    select:focus,
    input:focus {
        outline: none;
        border-color: #667eea;
    }

    button {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th,
    td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }

    th {
        background: #667eea;
        color: white;
    }

    tr:hover {
        background: #f5f5f5;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }

    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-in_transit {
        background: #ffc107;
        color: #000;
    }

    .badge-received {
        background: #28a745;
        color: white;
    }

    .badge-draft {
        background: #6c757d;
        color: white;
    }

    .badge-cancelled {
        background: #dc3545;
        color: white;
    }

    h3 {
        margin-top: 30px;
        color: #333;
    }
    </style>
</head>

<body>
    <div class="container">
        <h2>🚚 Distribusi Barang</h2>
        <div class="nav">
            <a href="dashboard_admin_pusat.php">← Kembali</a>
            <a href="logout.php">Logout</a>
        </div>

        <?php if($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label> Cabang Tujuan:</label>
                <select name="to_branch" required>
                    <option value="">-- Pilih Cabang --</option>
                    <?php foreach($branches as $b): ?>
                    <option value="<?= htmlspecialchars($b['branch_id']) ?>">
                        <?= htmlspecialchars($b['branch_name']) ?> (<?= htmlspecialchars($b['branch_city']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>📦 Barang:</label>
                <select name="barang_id" required>
                    <option value="">-- Pilih Barang --</option>
                    <?php foreach($barangs as $b): ?>
                    <option value="<?= htmlspecialchars($b['barang_id']) ?>">
                        <?= htmlspecialchars($b['nama_barang']) ?> - Rp <?= number_format($b['harga_jual']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>#️⃣ Jumlah:</label>
                <input type="number" name="qty" min="1" placeholder="Masukkan jumlah" required>
            </div>

            <button type="submit">📤 Kirim Distribusi</button>
        </form>

        <h3>📋 Riwayat Distribusi</h3>
        <table>
            <thead>
                <tr>
                    <th>Dist ID</th>
                    <th>Dari</th>
                    <th>Tujuan</th>
                    <th>Barang</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($distributions as $d): 
                    $items = json_decode($d['items_json'], true) ?? [];
                    $firstItem = $items[0] ?? [];
                    $barangId = $firstItem['barang_id'] ?? '-';
                    $qty = $firstItem['qty'] ?? 0;
                ?>
                <tr>
                    <td><b><?= htmlspecialchars($d['dist_id']) ?></b></td>
                    <td><?= htmlspecialchars($d['from_branch']) ?></td>
                    <td><?= htmlspecialchars($d['to_branch']) ?></td>
                    <td><?= htmlspecialchars($barangId) ?></td>
                    <td><?= $qty ?> unit</td>
                    <td><span class="badge badge-<?= $d['status'] ?>"><?= strtoupper($d['status']) ?></span></td>
                    <td><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($distributions)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:#999;">Belum ada riwayat distribusi</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>