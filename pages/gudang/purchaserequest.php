<?php 
session_start(); 
$page_title = "CHARA - Purchase Request";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_gudang.php';

try {
    $sql = "
    SELECT
        pr.id,
        pr.tanggal,
        pr.status,
        pr.tanggalApprove,
        pr.tanggalReject,
        u.username
    FROM tPurchaseRequest pr
    INNER JOIN tUser u
        ON pr.reqBy = u.id
    ORDER BY pr.tanggal DESC
";

    $stmt = $koneksi->prepare($sql);
    $stmt->execute();
    $pr = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                                        <h4 class="card-title mb-1">Purchase Request</h4>
                                        <p class="text-muted mb-0">
                                            Kelola purchase request bahan baku.
                                        </p>
                                    </div>
                                    <a href="addpurchaserequest.php" class="btn btn-primary">
                                        <i class="typcn typcn-plus"></i>
                                        Tambah Purchase Request
                                    </a>
                                </div>
                                <div class="table-responsive">
                                  <?php if(isset($_GET['success']) && $_GET['success'] == 'delete') : ?>
                                    <div class="alert alert-success">
                                        Bahan berhasil dihapus.
                                    </div>
                                    <?php endif; ?>
                                    <?php if(isset($_GET['error']) && $_GET['error'] == 'digunakan') : ?>
                                    <div class="alert alert-warning">
                                        Bahan tidak dapat dihapus karena masih digunakan oleh produk.
                                    </div>
                                    <?php endif; ?>
                                    <?php if(isset($_GET['error']) && $_GET['error'] == 'delete') : ?>
                                    <div class="alert alert-danger">
                                        Terjadi kesalahan saat menghapus bahan.
                                    </div>
                                    <?php endif; ?>
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID PR</th>
                                            <th>Tanggal Pengajuan</th>
                                            <th>Pengaju</th>
                                            <th>Status</th>
                                            <th>Tanggal Proses</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if(count($pr) > 0): ?>
                                        <?php foreach($pr as $row): ?>
                                        <tr>
                                          <td><?= $row['id'] ?></td>
                                          <td>
                                              <?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?>
                                          </td>
                                          <td><?= $row['username'] ?></td>
                                          <td>
                                              <?php
                                              if($row['status'] == 'Pending'){
                                                  echo '<span class="badge badge-warning">Pending</span>';
                                              }
                                              elseif($row['status'] == 'Approved'){
                                                  echo '<span class="badge badge-success">Approved</span>';
                                              }
                                              else{
                                                  echo '<span class="badge badge-danger">Rejected</span>';
                                              }
                                              ?>
                                          </td>

                                          <td>
                                              <?php
                                              if(
                                                  $row['status'] == 'Approved'
                                                  && !empty($row['tanggalApprove'])
                                              ){
                                                  echo date(
                                                      'd/m/Y H:i',
                                                      strtotime($row['tanggalApprove'])
                                                  );
                                              }
                                              elseif(
                                                  $row['status'] == 'Rejected'
                                                  && !empty($row['tanggalReject'])
                                              ){
                                                  echo date(
                                                      'd/m/Y H:i',
                                                      strtotime($row['tanggalReject'])
                                                  );
                                              }
                                              else{
                                                  echo '-';
                                              }
                                              ?>
                                          </td>

                                          <td>
                                              <a href="detailpurchaserequest.php?id=<?= $row['id'] ?>"
                                                  class="btn btn-info btn-sm">
                                                  Detail
                                              </a>
                                          </td>
                                      </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                Belum ada Purchase Request
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