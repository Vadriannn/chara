<?php
session_start();
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

    $hapus->execute([$kode]);

    header("Location: bahanbaku.php?success=delete");
    exit;

} catch(PDOException $e) {

    echo $e->getMessage(); // sementara untuk debugging
}
?>