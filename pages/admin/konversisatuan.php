<?php 
session_start(); 
$page_title = "CHARA - Konversi Satuan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';


try {
    $sql = "SELECT k.SatuanBesar_id, k.SatuanKecil_id, k.Konversi,
                   sb.nama as SatuanBesar, sk.nama as SatuanKecil
            FROM tkonversisatuan k
            JOIN tsatuan sb ON k.SatuanBesar_id = sb.id
            JOIN tsatuan sk ON k.SatuanKecil_id = sk.id
            ORDER BY sb.nama";

    $stmt = $koneksi->prepare($sql);
    $stmt->execute();

    $konversi = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                                        <h4 class="card-title mb-1">Konversi Satuan</h4>
                                        <p class="text-muted mb-0">
                                            Kelola master data konversi satuan bahan baku.
                                        </p>
                                    </div>
                                    <a href="addkonversi.php" class="btn btn-primary">
                                        <i class="typcn typcn-plus"></i>
                                        Tambah Konversi
                                    </a>
                                </div>
                                <div class="table-responsive">
                                  <?php if(isset($_GET['success']) && $_GET['success'] == 'delete') : ?>
                                    <div class="alert alert-success">
                                        Data konversi berhasil dihapus.
                                    </div>
                                    <?php endif; ?>
                                    <?php if(isset($_GET['success']) && $_GET['success'] == '1') : ?>
                                    <div class="alert alert-success">
                                        Data konversi berhasil ditambahkan/diubah.
                                    </div>
                                    <?php endif; ?>
                                    <?php if(isset($_GET['error']) && $_GET['error'] == 'delete') : ?>
                                    <div class="alert alert-danger">
                                        Terjadi kesalahan saat menghapus data.
                                    </div>
                                    <?php endif; ?>
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Satuan Besar</th>
                                                <th>Satuan Kecil</th>
                                                <th>Nilai Konversi</th>
                                                <th>Deskripsi</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($konversi) > 0): ?>
                                              <?php $no = 1; ?>
                                                <?php foreach ($konversi as $row): ?>
                                                    <tr>
                                                        <td><?= $no++ ?></td>
                                                        <td><?= $row['SatuanBesar'] ?></td>
                                                        <td><?= $row['SatuanKecil'] ?></td>
                                                        <td><?= $row['Konversi'] ?></td>
                                                        <td>1 <?= $row['SatuanBesar'] ?> = <?= $row['Konversi'] ?> <?= $row['SatuanKecil'] ?></td>
                                                        <td>
                                                            <a href="editkonversi.php?b=<?= $row['SatuanBesar_id'] ?>&k=<?= $row['SatuanKecil_id'] ?>"
                                                            class="btn btn-warning btn-sm">
                                                                Edit
                                                            </a>
                                                            <a href="delkonversi.php?b=<?= $row['SatuanBesar_id'] ?>&k=<?= $row['SatuanKecil_id'] ?>"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Apakah anda yakin ingin menghapus data konversi ini?')">
                                                                Hapus
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">
                                                            Belum ada data konversi satuan
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
