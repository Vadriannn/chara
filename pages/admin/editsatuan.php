<?php
session_start();
$page_title = "CHARA - Edit Satuan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

if (!isset($_GET['id'])) {
    header("Location: satuan.php");
    exit;
}

$id = $_GET['id'];

try {
    $stmt = $koneksi->prepare("SELECT * FROM tsatuan WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        header("Location: satuan.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    
    try {
        // Cek nama kembar selain id ini
        $cek = $koneksi->prepare("SELECT COUNT(*) FROM tsatuan WHERE nama = ? AND id != ?");
        $cek->execute([$nama, $id]);
        if ($cek->fetchColumn() > 0) {
            $error = "Satuan sudah ada.";
        } else {
            $sql = "UPDATE tsatuan SET nama = ? WHERE id = ?";
            $stmt = $koneksi->prepare($sql);
            $stmt->execute([$nama, $id]);
            
            catatLog($koneksi, "Edit Satuan", "Mengubah satuan dari " . $data['nama'] . " menjadi " . $nama, "Master Data");
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
                                <h4 class="card-title">Edit Satuan</h4>
                                    <?php if($error != "") : ?>
                                    <div class="alert alert-danger">
                                        <?= $error ?>
                                    </div>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>Nama Satuan</label>
                                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data['nama']) ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
