<?php
require 'config.php';
checkAuth();
if ($_SESSION['role'] != 'admin_pusat') redirect('login.php');

// Proses Tambah User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'tambah') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $role = $_POST['role'];
        $branch = $_POST['branch_id'] ?: null;
        
        // Tentukan database berdasarkan role
        if ($role == 'direktur' || $role == 'admin_pusat') {
            $targetDb = $pusat;
        } elseif ($branch == 'TSK') {
            $targetDb = $tasik;
        } else {
            $targetDb = $bogor;
        }
        
        try {
            $targetDb->prepare("INSERT INTO users (name, email, password, role, branch_id) VALUES (?, ?, ?, ?, ?)")
                     ->execute([$name, $email, $password, $role, $branch]);
            $success = "User $name berhasil ditambahkan!";
        } catch(Exception $e) {
            $error = "Gagal: Email mungkin sudah terdaftar. " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'nonaktif') {
        $email = $_POST['email'];
        $branch = $_POST['branch'];
        if ($branch == 'pusat') $targetDb = $pusat;
        elseif ($branch == 'tasik') $targetDb = $tasik;
        else $targetDb = $bogor;
        
        $targetDb->prepare("UPDATE users SET status='nonaktif' WHERE email=?")->execute([$email]);
        $success = "User dinonaktifkan!";
    }
}

// Ambil user dari semua database
$usersPusat = $pusat->query("SELECT *, 'pusat' as db_name FROM users")->fetchAll();
$usersTasik = $tasik->query("SELECT *, 'tasik' as db_name FROM users")->fetchAll();
$usersBogor = $bogor->query("SELECT *, 'bogor' as db_name FROM users")->fetchAll();
$allUsers = array_merge($usersPusat, $usersTasik, $usersBogor);
?>
<!DOCTYPE html>
<html>
<head><title>Kelola User - Admin Pusat</title>
<style>
body{font-family:sans-serif;background:#f4f4f4;margin:0}
.navbar{background:#007bff;color:white;padding:15px 30px;display:flex;justify-content:space-between}
.container{max-width:1200px;margin:30px auto;padding:0 20px}
.card{background:white;padding:25px;border-radius:8px;box-shadow:0 2px 5px rgba(0,0,0,0.1);margin-bottom:20px}
table{width:100%;border-collapse:collapse;margin-top:15px}
th,td{padding:10px;text-align:left;border-bottom:1px solid #ddd}
th{background:#007bff;color:white}
input,select{padding:8px;margin:5px 0;box-sizing:border-box}
button{padding:8px 15px;border:none;border-radius:4px;cursor:pointer;color:white}
.btn-add{background:#28a745}.btn-del{background:#dc3545}
.success{background:#d4edda;color:#155724;padding:15px;border-radius:4px;margin-bottom:20px}
.error{background:#f8d7da;color:#721c24;padding:15px;border-radius:4px;margin-bottom:20px}
.badge{padding:3px 8px;border-radius:3px;font-size:12px;color:white}
.role-direktur{background:#343a40}.role-admin_pusat{background:#007bff}.role-admin_cabang{background:#28a745}.role-kasir{background:#ffc107;color:black}
.branch-pusat{background:#6c757d}.branch-tasik{background:#28a745}.branch-bogor{background:#fd7e14}
</style>
</head>
<body>
<div class="navbar"><h2>👥 Kelola User (RBAC)</h2><div><span><?= $_SESSION['name'] ?></span> <a href="logout.php" style="color:white;margin-left:15px">Logout</a></div></div>
<div class="container">
    <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
    
    <div class="card">
        <h3>➕ Tambah User Baru</h3>
        <form method="POST" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
            <input type="hidden" name="action" value="tambah">
            <input type="text" name="name" placeholder="Nama Lengkap" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="">-- Role --</option>
                <option value="direktur">Direktur</option>
                <option value="admin_pusat">Admin Pusat</option>
                <option value="admin_cabang">Admin Cabang</option>
                <option value="kasir">Kasir</option>
            </select>
            <select name="branch_id">
                <option value="">-- Cabang (Pusat tidak perlu) --</option>
                <option value="TSK">Cabang Tasikmalaya</option>
                <option value="BGR">Cabang Bogor</option>
            </select>
            <button type="submit" class="btn-add" style="grid-column:span 3">💾 Simpan User</button>
        </form>
    </div>
    
    <div class="card">
        <h3>📋 Daftar User (<?= count($allUsers) ?> user)</h3>
        <table>
            <tr><th>Nama</th><th>Email</th><th>Role</th><th>Cabang</th><th>Database</th><th>Status</th></tr>
            <?php foreach($allUsers as $u): 
                $roleClass = 'role-' . str_replace('_', '_', $u['role']);
                $branchClass = 'branch-' . ($u['branch_id'] ? strtolower($u['branch_id']) : 'pusat');
            ?>
            <tr>
                <td><b><?= $u['name'] ?></b></td>
                <td><?= $u['email'] ?></td>
                <td><span class="badge <?= $roleClass ?>"><?= strtoupper(str_replace('_',' ',$u['role'])) ?></span></td>
                <td><span class="badge <?= $branchClass ?>"><?= $u['branch_id'] ?: 'PUSAT' ?></span></td>
                <td><?= strtoupper($u['db_name']) ?></td>
                <td><?= ($u['status'] ?? 'aktif') == 'nonaktif' ? '<span style="color:red">NONAKTIF</span>' : '<span style="color:green">AKTIF</span>' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
