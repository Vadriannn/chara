<?php 
session_start(); 
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

try {
    $sql = "
        SELECT
            p.kode,
            p.nama,
            COUNT(r.tBahan_kode) AS jumlah_bahan
        FROM tproduct p
        INNER JOIN tresep r
            ON p.kode = r.tProduct_kode
        GROUP BY p.kode, p.nama
        ORDER BY p.nama
    ";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute();
    $dataResep = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="card-title mb-0">
                                        Daftar Resep Produk
                                    </h4>
                                </div>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Kode Produk</th>
                                            <th>Nama Produk</th>
                                            <th>Jumlah Bahan</th>
                                            <th width="120">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($dataResep as $row): ?>
                                    <tr>
                                        <td><?= $row['kode']; ?></td>
                                        <td><?= $row['nama']; ?></td>
                                        <td><?= $row['jumlah_bahan']; ?> Bahan</td>
                                        <td>
                                          <a
                                              href="detailresep.php?kode=<?= $row['kode']; ?>"
                                              class="btn btn-info btn-sm">
                                              Detail
                                          </a>
                                          <a
                                              href="editresep.php?kode=<?= $row['kode']; ?>"
                                              class="btn btn-warning btn-sm">
                                              Edit
                                          </a>
                                      </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div> <!-- card -->
                    </div> <!-- col-lg-12 -->
                </div> <!-- row -->
             </div> <!-- content-wrapper -->
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
<?php 
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>