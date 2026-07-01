<?php 
session_start(); 
$page_title = "CHARA - Daftar Member";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

try {
    $sql = "SELECT * FROM tmember ORDER BY JoinDate DESC";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h4 class="card-title mb-1">Data Member</h4>
                                        <p class="text-muted mb-0">
                                            Kelola pelanggan setia (Member) dan poin mereka.
                                        </p>
                                    </div>
                                    <a href="addmember.php" class="btn btn-primary">
                                        <i class="typcn typcn-plus"></i>
                                        Tambah Member
                                    </a>
                                </div>
                                <div class="table-responsive">
                                  <?php if(isset($_GET['success']) && $_GET['success'] == 'delete') : ?>
                                    <div class="alert alert-success">Member berhasil dihapus.</div>
                                    <?php endif; ?>
                                    <?php if(isset($_GET['success']) && $_GET['success'] == '1') : ?>
                                    <div class="alert alert-success">Data Member berhasil disimpan.</div>
                                    <?php endif; ?>
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>No. HP (ID)</th>
                                                <th>Nama Lengkap</th>
                                                <th>Gender</th>
                                                <th>Tgl Lahir</th>
                                                <th>Tgl Bergabung</th>
                                                <th>Poin Aktif</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($members) > 0): ?>
                                                <?php foreach ($members as $row): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['noHp']) ?></td>
                                                        <td class="font-weight-bold"><?= htmlspecialchars($row['Nama']) ?></td>
                                                        <td><?= $row['Gender'] == 'M' ? 'Pria (M)' : 'Wanita (F)' ?></td>
                                                        <td><?= $row['BirthDate'] ?></td>
                                                        <td><?= $row['JoinDate'] ?></td>
                                                        <td class="text-success font-weight-bold"><?= number_format($row['Poin'], 0, ',', '.') ?> Poin</td>
                                                        <td>
                                                            <a href="editmember.php?id=<?= $row['noHp'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                                            <a href="delmember.php?id=<?= $row['noHp'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus member ini?')">Hapus</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted">Belum ada data member.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                      </table>
                                </div>
                            </div>
                        </div>
                    </div>
                  </div>
            </div>
<?php require_once '../includes/footer.php'; ?>
