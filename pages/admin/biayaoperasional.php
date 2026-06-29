<?php
session_start();
$page_title = "CHARA - Biaya Operasional";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

try {
    $sql = "
        SELECT
            b.id,
            b.tanggal,
            b.keterangan,
            b.nominal,
            k.jenis as kategori,
            u.username as username
        FROM tBiayaOperasional b
        LEFT JOIN tKategoriBiaya k
            ON b.tKategoriBiaya_id = k.id
        LEFT JOIN tUser u
            ON b.tUser_id = u.id
        ORDER BY b.tanggal DESC
    ";

    $stmt = $koneksi->prepare($sql);
    $stmt->execute();
    $biaya = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
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
                                        <h4 class="card-title mb-1">Biaya Operasional</h4>
                                        <p class="text-muted mb-0">
                                            Kelola Data Biaya Operasional.
                                        </p>
                                    </div>

                                    <a href="addbiaya.php" class="btn btn-primary">
                                        <i class="typcn typcn-plus"></i>
                                        Tambah Biaya
                                    </a>
                                </div>

                                <?php if(isset($_GET['success']) && $_GET['success'] == 'add'): ?>
                                    <div class="alert alert-success">
                                        Data biaya operasional berhasil ditambahkan.
                                    </div>
                                <?php endif; ?>

                                <?php if(isset($_GET['success']) && $_GET['success'] == 'edit'): ?>
                                    <div class="alert alert-success">
                                        Data biaya operasional berhasil diubah.
                                    </div>
                                <?php endif; ?>

                                <?php if(isset($_GET['success']) && $_GET['success'] == 'delete'): ?>
                                    <div class="alert alert-success">
                                        Data biaya operasional berhasil dihapus.
                                    </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th width="5%">No</th>
                                                <th width="18%">Tanggal</th>
                                                <th>Kategori</th>
                                                <th>Keterangan</th>
                                                <th>Nominal</th>
                                                <th>User</th>
                                                <th width="18%">Aksi</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php if(count($biaya) > 0): ?>
                                                <?php $no = 1; ?>
                                                <?php foreach($biaya as $row): ?>
                                                <tr>
                                                    <td><?= $no++ ?></td>

                                                    <td>
                                                        <?= date('d-m-Y H:i', strtotime($row['tanggal'])) ?>
                                                    </td>

                                                    <td>
                                                        <?= $row['kategori'] ?>
                                                    </td>

                                                    <td>
                                                        <?= $row['keterangan'] ?>
                                                    </td>

                                                    <td>
                                                        Rp <?= number_format($row['nominal'],0,',','.') ?>
                                                    </td>

                                                    <td>
                                                        <?= $row['username'] ?>
                                                    </td>

                                                    <td>
                                                        <a href="editbiaya.php?id=<?= $row['id'] ?>"
                                                            class="btn btn-warning btn-sm">
                                                            Edit
                                                        </a>

                                                        <a href="delbiaya.php?id=<?= $row['id'] ?>"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Apakah yakin ingin menghapus data ini?')">
                                                            Hapus
                                                        </a>
                                                    </td>

                                                </tr>
                                                <?php endforeach; ?>

                                            <?php else: ?>

                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">
                                                        Belum ada data biaya operasional.
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
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>