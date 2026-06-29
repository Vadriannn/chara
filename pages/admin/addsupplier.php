<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$pesan = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nama = trim($_POST['nama']);

    try {
        // Cek supplier sudah ada atau belum
        $cek = $koneksi->prepare("
            SELECT COUNT(*)
            FROM tsupplier
            WHERE nama = ?
        ");
        $cek->execute([$nama]);
        if ($cek->fetchColumn() > 0) {
            $error = "Supplier sudah ada.";
        } else {
            $sql = "
                INSERT INTO tsupplier (nama)
                VALUES (?)
            ";
            $stmt = $koneksi->prepare($sql);
            $stmt->execute([$nama]);
            
            header("Location: daftarsupplier.php?success=add");
            exit;
        }
    } catch (PDOException $e) {
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

                        <h4 class="card-title">Tambah Supplier</h4>

                        <?php if($error != "") : ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST">

                            <div class="form-group">
                                <label>Nama Supplier</label>
                                <input
                                    type="text"
                                    name="nama"
                                    class="form-control"
                                    placeholder="Masukkan nama supplier"
                                    required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Simpan
                            </button>

                            <a href="supplier.php" class="btn btn-secondary">
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
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>