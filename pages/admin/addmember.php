<?php
session_start();
$page_title = "CHARA - Tambah Member";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $noHp = trim($_POST['noHp']);
    $nama = trim($_POST['nama']);
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];
    
    try {
        $sql = "
            INSERT INTO tmember (noHp, Nama, Gender, BirthDate, Poin, JoinDate)
            VALUES (?, ?, ?, ?, 0, NOW())
        ";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([$noHp, $nama, $gender, $birthdate]);
        
        catatLog($koneksi, "Tambah Member", "Mendaftarkan member baru: " . $nama, "Master Data");
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
                                <h4 class="card-title">Tambah Member Baru</h4>
                                    <?php if($error != "") : ?>
                                    <div class="alert alert-danger"><?= $error ?></div>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>No. HP (ID Member)</label>
                                            <input type="text" name="noHp" class="form-control" placeholder="Contoh: 08123456789" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Nama Lengkap</label>
                                            <input type="text" name="nama" class="form-control" placeholder="Masukkan nama pelanggan" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Gender</label>
                                            <select name="gender" class="form-control" required>
                                                <option value="">-- Pilih Gender --</option>
                                                <option value="M">Pria (Male)</option>
                                                <option value="F">Wanita (Female)</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Tanggal Lahir</label>
                                            <input type="date" name="birthdate" class="form-control" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Simpan Member</button>
                                        <a href="member.php" class="btn btn-secondary">Kembali</a>
                                    </form>
                                </div>
                        </div>
                      </div>
                    </div>
                 </div>
<?php require_once '../includes/footer.php'; ?>
