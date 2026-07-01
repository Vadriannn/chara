<?php
require_once '../../auth.php';

if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Kasir') {
    header('location:../../index.php');
    exit;
}

// Cek Shift Kasir Aktif
$currentPage = basename($_SERVER['PHP_SELF']);
if ($_SESSION['role'] == 'Kasir' && $currentPage !== 'bukashift.php') {
    global $koneksi;
    if ($koneksi) {
        $stmtCekShift = $koneksi->prepare("
            SELECT id FROM tDetailShift 
            WHERE tUser_id = ? AND tanggal = CURDATE() AND jamKeluar IS NULL 
            ORDER BY id DESC LIMIT 1
        ");
        $stmtCekShift->execute([$_SESSION['id_user']]);
        $shiftAktif = $stmtCekShift->fetchColumn();
        
        if (!$shiftAktif) {
            header("Location: ../kasir/bukashift.php");
            exit;
        }
    }
}
?>