<?php 
session_start(); 
$page_title = "CHARA - Tambah Karyawan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$pesan = '';
try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $role     = $_POST['role'];
        $cek = $koneksi->prepare("SELECT COUNT(*) FROM tUser WHERE username = ?");
        $cek->execute([$username]);
        if ($cek->fetchColumn() > 0) {
            $pesan = "Username sudah digunakan!";
        } else {
            $sql = "
                INSERT INTO tUser
                (username, password, tRole_id)
                VALUES
                (?, SHA1(?), ?)
            ";
            $stmt = $koneksi->prepare($sql);
            $stmt->execute([
                $username,
                $password,
                $role
            ]);
            header("Location: employee.php");
            exit;
        }
    }
    $roles = $koneksi->query("SELECT * FROM tRole ORDER BY nama");
} catch(PDOException $e) {
    $pesan = $e->getMessage();
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
            <!-- TABEL -->
             <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="card-title mb-0">
                                        Tambah User
                                    </h4>
                                    <a href="employee.php" class="btn btn-secondary">
                                        Kembali
                                    </a>
                                </div>
                                <?php if(!empty($pesan)): ?>
                                    <div class="alert alert-danger">
                                        <?= $pesan ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST">
                                    <div class="form-group">
                                        <label>Username</label>
                                        <input
                                            type="text"
                                            name="username"
                                            class="form-control"
                                            required>
                                    </div>

                                    <div class="form-group">
                                        <label>Password</label>
                                        <input
                                            type="password"
                                            name="password"
                                            class="form-control"
                                            required>
                                    </div>
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select
                                            name="role"
                                            class="form-control"
                                            required>
                                            <option value="">
                                                -- Pilih Role --
                                            </option>
                                            <?php while($role = $roles->fetch(PDO::FETCH_ASSOC)): ?>
                                                <option value="<?= $role['id']; ?>">
                                                    <?= $role['nama']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <button
                                        type="submit"
                                        class="btn btn-primary">
                                        Simpan
                                    </button>
                                    <a
                                        href="employee.php"
                                        class="btn btn-light">
                                        Batal
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