<?php
session_start();
$page_title = "CHARA - Edit Kategori";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$error = "";
$pesan = "";
if (!isset($_GET['id'])) {
    header("Location: kategori.php");
    exit;
}
$id = $_GET['id'];
// Ambil data kategori
$stmt = $koneksi->prepare("
    SELECT *
    FROM tkategori
    WHERE id = ?
");
$stmt->execute([$id]);
$kategori = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$kategori) {
    die("Kategori tidak ditemukan.");
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    try {
        // Cek apakah ada kategori lain dengan nama sama
        $cek = $koneksi->prepare("
            SELECT COUNT(*)
            FROM tkategori
            WHERE nama = ?
            AND id != ?
        ");
        $cek->execute([$nama, $id]);
        if ($cek->fetchColumn() > 0) {
            $error = "Nama kategori sudah digunakan.";
        } else {
            $update = $koneksi->prepare("
                UPDATE tkategori
                SET nama = ?
                WHERE id = ?
            ");
            $update->execute([
                $nama,
                $id
            ]);
            catatLog($koneksi, "Ubah Kategori", "Mengubah kategori (" . $kategori['nama'] . ") menjadi: " . $nama, "Master Data");
            $pesan = "Kategori berhasil diperbarui.";
            // refresh data terbaru
            $stmt->execute([$id]);
            $kategori = $stmt->fetch(PDO::FETCH_ASSOC);
            header("Location: kategori.php?success=1");
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
                                <h4 class="card-title">Edit Kategori</h4>
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
                                        <label>Nama Kategori</label>
                                        <input
                                            type="text"
                                            name="nama"
                                            class="form-control"
                                            value="<?= htmlspecialchars($kategori['nama']) ?>"
                                            required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        Simpan Perubahan
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
          <!-- content -wrapper ends -->
          <!-- partial:partials/_footer.html -->
<?php 
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>