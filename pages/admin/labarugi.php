<?php
session_start();
$page_title = "CHARA - Laporan Laba Rugi";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

date_default_timezone_set('Asia/Jakarta');
$hariIni = date('Y-m-d');
$awalBulan = date('Y-m-01');

$tglMulai = !empty($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : $awalBulan;
$tglSelesai = !empty($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : $hariIni;

// 1. Ambil Data Penjualan (Pendapatan)
$qPendapatan = "
    SELECT 
        IFNULL(SUM(total), 0) AS penjualan_bersih, 
        IFNULL(SUM(diskon), 0) AS total_diskon 
    FROM tPenjualan 
    WHERE DATE(tanggal) >= ? AND DATE(tanggal) <= ?
";
$stmtPend = $koneksi->prepare($qPendapatan);
$stmtPend->execute([$tglMulai, $tglSelesai]);
$resPend = $stmtPend->fetch(PDO::FETCH_ASSOC);

$penjualanBersih = $resPend['penjualan_bersih'];
$totalDiskon = $resPend['total_diskon'];
$penjualanKotor = $penjualanBersih + $totalDiskon;

// 2. Ambil Data HPP
$qHPP = "
    SELECT IFNULL(SUM(dp.hpp * dp.jumlah), 0) AS total_hpp
    FROM tDetailPenjualan dp
    JOIN tPenjualan p ON dp.tPenjualan_nomor = p.nomor
    WHERE DATE(p.tanggal) >= ? AND DATE(p.tanggal) <= ?
";
$stmtHpp = $koneksi->prepare($qHPP);
$stmtHpp->execute([$tglMulai, $tglSelesai]);
$resHpp = $stmtHpp->fetch(PDO::FETCH_ASSOC);

$totalHPP = $resHpp['total_hpp'];

// Hitung Laba Kotor
$labaKotor = $penjualanBersih - $totalHPP;

// 3. Ambil Data Biaya Operasional
$qBiaya = "
    SELECT kb.jenis AS kategori, IFNULL(SUM(bo.nominal), 0) AS total_biaya
    FROM tBiayaOperasional bo
    JOIN tKategoriBiaya kb ON bo.tKategoriBiaya_id = kb.id
    WHERE bo.tanggal >= ? AND bo.tanggal <= ?
    GROUP BY kb.jenis
";
$stmtBiaya = $koneksi->prepare($qBiaya);
$stmtBiaya->execute([$tglMulai, $tglSelesai]);
$listBiaya = $stmtBiaya->fetchAll(PDO::FETCH_ASSOC);

$totalBiayaOperasional = 0;
foreach($listBiaya as $b) {
    $totalBiayaOperasional += $b['total_biaya'];
}

// 4. Hitung Laba Bersih
$labaBersih = $labaKotor - $totalBiayaOperasional;

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-dark mb-1">Laporan Laba Rugi</h4>
                    <p class="text-muted mb-4">Menganalisis profitabilitas bisnis melalui perbandingan Pendapatan, HPP, dan Biaya Operasional.</p>
                    
                    <!-- Filter -->
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
                                <a href="labarugi.php" class="btn btn-secondary btn-sm mb-2">
                                    <i class="typcn typcn-refresh"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Laporan Laba Rugi -->
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <!-- PENDAPATAN -->
                                <tr>
                                    <td colspan="2" class="font-weight-bold" style="font-size: 1.1rem;">PENDAPATAN</td>
                                </tr>
                                <tr>
                                    <td class="pl-4">Penjualan Kotor</td>
                                    <td class="text-right">Rp <?= number_format($penjualanKotor, 0, ',', '.') ?></td>
                                </tr>
                                <tr>
                                    <td class="pl-4">Diskon Penjualan</td>
                                    <td class="text-right text-danger">- Rp <?= number_format($totalDiskon, 0, ',', '.') ?></td>
                                </tr>
                                <tr>
                                    <td class="pl-4 font-weight-bold">Total Penjualan Bersih</td>
                                    <td class="text-right font-weight-bold">Rp <?= number_format($penjualanBersih, 0, ',', '.') ?></td>
                                </tr>

                                <!-- HARGA POKOK PENJUALAN -->
                                <tr>
                                    <td colspan="2" class="font-weight-bold mt-3" style="font-size: 1.1rem; border-top: 2px solid #ebedf2;">HARGA POKOK PENJUALAN</td>
                                </tr>
                                <tr>
                                    <td class="pl-4">Total Harga Pokok Penjualan (HPP)</td>
                                    <td class="text-right text-danger">- Rp <?= number_format($totalHPP, 0, ',', '.') ?></td>
                                </tr>
                                
                                <!-- LABA KOTOR -->
                                <tr>
                                    <td class="font-weight-bold text-uppercase" style="font-size: 1.1rem; border-top: 2px solid #ebedf2;">Laba Kotor</td>
                                    <td class="text-right font-weight-bold text-<?= $labaKotor >= 0 ? 'success' : 'danger' ?>" style="font-size: 1.1rem; border-top: 2px solid #ebedf2;">
                                        <?= $labaKotor < 0 ? '- Rp ' . number_format(abs($labaKotor), 0, ',', '.') : 'Rp ' . number_format($labaKotor, 0, ',', '.') ?>
                                    </td>
                                </tr>

                                <!-- BIAYA OPERASIONAL -->
                                <tr>
                                    <td colspan="2" class="font-weight-bold mt-3" style="font-size: 1.1rem; border-top: 2px solid #ebedf2;">BIAYA OPERASIONAL</td>
                                </tr>
                                <?php if(count($listBiaya) > 0): ?>
                                    <?php foreach($listBiaya as $biaya): ?>
                                    <tr>
                                        <td class="pl-4">Biaya <?= htmlspecialchars($biaya['kategori']) ?></td>
                                        <td class="text-right text-danger">- Rp <?= number_format($biaya['total_biaya'], 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td class="pl-4 text-muted font-italic">Tidak ada biaya operasional pada periode ini.</td>
                                        <td class="text-right text-danger">- Rp 0</td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td class="pl-4 font-weight-bold">Total Biaya Operasional</td>
                                    <td class="text-right font-weight-bold text-danger">- Rp <?= number_format($totalBiayaOperasional, 0, ',', '.') ?></td>
                                </tr>

                                <!-- LABA BERSIH -->
                                <tr>
                                    <td class="font-weight-bold text-uppercase" style="font-size: 1.2rem; border-top: 3px double #ebedf2; background-color: #f8f9fa;">Laba Bersih</td>
                                    <td class="text-right font-weight-bold text-<?= $labaBersih >= 0 ? 'success' : 'danger' ?>" style="font-size: 1.2rem; border-top: 3px double #ebedf2; background-color: #f8f9fa;">
                                        <?= $labaBersih < 0 ? '- Rp ' . number_format(abs($labaBersih), 0, ',', '.') : 'Rp ' . number_format($labaBersih, 0, ',', '.') ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
