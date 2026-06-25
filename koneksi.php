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
?>