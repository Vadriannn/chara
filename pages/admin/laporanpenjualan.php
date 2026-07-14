<?php
session_start();
$page_title = "CHARA - Laporan Penjualan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';
// AI
date_default_timezone_set('Asia/Jakarta');
$hariIni = date('Y-m-d');
$awalBulan = date('Y-m-01');

$tglMulai = !empty($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : $awalBulan;
$tglSelesai = !empty($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : $hariIni;
$filterProduk = !empty($_GET['filter_produk']) ? $_GET['filter_produk'] : '';
$filterMetbayar = !empty($_GET['filter_metbayar']) ? $_GET['filter_metbayar'] : '';

// 1. Ambil daftar Produk untuk Dropdown
$stmtProd = $koneksi->query("SELECT kode, nama FROM tproduct ORDER BY nama ASC");
$products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

// 2. Query Data Penjualan (Join dengan Detail)
$query = "
    SELECT 
        p.nomor,
        p.tanggal,
        p.metbayar,
        p.diskon,
        p.total AS grand_total,
        dp.tProduct_kode,
        prod.nama AS nama_produk,
        dp.harga_jual,
        dp.jumlah,
        dp.subtotal,
        u.username AS kasir
    FROM tpenjualan p
    JOIN tdetailpenjualan dp ON p.nomor = dp.tPenjualan_nomor
    JOIN tproduct prod ON dp.tProduct_kode = prod.kode
    LEFT JOIN tuser u ON p.tUser_id = u.id
    WHERE DATE(p.tanggal) >= ? AND DATE(p.tanggal) <= ?
";
$params = [$tglMulai, $tglSelesai];

if ($filterProduk != '') {
    $query .= " AND dp.tProduct_kode = ?";
    $params[] = $filterProduk;
}

if ($filterMetbayar != '') {
    $query .= " AND p.metbayar = ?";
    $params[] = $filterMetbayar;
}

$query .= " ORDER BY p.tanggal DESC, p.nomor DESC";

$stmt = $koneksi->prepare($query);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Kalkulasi Ringkasan Laporan
$uniqueTransactions = [];
$totalProdukTerjual = 0;
$totalPendapatanSubtotal = 0;

foreach ($data as $row) {
    if (!in_array($row['nomor'], $uniqueTransactions)) {
        $uniqueTransactions[] = $row['nomor'];
    }
    $totalProdukTerjual += $row['jumlah'];
    $totalPendapatanSubtotal += $row['subtotal']; 
}
$totalTransaksi = count($uniqueTransactions);

// Jika difilter per produk, pendapatan = subtotal produk tersebut
// Jika tidak difilter per produk, pendapatan = total nota (sudah dikurangi diskon)
$totalPendapatan = 0;
if ($filterProduk == '') {
    $visited = [];
    foreach ($data as $row) {
        if (!isset($visited[$row['nomor']])) {
            $visited[$row['nomor']] = true;
            $totalPendapatan += $row['grand_total']; 
        }
    }
} else {
    $totalPendapatan = $totalPendapatanSubtotal;
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="row">
        <!-- Rangkuman / Ringkasan Atas -->
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card bg-primary text-white border-0">
                <div class="card-body">
                    <h5 class="card-title text-white mb-2">Total Transaksi</h5>
                    <h2 class="mb-0 font-weight-bold"><?= number_format($totalTransaksi, 0, ',', '.') ?></h2>
                    <p class="mt-2 mb-0 text-white-50">Transaksi Berhasil</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card bg-success text-white border-0">
                <div class="card-body">
                    <h5 class="card-title text-white mb-2">Produk Terjual</h5>
                    <h2 class="mb-0 font-weight-bold"><?= number_format($totalProdukTerjual, 0, ',', '.') ?></h2>
                    <p class="mt-2 mb-0 text-white-50">Total Kuantitas (Cup/Item)</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card bg-info text-white border-0">
                <div class="card-body">
                    <h5 class="card-title text-white mb-2">Total Pendapatan</h5>
                    <h2 class="mb-0 font-weight-bold">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></h2>
                    <p class="mt-2 mb-0 text-white-50">Estimasi Pemasukan Bersih</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-dark mb-1">Rincian Laporan Penjualan</h4>
                    <p class="text-muted mb-4">Laporan mendetail mengenai penjualan produk berdasarkan rentang waktu, produk, dan metode pembayaran.</p>
                    
                    <!-- Filter Form -->
                    <form method="GET" class="mb-4">
                        <div class="row align-items-end">
                            <div class="col-md-2 mb-3">
                                <label class="font-weight-bold text-dark">Mulai Tanggal</label>
                                <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tglMulai) ?>">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="font-weight-bold text-dark">Sampai Tanggal</label>
                                <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tglSelesai) ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold text-dark">Berdasarkan Produk</label>
                                <select name="filter_produk" class="form-control form-control-sm">
                                    <option value="">Semua Produk</option>
                                    <?php foreach($products as $p): ?>
                                        <option value="<?= $p['kode'] ?>" <?= ($filterProduk == $p['kode']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                             <div class="col-md-3 mb-3">
                                 <label class="font-weight-bold text-dark">Metode Pembayaran</label>
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
                                <a href="laporanpenjualan.php" class="btn btn-secondary btn-sm btn-block">
                                    <i class="typcn typcn-refresh"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Table Data -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="bg-light">
                                <tr align = "Center">
                                    <th width="5%">No</th>
                                    <th>Waktu Penjualan</th>
                                    <th>No. Nota</th>
                                    <th>Nama Produk</th>
                                    <th class="text-right">Harga Jual</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Total</th>
                                    <th>Pembayaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($data) > 0): ?>
                                    <?php $no = 1; foreach($data as $row): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                                        <td class="font-weight-bold">
                                            <a href="../kasir/detailpenjualan.php?nomor=<?= urlencode($row['nomor']) ?>" target="_blank" class="text-primary text-decoration-none">
                                                #<?= htmlspecialchars($row['nomor']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                                        <td class="text-right">Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></td>
                                        <td class="text-center"><?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                        <td class="text-right font-weight-bold text-success">Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge badge-outline-info font-weight-bold"><?= htmlspecialchars($row['metbayar']) ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted font-italic">Tidak ada data penjualan yang cocok dengan filter.</td>
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
