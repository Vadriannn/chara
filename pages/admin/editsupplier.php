<?php
session_start();
$page_title = "CHARA - Edit Supplier";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

if (!isset($_SESSION['is_auth']) || $_SESSION['is_auth'] !== true) {
    header("Location: ../../login.php");
    exit;
}
$error = "";
$pesan = "";
/* Cek ID Supplier*/
if (!isset($_GET['id'])) {
    header("Location: daftarsupplier.php");
    exit;
}
$id = $_GET['id'];
try {
    /*Ambil Data Supplier */
    $stmt = $koneksi->prepare("
        SELECT *
        FROM tsupplier
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$supplier) {
        die("Data supplier tidak ditemukan.");
    }
    /*Proses Update*/
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $nama = trim($_POST['nama']);

        /* Cek Nama Supplier Duplikat */
        $cek = $koneksi->prepare("
            SELECT COUNT(*)
            FROM tsupplier
            WHERE nama = ?
            AND id != ?
        ");
        $cek->execute([
            $nama,
            $id
        ]);
        if ($cek->fetchColumn() > 0) {
            $error = "Nama supplier sudah digunakan.";
        } else {
            $update = $koneksi->prepare("
                UPDATE tsupplier
                SET nama = ?
                WHERE id = ?
            ");
            $update->execute([
                $nama,
                $id
            ]);
            catatLog($koneksi, "Ubah Supplier", "Mengubah supplier (" . $supplier['nama'] . ") menjadi: " . $nama, "Master Data");
            header("Location: daftarsupplier.php?success=edit");
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

                        <h4 class="card-title">Edit Supplier</h4>

                        <?php if($pesan != "") : ?>
                        <div class="alert alert-success">
                            <?= $pesan ?>
                        </div>
                        <?php endif; ?>

                        <?php if($error != "") : ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST">

                            <div class="form-group">
                                <label>ID Supplier</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?= $supplier['id'] ?>"
                                    readonly>
                            </div>

                            <div class="form-group">
                                <label>Nama Supplier</label>
                                <input
                                    type="text"
                                    name="nama"
                                    class="form-control"
                                    value="<?= htmlspecialchars($supplier['nama']) ?>"
                                    required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Simpan Perubahan
                            </button>

                            <a href="daftarsupplier.php" class="btn btn-secondary">
                                Kembali
                            </a>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

          <!-- content -wrapper ends -->
          <!-- partial:partials/_footer.html -->
<?php 
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>