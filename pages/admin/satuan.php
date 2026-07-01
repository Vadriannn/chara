<?php 
session_start(); 
$page_title = "CHARA - Satuan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';


try {

    $sql = "SELECT *
            FROM tsatuan
            ORDER BY id";

    $stmt = $koneksi->prepare($sql);
    $stmt->execute();

    $satuan = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                                        <h4 class="card-title mb-1">Satuan</h4>
                                        <p class="text-muted mb-0">
                                            Kelola satuan bahan baku dan produk yang digunakan sistem.
                                        </p>
                                    </div>
                                    <a href="addsatuan.php" class="btn btn-primary">
                                        <i class="typcn typcn-plus"></i>
                                        Tambah Satuan
                                    </a>
                                </div>
                                <div class="table-responsive">
                                  <?php if(isset($_GET['success']) && $_GET['success'] == 'delete') : ?>
                                    <div class="alert alert-success">
                                        Satuan berhasil dihapus.
                                    </div>
                                    <?php endif; ?>
                                    <?php if(isset($_GET['error']) && $_GET['error'] == 'digunakan') : ?>
                                    <div class="alert alert-warning">
                                        Satuan tidak dapat dihapus karena masih digunakan.
                                    </div>
                                    <?php endif; ?>
                                    <?php if(isset($_GET['error']) && $_GET['error'] == 'delete') : ?>
                                    <div class="alert alert-danger">
                                        Terjadi kesalahan saat menghapus satuan.
                                    </div>
                                    <?php endif; ?>
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th >No</th>
                                                <th >Nama Satuan</th>
                                                <th >Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($satuan) > 0): ?>
                                              <?php $no = 1; ?>
                                                <?php foreach ($satuan as $row): ?>
                                                    <tr>
                                                        <td><?= $no++ ?></td>
                                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                                        <td>
                                                            <a href="editsatuan.php?id=<?= $row['id'] ?>"
                                                            class="btn btn-warning btn-sm">
                                                                Edit
                                                            </a>
                                                            <a href="delsatuan.php?id=<?= $row['id'] ?>"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Apakah anda yakin ingin menghapus satuan ini?')">
                                                                Hapus
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted">
                                                            Belum ada satuan
                                                        </td>
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
<?php 
require_once '../includes/footer.php'; 
?>
