<?php
session_start();
$page_title = "CHARA - Edit Member";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

if (!isset($_GET['id'])) {
    header("Location: member.php");
    exit;
}
$id = $_GET['id'];

try {
    $stmt = $koneksi->prepare("SELECT * FROM tmember WHERE noHp = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        header("Location: member.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];
    $poin = (int)$_POST['poin'];
    
    try {
        $sql = "UPDATE tmember SET Nama = ?, Gender = ?, BirthDate = ?, Poin = ? WHERE noHp = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([$nama, $gender, $birthdate, $poin, $id]);
        
        catatLog($koneksi, "Edit Member", "Mengubah data member: " . $nama, "Master Data");
        header("Location: member.php?success=1");
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
                                <h4 class="card-title">Edit Data Member</h4>
                                    <?php if($error != "") : ?>
                                    <div class="alert alert-danger"><?= $error ?></div>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>No. HP (ID)</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($data['noHp']) ?>" disabled>
                                        </div>
                                        <div class="form-group">
                                            <label>Nama Lengkap</label>
                                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data['Nama']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Gender</label>
                                            <select name="gender" class="form-control" required>
                                                <option value="M" <?= $data['Gender'] == 'M' ? 'selected' : '' ?>>Pria (Male)</option>
                                                <option value="F" <?= $data['Gender'] == 'F' ? 'selected' : '' ?>>Wanita (Female)</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Tanggal Lahir</label>
                                            <input type="date" name="birthdate" class="form-control" value="<?= $data['BirthDate'] ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Poin Aktif</label>
                                            <input type="number" name="poin" class="form-control" value="<?= $data['Poin'] ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                        <a href="member.php" class="btn btn-secondary">Kembali</a>
                                    </form>
                                </div>
                        </div>
                      </div>
                    </div>
                 </div>
<?php require_once '../includes/footer.php'; ?>
