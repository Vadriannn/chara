<?php 
session_start(); 
$page_title = "CHARA - Stok Bahan Baku";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_gudang.php';

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $sql = "
        SELECT
            b.kode,
            b.nama,
            b.stok,
            s.nama AS satuan
        FROM tbahan b
        JOIN tsatuan s
            ON b.tSatuan_id = s.id
        WHERE b.nama LIKE :search OR b.kode LIKE :search
        ORDER BY b.nama ASC
        ";
        
    $stmt = $koneksi->prepare($sql);
    $stmt->execute(['search' => "%$search%"]);
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
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h4 class="card-title mb-1">Stok Bahan Baku</h4>
                                        <p class="text-muted mb-0">
                                            Pantau ketersediaan stok bahan baku di gudang.
                                        </p>
                                    </div>
                                    <form method="GET" class="form-inline">
                                        <div class="input-group">
                                            <input type="text" name="search" class="form-control" placeholder="Cari nama bahan..." value="<?= htmlspecialchars($search) ?>">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">Cari</button>
                                                <?php if($search != ''): ?>
                                                    <a href="bahanbaku.php" class="btn btn-secondary">Reset</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                      <thead>
                                          <tr>
                                              <th width="5%">No</th>
                                              <th width="10%">Kode</th>
                                              <th>Nama Bahan Baku</th>
                                              <th width="15%">Satuan</th>
                                              <th width="15%">Jumlah Stok</th>
                                              <th width="20%" class="text-center">Aksi</th>
                                          </tr>
                                      </thead>
                                      <tbody>
                                          <?php if(count($bahan) > 0): ?>
                                              <?php $no = 1; foreach($bahan as $row): 
                                                    $stokVal = (float)$row['stok'];
                                                    $isLowStock = $stokVal < 10; 
                                              ?>
                                              <tr <?= $isLowStock ? 'class="table-warning"' : '' ?>>
                                                  <td><?= $no++ ?></td>
                                                  <td><?= htmlspecialchars($row['kode']) ?></td>
                                                  <td class="font-weight-bold"><?= htmlspecialchars($row['nama']) ?></td>
                                                  <td><?= htmlspecialchars($row['satuan']) ?></td>
                                                  <td>
                                                      <span class="font-weight-bold <?= $isLowStock ? 'text-danger' : '' ?>">
                                                          <?= rtrim(rtrim($row['stok'], '0'), '.') ?>
                                                      </span>
                                                      <?php if($isLowStock): ?>
                                                          <span class="badge badge-danger ml-2">Menipis</span>
                                                      <?php endif; ?>
                                                  </td>
                                                  <td class="text-center">
                                                      <a href="addpurchaserequest.php?bahan=<?= urlencode($row['kode']) ?>" class="btn btn-primary btn-sm">
                                                          <i class="typcn typcn-shopping-cart"></i> Buat PR
                                                      </a>
                                                  </td>
                                              </tr>
                                              <?php endforeach; ?>
                                          <?php else: ?>
                                              <tr>
                                                  <td colspan="6" class="text-center py-4 text-muted">Bahan baku tidak ditemukan.</td>
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
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>
