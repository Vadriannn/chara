<?php 
session_start(); 
$page_title = "CHARA - Analytics Dashboard";
require_once '../../koneksi.php';
require_once '../../auth.php';

// AI
// Default to "This Month"
$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

$current_month_name = date('F Y', strtotime($start_date));

// Function to safely fetch single value
function fetchSingleValue($koneksi, $sql, $params = []) {
    $stmt = $koneksi->prepare($sql);
    $stmt->execute($params);
    $res = $stmt->fetchColumn();
    return $res ? $res : 0;
}

// -------------------------------------------------------------
// 1. COMPANY ANALYTICS
// -------------------------------------------------------------
$gross_sales = fetchSingleValue($koneksi, "SELECT SUM(total + diskon) FROM tpenjualan WHERE DATE(tanggal) >= ? AND DATE(tanggal) <= ?", [$start_date, $end_date]);
$net_sales = fetchSingleValue($koneksi, "SELECT SUM(total) FROM tpenjualan WHERE DATE(tanggal) >= ? AND DATE(tanggal) <= ?", [$start_date, $end_date]);
$active_members = fetchSingleValue($koneksi, "SELECT COUNT(DISTINCT tMember_noHp) FROM tpenjualan WHERE tMember_noHp IS NOT NULL AND tMember_noHp != '' AND DATE(tanggal) >= ? AND DATE(tanggal) <= ?", [$start_date, $end_date]);

$stmtTopProducts = $koneksi->prepare("SELECT p.nama, SUM(dp.jumlah) as total_qty FROM tdetailpenjualan dp JOIN tproduct p ON dp.tProduct_kode = p.kode JOIN tpenjualan j ON dp.tPenjualan_nomor = j.nomor WHERE DATE(j.tanggal) >= ? AND DATE(j.tanggal) <= ? GROUP BY p.kode ORDER BY total_qty DESC LIMIT 3");
$stmtTopProducts->execute([$start_date, $end_date]);
$top_products = $stmtTopProducts->fetchAll(PDO::FETCH_ASSOC);

$stmtAlerts = $koneksi->query("SELECT b.nama, b.stok, s.nama as satuan FROM tbahan b LEFT JOIN tsatuan s ON b.tSatuan_id = s.id WHERE b.stok < 50 ORDER BY b.stok ASC");
$alerts = $stmtAlerts->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------------------------------------
// 2. MEMBER ANALYTICS
// -------------------------------------------------------------
$member_rev = fetchSingleValue($koneksi, "SELECT SUM(total) FROM tpenjualan WHERE tMember_noHp IS NOT NULL AND tMember_noHp != '' AND DATE(tanggal) >= ? AND DATE(tanggal) <= ?", [$start_date, $end_date]);
$non_member_rev = fetchSingleValue($koneksi, "SELECT SUM(total) FROM tpenjualan WHERE (tMember_noHp IS NULL OR tMember_noHp = '') AND DATE(tanggal) >= ? AND DATE(tanggal) <= ?", [$start_date, $end_date]);
$new_members = fetchSingleValue($koneksi, "SELECT COUNT(*) FROM tmember WHERE DATE(JoinDate) >= ? AND DATE(JoinDate) <= ?", [$start_date, $end_date]);

// New Member Growth compared to last month
$last_month_start = date('Y-m-01', strtotime('-1 month', strtotime($start_date)));
$last_month_end = date('Y-m-t', strtotime('-1 month', strtotime($start_date)));
$last_new_members = fetchSingleValue($koneksi, "SELECT COUNT(*) FROM tmember WHERE DATE(JoinDate) >= ? AND DATE(JoinDate) <= ?", [$last_month_start, $last_month_end]);
$member_growth = $new_members - $last_new_members;
$member_growth_text = ($member_growth > 0 ? "+" : "") . $member_growth;

