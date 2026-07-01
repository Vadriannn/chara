<?php
session_start();
$page_title = "CHARA - Tambah Shift";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idShift = $_POST['idShift'];
    $jamMulai = $_POST['jamMulai'];
    $jamBerakhir = $_POST['jamBerakhir'];
    
    try {
        $cek = $koneksi->prepare("SELECT COUNT(*) FROM tshift WHERE idShift = ?");
        $cek->execute([$idShift]);
        if ($cek->fetchColumn() > 0) {
            $error = "ID Shift sudah digunakan.";
        } else {
            $sql = "INSERT INTO tshift (idShift, jamMulai, jamBerakhir) VALUES (?, ?, ?)";
            $stmt = $koneksi->prepare($sql);
            $stmt->execute([$idShift, $jamMulai, $jamBerakhir]);
            
            catatLog($koneksi, "Tambah Shift", "Menambahkan shift baru: Shift " . $idShift, "Master Data");
            header("Location: shift.php?success=1");
            exit;
        }
    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Tambah Shift</h4>
                                    <?php if($error != "") : ?>
                                    <div class="alert alert-danger"><?= $error ?></div>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>ID Shift (Angka, Misal: 1 untuk Pagi, 2 untuk Malam)</label>
                                            <input type="number" name="idShift" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Jam Mulai (Format HH:MM)</label>
                                            <input type="time" name="jamMulai" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Jam Berakhir (Format HH:MM)</label>
                                            <input type="time" name="jamBerakhir" class="form-control" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                        <a href="shift.php" class="btn btn-secondary">Kembali</a>
                                    </form>
                                </div>
                        </div>
                      </div>
                    </div>
                 </div>
<?php require_once '../includes/footer.php'; ?>
