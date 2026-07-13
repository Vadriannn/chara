<?php
session_start();
$page_title = "CHARA - Daftar Produk";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

try {
    $sql = "
        SELECT
            p.kode,
            p.nama,
            p.hargaJual,
            k.nama AS kategori,
            p.status as status
        FROM tproduct p
        LEFT JOIN tkategori k
        ON p.tKategori_id = k.id
        ORDER BY p.kode
    ";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute();
    $produk = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error : " . $e->getMessage());
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
                                        <h4 class="card-title mb-1">Produk</h4>
                                        <p class="text-muted mb-0">
                                            Kelola Data Produk.
                                        </p>
                                    </div>
                                    <a href="addproduk.php" class="btn btn-primary">
                                        <i class="typcn typcn-plus"></i>
                                        Tambah Produk
                                    </a>
                                </div>
                                        <?php if(isset($_GET['success']) && $_GET['success'] == 'add') : ?>
                                            <div class="alert alert-success">
                                                Produk berhasil ditambahkan.
                                            </div>
                                        <?php endif; ?>
                                        <?php if(isset($_GET['success']) && $_GET['success'] == 'edit') : ?>
                                            <div class="alert alert-success">
                                                Produk berhasil diubah.
                                            </div>
                                        <?php endif; ?>
                                        <?php if(isset($_GET['success']) && $_GET['success'] == 'delete') : ?>
                                            <div class="alert alert-success">
                                                Produk berhasil dihapus.
                                            </div>
                                        <?php endif; ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">No</th>
                                                        <th width="15%">Kode</th>
                                                        <th>Nama Produk</th>
                                                        <th>Kategori</th>
                                                        <th>Harga Jual</th>
                                                        <th> Status </th>
                                                        <th width="20%">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php if(count($produk) > 0): ?>
                                                    <?php $no = 1; ?>
                                                    <?php foreach($produk as $row): ?>
                                                    <tr>
                                                        <td><?= $no++ ?></td>
                                                        <td><?= $row['kode'] ?></td>
                                                        <td><?= $row['nama'] ?></td>
                                                        <td><?= $row['kategori'] ?></td>
                                                        <td>
                                                            Rp <?= number_format($row['hargaJual'], 0, ',', '.') ?>
                                                        </td>
                                                        <td><?= $row['status'] ?></td>
                                                        <td>
                                                            <a href="editproduk.php?kode=<?= $row['kode'] ?>"
                                                            class="btn btn-warning btn-sm">
                                                                Edit
                                                            </a>
                                                            <a href="delproduk.php?kode=<?= $row['kode'] ?>"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Apakah anda yakin ingin menghapus produk ini?')">
                                                                Hapus
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted">
                                                            Belum ada produk
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