// New Member Chart Data
$days_diff = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
if ($days_diff > 62) { // Group by month if range is more than 2 months
    $stmtChart = $koneksi->prepare("SELECT DATE_FORMAT(JoinDate, '%Y-%m') as tgl, COUNT(*) as qty FROM tmember WHERE DATE(JoinDate) >= ? AND DATE(JoinDate) <= ? GROUP BY DATE_FORMAT(JoinDate, '%Y-%m') ORDER BY tgl ASC");
} else {
    $stmtChart = $koneksi->prepare("SELECT DATE(JoinDate) as tgl, COUNT(*) as qty FROM tmember WHERE DATE(JoinDate) >= ? AND DATE(JoinDate) <= ? GROUP BY DATE(JoinDate) ORDER BY tgl ASC");
}
$stmtChart->execute([$start_date, $end_date]);
$chart_data = $stmtChart->fetchAll(PDO::FETCH_ASSOC);
$labels = []; $data = [];
foreach($chart_data as $cd) {
    $labels[] = ($days_diff > 62) ? date('M Y', strtotime($cd['tgl'].'-01')) : date('d M', strtotime($cd['tgl']));
    $data[] = $cd['qty'];
}

// Member Demographics
$avg_age = fetchSingleValue($koneksi, "SELECT AVG(TIMESTAMPDIFF(YEAR, BirthDate, CURDATE())) FROM tmember WHERE BirthDate IS NOT NULL AND BirthDate != '0000-00-00'");
$male_count = fetchSingleValue($koneksi, "SELECT COUNT(*) FROM tmember WHERE Gender = 'M'");
$female_count = fetchSingleValue($koneksi, "SELECT COUNT(*) FROM tmember WHERE Gender = 'F'");
$total_gender = $male_count + $female_count;
$male_pct = $total_gender > 0 ? round(($male_count / $total_gender) * 100) : 0;
$female_pct = $total_gender > 0 ? round(($female_count / $total_gender) * 100) : 0;

// CLV All Time Average
$clv = fetchSingleValue($koneksi, "SELECT (SUM(total) / COUNT(DISTINCT tMember_noHp)) FROM tpenjualan WHERE tMember_noHp IS NOT NULL AND tMember_noHp != ''");

// -------------------------------------------------------------
// 3. FINANCE ANALYTICS
// -------------------------------------------------------------
$atv = fetchSingleValue($koneksi, "SELECT AVG(total) FROM tpenjualan WHERE DATE(tanggal) >= ? AND DATE(tanggal) <= ?", [$start_date, $end_date]);
$cogs = fetchSingleValue($koneksi, "SELECT SUM(dp.hpp * dp.jumlah) FROM tdetailpenjualan dp JOIN tpenjualan j ON dp.tPenjualan_nomor = j.nomor WHERE DATE(j.tanggal) >= ? AND DATE(j.tanggal) <= ?", [$start_date, $end_date]);
$gross_margin = $net_sales - $cogs;

$cogs_pct = $net_sales > 0 ? round(($cogs / $net_sales) * 100, 1) : 0;
$gpm_pct = $net_sales > 0 ? round(($gross_margin / $net_sales) * 100, 1) : 0;

$stmtRevProducts = $koneksi->prepare("SELECT p.nama, SUM(dp.harga_jual * dp.jumlah) as total_rev FROM tdetailpenjualan dp JOIN tproduct p ON dp.tProduct_kode = p.kode JOIN tpenjualan j ON dp.tPenjualan_nomor = j.nomor WHERE DATE(j.tanggal) >= ? AND DATE(j.tanggal) <= ? GROUP BY p.kode ORDER BY total_rev DESC LIMIT 5");
$stmtRevProducts->execute([$start_date, $end_date]);
$top_rev_products = $stmtRevProducts->fetchAll(PDO::FETCH_ASSOC);

