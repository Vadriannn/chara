<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

if (!isset($_GET['id'])) {
    header("Location: shift.php");
    exit;
}

try {
    $stmt = $koneksi->prepare("DELETE FROM tshift WHERE idShift = ?");
    $stmt->execute([$_GET['id']]);
    
    catatLog($koneksi, "Hapus Shift", "Menghapus data shift ID: " . $_GET['id'], "Master Data");
    header("Location: shift.php?success=delete");
    exit;
} catch (PDOException $e) {
    header("Location: shift.php?error=digunakan");
    exit;
}
?>
