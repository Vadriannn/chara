<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

if (!isset($_GET['b']) || !isset($_GET['k'])) {
    header("Location: konversisatuan.php");
    exit;
}

$satuan_besar = $_GET['b'];
$satuan_kecil = $_GET['k'];

try {
    $stmt = $koneksi->prepare("DELETE FROM tkonversisatuan WHERE SatuanBesar_id = ? AND SatuanKecil_id = ?");
    $stmt->execute([$satuan_besar, $satuan_kecil]);
    
    catatLog($koneksi, "Hapus Konversi", "Menghapus konversi satuan", "Master Data");
    header("Location: konversisatuan.php?success=delete");
    exit;

} catch (PDOException $e) {
    header("Location: konversisatuan.php?error=delete");
    exit;
}
?>
