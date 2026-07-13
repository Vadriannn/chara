<?php
session_start();
$page_title = "CHARA - Arus Kas";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

date_default_timezone_set('Asia/Jakarta');
$hariIni = date('Y-m-d');
$awalBulan = date('Y-m-01');

$tglMulai = !empty($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : $awalBulan;
$tglSelesai = !empty($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : $hariIni;

// Hitung saldo awal (sebelum tgl_mulai)
$saldoAwal = 0;
try {
    $qSaldoAwal = "
        SELECT 
            SUM(CASE WHEN jenis = 'Masuk' THEN nominal ELSE 0 END) - 
            SUM(CASE WHEN jenis = 'Keluar' THEN nominal ELSE 0 END) AS saldo_awal
        FROM taruskas
        WHERE DATE(tanggal) < ?
    ";
    $stmtAwal = $koneksi->prepare($qSaldoAwal);
    $stmtAwal->execute([$tglMulai]);
    $resAwal = $stmtAwal->fetch(PDO::FETCH_ASSOC);
    if ($resAwal && $resAwal['saldo_awal']) {
        $saldoAwal = $resAwal['saldo_awal'];
    }
} catch (Exception $e) {}

// Ambil transaksi pada periode dari tabel tArusKas
$query = "
    SELECT 
        tanggal, 
        sumber AS keterangan, 
        jenis AS tipe, 
        nominal
    FROM taruskas
    WHERE DATE(tanggal) >= ? AND DATE(tanggal) <= ?
    ORDER BY tanggal ASC
";

$stmt = $koneksi->prepare($query);
$stmt->execute([$tglMulai, $tglSelesai]);
$aruskas = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-dark mb-1">Laporan Arus Kas</h4>
                    <p class="text-muted mb-4">Pantau aliran kas masuk dari penjualan dan keluar dari pembelian.</p>
                    
                    <form method="GET" class="mb-4">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold text-dark">Mulai Tanggal</label>
                                <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tglMulai) ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold text-dark">Sampai Tanggal</label>
                                <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tglSelesai) ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <button type="submit" class="btn btn-info btn-sm mb-2">
                                    <i class="typcn typcn-zoom"></i> Filter
                                </button>
                                <a href="aruskas.php" class="btn btn-secondary btn-sm mb-2">
                                    <i class="typcn typcn-refresh"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="15%">Tanggal</th>
                                    <th>Keterangan</th>
                                    <th class="text-right text-success">Masuk (Debit)</th>
                                    <th class="text-right text-danger">Keluar (Kredit)</th>
                                    <th class="text-right">Saldo Berjalan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-right font-weight-bold">SALDO AWAL SEBELUM PERIODE</td>
                                    <td class="text-right font-weight-bold">Rp <?= number_format($saldoAwal, 0, ',', '.') ?></td>
                                </tr>
                                <?php if(count($aruskas) > 0): ?>
                                    <?php 
                                    $no = 1; 
                                    $saldo = $saldoAwal;
                                    $totalMasuk = 0;
                                    $totalKeluar = 0;
                                    foreach($aruskas as $row): 
                                        if($row['tipe'] == 'Masuk'){
                                            $saldo += $row['nominal'];
                                            $totalMasuk += $row['nominal'];
                                        } else {
                                            $saldo -= $row['nominal'];
                                            $totalKeluar += $row['nominal'];
                                        }
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('d-m-Y H:i', strtotime($row['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                        <td class="text-right text-success font-weight-bold">
                                            <?= $row['tipe'] == 'Masuk' ? 'Rp ' . number_format($row['nominal'], 0, ',', '.') : '-' ?>
                                        </td>
                                        <td class="text-right text-danger font-weight-bold">
                                            <?= $row['tipe'] == 'Keluar' ? 'Rp ' . number_format($row['nominal'], 0, ',', '.') : '-' ?>
                                        </td>
                                        <td class="text-right font-weight-bold">Rp <?= number_format($saldo, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="3" class="text-right font-weight-bold">TOTAL MUTASI PERIODE INI</td>
                                        <td class="text-right text-success font-weight-bold">Rp <?= number_format($totalMasuk, 0, ',', '.') ?></td>
                                        <td class="text-right text-danger font-weight-bold">Rp <?= number_format($totalKeluar, 0, ',', '.') ?></td>
                                        <td class="text-right font-weight-bold">Rp <?= number_format($saldo, 0, ',', '.') ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada transaksi pada periode ini.</td>
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
<?php require_once '../includes/footer.php'; ?>
