<?php
session_start();
$page_title = "CHARA - Hapus Bahan Baku";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$kode = $_GET['kode'];

try {

    // Cek apakah bahan digunakan di resep
    $cekResep = $koneksi->prepare("
        SELECT COUNT(*)
        FROM tresep
        WHERE tBahan_kode = ?
    ");

    $cekResep->execute([$kode]);

    if ($cekResep->fetchColumn() > 0) {

        header("Location: bahanbaku.php?error=digunakan");
        exit;
    }

    // Hapus bahan
    $hapus = $koneksi->prepare("
        DELETE FROM tbahan
        WHERE kode = ?
    ");

    $stmtNama = $koneksi->prepare("SELECT nama FROM tbahan WHERE kode = ?");
    $stmtNama->execute([$kode]);
    $namaBahan = $stmtNama->fetchColumn();

    $hapus->execute([$kode]);
    
    if($namaBahan) {
        catatLog($koneksi, "Hapus Bahan Baku", "Menghapus bahan baku: " . $namaBahan, "Master Data", $kode);
    }

    header("Location: bahanbaku.php?success=delete");
    exit;

} catch(PDOException $e) {

    echo $e->getMessage(); // sementara untuk debugging
}
?>