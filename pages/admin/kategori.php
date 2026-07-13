<?php 
session_start(); 
$page_title = "CHARA - Kategori";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';


try {

    $sql = "SELECT *
            FROM tkategori
            ORDER BY id";

    $stmt = $koneksi->prepare($sql);
    $stmt->execute();

    $kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                                        <h4 class="card-title mb-1">Kategori</h4>
                                        <p class="text-muted mb-0">
                                            Kelola kategori bahan baku yang digunakan sistem.
                                        </p>
                                    </div>
                                    <a href="addkategori.php" class="btn btn-primary">
                                        <i class="typcn typcn-plus"></i>
                                        Tambah Kategori
                                    </a>
                                </div>
                                <div class="table-responsive">
                                  <?php if(isset($_GET['success']) && $_GET['success'] == 'delete') : ?>
                                    <div class="alert alert-success">
                                        Kategori berhasil dihapus.
                                    </div>
                                    <?php endif; ?>
                                    <?php if(isset($_GET['error']) && $_GET['error'] == 'digunakan') : ?>
                                    <div class="alert alert-warning">
                                        Kategori tidak dapat dihapus karena masih digunakan oleh produk.
                                    </div>
                                    <?php endif; ?>
                                    <?php if(isset($_GET['error']) && $_GET['error'] == 'delete') : ?>
                                    <div class="alert alert-danger">
                                        Terjadi kesalahan saat menghapus kategori.
                                    </div>
                                    <?php endif; ?>
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th >No</th>
                                                <th >Nama Kategori</th>
                                                <th >Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($kategori) > 0): ?>
                                              <?php $no = 1; ?>
                                                <?php foreach ($kategori as $row): ?>
                                                    <tr>
                                                        <td><?= $no++ ?></td>
                                                        <td><?= $row['nama'] ?></td>
                                                        <td>
                                                            <a href="editkategori.php?id=<?= $row['id'] ?>"
                                                            class="btn btn-warning btn-sm">
                                                                Edit
                                                            </a>
                                                            <a href="delkategori.php?id=<?= $row['id'] ?>"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Apakah anda yakin ingin menghapus kategori ini?')">
                                                                Hapus
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted">
                                                            Belum ada kategori
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
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
<?php 
require_once '../includes/footer.php'; 
?>