<?php
session_start();
$page_title = "CHARA - Tambah Member";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$error = "";
$old_noHp = "";
$old_nama = "";
$old_gender = "";
$old_birthdate = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $noHp = trim($_POST['noHp']);
    $nama = trim($_POST['nama']);
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];
    
    // Retain data for form repopulation
    $old_noHp = $noHp;
    $old_nama = $nama;
    $old_gender = $gender;
    $old_birthdate = $birthdate;

    // Server-side validation
    if (empty($noHp) || empty($nama) || empty($gender) || empty($birthdate)) {
        $error = "Semua kolom wajib diisi.";
    } elseif (!preg_match('/^[0-9]+$/', $noHp) || strlen($noHp) < 10 || strlen($noHp) > 15) {
        $error = "Nomor HP tidak valid. Harus berupa angka dan panjang antara 10 hingga 15 karakter.";
    } elseif (strtotime($birthdate) >= time()) {
        $error = "Tanggal lahir tidak valid. Harus tanggal di masa lalu.";
    } else {
        try {
            // Check for duplicate No HP
            $stmtCek = $koneksi->prepare("SELECT noHp FROM tmember WHERE noHp = ?");
            $stmtCek->execute([$noHp]);
            if ($stmtCek->rowCount() > 0) {
                $error = "Nomor HP sudah terdaftar sebagai member.";
            } else {
                $sql = "
                    INSERT INTO tmember (noHp, Nama, Gender, BirthDate, Poin, JoinDate)
                    VALUES (?, ?, ?, ?, 0, NOW())
                ";
                $stmt = $koneksi->prepare($sql);
                $stmt->execute([$noHp, $nama, $gender, $birthdate]);
                
                catatLog($koneksi, "Tambah Member", "Mendaftarkan member baru: " . $nama, "Master Data");
                header("Location: member.php?success=1");
                exit;
            }
        } catch(PDOException $e) {
            // Log real error internally and show generic error
            error_log($e->getMessage());
            $error = "Terjadi kesalahan pada sistem. Silakan coba lagi.";
        }
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
                                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>No. HP (ID Member)</label>
                                            <input type="text" name="noHp" class="form-control" placeholder="Contoh: 08123456789" value="<?= htmlspecialchars($old_noHp) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Nama Lengkap</label>
                                            <input type="text" name="nama" class="form-control" placeholder="Masukkan nama pelanggan" value="<?= htmlspecialchars($old_nama) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Gender</label>
                                            <select name="gender" class="form-control" required>
                                                <option value="">-- Pilih Gender --</option>
                                                <option value="M" <?= $old_gender == 'M' ? 'selected' : '' ?>>Pria (Male)</option>
                                                <option value="F" <?= $old_gender == 'F' ? 'selected' : '' ?>>Wanita (Female)</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Tanggal Lahir</label>
                                            <input type="date" name="birthdate" class="form-control" value="<?= htmlspecialchars($old_birthdate) ?>" required>
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
