<?php 
session_start();
require_once '../koneksi.php';

catatLog($koneksi, "Logout", "Pengguna logout dari sistem", "Auth");

session_unset();
session_destroy();

header('location: ../pages/login.php');
?>