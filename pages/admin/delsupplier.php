<?php

session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$id = $_GET['id'];

try {

    // Cek apakah supplier ada di pembelian 
    $cekProduk = $koneksi->prepare("
        SELECT COUNT(*)
        FROM tpembelian
        WHERE tSupplier_id = ?
    ");

    $cekProduk->execute([$id]);

    if ($cekProduk->fetchColumn() > 0) {

        header("Location: daftarsupplier.php?error=digunakan");
        exit;
    }

    // Hapus supplier
    $hapus = $koneksi->prepare("
        DELETE FROM tsupplier
        WHERE id = ?
    ");

    $hapus->execute([$id]);

    header("Location: daftarsupplier.php?success=delete");
    exit;

} catch(PDOException $e) {

    echo $e->getMessage(); // sementara untuk debugging
}
?>