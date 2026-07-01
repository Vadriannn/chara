<?php
session_start();
$page_title = "CHARA - Edit Shift";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

if (!isset($_GET['id'])) {
    header("Location: shift.php");
    exit;
}
$id = $_GET['id'];

try {
    $stmt = $koneksi->prepare("SELECT * FROM tshift WHERE idShift = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        header("Location: shift.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jamMulai = $_POST['jamMulai'];
    $jamBerakhir = $_POST['jamBerakhir'];
    
    try {
        $sql = "UPDATE tshift SET jamMulai = ?, jamBerakhir = ? WHERE idShift = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([$jamMulai, $jamBerakhir, $id]);
        
        catatLog($koneksi, "Edit Shift", "Mengubah data shift: Shift " . $id, "Master Data");
        header("Location: shift.php?success=1");
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
                                <h4 class="card-title">Edit Data Shift</h4>
                                    <?php if($error != "") : ?>
                                    <div class="alert alert-danger"><?= $error ?></div>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>ID Shift</label>
                                            <input type="number" class="form-control" value="<?= $data['idShift'] ?>" disabled>
                                        </div>
                                        <div class="form-group">
                                            <label>Jam Mulai (Format HH:MM)</label>
                                            <input type="time" name="jamMulai" class="form-control" value="<?= $data['jamMulai'] ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Jam Berakhir (Format HH:MM)</label>
                                            <input type="time" name="jamBerakhir" class="form-control" value="<?= $data['jamBerakhir'] ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                        <a href="shift.php" class="btn btn-secondary">Kembali</a>
                                    </form>
                                </div>
                        </div>
                      </div>
                    </div>
                 </div>
<?php require_once '../includes/footer.php'; ?>
