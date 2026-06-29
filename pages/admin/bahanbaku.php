<?php 
session_start(); 
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

try {

    $sql = "
        SELECT
            b.kode,
            b.nama,
            b.stok,
            b.harga,
            s.nama AS satuan
        FROM tbahan b
        INNER JOIN tsatuan s
            ON b.tSatuan_id = s.id
        ORDER BY b.kode ASC
        ";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute();
        $bahan = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                                        <h4 class="card-title mb-1">Bahan</h4>
                                        <p class="text-muted mb-0">
                                            Kelola bahan yang digunakan sistem.
                                        </p>
                                    </div>
                                    <a href="addbahan.php" class="btn btn-primary">
                                        <i class="typcn typcn-plus"></i>
                                        Tambah Bahan
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
                                              <th>Kode</th>
                                              <th>Nama Bahan</th>
                                              <th>Satuan</th>
                                              <th>Stok</th>
                                              <th>Harga Satuan Rata-Rata</th>
                                              <th>Aksi</th>
                                          </tr>
                                      </thead>
                                      <tbody>
                                          <?php if(count($bahan) > 0): ?>
                                              <?php foreach($bahan as $row): ?>
                                              <tr>
                                                  <td><?= $row['kode'] ?></td>
                                                  <td><?= $row['nama'] ?></td>
                                                  <td><?= $row['satuan'] ?></td>
                                                  <td><?= rtrim(rtrim($row['stok'], '0'), '.') ?></td>
                                                  <td>
                                                      Rp <?= number_format($row['harga'], 0, ',', '.') ?> <small class="text-muted">/ <?= $row['satuan'] ?></small>
                                                      
                                                      <?php if(strtolower($row['satuan']) == 'kg' || strtolower($row['satuan']) == 'liter'): ?>
                                                          <br>
                                                          <small class="text-info">
                                                              (Rp <?= number_format($row['harga'] / 1000, 2, ',', '.') ?> / <?= strtolower($row['satuan']) == 'kg' ? 'Gram' : 'Ml' ?>)
                                                          </small>
                                                      <?php endif; ?>
                                                  </td>
                                                  <td>
                                                      <a href="editbahan.php?kode=<?= $row['kode'] ?>"
                                                        class="btn btn-warning btn-sm">
                                                          Edit
                                                      </a>
                                                      <a href="delbahan.php?kode=<?= $row['kode'] ?>"
                                                        class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Apakah anda yakin ingin menghapus bahan ini?')">
                                                          Hapus
                                                      </a>
                                                  </td>
                                              </tr>
                                              <?php endforeach; ?>
                                          <?php else: ?>
                                              <tr>
                                                  <td colspan="6" class="text-center text-muted">
                                                      Belum ada bahan
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