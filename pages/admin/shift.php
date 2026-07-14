<?php 
session_start(); 
$page_title = "CHARA - Master Shift";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

// AI

try {
    $sql = "SELECT * FROM tshift ORDER BY idShift";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute();
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                                        <h4 class="card-title mb-1">Master Shift Kasir</h4>
                                        <p class="text-muted mb-0">
                                            Kelola rentang waktu operasional shift kasir.
                                        </p>
                                    </div>
                                    <a href="addshift.php" class="btn btn-primary">
                                        <i class="typcn typcn-plus"></i> Tambah Shift
                                    </a>
                                </div>
                                <div class="table-responsive">
                                  <?php if(isset($_GET['success']) && $_GET['success'] == 'delete') : ?>
                                    <div class="alert alert-success">Shift berhasil dihapus.</div>
                                    <?php endif; ?>
                                    <?php if(isset($_GET['success']) && $_GET['success'] == '1') : ?>
                                    <div class="alert alert-success">Data shift berhasil disimpan.</div>
                                    <?php endif; ?>
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID Shift</th>
                                                <th>Jam Mulai</th>
                                                <th>Jam Berakhir</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($shifts) > 0): ?>
                                                <?php foreach ($shifts as $row): ?>
                                                    <tr>
                                                        <td>Shift <?= $row['idShift'] ?></td>
                                                        <td><?= $row['jamMulai'] ?></td>
                                                        <td><?= $row['jamBerakhir'] ?></td>
                                                        <td>
                                                            <a href="editshift.php?id=<?= $row['idShift'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                                            <a href="delshift.php?id=<?= $row['idShift'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus shift ini?')">Hapus</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">Belum ada data shift.</td>
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
