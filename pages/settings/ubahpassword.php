<?php 
session_start();
$page_title = "CHARA - Ubah Password";
require_once '../../koneksi.php';
if (!isset($_SESSION['is_auth']) || $_SESSION['is_auth'] !== true) {
    header("Location: login.php");
    exit;
}
$pesan = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi'];

    $id_user = $_SESSION['id_user'];

    try {

        // Ambil password user saat ini
        $sql = "SELECT password FROM tuser WHERE id = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([$id_user]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = "User tidak ditemukan.";
        }

        elseif ($user['password'] != sha1($password_lama)) {
            $error = "Password lama salah.";
        }

        elseif ($password_baru != $konfirmasi) {
            $error = "Konfirmasi password tidak cocok.";
        }

        else {

            $sqlUpdate = "UPDATE tuser
                          SET password = ?
                          WHERE id = ?";

            $stmtUpdate = $koneksi->prepare($sqlUpdate);
            $stmtUpdate->execute([
                sha1($password_baru),
                $id_user
            ]);
            
            catatLog($koneksi, "Ubah Password", "Mengubah password akun sendiri", "Pengaturan Akun", $id_user);

            $pesan = "Password berhasil diubah.";
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
                    <div class="col-md-6 mx-auto">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">
                                    Ubah Password
                                </h4>
                                <?php if ($pesan != "") : ?>
                                    <div class="alert alert-success">
                                        <?= $pesan ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($error != "") : ?>
                                    <div class="alert alert-danger">
                                        <?= $error ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST">
                                    <div class="form-group">
                                        <label>Password Lama</label>
                                        <input
                                            type="password"
                                            name="password_lama"
                                            class="form-control"
                                            required>
                                    </div>
                                    <div class="form-group">
                                        <label>Password Baru</label>
                                        <input
                                            type="password"
                                            name="password_baru"
                                            class="form-control"
                                            required>
                                    </div>
                                    <div class="form-group">
                                        <label>Konfirmasi Password Baru</label>
                                        <input
                                            type="password"
                                            name="konfirmasi"
                                            class="form-control"
                                            required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        Simpan Perubahan
                                    </button>
                                    <a href="../index.php" class="btn btn-secondary">
                                        Kembali
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php require_once '../includes/footer.php'; ?>