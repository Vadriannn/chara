<?php
session_start();
$page_title = "CHARA - Data Penjualan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_kasir.php';

// 1. Set zona waktu secara eksplisit agar PHP dan MySQL sinkron
date_default_timezone_set('Asia/Jakarta');

// 2. Ambil tanggal hari ini dan tanggal 1 awal bulan ini
$hariIni = date('Y-m-d');
$awalBulan = date('Y-m-01');

// JIKA KOSONG: Mulai dari awal bulan, sampai hari ini.
$tglMulai = !empty($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : $awalBulan;
$tglSelesai = !empty($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : $hariIni;

$searchNota = isset($_GET['search_nota']) ? trim($_GET['search_nota']) : '';
$filterKasir = isset($_GET['filter_kasir']) ? $_GET['filter_kasir'] : '';
$filterMetbayar = isset($_GET['filter_metbayar']) ? $_GET['filter_metbayar'] : '';

// Ambil daftar kasir untuk dropdown filter
$stmtKasir = $koneksi->query("SELECT id, username FROM tUser ORDER BY username ASC");
$kasirList = $stmtKasir->fetchAll(PDO::FETCH_ASSOC);

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

if ($searchNota != '') {
    $where[] = "p.nomor LIKE ?";
    $params[] = "%$searchNota%";
}

if ($filterKasir != '') {
    $where[] = "p.tUser_id = ?";
    $params[] = $filterKasir;
}

if ($filterMetbayar != '') {
    $where[] = "p.metbayar = ?";
    $params[] = $filterMetbayar;
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
                            <form method="GET" class="mb-3">
                                <div class="row align-items-end">
                                    <div class="col-md-2 mb-3">
                                        <label class="font-weight-bold text-dark">Mulai Tanggal</label>
                                        <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tglMulai) ?>">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="font-weight-bold text-dark">Sampai Tanggal</label>
                                        <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tglSelesai) ?>">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="font-weight-bold text-dark">No. Nota</label>
                                        <input type="text" name="search_nota" class="form-control form-control-sm" placeholder="Cari No Nota..." value="<?= htmlspecialchars($searchNota) ?>">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="font-weight-bold text-dark">Kasir</label>
                                        <select name="filter_kasir" class="form-control form-control-sm">
                                            <option value="">Semua Kasir</option>
                                            <?php foreach($kasirList as $k): ?>
                                                <option value="<?= $k['id'] ?>" <?= ($filterKasir == $k['id']) ? 'selected' : '' ?>><?= htmlspecialchars($k['username']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="font-weight-bold text-dark">Metode Bayar</label>
                                        <select name="filter_metbayar" class="form-control form-control-sm">
                                            <option value="">Semua Metode</option>
                                            <option value="Tunai" <?= ($filterMetbayar == 'Tunai') ? 'selected' : '' ?>>Tunai</option>
                                            <option value="QRIS" <?= ($filterMetbayar == 'QRIS') ? 'selected' : '' ?>>QRIS</option>
                                            <option value="Debit" <?= ($filterMetbayar == 'Debit') ? 'selected' : '' ?>>Debit</option>
                                            <option value="Midtrans" <?= ($filterMetbayar == 'Midtrans') ? 'selected' : '' ?>>Midtrans</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <button type="submit" class="btn btn-info btn-sm btn-block mb-2">
                                            <i class="typcn typcn-zoom"></i> Filter
                                        </button>
                                        <a href="datapenjualan.php" class="btn btn-secondary btn-sm btn-block">
                                            <i class="typcn typcn-refresh"></i> Reset
                                        </a>
                                    </div>
                                </div>
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
    