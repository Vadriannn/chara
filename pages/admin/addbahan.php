<?php
session_start();

$page_title = "CHARA - Tambah Bahan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$error = "";
$satuan = []; 

try {
    // Mengambil data satuan
    $stmtSatuan = $koneksi->query("SELECT * FROM tsatuan ORDER BY nama");
    if ($stmtSatuan) {
        $satuan = $stmtSatuan->fetchAll(PDO::FETCH_ASSOC);
    }

    // LOGIKA AUTO GENERATE KODE BAHAN
    $stmtLast = $koneksi->query("SELECT kode FROM tbahan WHERE kode LIKE 'B%' ORDER BY kode DESC LIMIT 1");
    $lastBahan = $stmtLast->fetch(PDO::FETCH_ASSOC);

    if ($lastBahan) {
        $lastNumber = (int) substr($lastBahan['kode'], 1);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    $kodeOtomatis = 'B' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

    // Proses Form POST
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $kode       = $kodeOtomatis;
        $nama       = trim($_POST['nama']);
        $satuanId   = $_POST['satuan'];

        $cek = $koneksi->prepare("SELECT COUNT(*) FROM tbahan WHERE kode = ?");
        $cek->execute([$kode]);

        if ($cek->fetchColumn() > 0) {
            $error = "Kode bahan sudah digunakan. Silakan muat ulang halaman.";
        } else {
            $sql = "INSERT INTO tbahan (kode, nama, stok, harga, tSatuan_id) VALUES (?, ?, 0, 0, ?)";
            $stmt = $koneksi->prepare($sql);
            $stmt->execute([$kode, $nama, $satuanId]);
            
            catatLog($koneksi, "Tambah Bahan Baku", "Menambahkan bahan: " . $nama, "Master Data", $kode);

            header("Location: bahanbaku.php?success=add");
            exit;
        }
    }
} catch(PDOException $e) {
    $error = $e->getMessage();
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Tambah Bahan Baku</h4>
                    <?php if($error != "") : ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Kode Bahan</label>
                            <input type="text" name="kode" value="<?= $kodeOtomatis ?>" class="form-control" readonly required>
                        </div>
                        <div class="form-group">
                            <label>Nama Bahan</label>
                            <input type="text" name="nama" class="form-control" placeholder="Masukkan nama bahan" required>
                        </div>
                        <div class="form-group">
                            <label>Satuan</label>
                            <select name="satuan" class="form-control" required>
                                <option value="">-- Pilih Satuan --</option>
                                <?php if (!empty($satuan)): ?>
                                    <?php foreach($satuan as $row): ?>
                                        <option value="<?= $row['id'] ?>"><?= $row['nama'] ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">-- Data Satuan Tidak Tersedia --</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="bahanbaku.php" class="btn btn-secondary">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once '../includes/footer.php'; 
?>