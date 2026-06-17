<?php
session_start();

require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$id = $_GET['id'];

try {

    // Cek apakah kategori digunakan oleh produk
    $cekProduk = $koneksi->prepare("
        SELECT COUNT(*)
        FROM tproduct
        WHERE tKategori_ID = ?
    ");

    $cekProduk->execute([$id]);

    if ($cekProduk->fetchColumn() > 0) {

        header("Location: kategori.php?error=digunakan");
        exit;
    }

    // Hapus kategori
    $hapus = $koneksi->prepare("
        DELETE FROM tkategori
        WHERE id = ?
    ");

    $hapus->execute([$id]);

    header("Location: kategori.php?success=delete");
    exit;

} catch(PDOException $e) {

    echo $e->getMessage(); // sementara untuk debugging
}
?>