<?php
session_start();
$page_title = "CHARA - Tambah Satuan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    try {
        // Cek satuan sudah ada atau belum
        $cek = $koneksi->prepare("
            SELECT COUNT(*) 
            FROM tsatuan 
            WHERE nama = ?
        ");
        $cek->execute([$nama]);
        if ($cek->fetchColumn() > 0) {
            $error = "Satuan sudah ada.";
        }
        else {
            $sql = "
                INSERT INTO tsatuan (nama)
                VALUES (?)
            ";
            $stmt = $koneksi->prepare($sql);
            $stmt->execute([$nama]);
            catatLog($koneksi, "Tambah Satuan", "Menambahkan satuan: " . $nama, "Master Data");
            header("Location: satuan.php?success=1");
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
                                <h4 class="card-title">Tambah Satuan</h4>
                                    <?php if($error != "") : ?>
                                    <div class="alert alert-danger">
                                        <?= $error ?>
                                    </div>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>Nama Satuan</label>
                                            <input type="text" name="nama" class="form-control" placeholder="Contoh: Kilogram" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                        <a href="satuan.php" class="btn btn-secondary">Kembali</a>
                                    </form>
                                </div>
                        </div>
                      </div>
                    </div>
                 </div>
<?php 
require_once '../includes/footer.php'; 
?>
