<?php
session_start();

require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

/*
|--------------------------------------------------------------------------
| Cek Login
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['is_auth']) || $_SESSION['is_auth'] !== true) {
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Cek Parameter Kode Produk
|--------------------------------------------------------------------------
*/
if (!isset($_GET['kode']) || empty($_GET['kode'])) {
    header('Location: produk.php');
    exit;
}

$kode = $_GET['kode'];

try {

    /*
    |--------------------------------------------------------------------------
    | Cek Produk Ada atau Tidak
    |--------------------------------------------------------------------------
    */
    $cek = $koneksi->prepare("
        SELECT kode, nama
        FROM tproduct
        WHERE kode = ?
    ");
    $cek->execute([$kode]);
    $produk = $cek->fetch(PDO::FETCH_ASSOC);

    if (!$produk) {
        echo "
        <script>
            alert('Produk tidak ditemukan!');
            window.location='produk.php';
        </script>
        ";
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Mulai Transaction
    |--------------------------------------------------------------------------
    */
    $koneksi->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Hapus Data Resep Produk
    |--------------------------------------------------------------------------
    */
    $hapusResep = $koneksi->prepare("
        DELETE FROM tresep
        WHERE tProduct_kode = ?
    ");
    $hapusResep->execute([$kode]);

    /*
    |--------------------------------------------------------------------------
    | Hapus Produk
    |--------------------------------------------------------------------------
    */
    $hapusProduk = $koneksi->prepare("
        DELETE FROM tproduct
        WHERE kode = ?
    ");
    $hapusProduk->execute([$kode]);

    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */
    
    catatLog($koneksi, "Hapus Produk", "Menghapus produk: " . $produk['nama'], "Master Data", $kode);
    
    $koneksi->commit();

    echo "
    <script>
        alert('Produk berhasil dihapus!');
        window.location='produk.php';
    </script>
    ";
    exit;

} catch(PDOException $e) {

    if ($koneksi->inTransaction()) {
        $koneksi->rollBack();
    }

    echo "
    <script>
        alert('".$e->getMessage()."');
        window.location='produk.php';
    </script>
    ";
}
?>