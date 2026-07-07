<?php
require 'config.php';
checkAuth();

// Redirect ke dashboard sesuai role
switch($_SESSION['role']) {
    case 'direktur': redirect('dashboard_direktur.php'); break;
    case 'admin_pusat': redirect('dashboard_admin_pusat.php'); break;
    case 'admin_cabang': redirect('dashboard_admin_cabang.php'); break;
    case 'kasir': redirect('dashboard_kasir.php'); break;
    default: redirect('login.php');
}
?>