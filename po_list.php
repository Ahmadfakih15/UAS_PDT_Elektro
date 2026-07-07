<?php
require 'config.php';
checkAuth();
if ($_SESSION['role'] != 'admin_pusat') redirect('login.php');

$message = '';
$messageType = '';

// Proses buat PO baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    try {
        $supplierName = $_POST['supplier_name'] ?? '';
        $barangIds = $_POST['barang_id'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        
        if (empty($barangIds) || empty($qtys)) {
            throw new Exception("Pilih minimal 1 barang!");
        }
        
        // Build items array dan hitung total
        $items = [];
        $totalValue = 0;
        
        for ($i = 0; $i < count($barangIds); $i++) {
            if (!empty($barangIds[$i]) && !empty($qtys[$i])) {
                // Ambil harga dari database
                $stmt = $pusat->prepare("SELECT harga_beli FROM barang WHERE barang_id = ?");
                $stmt->execute([$barangIds[$i]]);
                $harga = $stmt->fetchColumn() ?: 0;
                
                $qty = (int)$qtys[$i];
                $subtotal = $harga * $qty;
                
                $items[] = [
                    'barang_id' => $barangIds[$i],
                    'qty' => $qty,
                    'harga' => $harga,
                    'subtotal' => $subtotal
                ];
                
                $totalValue += $subtotal;
            }
        }
        
        if (empty($items)) {
            throw new Exception("Tidak ada barang yang valid!");
        }
        
        $itemsJson = json_encode($items);
        $poId = 'PO-' . date('YmdHis');
        $createdBy = $_SESSION['name'] ?? 'Admin Pusat';
        
        // Auto approve jika < 50 juta
        $status = ($totalValue < 50000000) ? 'approved' : 'pending_approval';
        
        $stmt = $pusat->prepare("INSERT INTO purchase_orders (po_id, supplier_name, items_json, total_value, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$poId, $supplierName, $itemsJson, $totalValue, $status, $createdBy]);
        
        $message = "✅ PO $poId berhasil dibuat! Total: Rp " . number_format($totalValue, 0, ',', '.');
        $messageType = 'success';
        
    } catch(Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Ambil data
try {
    $pos = $pusat->query("SELECT * FROM purchase_orders ORDER BY created_at DESC")->fetchAll();
    $barangs = $pusat->query("SELECT barang_id, nama_barang, harga_beli FROM barang WHERE status='aktif'")->fetchAll();
} catch(Exception $e) {
    $pos = [];
    $barangs = [];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order</title>
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

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-success {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        color: white;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }

    input,
    select {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
    }

    .item-row {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        align-items: center;
    }

    .item-row select {
        flex: 2;
    }

    .item-row input {
        flex: 1;
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

    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        color: white;
    }

    .badge-approved {
        background: #28a745;
    }

    .badge-pending_approval {
        background: #ffc107;
        color: #000;
    }

    .badge-received {
        background: #007bff;
    }

    .badge-rejected {
        background: #dc3545;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background: white;
        margin: 5% auto;
        padding: 30px;
        border-radius: 15px;
        max-width: 700px;
        max-height: 90vh;
        overflow-y: auto;
    }

    .close {
        float: right;
        font-size: 28px;
        cursor: pointer;
        color: #999;
    }

    .close:hover {
        color: #333;
    }
    </style>
</head>

<body>
    <div class="container">
        <h2>📝 Purchase Order</h2>
        <div class="nav">
            <a href="dashboard_admin_pusat.php">← Kembali</a>
            <a href="logout.php">Logout</a>
            <button class="btn btn-primary" onclick="openModal()" style="float:right;">+ Buat PO Baru</button>
        </div>

        <?php if($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>PO ID</th>
                    <th>Supplier</th>
                    <th>Total Value</th>
                    <th>Status</th>
                    <th>Dibuat Oleh</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pos as $po): ?>
                <tr>
                    <td><b><?= htmlspecialchars($po['po_id']) ?></b></td>
                    <td><?= htmlspecialchars($po['supplier_name'] ?? '-') ?></td>
                    <td>Rp <?= number_format($po['total_value'] ?? 0, 0, ',', '.') ?></td>
                    <td><span
                            class="badge badge-<?= $po['status'] ?>"><?= strtoupper(str_replace('_', ' ', $po['status'])) ?></span>
                    </td>
                    <td><?= htmlspecialchars($po['created_by'] ?? '-') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($po['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div id="poModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Buat Purchase Order Baru</h3>

            <form method="POST">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label>Nama Supplier:</label>
                    <input type="text" name="supplier_name" required placeholder="Contoh: PT Sumber Listrik">
                </div>

                <div class="form-group">
                    <label>Barang:</label>
                    <div id="itemsContainer">
                        <div class="item-row">
                            <select name="barang_id[]" required>
                                <option value="">-- Pilih Barang --</option>
                                <?php foreach($barangs as $b): ?>
                                <option value="<?= $b['barang_id'] ?>" data-harga="<?= $b['harga_beli'] ?>">
                                    <?= $b['nama_barang'] ?> - Rp <?= number_format($b['harga_beli']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="qty[]" placeholder="Qty" min="1" required>
                            <button type="button" class="btn btn-danger" onclick="removeItem(this)">X</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="addItem()">+ Tambah Barang</button>
                </div>

                <div class="form-group">
                    <label>Total Estimasi: <span id="totalDisplay">Rp 0</span></label>
                </div>

                <button type="submit" class="btn btn-success" style="width:100%;">Simpan PO</button>
            </form>
        </div>
    </div>

    <script>
    function openModal() {
        document.getElementById('poModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('poModal').style.display = 'none';
    }

    function addItem() {
        const container = document.getElementById('itemsContainer');
        const newRow = document.createElement('div');
        newRow.className = 'item-row';
        newRow.innerHTML = `
                <select name="barang_id[]" required>
                    <option value="">-- Pilih Barang --</option>
                    <?php foreach($barangs as $b): ?>
                    <option value="<?= $b['barang_id'] ?>" data-harga="<?= $b['harga_beli'] ?>">
                        <?= $b['nama_barang'] ?> - Rp <?= number_format($b['harga_beli']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="qty[]" placeholder="Qty" min="1" required>
                <button type="button" class="btn btn-danger" onclick="removeItem(this)">X</button>
            `;
        container.appendChild(newRow);
        attachEvents(newRow);
    }

    function removeItem(btn) {
        if (document.querySelectorAll('.item-row').length > 1) {
            btn.parentElement.remove();
            calculateTotal();
        }
    }

    function attachEvents(row) {
        row.querySelector('select').addEventListener('change', calculateTotal);
        row.querySelector('input').addEventListener('input', calculateTotal);
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const select = row.querySelector('select');
            const qty = parseInt(row.querySelector('input').value) || 0;
            const harga = parseInt(select.options[select.selectedIndex]?.dataset.harga) || 0;
            total += (qty * harga);
        });
        document.getElementById('totalDisplay').textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    document.querySelectorAll('.item-row').forEach(attachEvents);
    window.onclick = function(e) {
        if (e.target.id === 'poModal') closeModal();
    }
    </script>
</body>

</html>