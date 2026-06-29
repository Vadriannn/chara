<?php
session_start();
$page_title = "CHARA - Data Penjualan";
require_once '../../koneksi.php';
require_once '../../auth.php';

// 1. Set zona waktu secara eksplisit agar PHP dan MySQL sinkron
date_default_timezone_set('Asia/Jakarta');

// 2. Ambil tanggal hari ini dan tanggal 1 awal bulan ini
$hariIni = date('Y-m-d');
$awalBulan = date('Y-m-01');

// JIKA KOSONG: Mulai dari awal bulan, sampai hari ini.
$tglMulai = !empty($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : $awalBulan;
$tglSelesai = !empty($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : $hariIni;

$query = "
    SELECT 
        p.nomor,
        p.tanggal,
        p.total,
        p.metbayar,
        u.username AS kasir
    FROM tPenjualan p
    LEFT JOIN tUser u ON p.tUser_id = u.id
";

$where = [];
$params = [];

// 4. Tambahkan kondisi filter tanggal
if ($tglMulai != '') {
    $where[] = "DATE(p.tanggal) >= ?";
    $params[] = $tglMulai;
}

if ($tglSelesai != '') {
    $where[] = "DATE(p.tanggal) <= ?";
    $params[] = $tglSelesai;
}

// 5. Gabungkan kondisi WHERE jika ada
if (count($where) > 0) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Tambahkan urutan (terbaru di atas)
$query .= " ORDER BY p.tanggal DESC";

// 6. Eksekusi query
$stmt = $koneksi->prepare($query);
$stmt->execute($params);
$dataPenjualan = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

          <div class="content-wrapper">
            
            <?php if(isset($_GET['success']) && $_GET['success'] == '1') : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Transaksi penjualan berhasil diproses!
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

              <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title text-dark mb-1">Riwayat Data Penjualan</h4>
                            <p class="text-muted mb-0">Kelola dan pantau seluruh transaksi penjualan harian.</p>
                        </div>
                        <a href="transaksipenjualan.php" class="btn btn-primary">
                            <i class="typcn typcn-plus"></i> Transaksi Baru
                        </a>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form method="GET" class="form-inline mb-2">
                                <div class="form-group mr-3">
                                    <label class="mr-2 font-weight-bold text-dark">Mulai Tanggal:</label>
                                    <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tglMulai) ?>">
                                </div>
                                <div class="form-group mr-3">
                                    <label class="mr-2 font-weight-bold text-dark">Sampai Tanggal:</label>
                                    <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tglSelesai) ?>">
                                </div>
                                <button type="submit" class="btn btn-info mr-2">
                                    <i class="typcn typcn-zoom"></i> Filter
                                </button>
                                <a href="datapenjualan.php" class="btn btn-secondary">
                                    <i class="typcn typcn-refresh"></i> Reset
                                </a>
                            </form>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                      <table class="table table-bordered">
                        <thead>
                          <tr>
                            <th class="font-weight-bold text-dark">No. Nota</th>
                            <th class="font-weight-bold text-dark">Tanggal Waktu</th>
                            <th class="font-weight-bold text-dark">Kasir</th>
                            <th class="font-weight-bold text-dark text-center">Metode Bayar</th>
                            <th class="font-weight-bold text-dark text-right">Total Belanja</th>
                            <th class="font-weight-bold text-dark text-center">Aksi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if(count($dataPenjualan) > 0): ?>
                              <?php foreach($dataPenjualan as $row): ?>
                              <tr>
                                  <td class="font-weight-bold">PJ-<?= str_pad($row['nomor'], 4, '0', STR_PAD_LEFT) ?></td>
                                  <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?> WIB</td>
                                  <td><?= htmlspecialchars($row['kasir'] ?: 'Sistem') ?></td>
                                  <td class="text-center">
                                      <span class="badge badge-outline-info font-weight-bold"><?= $row['metbayar'] ?></span>
                                  </td>
                                  <td class="text-right font-weight-bold">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                  <td class="text-center">
                                      <a href="detailpenjualan.php?nomor=<?= $row['nomor'] ?>" class="btn btn-primary btn-sm px-3">
                                        Detail
                                      </a>
                                  </td>
                              </tr>
                              <?php endforeach; ?>
                          <?php else: ?>
                              <tr>
                                  <td colspan="6" class="text-center text-muted py-4">
                                      Belum ada data transaksi penjualan pada rentang tanggal tersebut.
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
<?php 
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>
    