$stmtRevProductsAsc = $koneksi->prepare("SELECT p.nama, SUM(dp.harga_jual * dp.jumlah) as total_rev FROM tdetailpenjualan dp JOIN tproduct p ON dp.tProduct_kode = p.kode JOIN tpenjualan j ON dp.tPenjualan_nomor = j.nomor WHERE DATE(j.tanggal) >= ? AND DATE(j.tanggal) <= ? GROUP BY p.kode ORDER BY total_rev ASC LIMIT 5");
$stmtRevProductsAsc->execute([$start_date, $end_date]);
$bot_rev_products = $stmtRevProductsAsc->fetchAll(PDO::FETCH_ASSOC);

$stmtPayment = $koneksi->prepare("SELECT metbayar, COUNT(*) as qty, SUM(total) as rev FROM tpenjualan WHERE DATE(tanggal) >= ? AND DATE(tanggal) <= ? GROUP BY metbayar");
$stmtPayment->execute([$start_date, $end_date]);
$payment_methods = $stmtPayment->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------------------------------------
// 4. PRODUCT ANALYTICS
// -------------------------------------------------------------
$discount_cost = fetchSingleValue($koneksi, "SELECT SUM(diskon) FROM tpenjualan WHERE DATE(tanggal) >= ? AND DATE(tanggal) <= ?", [$start_date, $end_date]);

$stmtRawMat = $koneksi->prepare("SELECT b.nama, SUM(dp.jumlah * r.jumlah) as usage_qty, s.nama as satuan FROM tdetailpenjualan dp JOIN tpenjualan j ON dp.tPenjualan_nomor = j.nomor JOIN tresep r ON dp.tProduct_kode = r.tProduct_kode JOIN tbahan b ON r.tBahan_kode = b.kode LEFT JOIN tsatuan s ON b.tSatuan_id = s.id WHERE DATE(j.tanggal) >= ? AND DATE(j.tanggal) <= ? GROUP BY b.kode ORDER BY usage_qty DESC");
$stmtRawMat->execute([$start_date, $end_date]);
$raw_mats = $stmtRawMat->fetchAll(PDO::FETCH_ASSOC);

$stmtBundle = $koneksi->prepare("SELECT p1.nama as prod1, p2.nama as prod2, COUNT(*) as freq FROM tdetailpenjualan dp1 JOIN tdetailpenjualan dp2 ON dp1.tPenjualan_nomor = dp2.tPenjualan_nomor AND dp1.tProduct_kode < dp2.tProduct_kode JOIN tproduct p1 ON dp1.tProduct_kode = p1.kode JOIN tproduct p2 ON dp2.tProduct_kode = p2.kode JOIN tpenjualan j ON dp1.tPenjualan_nomor = j.nomor WHERE DATE(j.tanggal) >= ? AND DATE(j.tanggal) <= ? GROUP BY p1.kode, p2.kode ORDER BY freq DESC LIMIT 5");
$stmtBundle->execute([$start_date, $end_date]);
$bundling = $stmtBundle->fetchAll(PDO::FETCH_ASSOC);

