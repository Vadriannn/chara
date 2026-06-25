<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$id = $_GET['id'];

try {

    $hapus = $koneksi->prepare("
        DELETE FROM tbiayaOperasional
        WHERE id = ?
    ");

    $hapus->execute([$id]);

    header("Location: biayaoperasional.php?success=delete");
    exit;

} catch (PDOException $e) {

    echo $e->getMessage();
}
?>