<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

if (!isset($_GET['id'])) {
    header("Location: satuan.php");
    exit;
}

try {
    $stmt = $koneksi->prepare("DELETE FROM tsatuan WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    catatLog($koneksi, "Hapus Satuan", "Menghapus data satuan ID: " . $_GET['id'], "Master Data");
    header("Location: satuan.php?success=delete");
    
} catch(PDOException $e) {
    // 1451 adalah kode error MySQL untuk foreign key constraint fails
    if ($e->errorInfo[1] == 1451) {
        header("Location: satuan.php?error=digunakan");
    } else {
        header("Location: satuan.php?error=delete");
    }
}
?>