// Top Product by Demographics
$top_male_prod = fetchSingleValue($koneksi, "SELECT p.nama FROM tdetailpenjualan dp JOIN tpenjualan j ON dp.tPenjualan_nomor = j.nomor JOIN tmember m ON j.tMember_noHp = m.noHp JOIN tproduct p ON dp.tProduct_kode = p.kode WHERE m.Gender = 'M' AND DATE(j.tanggal) >= ? AND DATE(j.tanggal) <= ? GROUP BY p.kode ORDER BY SUM(dp.jumlah) DESC LIMIT 1", [$start_date, $end_date]);
$top_female_prod = fetchSingleValue($koneksi, "SELECT p.nama FROM tdetailpenjualan dp JOIN tpenjualan j ON dp.tPenjualan_nomor = j.nomor JOIN tmember m ON j.tMember_noHp = m.noHp JOIN tproduct p ON dp.tProduct_kode = p.kode WHERE m.Gender = 'F' AND DATE(j.tanggal) >= ? AND DATE(j.tanggal) <= ? GROUP BY p.kode ORDER BY SUM(dp.jumlah) DESC LIMIT 1", [$start_date, $end_date]);
$top_member_prod = fetchSingleValue($koneksi, "SELECT p.nama FROM tdetailpenjualan dp JOIN tpenjualan j ON dp.tPenjualan_nomor = j.nomor JOIN tproduct p ON dp.tProduct_kode = p.kode WHERE j.tMember_noHp IS NOT NULL AND j.tMember_noHp != '' AND DATE(j.tanggal) >= ? AND DATE(j.tanggal) <= ? GROUP BY p.kode ORDER BY SUM(dp.jumlah) DESC LIMIT 1", [$start_date, $end_date]);

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!-- Add Chart.js for the graph -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* Styling for Tab Buttons to make them distinct */
.nav-tabs .nav-link { 
    background-color: #e9ecef; 
    color: #495057; 
    margin-right: 5px; 
    border-radius: 5px 5px 0 0; 
    font-weight: bold; 
    border: 1px solid #dee2e6;
}
.nav-tabs .nav-link:hover {
    background-color: #d6d8db;
}
.nav-tabs .nav-link.active { 
    background-color: #343a40 !important; 
    color: #fff !important; 
    border-color: #343a40;
}
.border-right-divider {
    border-right: 2px dashed #dee2e6;
}
@media (max-width: 768px) {
    .border-right-divider {
        border-right: none;
        border-bottom: 2px dashed #dee2e6;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
}
/* Dashboard Premium Cards Sync */
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
.bg-gradient-info-custom {
    background: linear-gradient(135deg, #0dcaf0 0%, #057a8f 100%);
    color: white;
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
</style>

<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Analytics Dashboard</h4>
                    
                    <form id="filterForm" method="GET" action="" class="mb-4 form-inline align-items-center">
                        <button type="button" class="btn btn-outline-secondary mr-2" onclick="changeMonth(-1)">
                            <i class="typcn typcn-chevron-left"></i>
                        </button>
                        <h5 class="mr-3 mb-0" style="min-width: 130px; text-align: center;"><strong><?= $current_month_name ?></strong></h5>
                        <button type="button" class="btn btn-outline-secondary mr-3" onclick="changeMonth(1)">
                            <i class="typcn typcn-chevron-right"></i>
                        </button>

                        <span class="mr-2 ml-3">Custom Range: </span>
                        <input type="date" name="start_date" id="start_date" class="form-control mr-2" value="<?= htmlspecialchars($start_date) ?>" title="Mulai Tanggal">
                        <span class="mr-2">s/d</span>
                        <input type="date" name="end_date" id="end_date" class="form-control mr-2" value="<?= htmlspecialchars($end_date) ?>" title="Sampai Tanggal">
                        <button type="submit" class="btn btn-primary mr-2">Filter</button>
                    </form>

                    <ul class="nav nav-tabs" id="analyticsTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="company-tab" data-toggle="tab" href="#company" role="tab">Company Analytics</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="member-tab" data-toggle="tab" href="#member" role="tab">Member Analytics</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="finance-tab" data-toggle="tab" href="#finance" role="tab">Finance Analytics</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="product-tab" data-toggle="tab" href="#product" role="tab">Product Analytics</a>
                        </li>
                    </ul>

                    <div class="tab-content border border-top-0 p-4" id="analyticsTabsContent" style="background-color: #fff;">
                        
                        <!-- 1. COMPANY ANALYTICS -->
                        <div class="tab-pane fade show active" id="company" role="tabpanel">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card premium-card shadow-sm bg-gradient-success-custom h-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <p class="mb-1 text-white font-weight-medium">Total Gross Sales</p>
                                            <h3 class="font-weight-bold mb-0">Rp <?= number_format($gross_sales, 0, ',', '.') ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card premium-card shadow-sm bg-gradient-primary-custom h-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <p class="mb-1 text-white font-weight-medium">Total Net Sales</p>
                                            <h3 class="font-weight-bold mb-0">Rp <?= number_format($net_sales, 0, ',', '.') ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card premium-card shadow-sm bg-gradient-warning-custom h-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <p class="mb-1 text-white font-weight-medium">Active Members</p>
                                            <h3 class="font-weight-bold mb-0"><?= number_format($active_members, 0, ',', '.') ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 border-right-divider">
                                    <h5 class="mb-3 font-weight-bold text-dark">Top 3 Best Selling Products (Qty)</h5>
                                    <table class="table table-premium table-borderless w-100 mt-3">
                                        <thead class="bg-light"><tr><th>Product</th><th>Qty Sold</th></tr></thead>
                                        <tbody>
                                            <?php foreach($top_products as $tp): ?>
                                            <tr><td><p class="mb-0 font-weight-bold text-dark"><?= htmlspecialchars($tp['nama']) ?></p></td><td><span class="badge badge-info rounded-pill px-3 py-1"><?= $tp['total_qty'] ?></span></td></tr>
                                            <?php endforeach; ?>
                                            <?php if(empty($top_products)) echo '<tr><td colspan="2" class="text-muted">No sales data.</td></tr>'; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6 pl-md-4">
                                    <h5 class="text-danger mb-3 font-weight-bold"><i class="typcn typcn-warning"></i> Stock Alerts (< 50 items)</h5>
                                    <table class="table table-premium table-borderless w-100 mt-3">
                                        <thead class="bg-light"><tr><th>Raw Material</th><th>Stock Remaining</th></tr></thead>
                                        <tbody>
                                            <?php foreach($alerts as $al): ?>
                                            <tr>
                                                <td><p class="mb-0 font-weight-bold text-dark"><?= htmlspecialchars($al['nama']) ?></p></td>
                                                <td><h5 class="mb-0"><span class="badge badge-danger rounded-pill px-3 py-1"><?= floatval($al['stok']) ?> <?= htmlspecialchars($al['satuan'] ?? 'pcs') ?></span></h5></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if(empty($alerts)) echo '<tr><td colspan="2" class="text-success font-weight-bold">All stocks are safe.</td></tr>'; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- 2. MEMBER ANALYTICS -->
                        <div class="tab-pane fade" id="member" role="tabpanel">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card shadow-sm border-left-primary">
                                        <div class="card-body">
                                            <h4 class="text-primary mb-3">Total Net Revenue</h4>
                                            <h2>Rp <?= number_format($net_sales, 0, ',', '.') ?></h2>
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <span class="text-muted">From Members</span><br>
                                                    <h5 class="text-success">Rp <?= number_format($member_rev, 0, ',', '.') ?></h5>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-muted">From Non-Members</span><br>
                                                    <h5 class="text-secondary">Rp <?= number_format($non_member_rev, 0, ',', '.') ?></h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card shadow-sm border-left-success">
                                        <div class="card-body">
                                            <h4 class="text-success mb-3">Customer Lifetime Value (Avg) <i class="typcn typcn-info-large-outline text-muted" title="Rata-rata total belanja per member selama menjadi member"></i></h4>
                                            <h2>Rp <?= number_format($clv, 0, ',', '.') ?></h2>
                                            <p class="text-muted mt-2">Dihitung dari total seluruh pendapatan member dibagi dengan jumlah member unik yang pernah bertransaksi.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card premium-card bg-gradient-info-custom shadow-sm mb-3">
                                        <div class="card-body">
                                            <p class="mb-1 text-white font-weight-medium">New Members (<?= $current_month_name ?>)</p>
                                            <div class="d-flex align-items-center">
                                                <h3 class="mb-0 mr-3 font-weight-bold text-white"><?= number_format($new_members, 0, ',', '.') ?></h3>
                                                <h6 class="mb-0 bg-white text-dark px-2 py-1 rounded shadow-sm"><?= $member_growth_text ?> vs last month</h6>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- CHART CONTAINER -->
                                    <div class="card shadow-sm mb-3">
                                        <div class="card-body">
                                            <canvas id="memberChart" height="150"></canvas>
                                        </div>
                                    </div>

                                </div>
                                <div class="col-md-6">
                                    <div class="card shadow-sm border-left-warning mb-3 h-100">
                                        <div class="card-body">
                                            <h4 class="text-warning mb-3">Member Demographics</h4>
                                            <div class="row text-center mt-4">
                                                <div class="col-6 border-right">
                                                    <span class="text-muted">Average Age</span><br>
                                                    <h2 class="mt-2 text-dark"><?= round($avg_age) ?> <small>thn</small></h2>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted">Total Gender</span><br>
                                                    <div class="d-flex justify-content-center align-items-center mt-3">
                                                        <h5 class="text-info mr-3"><i class="typcn typcn-user"></i> <?= $male_pct ?>% M</h5>
                                                        <h5 class="text-danger"><i class="typcn typcn-user-outline"></i> <?= $female_pct ?>% F</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. FINANCE ANALYTICS -->
                        <div class="tab-pane fade" id="finance" role="tabpanel">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card premium-card shadow-sm bg-gradient-primary-custom h-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <p class="mb-1 text-white font-weight-medium">Average Transaction Value</p>
                                            <h3 class="font-weight-bold mb-0">Rp <?= number_format($atv, 0, ',', '.') ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card premium-card shadow-sm bg-gradient-danger-custom h-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <p class="mb-1 text-white font-weight-medium">COGS (HPP)</p>
                                            <h3 class="font-weight-bold mb-0">Rp <?= number_format($cogs, 0, ',', '.') ?></h3>
                                            <span class="badge badge-light text-danger mt-2 align-self-start py-1 px-2"><?= $cogs_pct ?>% of Net Sales</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card premium-card shadow-sm bg-gradient-success-custom h-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <p class="mb-1 text-white font-weight-medium">Gross Profit Margin</p>
                                            <h3 class="font-weight-bold mb-0">Rp <?= number_format($gross_margin, 0, ',', '.') ?></h3>
                                            <span class="badge badge-light text-success mt-2 align-self-start py-1 px-2"><?= $gpm_pct ?>% of Net Sales</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5>Top 5 Products by Revenue</h5>
                                    <table class="table table-bordered table-striped mt-2">
                                        <thead class="bg-light"><tr><th>Product</th><th>Revenue generated</th></tr></thead>
                                        <tbody>
                                            <?php foreach($top_rev_products as $trp): ?>
                                            <tr><td><?= htmlspecialchars($trp['nama']) ?></td><td class="text-success font-weight-bold">Rp <?= number_format($trp['total_rev'], 0, ',', '.') ?></td></tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5>Bottom 5 Products by Revenue</h5>
                                    <table class="table table-bordered table-striped mt-2">
                                        <thead class="bg-light"><tr><th>Product</th><th>Revenue generated</th></tr></thead>
                                        <tbody>
                                            <?php foreach($bot_rev_products as $brp): ?>
                                            <tr><td><?= htmlspecialchars($brp['nama']) ?></td><td class="text-danger">Rp <?= number_format($brp['total_rev'], 0, ',', '.') ?></td></tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <h5>Payment Methods Breakdown</h5>
                            <table class="table table-bordered table-striped mt-2">
                                <thead class="bg-light"><tr><th>Method</th><th>Total Transactions</th><th>Total Revenue</th></tr></thead>
                                <tbody>
                                    <?php foreach($payment_methods as $pm): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($pm['metbayar']) ?></strong></td>
                                        <td><?= $pm['qty'] ?></td>
                                        <td>Rp <?= number_format($pm['rev'], 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- 4. PRODUCT ANALYTICS -->
                        <div class="tab-pane fade" id="product" role="tabpanel">
                             <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card premium-card shadow-sm bg-gradient-danger-custom h-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <p class="mb-1 text-white font-weight-medium">Margin Erosion (Total Diskon)</p>
                                            <h3 class="font-weight-bold mb-0">Rp <?= number_format($discount_cost, 0, ',', '.') ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="card shadow-sm border-left-info h-100">
                                        <div class="card-body">
                                            <h5 class="text-info">Top Products by Demographic</h5>
                                            <div class="row mt-3">
                                                <div class="col-md-4 border-right">
                                                    <span class="text-muted small">Most Bought by Men</span>
                                                    <h6 class="font-weight-bold mt-1 text-primary"><?= $top_male_prod ?: 'N/A' ?></h6>
                                                </div>
                                                <div class="col-md-4 border-right">
                                                    <span class="text-muted small">Most Bought by Women</span>
                                                    <h6 class="font-weight-bold mt-1" style="color: #e83e8c;"><?= $top_female_prod ?: 'N/A' ?></h6>
                                                </div>
                                                <div class="col-md-4">
                                                    <span class="text-muted small">Most Bought by All Members</span>
                                                    <h6 class="font-weight-bold mt-1 text-success"><?= $top_member_prod ?: 'N/A' ?></h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 border-right-divider">
                                    <h5>Raw Material Usage Prediction</h5>
                                    <p class="text-muted small">Dihitung berdasarkan resep produk yang terjual</p>
                                    <table class="table table-bordered table-striped mt-2">
                                        <thead class="bg-light"><tr><th>Raw Material</th><th>Calculated Usage</th></tr></thead>
                                        <tbody>
                                            <?php foreach($raw_mats as $rm): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($rm['nama']) ?></td>
                                                <td><h5><span class="badge badge-info"><?= floatval($rm['usage_qty']) ?> <?= htmlspecialchars($rm['satuan'] ?? 'pcs') ?></span></h5></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6 pl-md-4">
                                    <h5>Product Affinity (Top 5 Bundles)</h5>
                                    <p class="text-muted small">Produk yang sering dibeli bersamaan dalam 1 struk</p>
                                    <table class="table table-bordered table-striped mt-2">
                                        <thead class="bg-light"><tr><th>Product 1</th><th>Product 2</th><th>Bought Together</th></tr></thead>
                                        <tbody>
                                            <?php foreach($bundling as $bnd): ?>
                                            <tr><td><?= htmlspecialchars($bnd['prod1']) ?></td><td><?= htmlspecialchars($bnd['prod2']) ?></td><td class="text-center"><strong><?= $bnd['freq'] ?> times</strong></td></tr>
                                            <?php endforeach; ?>
                                            <?php if(empty($bundling)) echo '<tr><td colspan="3" class="text-center">No bundling data available.</td></tr>'; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div> <!-- End Tab Content -->
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Logic to handle Previous / Next month buttons
function changeMonth(offset) {
    let startDateInput = document.getElementById('start_date').value;
    let date = new Date(startDateInput);
    date.setDate(1); // Ensure we are on the 1st of the month
    date.setMonth(date.getMonth() + offset);
    
    // Format new start date (YYYY-MM-DD)
    let newStart = date.getFullYear() + "-" + String(date.getMonth() + 1).padStart(2, '0') + "-01";
    
    // Calculate last day of the new month
    let nextMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0); 
    let newEnd = nextMonth.getFullYear() + "-" + String(nextMonth.getMonth() + 1).padStart(2, '0') + "-" + String(nextMonth.getDate()).padStart(2, '0');
    
    document.getElementById('start_date').value = newStart;
    document.getElementById('end_date').value = newEnd;
    document.getElementById('filterForm').submit();
}

// Render Member Growth Chart
document.addEventListener("DOMContentLoaded", function() {
    var ctx = document.getElementById('memberChart').getContext('2d');
    var memberChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'New Members',
                data: <?= json_encode($data) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<?php 
require_once '../includes/footer.php'; 
?>
