<?php
session_start();
$page_title = "CHARA - Edit Konversi Satuan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$pesan = "";
$error = "";

if (!isset($_GET['b']) || !isset($_GET['k'])) {
    header("Location: konversisatuan.php");
    exit;
}

$satuan_besar = $_GET['b'];
$satuan_kecil = $_GET['k'];

try {
    $stmt = $koneksi->prepare("SELECT * FROM tkonversisatuan WHERE SatuanBesar_id = ? AND SatuanKecil_id = ?");
    $stmt->execute([$satuan_besar, $satuan_kecil]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        header("Location: konversisatuan.php");
        exit;
    }

    $stmtSatuan = $koneksi->query("SELECT * FROM tsatuan ORDER BY nama");
    $satuans = $stmtSatuan->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $konversi = $_POST['konversi'];
    
    try {
        $sql = "
            UPDATE tkonversisatuan
            SET Konversi = ?
            WHERE SatuanBesar_id = ? AND SatuanKecil_id = ?
        ";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([
            $konversi, $satuan_besar, $satuan_kecil
        ]);
        catatLog($koneksi, "Edit Konversi", "Mengubah konversi satuan", "Master Data");
        header("Location: konversisatuan.php?success=1");
        exit;
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
                                <h4 class="card-title">Edit Konversi Satuan</h4>
                                    <?php if($error != "") : ?>
                                    <div class="alert alert-danger">
                                        <?= $error ?>
                                    </div>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>Satuan Besar</label>
                                            <select name="satuan_besar" class="form-control" disabled>
                                                <?php foreach ($satuans as $s): ?>
                                                    <option value="<?= $s['id'] ?>" <?= $s['id'] == $satuan_besar ? 'selected' : '' ?>><?= $s['nama'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Satuan Kecil</label>
                                            <select name="satuan_kecil" class="form-control" disabled>
                                                <?php foreach ($satuans as $s): ?>
                                                    <option value="<?= $s['id'] ?>" <?= $s['id'] == $satuan_kecil ? 'selected' : '' ?>><?= $s['nama'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Nilai Konversi (1 Satuan Besar = ? Satuan Kecil)</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                name="konversi"
                                                class="form-control"
                                                value="<?= $data['Konversi'] ?>"
                                                required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            Simpan Perubahan
                                        </button>
                                        <a href="konversisatuan.php" class="btn btn-secondary">
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
