<?php
session_start();
require_once '../../koneksi.php';
/*
|--------------------------------------------------------------------------
| Cek Login 
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['is_auth']) || $_SESSION['is_auth'] !== true) {
    header('location:login.php');
    exit;
}
/*
|--------------------------------------------------------------------------
| Cek ID
|--------------------------------------------------------------------------
*/
if (!isset($_GET['kode'])) {
    header('location:produk.php');
    exit;
}
$kode = $_GET['kode'];
/*
|--------------------------------------------------------------------------
| Cegah Menghapus Diri Sendiri
|--------------------------------------------------------------------------
*/
if ($id == $_SESSION['id_user']) {
    echo "
        <script>
            alert('Anda tidak dapat menghapus akun yang sedang digunakan!');
            window.location='produk.php';
        </script>
    ";
    exit;
}
try {
    /*
    |--------------------------------------------------------------------------
    | Cek User Ada atau Tidak
    |--------------------------------------------------------------------------
    */
    $cek = $koneksi->prepare("
        SELECT kode
        FROM tproduct
        WHERE kode = ?
    ");
    $cek->execute([$kode]);
    if ($cek->rowCount() == 0) {
        echo "
            <script>
                alert('product tidak ditemukan!');
                window.location='produk.php';
            </script>
        ";
        exit;
    }
    /*
    |--------------------------------------------------------------------------
    | Hapus Produk
    |--------------------------------------------------------------------------
    */
    $hapus = $koneksi->prepare("
        DELETE FROM tproduct
        WHERE kode = ?
    ");
    $hapus->execute([$kode]);
    echo "
        <script>
            alert('Produk berhasil dihapus!');
            window.location='produk.php';
        </script>
    ";
}
catch(PDOException $e){
    echo "
        <script>
            alert('".$e->getMessage()."');
            window.location='produk.php';
        </script>
    ";
}
?>