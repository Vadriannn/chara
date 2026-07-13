<?php
session_start();
header('Content-Type: application/json');
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_kasir.php'; // Ensure cashier is logged in

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$noHp = trim($_POST['noHp'] ?? '');
$nama = trim($_POST['nama'] ?? '');
$gender = $_POST['gender'] ?? '';
$birthdate = $_POST['birthdate'] ?? '';

// Server-side validation
if (empty($noHp) || empty($nama) || empty($gender) || empty($birthdate)) {
    echo json_encode(['success' => false, 'message' => 'Semua kolom wajib diisi.']);
    exit;
}

if (!preg_match('/^[0-9]+$/', $noHp) || strlen($noHp) < 10 || strlen($noHp) > 15) {
    echo json_encode(['success' => false, 'message' => 'Nomor HP tidak valid. Harus berupa angka dan panjang antara 10 hingga 15 karakter.']);
    exit;
}

if (strtotime($birthdate) >= time()) {
    echo json_encode(['success' => false, 'message' => 'Tanggal lahir tidak valid.']);
    exit;
}

try {
    // Check for duplicate No HP
    $stmtCek = $koneksi->prepare("SELECT noHp FROM tmember WHERE noHp = ?");
    $stmtCek->execute([$noHp]);
    if ($stmtCek->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Nomor HP sudah terdaftar.']);
        exit;
    }

    $sql = "
        INSERT INTO tmember (noHp, Nama, Gender, BirthDate, Poin, JoinDate)
        VALUES (?, ?, ?, ?, 0, NOW())
    ";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute([$noHp, $nama, $gender, $birthdate]);
    
    catatLog($koneksi, "Tambah Member Kasir", "Kasir mendaftarkan member baru: " . $nama, "Kasir");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Member berhasil ditambahkan.',
        'member' => [
            'noHp' => $noHp,
            'nama' => $nama,
            'poin' => 0
        ]
    ]);

} catch(PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada sistem database.']);
}
