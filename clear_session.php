<?php
session_start();
session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
echo "<h2>Session berhasil dihapus!</h2>";
echo "<p><a href='login.php' style='color:blue;font-size:20px;'>Klik di sini untuk Login</a></p>";