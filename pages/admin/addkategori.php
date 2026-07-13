<?php
session_start();
$page_title = "CHARA - Tambah Kategori";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$pesan = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $status = $_POST['status'];
    try {
        // Cek kategori sudah ada atau belum
        $cek = $koneksi->prepare("
            SELECT COUNT(*) 
            FROM tkategori 
            WHERE nama = ?
        ");
        $cek->execute([$nama]);
        if ($cek->fetchColumn() > 0) {
            $error = "Kategori sudah ada.";
        }
        else {
            $sql = "
                INSERT INTO tkategori
                (nama)
                VALUES (?)
            ";
            $stmt = $koneksi->prepare($sql);
            $stmt->execute([
                $nama
            ]);
            catatLog($koneksi, "Tambah Kategori", "Menambahkan kategori: " . $nama, "Master Data");
            header("Location: kategori.php?success=1");
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
                                <h4 class="card-title">Tambah Kategori</h4>
                                    <?php if($error != "") : ?>
                                    <div class="alert alert-danger">
                                        <?= $error ?>
                                    </div>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>Nama Kategori</label>
                                            <input
                                                type="text"
                                                name="nama"
                                                class="form-control"
                                                placeholder="Masukkan nama kategori"
                                                required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            Simpan
                                        </button>
                                        <a href="kategori.php" class="btn btn-secondary">
                                            Kembali
                                        </a>
                                    </form>
                                </div>
                        </div>
                      </div>
                    </div>
                 </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
<?php 
require_once '../includes/footer.php'; 
?>