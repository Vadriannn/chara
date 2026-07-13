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
    header('location:login.php');
    exit;
}
/*
|--------------------------------------------------------------------------
| Cek ID
|--------------------------------------------------------------------------
*/
if (!isset($_GET['id'])) {
    header('location:employee.php');
    exit;
}
$id = (int)$_GET['id'];
/*
|--------------------------------------------------------------------------
| Cegah Menghapus Diri Sendiri
|--------------------------------------------------------------------------
*/
if ($id == $_SESSION['id_user']) {
    echo "
        <script>
            alert('Anda tidak dapat menghapus akun yang sedang digunakan!');
            window.location='employee.php';
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
        SELECT id
        FROM tuser
        WHERE id = ?
    ");
    $cek->execute([$id]);
    if ($cek->rowCount() == 0) {
        echo "
            <script>
                alert('User tidak ditemukan!');
                window.location='employee.php';
            </script>
        ";
        exit;
    }
    /*
    |--------------------------------------------------------------------------
    | Hapus User
    |--------------------------------------------------------------------------
    */
    $hapus = $koneksi->prepare("
        DELETE FROM tuser
        WHERE id = ?
    ");
    $hapus->execute([$id]);
    echo "
        <script>
            alert('User berhasil dihapus!');
            window.location='employee.php';
        </script>
    ";
}
catch(PDOException $e){
    echo "
        <script>
            alert('".$e->getMessage()."');
            window.location='employee.php';
        </script>
    ";
}
?>