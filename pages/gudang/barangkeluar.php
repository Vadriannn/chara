<?php 
session_start(); 
$page_title = "CHARA - Barang Keluar (Penjualan)";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_gudang.php';

try {
    date_default_timezone_set('Asia/Jakarta');
    $hariIni = date('Y-m-d');
    $tglMulai = !empty($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : $hariIni;
    $tglSelesai = !empty($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : $hariIni;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $query = "
        SELECT
            m.tanggal,
            m.referensi,
            m.qty,
            m.stokSebelum,
            m.stokSesudah,
            b.nama AS nama_bahan,
            s.nama AS satuan,
            u.username AS kasir
        FROM tMutasiStok m
        JOIN tbahan b ON m.tBahan_kode = b.kode
        JOIN tsatuan s ON b.tSatuan_id = s.id
        LEFT JOIN tuser u ON m.tUser_id = u.id
        WHERE m.jenis = 'Penjualan'
          AND DATE(m.tanggal) >= :tgl_mulai AND DATE(m.tanggal) <= :tgl_selesai
    ";
    
    $params = [
        ':tgl_mulai' => $tglMulai,
        ':tgl_selesai' => $tglSelesai
    ];
    if ($search != '') {
        $query .= " AND (m.referensi LIKE :search OR b.nama LIKE :search) ";
        $params['search'] = "%$search%";
    }
    
    $query .= " ORDER BY m.tanggal DESC";
    
    $stmt = $koneksi->prepare($query);
    $stmt->execute($params);
    $mutasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query untuk rekap total penggunaan bahan baku
    $queryRekap = "
        SELECT
            b.nama AS nama_bahan,
            s.nama AS satuan,
            SUM(m.qty) AS total_qty
        FROM tMutasiStok m
        JOIN tbahan b ON m.tBahan_kode = b.kode
        JOIN tsatuan s ON b.tSatuan_id = s.id
        WHERE m.jenis = 'Penjualan'
          AND DATE(m.tanggal) >= :tgl_mulai AND DATE(m.tanggal) <= :tgl_selesai
    ";
    
    $paramsRekap = [
        ':tgl_mulai' => $tglMulai,
        ':tgl_selesai' => $tglSelesai
    ];
    if ($search != '') {
        $queryRekap .= " AND (m.referensi LIKE :search OR b.nama LIKE :search) ";
        $paramsRekap['search'] = "%$search%";
    }
    
    $queryRekap .= " GROUP BY b.kode ORDER BY total_qty DESC";
    
    $stmtRekap = $koneksi->prepare($queryRekap);
    $stmtRekap->execute($paramsRekap);
    $rekapPenggunaan = $stmtRekap->fetchAll(PDO::FETCH_ASSOC);

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
                                        <h4 class="card-title mb-1">History Pengurangan Stok</h4>
                                        <p class="text-muted mb-0">
                                            Catatan barang keluar yang diakibatkan oleh transaksi penjualan kasir.
                                        </p>
                                    </div>
                                </div>
                                <form method="GET" class="mb-4 w-100">
                                    <div class="row align-items-end">
                                        <div class="col-md-3 mb-3">
                                            <label class="font-weight-bold text-dark">Mulai Tanggal</label>
                                            <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tglMulai) ?>">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="font-weight-bold text-dark">Sampai Tanggal</label>
                                            <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tglSelesai) ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="font-weight-bold text-dark">Pencarian</label>
                                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nota / nama bahan..." value="<?= htmlspecialchars($search) ?>">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <button type="submit" class="btn btn-info btn-sm mb-2">Filter</button>
                                            <a href="barangkeluar.php" class="btn btn-secondary btn-sm mb-2">Reset</a>
                                        </div>
                                    </div>
                                </form>

                                <h5 class="mb-3 font-weight-bold text-dark">Rekap Total Penggunaan Bahan Baku</h5>
                                <?php if(count($rekapPenggunaan) > 0): ?>
                                    <div class="row mb-4">
                                        <?php foreach($rekapPenggunaan as $rekap): ?>
                                            <div class="col-md-3 mb-3">
                                                <div class="card bg-light border-0 h-100">
                                                    <div class="card-body py-3 px-4">
                                                        <p class="mb-1 text-muted font-weight-bold" style="font-size: 0.85rem;"><?= htmlspecialchars($rekap['nama_bahan']) ?></p>
                                                        <h4 class="mb-0 text-dark">
                                                            <?= (float)$rekap['total_qty'] ?> <small style="font-size: 0.8rem; font-weight: normal;"><?= htmlspecialchars($rekap['satuan']) ?></small>
                                                        </h4>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted mb-4 pb-2 border-bottom">Belum ada penggunaan pada periode ini.</p>
                                <?php endif; ?>

                                <h5 class="mb-3 font-weight-bold text-dark mt-2">Rincian History Pengeluaran</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                      <thead>
                                          <tr>
                                              <th width="5%">No</th>
                                              <th width="15%">Tanggal Waktu</th>
                                              <th width="15%">No. Referensi (Nota)</th>
                                              <th>Nama Bahan Baku</th>
                                              <th class="text-center">Stok Awal</th>
                                              <th class="text-center text-danger">Pengurangan (Qty)</th>
                                              <th class="text-center">Sisa Stok</th>
                                          </tr>
                                      </thead>
                                      <tbody>
                                          <?php if(count($mutasi) > 0): ?>
                                              <?php $no = 1; foreach($mutasi as $row): ?>
                                              <tr>
                                                  <td><?= $no++ ?></td>
                                                  <td><?= date('d-m-Y H:i', strtotime($row['tanggal'])) ?></td>
                                                  <td class="font-weight-bold">#<?= htmlspecialchars($row['referensi']) ?></td>
                                                  <td class="font-weight-bold"><?= htmlspecialchars($row['nama_bahan']) ?></td>
                                                  <td class="text-center text-muted">
                                                      <?= (float)$row['stokSebelum'] ?> 
                                                      <small><?= htmlspecialchars($row['satuan']) ?></small>
                                                  </td>
                                                  <td class="text-center font-weight-bold text-danger">
                                                      - <?= (float)$row['qty'] ?> 
                                                      <small><?= htmlspecialchars($row['satuan']) ?></small>
                                                  </td>
                                                  <td class="text-center font-weight-bold">
                                                      <?= (float)$row['stokSesudah'] ?> 
                                                      <small><?= htmlspecialchars($row['satuan']) ?></small>
                                                  </td>
                                              </tr>
                                              <?php endforeach; ?>
                                          <?php else: ?>
                                              <tr>
                                                  <td colspan="8" class="text-center py-4 text-muted">Belum ada history pengeluaran barang.</td>
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
