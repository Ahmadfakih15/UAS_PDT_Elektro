<?php
session_start();

// ✅ SET TIMEZONE WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Koneksi ke 3 Database Terdistribusi (Docker)
try {
    $pusat = new PDO('mysql:host=127.0.0.1;port=3306;dbname=db_elektro_pusat', 'root', 'root123');
    $pusat->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) { die("Koneksi Pusat gagal: " . $e->getMessage()); }

try {
    $tasik = new PDO('mysql:host=127.0.0.1;port=3307;dbname=db_elektro_tasik', 'root', 'root123');
    $tasik->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) { die("Koneksi Tasik gagal: " . $e->getMessage()); }

try {
    $bogor = new PDO('mysql:host=127.0.0.1;port=3308;dbname=db_elektro_bogor', 'root', 'root123');
    $bogor->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) { die("Koneksi Bogor gagal: " . $e->getMessage()); }

function redirect($url) { header("Location: $url"); exit(); }
function checkAuth() { if (!isset($_SESSION['user_id'])) { redirect('login.php'); } }
?>