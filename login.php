<?php
require 'config.php';

// Cek jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'direktur') redirect('dashboard_direktur.php');
    elseif ($_SESSION['role'] == 'admin_pusat') redirect('dashboard_admin_pusat.php');
    elseif ($_SESSION['role'] == 'admin_cabang') redirect('dashboard_admin_cabang.php');
    elseif ($_SESSION['role'] == 'kasir') redirect('dashboard_kasir.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Cari user di database pusat
    $db = $pusat;
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Jika tidak ada di pusat, cari di tasik
    if (!$user) {
        $stmt = $tasik->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $db = $tasik;
    }
    
    // Jika tidak ada di tasik, cari di bogor
    if (!$user) {
        $stmt = $bogor->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $db = $bogor;
    }
    
    // Verifikasi password
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['branch_id'] = $user['branch_id'];
        
        // Set database name berdasarkan branch
        if ($user['branch_id'] == 'TSK') $_SESSION['db_name'] = 'tasik';
        elseif ($user['branch_id'] == 'BGR') $_SESSION['db_name'] = 'bogor';
        else $_SESSION['db_name'] = 'pusat';
        
        // Redirect berdasarkan role
        if ($user['role'] == 'direktur') redirect('dashboard_direktur.php');
        elseif ($user['role'] == 'admin_pusat') redirect('dashboard_admin_pusat.php');
        elseif ($user['role'] == 'admin_cabang') redirect('dashboard_admin_cabang.php');
        elseif ($user['role'] == 'kasir') redirect('dashboard_kasir.php');
    } else {
        $error = "Email atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PT Elektro Nusantara</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html,
    body {
        height: 100%;
        width: 100%;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        overflow: hidden;
    }

    .login-container {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        width: 100%;
        max-width: 420px;
        animation: fadeIn 0.6s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2.5rem 2rem;
        text-align: center;
    }

    .login-header i {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }

    .login-header h1 {
        font-size: 1.6rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .login-header p {
        opacity: 0.9;
        font-size: 0.9rem;
    }

    .login-body {
        padding: 2rem;
    }

    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: #721c24;
        border-left: 4px solid #dc3545;
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
        font-size: 0.95rem;
    }

    .input-group {
        position: relative;
    }

    .input-group i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #667eea;
    }

    .form-group input {
        width: 100%;
        padding: 12px 15px 12px 45px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 1rem;
        transition: border-color 0.3s;
        font-family: 'Inter', sans-serif;
    }

    .form-group input:focus {
        outline: none;
        border-color: #667eea;
    }

    .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.05rem;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.3s, box-shadow 0.3s;
        font-family: 'Inter', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    }

    .quick-access {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e2e8f0;
        text-align: center;
    }

    .quick-access p {
        color: #718096;
        margin-bottom: 1rem;
        font-size: 0.85rem;
    }

    .quick-links {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .quick-link {
        padding: 8px 14px;
        background: #f7fafc;
        border-radius: 20px;
        text-decoration: none;
        color: #667eea;
        font-size: 0.8rem;
        font-weight: 600;
        transition: all 0.3s;
    }

    .quick-link:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: translateY(-2px);
    }

    .footer {
        text-align: center;
        padding: 1.2rem;
        background: #f7fafc;
        color: #718096;
        font-size: 0.8rem;
    }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-building"></i>
            <h1>PT Elektro Nusantara</h1>
            <p>Sistem Informasi Distribusi Terintegrasi</p>
        </div>

        <div class="login-body">
            <?php if($error): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $error ?></span>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="nama@email.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="quick-access">
                <p>Quick Access (Development Mode):</p>
                <div class="quick-links">
                    <a href="aktor.php" class="quick-link"> Semua Aktor</a>
                    <a href="aktor.php?login=direktur" class="quick-link">👔 Direktur</a>
                    <a href="aktor.php?login=admin_pusat" class="quick-link">🏢 Admin</a>
                </div>
            </div>
        </div>

        <div class="footer">
            <i class="fas fa-code"></i> Pemrosesan Data Terdistribusi © 2026
        </div>
    </div>
</body>

</html>