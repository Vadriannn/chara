<?php 
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "chara";

// 1. Deklarasikan variabel koneksi di luar blok try-catch
$koneksi = null; 
$pesan = '';

try {
    $koneksi = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pesan = "Koneksi berhasil";
} catch(PDOException $e) {
    // 2. Jika gagal, langsung matikan proses dan tampilkan alasannya!
    die("Koneksi Database Gagal: " . $e->getMessage()); 
}

if (!function_exists('catatLog')) {
    function catatLog($koneksi, $aktivitas, $keterangan, $modul, $referensi = '-') {
        if(isset($_SESSION['id_user'])) {
            try {
                $stmt = $koneksi->prepare("INSERT INTO tLog (aktivitas, keterangan, waktu, modul, referensi, tUser_id) VALUES (?, ?, NOW(), ?, ?, ?)");
                $stmt->execute([$aktivitas, $keterangan, $modul, $referensi, $_SESSION['id_user']]);
            } catch (Exception $e) {
                // Abaikan error log agar tidak memblokir transaksi utama
            }
        }
    }
}
?>