<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';

if (!isset($_GET['nomor']) || empty($_GET['nomor'])) {
    header("Location: datapenjualan.php");
    exit;
}

$nomor = $_GET['nomor'];

// 1. Ambil Data Header Penjualan
$stmtHeader = $koneksi->prepare("
    SELECT 
        p.nomor, 
        p.tanggal, 
        p.total, 
        p.diskon, 
        p.metbayar, 
        u.username AS kasir 
    FROM tPenjualan p
    LEFT JOIN tUser u ON p.tUser_id = u.id
    WHERE p.nomor = ?
");
$stmtHeader->execute([$nomor]);
$penjualan = $stmtHeader->fetch(PDO::FETCH_ASSOC);

if (!$penjualan) {
    die("Data penjualan tidak ditemukan.");
}

// 2. Ambil Data Detail Item beserta kustomisasinya via Subquery (GROUP_CONCAT)
$stmtDetail = $koneksi->prepare("
    SELECT 
        d.jumlah, 
        d.harga_jual, 
        d.subtotal, 
        pr.nama AS nama_produk,
        pr.kode AS kode_produk,
        (SELECT GROUP_CONCAT(CONCAT(m.nama, ' ', m.kategori) SEPARATOR ', ')
         FROM tDetailPenjualanModifier dhm
         JOIN tModifier m ON dhm.tModifier_id = m.id
         WHERE dhm.tDetailPenjualan_id = d.id) AS teks_modifier
    FROM tDetailPenjualan d
    JOIN tProduct pr ON d.tProduct_kode = pr.kode
    WHERE d.tPenjualan_nomor = ?
");
$stmtDetail->execute([$nomor]);
$details = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

$subtotalAwal = $penjualan['total'] + $penjualan['diskon'];
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> CHARA - Detail Penjualan</title>
    <link rel="stylesheet" href="../../vendors/typicons.font/font/typicons.css">
    <link rel="stylesheet" href="../../vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../../css/vertical-layout-light/style.css">
    <link rel="shortcut icon" href="../../images/charaicon.png" />
  </head>
  <body>
    <div class="container-scroller">
      <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
        <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
          <a class="navbar-brand brand-logo" href="../../index.php"><img src="../../images/logochara.png" alt="logo"/></a>
          <a class="navbar-brand brand-logo-mini" href="../../index.php"><img src="../../images/logo-mini.svg" alt="logo"/></a>
        </div>
        <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
          <ul class="navbar-nav navbar-nav-right">
            <li class="nav-item nav-profile dropdown">
              <a class="nav-link dropdown-toggle pl-0 pr-0" href="#" data-toggle="dropdown" id="profileDropdown">
                <i class="typcn typcn-user-outline mr-0"></i>
                <span class="nav-profile-name"> <?php echo $_SESSION['nama']; ?></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                <a class="dropdown-item"><i class="typcn typcn-cog text-primary"></i>Settings</a>
                <a class="dropdown-item" href="../logout.php"><i class="typcn typcn-power text-primary"></i>Logout</a>
              </div>
            </li>
          </ul>
        </div>
      </nav>
      
      <div class="container-fluid page-body-wrapper">
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
          <ul class="nav">
            <li class="nav-item">
              <div class="d-flex sidebar-profile">
                <div class="sidebar-profile-image">
                  <img src="../../images/faces/face29.png" alt="image">
                  <span class="sidebar-status-indicator"></span>
                </div>
                <div class="sidebar-profile-name">
                  <p class="sidebar-name"><?php echo $_SESSION['nama']; ?></p>
                  <p class="sidebar-designation"><?php echo $_SESSION['role']; ?></p>
                </div>
              </div>
            </li>
            
            <?php if ($_SESSION['role'] == 'Admin'): ?>
            <p class="sidebar-menu-title"> Admin Modules</p>
            <li class="nav-item"><a class="nav-link" href="../admin/dashboard.php"><i class="typcn typcn-device-desktop menu-icon"></i><span class="menu-title">Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../admin/employee.php"><i class="typcn typcn-user menu-icon"></i><span class="menu-title">Employee</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../admin/biayaoperasional.php"><i class="typcn typcn-document-text menu-icon"></i><span class="menu-title">Biaya Operasional</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../admin/logaktivitas.php"><i class="typcn typcn-group menu-icon"></i><span class="menu-title">Log Aktivitas</span></a></li>
            
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#stok" aria-expanded="false" aria-controls="stok">
                <i class="typcn typcn-document-text menu-icon"></i><span class="menu-title">Stok</span><i class="menu-arrow"></i>
              </a>
              <div class="collapse" id="stok">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item"><a class="nav-link" href="../admin/bahanbaku.php">Bahan Baku</a></li>
                  <li class="nav-item"><a class="nav-link" href="../admin/produk.php">Produk</a></li>
                  <li class="nav-item"><a class="nav-link" href="../admin/kategori.php">Kategori</a></li>
                  <li class="nav-item"><a class="nav-link" href="../admin/resep.php">Resep</a></li>
                </ul>
              </div>
            </li>
            
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#pembelian" aria-expanded="false" aria-controls="pembelian">
                <i class="typcn typcn-shopping-cart menu-icon"></i><span class="menu-title">Pembelian</span><i class="menu-arrow"></i>
              </a>
              <div class="collapse" id="pembelian">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item"><a class="nav-link" href="../admin/purchaserequestadmin.php">Purchase Request</a></li>
                  <li class="nav-item"><a class="nav-link" href="../admin/hispembelian.php">Histori Pembelian</a></li>
                  <li class="nav-item"><a class="nav-link" href="../admin/pembelian.php">Pengajuan Pembelian</a></li>
                  <li class="nav-item"><a class="nav-link" href="../admin/daftarsupplier.php">Daftar Supplier</a></li>
                </ul>
              </div>
            </li>
            
            <li class="nav-item">
              <a class="nav-link" data-toggle="collapse" href="#laporan" aria-expanded="false" aria-controls="laporan">
                <i class="typcn typcn-document-text menu-icon"></i><span class="menu-title">Laporan</span><i class="menu-arrow"></i>
              </a>
              <div class="collapse" id="laporan">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item"><a class="nav-link" href="../admin/laporanpenjualan.php">Laporan Penjualan</a></li>
                  <li class="nav-item"><a class="nav-link" href="../admin/laporankeuangan.php">Laporan Keuangan</a></li>
                  <li class="nav-item"><a class="nav-link" href="../admin/aruskas.php">Arus Kas</a></li>
                  <li class="nav-item"><a class="nav-link" href="../admin/labarugi.php">Laba Rugi</a></li>
                </ul>
              </div>
            </li>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'Kasir' or $_SESSION['role'] == 'Admin'): ?>
              <p class="sidebar-menu-title"> Sales Modules</p>
              <li class="nav-item"><a class="nav-link" href="transaksipenjualan.php"><i class="typcn typcn-shopping-cart menu-icon"></i><span class="menu-title"> Transaksi Penjualan</span></a></li>
              <li class="nav-item"><a class="nav-link" href="datapenjualan.php"><i class="typcn typcn-chart-bar menu-icon"></i><span class="menu-title"> Data Penjualan</span></a></li>
            <?php endif ?>

            <?php if ($_SESSION['role'] == 'Gudang' or $_SESSION['role'] == 'Admin'): ?>
              <p class="sidebar-menu-title"> Stock Modules</p>
              <li class="nav-item"><a class="nav-link" href="../gudang/bahanbaku.php"><i class="typcn typcn-th-large menu-icon"></i><span class="menu-title"> Bahan Baku</span></a></li>
              <li class="nav-item"><a class="nav-link" href="../gudang/barangmasuk.php"><i class="typcn typcn-arrow-down menu-icon"></i><span class="menu-title"> Barang Masuk </span></a></li>
              <li class="nav-item"><a class="nav-link" href="../gudang/barangkeluar.php"><i class="typcn typcn-arrow-up menu-icon"></i><span class="menu-title"> Barang Keluar</span></a></li>
              <li class="nav-item"><a class="nav-link" href="../gudang/purchaserequest.php"><i class="typcn typcn-arrow-forward-outline menu-icon"></i><span class="menu-title"> Purchase Request</span></a></li>
            <?php endif ?>
            
            <p class="sidebar-menu-title"> Settings</p>
            <li class="nav-item"><a class="nav-link" href="../settings/ubahpassword.php"><i class="typcn typcn-key menu-icon"></i><span class="menu-title"> Ubah Password</span></a></li>
          </ul>
        </nav>

        <div class="main-panel">
          <div class="content-wrapper">
            
            <?php if(isset($_GET['success']) && $_GET['success'] == '1') : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Transaksi berhasil diproses! Struk pembelian dapat dilihat di bawah ini.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="row">
              <div class="col-lg-12 grid-margin stretch-card">
                <div class="card shadow-sm border-0">
                  <div class="card-body">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title mb-1">Rincian Penjualan</h4>
                            <p class="text-muted mb-0">Detail item produk untuk Nota #PJ-<?= str_pad($penjualan['nomor'], 4, '0', STR_PAD_LEFT) ?></p>
                        </div>
                        <a href="datapenjualan.php" class="btn btn-secondary">Kembali</a>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <p class="text-muted mb-1">Tanggal & Waktu</p>
                            <h6 class="font-weight-bold text-dark"><?= date('d-m-Y H:i', strtotime($penjualan['tanggal'])) ?> WIB</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1">Kasir Bertugas</p>
                            <h6 class="font-weight-bold text-dark"><?= htmlspecialchars($penjualan['kasir'] ?: 'Sistem') ?></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1">Metode Bayar</p>
                            <span class="badge badge-info"><?= $penjualan['metbayar'] ?></span>
                        </div>
                    </div>

                    <div class="table-responsive pt-3">
                      <table class="table table-bordered">
                        <thead class="bg-light">
                          <tr>
                            <th width="5%" class="font-weight-bold text-dark">No</th>
                            <th class="font-weight-bold text-dark">Produk & Kustomisasi</th>
                            <th width="15%" class="text-right font-weight-bold text-dark">Harga Jual</th>
                            <th width="10%" class="text-center font-weight-bold text-dark">Qty</th>
                            <th width="20%" class="text-right font-weight-bold text-dark">Subtotal</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php $no = 1; foreach($details as $row): ?>
                          <tr>
                              <td class="text-center"><?= $no++ ?></td>
                              <td>
                                  <span class="font-weight-bold text-dark d-block"><?= htmlspecialchars($row['nama_produk']) ?></span>
                                  <?php if (!empty($row['teks_modifier'])): ?>
                                      <small class="text-danger font-weight-bold">(<?= htmlspecialchars($row['teks_modifier']) ?>)</small>
                                  <?php endif; ?>
                              </td>
                              <td class="text-right">Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></td>
                              <td class="text-center"><?= $row['jumlah'] ?></td>
                              <td class="text-right font-weight-bold">Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <th colspan="4" class="text-right border-top border-bottom-0 py-2">Subtotal Awal</th>
                                <td class="text-right border-top border-bottom-0 py-2">Rp <?= number_format($subtotalAwal, 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-right border-top-0 border-bottom-0 py-2">Diskon / Potongan</th>
                                <td class="text-right border-top-0 border-bottom-0 py-2 text-danger">- Rp <?= number_format($penjualan['diskon'], 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-right border-top font-weight-bold text-dark">Grand Total</th>
                                <td class="text-right font-weight-bold text-success" style="font-size: 1.15rem;">Rp <?= number_format($penjualan['total'], 0, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                      </table>
                    </div>

                  </div>
                </div>
              </div>
            </div>

          </div>
          <footer class="footer"></footer>
        </div>
      </div>
    </div>
    
    <script src="../../vendors/js/vendor.bundle.base.js"></script>
    <script src="../../js/template.js"></script>
  </body>
</html>