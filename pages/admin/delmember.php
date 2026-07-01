<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

if (!isset($_GET['id'])) {
    header("Location: member.php");
    exit;
}

try {
    $stmt = $koneksi->prepare("DELETE FROM tmember WHERE noHp = ?");
    $stmt->execute([$_GET['id']]);
    
    catatLog($koneksi, "Hapus Member", "Menghapus data member ID: " . $_GET['id'], "Master Data");
    header("Location: member.php?success=delete");
    exit;
} catch (PDOException $e) {
    // Kemungkinan gagal jika id digunakan di transaksi
    header("Location: member.php?error=digunakan");
    exit;
}
?>
