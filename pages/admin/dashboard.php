<?php 
session_start(); 
$page_title = "CHARA - Dashboard";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

// 1. Total Pendapatan Bulan Ini
$stmtPendapatan = $koneksi->query("
    SELECT SUM(total) as total 
    FROM tPenjualan 
    WHERE MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())
");
$pendapatan = $stmtPendapatan->fetchColumn() ?: 0;

// 2. Total Transaksi Bulan Ini
$stmtTransaksi = $koneksi->query("
    SELECT COUNT(*) as jumlah 
    FROM tPenjualan 
    WHERE MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())
");
$jumlahTransaksi = $stmtTransaksi->fetchColumn() ?: 0;

// 3. Total Pengeluaran Bulan Ini
$stmtPengeluaran = $koneksi->query("
    SELECT SUM(nominal) as total 
    FROM tArusKas 
    WHERE jenis = 'Keluar' AND MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())
");
$pengeluaran = $stmtPengeluaran->fetchColumn() ?: 0;

// 4. Laba Bersih
$labaBersih = $pendapatan - $pengeluaran;

// 5. Stok Kritis
$stmtStok = $koneksi->query("
    SELECT b.kode, b.nama, b.stok, s.nama as satuan
    FROM tBahan b
    JOIN tSatuan s ON b.tSatuan_id = s.id
    WHERE b.stok <= 50
    ORDER BY b.stok ASC
    LIMIT 6
");
$stokKritis = $stmtStok->fetchAll(PDO::FETCH_ASSOC);

// 6. Log Aktivitas Terbaru
$stmtLog = $koneksi->query("
    SELECT l.*, u.username
    FROM tLog l
    LEFT JOIN tUser u ON l.tUser_id = u.id
    ORDER BY l.waktu DESC
    LIMIT 6
");
$logAktivitas = $stmtLog->fetchAll(PDO::FETCH_ASSOC);

// 7. Transaksi Terakhir
$stmtRecentSales = $koneksi->query("
    SELECT nomor, tanggal as waktu, total, metbayar as metode, 'Selesai' as status
    FROM tPenjualan
    ORDER BY tanggal DESC
    LIMIT 6
");
$recentSales = $stmtRecentSales->fetchAll(PDO::FETCH_ASSOC);

// 8. Analisis Performa Bisnis (Bulan Ini)
$stmtHppDashboard = $koneksi->query("
    SELECT SUM(dp.hpp * dp.jumlah)
    FROM tDetailPenjualan dp
    JOIN tPenjualan p ON dp.tPenjualan_nomor = p.nomor
    WHERE MONTH(p.tanggal) = MONTH(CURRENT_DATE()) AND YEAR(p.tanggal) = YEAR(CURRENT_DATE())
");
$hppDashboard = $stmtHppDashboard->fetchColumn() ?: 0;
$labaKotorDashboard = $pendapatan - $hppDashboard;

$marginLabaKotor = 0;
if ($pendapatan > 0) {
    $marginLabaKotor = ($labaKotorDashboard / $pendapatan) * 100;
}

$stmtTerlaris = $koneksi->query("
    SELECT pr.nama, SUM(dp.jumlah) as total_qty
    FROM tDetailPenjualan dp
    JOIN tPenjualan p ON dp.tPenjualan_nomor = p.nomor
    JOIN tProduct pr ON dp.tProduct_kode = pr.kode
    WHERE MONTH(p.tanggal) = MONTH(CURRENT_DATE()) AND YEAR(p.tanggal) = YEAR(CURRENT_DATE())
    GROUP BY pr.kode, pr.nama
    ORDER BY total_qty DESC
    LIMIT 1
");
$produkTerlaris = $stmtTerlaris->fetch(PDO::FETCH_ASSOC);

$stmtLabaTerbesar = $koneksi->query("
    SELECT pr.nama, SUM((dp.harga_jual - dp.hpp) * dp.jumlah) as total_laba
    FROM tDetailPenjualan dp
    JOIN tPenjualan p ON dp.tPenjualan_nomor = p.nomor
    JOIN tProduct pr ON dp.tProduct_kode = pr.kode
    WHERE MONTH(p.tanggal) = MONTH(CURRENT_DATE()) AND YEAR(p.tanggal) = YEAR(CURRENT_DATE())
    GROUP BY pr.kode, pr.nama
    ORDER BY total_laba DESC
    LIMIT 1
");
$produkLabaTerbesar = $stmtLabaTerbesar->fetch(PDO::FETCH_ASSOC);

$nama = $_SESSION['nama'];
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
<style>
    .premium-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .premium-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .bg-gradient-primary-custom {
        background: linear-gradient(135deg, #4b49ac 0%, #29285f 100%);
        color: white;
    }
    .bg-gradient-success-custom {
        background: linear-gradient(135deg, #248AFA 0%, #17549C 100%);
        color: white;
    }
    .bg-gradient-warning-custom {
        background: linear-gradient(135deg, #FFC100 0%, #E68A00 100%);
        color: white;
    }
    .bg-gradient-danger-custom {
        background: linear-gradient(135deg, #f35a5a 0%, #c43c3c 100%);
        color: white;
    }
    .stat-icon {
        font-size: 3rem;
        opacity: 0.8;
    }
    .table-premium th {
        text-transform: uppercase;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        background-color: #f8f9fa;
        color: #495057;
        border-bottom: 2px solid #e9ecef;
    }
    .table-premium td {
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f5;
    }
    .timeline-log {
        position: relative;
        padding-left: 30px;
        list-style: none;
    }
    .timeline-log::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 8px;
        width: 2px;
        background-color: #e9ecef;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        width: 12px;
        height: 12px;
        background-color: #4b49ac;
        border: 2px solid #fff;
        border-radius: 50%;
        left: -27px;
        top: 4px;
        box-shadow: 0 0 0 2px rgba(75, 73, 172, 0.2);
    }
</style>

<div class="content-wrapper bg-light">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h3 class="mb-1 font-weight-bold text-dark">Selamat Datang, <?= htmlspecialchars($nama) ?>!</h3>
            <p class="text-muted">Ini adalah ringkasan performa bisnis Anda bulan ini.</p>
        </div>
        <div class="col-md-6 d-flex justify-content-md-end">
            <span class="badge badge-info p-2 px-3 shadow-sm" style="font-size: 14px;">
                <i class="typcn typcn-calendar-outline mr-2"></i> Bulan <?= date('F Y') ?>
            </span>
        </div>
    </div>

    <!-- Top Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card premium-card shadow-sm bg-gradient-success-custom">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="mb-1 text-white font-weight-medium">Total Pendapatan</p>
                            <h3 class="font-weight-bold mb-0">Rp <?= number_format($pendapatan, 0, ',', '.') ?></h3>
                        </div>
                        <i class="typcn typcn-chart-line-outline stat-icon"></i>
                    </div>
                    <small class="text-white-50">Total penjualan kotor bulan ini</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 grid-margin stretch-card">
            <div class="card premium-card shadow-sm bg-gradient-danger-custom">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="mb-1 text-white font-weight-medium">Pengeluaran</p>
                            <h3 class="font-weight-bold mb-0">Rp <?= number_format($pengeluaran, 0, ',', '.') ?></h3>
                        </div>
                        <i class="typcn typcn-arrow-down-thick stat-icon"></i>
                    </div>
                    <small class="text-white-50">Pembelian & Operasional bulan ini</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 grid-margin stretch-card">
            <div class="card premium-card shadow-sm bg-gradient-primary-custom">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="mb-1 text-white font-weight-medium">Laba Bersih</p>
                            <h3 class="font-weight-bold mb-0">
                                <?php if($labaBersih < 0): ?>
                                    - Rp <?= number_format(abs($labaBersih), 0, ',', '.') ?>
                                <?php else: ?>
                                    Rp <?= number_format($labaBersih, 0, ',', '.') ?>
                                <?php endif; ?>
                            </h3>
                        </div>
                        <i class="typcn typcn-briefcase stat-icon"></i>
                    </div>
                    <small class="text-white-50">Pendapatan dikurangi pengeluaran</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 grid-margin stretch-card">
            <div class="card premium-card shadow-sm bg-white">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="mb-1 text-muted font-weight-medium">Total Transaksi</p>
                            <h3 class="font-weight-bold mb-0 text-dark"><?= number_format($jumlahTransaksi, 0, ',', '.') ?></h3>
                        </div>
                        <div class="icon-rounded bg-light text-primary p-3 rounded-circle d-flex align-items-center justify-content-center">
                            <i class="typcn typcn-shopping-cart stat-icon text-primary m-0" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <small class="text-muted">Jumlah struk bulan ini</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Analisis Performa Bisnis -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h4 class="card-title text-dark mb-3">Analisis Performa Bisnis</h4>
        </div>
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card premium-card shadow-sm border-0 w-100" style="background-color: #e8f5e9; border-left: 5px solid #2e7d32 !important;">
                <div class="card-body p-4">
                    <h6 class="text-muted mb-1 text-uppercase font-weight-bold" style="letter-spacing: 0.5px;">Margin Laba Kotor</h6>
                    <h3 class="mb-0 text-dark font-weight-bold"><?= number_format($marginLabaKotor, 2, ',', '.') ?>%</h3>
                    <small class="text-muted d-block mt-2">Persentase keuntungan dari total penjualan</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card premium-card shadow-sm border-0 w-100" style="background-color: #e3f2fd; border-left: 5px solid #1565c0 !important;">
                <div class="card-body p-4">
                    <h6 class="text-muted mb-1 text-uppercase font-weight-bold" style="letter-spacing: 0.5px;">Produk Terlaris</h6>
                    <h3 class="mb-0 text-dark font-weight-bold text-truncate" title="<?= $produkTerlaris ? htmlspecialchars($produkTerlaris['nama']) : 'Belum ada' ?>">
                        <?= $produkTerlaris ? htmlspecialchars($produkTerlaris['nama']) : 'Belum ada data' ?>
                    </h3>
                    <?php if($produkTerlaris): ?>
                        <small class="text-primary font-weight-bold d-block mt-2"><?= $produkTerlaris['total_qty'] ?> porsi terjual bulan ini</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card premium-card shadow-sm border-0 w-100" style="background-color: #fff3e0; border-left: 5px solid #ef6c00 !important;">
                <div class="card-body p-4">
                    <h6 class="text-muted mb-1 text-uppercase font-weight-bold" style="letter-spacing: 0.5px;">Penyumbang Laba Terbesar</h6>
                    <h3 class="mb-0 text-dark font-weight-bold text-truncate" title="<?= $produkLabaTerbesar ? htmlspecialchars($produkLabaTerbesar['nama']) : 'Belum ada' ?>">
                        <?= $produkLabaTerbesar ? htmlspecialchars($produkLabaTerbesar['nama']) : 'Belum ada data' ?>
                    </h3>
                    <?php if($produkLabaTerbesar): ?>
                        <small class="text-success font-weight-bold d-block mt-2">Menghasilkan Laba Rp <?= number_format($produkLabaTerbesar['total_laba'], 0, ',', '.') ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row -->
    <div class="row mb-4">
        <!-- Peringatan Stok -->
        <div class="col-lg-6 grid-margin stretch-card">
            <div class="card premium-card shadow-sm w-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-0 font-weight-bold text-dark">Peringatan Stok Kritis</h4>
                        <a href="../admin/bahanbaku.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
                    </div>
                    <?php if(count($stokKritis) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-premium table-borderless w-100">
                                <thead>
                                    <tr>
                                        <th>Bahan Baku</th>
                                        <th class="text-center">Sisa Stok</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($stokKritis as $b): 
                                        $badgeClass = ($b['stok'] <= 10) ? 'badge-danger' : 'badge-warning';
                                        $statusText = ($b['stok'] <= 10) ? 'Sangat Kritis' : 'Hampir Habis';
                                    ?>
                                    <tr>
                                        <td>
                                            <p class="mb-0 font-weight-bold text-dark"><?= htmlspecialchars($b['nama']) ?></p>
                                            <small class="text-muted"><?= htmlspecialchars($b['kode']) ?></small>
                                        </td>
                                        <td class="text-center">
                                            <h5 class="mb-0 font-weight-bold <?= ($b['stok'] <= 10) ? 'text-danger' : 'text-warning' ?>">
                                                <?= number_format($b['stok'], 2, ',', '.') ?>
                                            </h5>
                                            <small class="text-muted"><?= htmlspecialchars($b['satuan']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?> px-2 py-1 rounded-pill"><?= $statusText ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="typcn typcn-tick text-success" style="font-size: 4rem;"></i>
                            <h5 class="mt-3 text-muted">Stok Aman</h5>
                            <p class="text-muted">Tidak ada bahan baku yang mencapai limit kritis.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Log Aktivitas -->
        <div class="col-lg-6 grid-margin stretch-card">
            <div class="card premium-card shadow-sm w-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-0 font-weight-bold text-dark">Aktivitas Terkini</h4>
                        <a href="logaktivitas.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Riwayat Log</a>
                    </div>
                    <ul class="timeline-log m-0 pl-4 mt-3">
                        <?php if(count($logAktivitas) > 0): ?>
                            <?php foreach($logAktivitas as $log): ?>
                                <li class="timeline-item">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="font-weight-bold text-dark"><?= htmlspecialchars($log['aktivitas']) ?></span>
                                        <span class="text-muted small"><?= date('H:i, d M', strtotime($log['waktu'])) ?></span>
                                    </div>
                                    <p class="mb-1 text-muted" style="line-height: 1.4; font-size: 13px;">
                                        <?= htmlspecialchars($log['keterangan']) ?>
                                    </p>
                                    <small class="text-primary font-weight-bold">
                                        Oleh: <?= htmlspecialchars($log['username']) ?> 
                                        <span class="text-muted font-weight-normal px-1">&bull;</span> 
                                        <?= htmlspecialchars($log['modul']) ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="text-center py-4 text-muted">Belum ada aktivitas.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Third Row: Recent Transactions -->
    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card premium-card shadow-sm w-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-0 font-weight-bold text-dark">Transaksi Kasir Terakhir</h4>
                        <a href="laporanpenjualan.php" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">Laporan Penjualan</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-premium w-100 table-hover">
                            <thead>
                                <tr>
                                    <th>Nomor Struk</th>
                                    <th>Waktu Transaksi</th>
                                    <th>Metode Pembayaran</th>
                                    <th class="text-right">Total Nominal</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($recentSales) > 0): ?>
                                    <?php foreach($recentSales as $sale): 
                                        $badgeSale = ($sale['status'] == 'Selesai') ? 'badge-success' : 'badge-danger';
                                    ?>
                                        <tr>
                                            <td class="font-weight-bold text-dark">#<?= htmlspecialchars($sale['nomor']) ?></td>
                                            <td><?= date('d M Y, H:i', strtotime($sale['waktu'])) ?></td>
                                            <td><?= htmlspecialchars($sale['metode']) ?></td>
                                            <td class="text-right font-weight-bold text-primary">Rp <?= number_format($sale['total'], 0, ',', '.') ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $badgeSale ?> rounded-pill px-3 py-1"><?= htmlspecialchars($sale['status']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi penjualan.</td>
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
require_once '../includes/footer.php'; 
?>