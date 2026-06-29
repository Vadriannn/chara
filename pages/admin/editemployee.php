<?php

session_start();
$page_title = "CHARA - Edit Karyawan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

if (!isset($_GET['id'])) {
    header('location:employee.php');
    exit;
}

$id = (int)$_GET['id'];
$pesan = '';
try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id       = $_POST['id'];
        $username = trim($_POST['username']);
        $role     = $_POST['role'];
        $password = trim($_POST['password']);
        // cek username dipakai user lain
        $cek = $koneksi->prepare("
            SELECT COUNT(*)
            FROM tUser
            WHERE username = ?
            AND id <> ?
        ");
        $cek->execute([$username, $id]);
        if ($id == $_SESSION['id_user']) {
            $_SESSION['nama'] = $username;
        }
        if ($cek->fetchColumn() > 0) {
            $pesan = "Username sudah digunakan!";
        } else {
            // password diisi
            if (!empty($password)) {
                $sql = "
                    UPDATE tUser
                    SET
                        username = ?,
                        password = SHA1(?),
                        tRole_id = ?
                    WHERE id = ?
                ";
                $stmt = $koneksi->prepare($sql);
                $stmt->execute([
                    $username,
                    $password,
                    $role,
                    $id
                ]);
                if ($id == $_SESSION['id_user']) {
                    $_SESSION['nama'] = $username;
                }
            }
            else {
                $sql = "
                    UPDATE tUser
                    SET
                        username = ?,
                        tRole_id = ?
                    WHERE id = ?
                ";

                $stmt = $koneksi->prepare($sql);

                $stmt->execute([
                    $username,
                    $role,
                    $id
                ]);
            }
            header('location:employee.php');
            exit;
        }
    }
    $sql = "
        SELECT *
        FROM tUser
        WHERE id = ?
    ";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        header('location:employee.php');
        exit;
    }
    $roles = $koneksi->query("
        SELECT *
        FROM tRole
        ORDER BY nama
    ");
}
catch(PDOException $e){
    $pesan = $e->getMessage();
}
$nama = $_SESSION['nama'];
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
          <div class="content-wrapper">
            <div class="row">
                <div class="col-lg-8 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="card-title mb-0">
                                    Edit User
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
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $user['id']; ?>">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input
                                        type="text"
                                        name="username"
                                        class="form-control"
                                        value="<?= htmlspecialchars($user['username']); ?>"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label>Password Baru</label>
                                    <input
                                        type="password"
                                        name="password"
                                        class="form-control">
                                    <small class="text-muted">
                                        Kosongkan jika tidak ingin mengganti password
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label>Role</label>
                                    <select
                                        name="role"
                                        class="form-control"
                                        required>
                                        <?php while($role = $roles->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option
                                                value="<?= $role['id']; ?>"
                                                <?= ($role['id'] == $user['tRole_id']) ? 'selected' : ''; ?>>
                                                <?= $role['nama']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <button
                                    type="submit"
                                    class="btn btn-warning">
                                    Update
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