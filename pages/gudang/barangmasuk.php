<?php
session_start();

require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_gudang.php';

/*
|--------------------------------------------------------------------------
| RECEIVE BARANG
|--------------------------------------------------------------------------
*/
if(isset($_GET['receive'])){
    try{
        $koneksi->beginTransaction();
        $nomor = $_GET['receive'];
        
        $stmt = $koneksi->prepare("
            SELECT
                tBahan_kode,
                jumlah,
                konversi,
                subtotal 
            FROM tDetailPembelian
            WHERE tPembelian_nomor = ?
        ");
        $stmt->execute([$nomor]);
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $stokTambah = $row['jumlah'] * $row['konversi'];

            $cekStok = $koneksi->prepare("
                SELECT stok, harga
                FROM tBahan
                WHERE kode = ?
            ");
            $cekStok->execute([
                $row['tBahan_kode']
            ]);

            $dataBahan = $cekStok->fetch(PDO::FETCH_ASSOC);
            $stokLama = $dataBahan['stok'];
            $hargaLama = $dataBahan['harga']; 
            
            $stokBaru = $stokLama + $stokTambah;

            $nilaiLama = $stokLama * $hargaLama;
            $nilaiBaru = $row['subtotal']; 
            
            if ($stokBaru > 0) {
                $hargaBaru = ($nilaiLama + $nilaiBaru) / $stokBaru;
            } else {
                $hargaBaru = $hargaLama; 
            }

            $update = $koneksi->prepare("
                UPDATE tBahan
                SET stok = ?, harga = ?
                WHERE kode = ?
            ");

            $update->execute([
                $stokBaru,
                $hargaBaru, 
                $row['tBahan_kode']
            ]);

            $mutasi = $koneksi->prepare("
                INSERT INTO tMutasiStok
                (
                    tanggal,
                    jenis,
                    qty,
                    stokSebelum,
                    stokSesudah,
                    referensi,
                    tBahan_kode,
                    tUser_id
                )
                VALUES
                (
                    NOW(),
                    'Pembelian',
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $mutasi->execute([
                $stokTambah,
                $stokLama,
                $stokBaru,
                $nomor,
                $row['tBahan_kode'],
                $_SESSION['id_user']
            ]);
        }

        $penerimaan = $koneksi->prepare("
            INSERT INTO tPenerimaanBarang
            (
                tanggal,
                tPembelian_nomor,
                tUser_id
            )
            VALUES
            (
                NOW(),
                ?,
                ?
            )
        ");

        $penerimaan->execute([
            $nomor,
            $_SESSION['id_user']
        ]);
        
        $stmt = $koneksi->prepare("
            UPDATE tPembelian
            SET status = 'Diterima'
            WHERE nomor = ?
        ");
        $stmt->execute([$nomor]);
        
        $koneksi->commit();
        header("Location: barangmasuk.php?success=receive");
        exit;
        
    }catch(PDOException $e){
        if($koneksi->inTransaction()){
            $koneksi->rollBack();
        }
        die($e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| DATA PEMBELIAN YANG BELUM DITERIMA (MENUNGGU)
|--------------------------------------------------------------------------
*/
$stmt = $koneksi->prepare("
    SELECT
        p.nomor,
        p.tanggal,
        p.total,
        p.status,
        s.nama AS supplier
    FROM tPembelian p
    JOIN tSupplier s
        ON p.tSupplier_id = s.id
    WHERE p.status = 'Dipesan'
    ORDER BY p.tanggal DESC
");
$stmt->execute();
$dataMenunggu = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| DATA RIWAYAT BARANG MASUK (DITERIMA)
|--------------------------------------------------------------------------
*/
$stmtRiwayat = $koneksi->prepare("
    SELECT
        p.nomor,
        pb.tanggal AS tanggal_terima,
        p.total,
        p.status,
        s.nama AS supplier,
        u.username AS penerima
    FROM tPembelian p
    JOIN tSupplier s
        ON p.tSupplier_id = s.id
    JOIN tPenerimaanBarang pb
        ON p.nomor = pb.tPembelian_nomor
    LEFT JOIN tUser u
        ON pb.tUser_id = u.id
    WHERE p.status = 'Diterima'
    ORDER BY pb.tanggal DESC
");
$stmtRiwayat->execute();
$dataRiwayat = $stmtRiwayat->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> CHARA - Barang Masuk</title>
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
          <button class="navbar-toggler navbar-toggler align-self-center d-none d-lg-flex" type="button" data-toggle="minimize">
            <span class="typcn typcn-th-menu"></span>
          </button>
        </div>
        <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
          <ul class="navbar-nav navbar-nav-right">
            <li class="nav-item nav-profile dropdown">
              <a class="nav-link dropdown-toggle  pl-0 pr-0" href="#" data-toggle="dropdown" id="profileDropdown">
                <i class="typcn typcn-user-outline mr-0"></i>
                <span class="nav-profile-name"> <?php echo $_SESSION['nama']; ?></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                <a class="dropdown-item"><i class="typcn typcn-cog text-primary"></i>Settings</a>
                <a class="dropdown-item" href="../logout.php"><i class="typcn typcn-power text-primary"></i>Logout</a>
              </div>
            </li>
          </ul>
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="typcn typcn-th-menu"></span>
          </button>
        </div>
      </nav>
      <div class="container-fluid page-body-wrapper">
        <div class="theme-setting-wrapper">
          <div id="settings-trigger"><i class="typcn typcn-cog-outline"></i></div>
          <div id="theme-settings" class="settings-panel">
            <i class="settings-close typcn typcn-delete-outline"></i>
            <p class="settings-heading">SIDEBAR SKINS</p>
            <div class="sidebar-bg-options" id="sidebar-light-theme"><div class="img-ss rounded-circle bg-light border mr-3"></div>Light</div>
            <div class="sidebar-bg-options selected" id="sidebar-dark-theme"><div class="img-ss rounded-circle bg-dark border mr-3"></div>Dark</div>
            <p class="settings-heading mt-2">HEADER SKINS</p>
            <div class="color-tiles mx-0 px-4">
              <div class="tiles success"></div><div class="tiles warning"></div><div class="tiles danger"></div>
              <div class="tiles primary"></div><div class="tiles info"></div><div class="tiles dark"></div>
              <div class="tiles default border"></div>
            </div>
          </div>
        </div>
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
            <div class="nav-search">
              <div class="input-group">
                <input type="text" class="form-control" placeholder="Type to search..." aria-label="search" aria-describedby="search">
                <div class="input-group-append">
                  <span class="input-group-text" id="search"><i class="typcn typcn-zoom"></i></span>
                </div>
              </div>
            </div>
            <?php if ($_SESSION['role'] == 'Admin'): ?>
            <p class="sidebar-menu-title"> Admin Modules</p>
          </li>
          <li class="nav-item"><a class="nav-link" href="../admin/dashboard.php"><i class="typcn typcn-device-desktop menu-icon"></i><span class="menu-title">Dashboard</span></a></li>
          <li class = "nav-item"><a class="nav-link" href="../admin/employee.php"><i class="typcn typcn-user menu-icon"></i><span class="menu-title">Employee</span></a></li>
          <li class = "nav-item"><a class="nav-link" href="../admin/biayaoperasional.php"><i class="typcn typcn-document-text menu-icon"></i><span class="menu-title">Biaya Operasional</span></a></li>
          <li class = "nav-item"><a class="nav-link" href="../admin/logaktivitas.php"><i class="typcn typcn-group menu-icon"></i><span class="menu-title">Log Aktivitas</span></a></li>
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
            <a class="nav-link" data-toggle="collapse" href="#pembelian" aria-expanded="false" aria-controls="stok">
              <i class="typcn typcn-shopping-cart menu-icon"></i><span class="menu-title">Pembelian</span><i class="menu-arrow"></i>
            </a>
          <div class="collapse" id="pembelian">
            <ul class="nav flex-column sub-menu">
              <li class ="nav-item"><a class="nav-link" href="../admin/purchaserequestadmin.php">Purchase Request</a></li>
              <li class ="nav-item"><a class="nav-link" href="../admin/hispembelian.php">Histori Pembelian</a></li>
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
            <p class = "sidebar-menu-title"> Sales Modules</p>
            <li class="nav-item"><a class="nav-link" href="../kasir/transaksipenjualan.php"><i class="typcn typcn-shopping-cart menu-icon"></i><span class="menu-title"> Transaksi Penjualan</span></a></li>
            <li class="nav-item"><a class="nav-link" href="../kasir/datapenjualan.php"><i class="typcn typcn-chart-bar menu-icon"></i><span class="menu-title"> Data Penjualan</span></a></li>
          <?php endif ?>
          <?php if ($_SESSION['role'] == 'Gudang' or $_SESSION['role'] == 'Admin'): ?>
            <p class = "sidebar-menu-title"> Stock Modules</p>
            <li class = "nav-item"><a class="nav-link" href= "bahanbaku.php"><i class="typcn typcn-th-large menu-icon"></i><span class="menu-title"> Bahan Baku</span></a></li>
            <li class = "nav-item"><a class="nav-link" href="barangmasuk.php"><i class="typcn typcn-arrow-down menu-icon"></i><span class="menu-title"> Barang Masuk </span></a></li>
            <li class = "nav-item"><a class="nav-link" href="barangkeluar.php"><i class="typcn typcn-arrow-up menu-icon"></i><span class="menu-title"> Barang Keluar</span></a></li>
            <li class = "nav-item"><a class="nav-link" href="purchaserequest.php"><i class="typcn typcn-arrow-forward-outline menu-icon"></i><span class="menu-title"> Purchase Request</span></a></li>
          <?php endif ?>
          <p class = "sidebar-menu-title"> Settings</p>
          <li class="nav-item"><a class="nav-link" href="../settings/ubahpassword.php"><i class="typcn typcn-key menu-icon"></i><span class="menu-title"> Ubah Password</span></a></li>
        </ul>
      </nav>
        <div class="main-panel">
            <div class="content-wrapper">
                
                <?php if(isset($_GET['success']) && $_GET['success'] == 'receive') : ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Barang berhasil diterima dan stok telah diperbarui!
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- TABEL 1: BARANG MENUNGGU DITERIMA -->
                <div class="row mb-4">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title text-warning">
                                    <i class="typcn typcn-time"></i> Menunggu Diterima (Pending)
                                </h4>
                                <p class="text-muted">Daftar pembelian yang sedang dipesan dan belum masuk ke gudang.</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>No Pembelian</th>
                                                <th>Tgl Pesan</th>
                                                <th>Supplier</th>
                                                <th>Total Pembelian</th>
                                                <th>Status</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php if(count($dataMenunggu) > 0): ?>
                                            <?php foreach($dataMenunggu as $row): ?>
                                            <tr>
                                                <td class="font-weight-bold">PB-<?= str_pad($row['nomor'], 4, '0', STR_PAD_LEFT) ?></td>
                                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                                <td><?= $row['supplier'] ?></td>
                                                <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                                <td><span class="badge badge-warning"><?= $row['status'] ?></span></td>
                                                <td class="text-center">
                                                    <a href="detailbarangmasuk.php?nomor=<?= $row['nomor'] ?>" class="btn btn-info btn-sm">Detail</a>
                                                    <a href="?receive=<?= $row['nomor'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Terima barang ini ke gudang?')">Receive</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada barang yang menunggu diterima</td></tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABEL 2: RIWAYAT BARANG MASUK -->
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title text-success">
                                    <i class="typcn typcn-tick"></i> Riwayat Barang Masuk
                                </h4>
                                <p class="text-muted">Daftar barang yang sudah berhasil diterima dan masuk ke stok gudang.</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>No Pembelian</th>
                                                <th>Waktu Diterima</th>
                                                <th>Supplier</th>
                                                <th>Diterima Oleh</th>
                                                <th>Status</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php if(count($dataRiwayat) > 0): ?>
                                            <?php foreach($dataRiwayat as $row): ?>
                                            <tr>
                                                <td class="font-weight-bold">PB-<?= str_pad($row['nomor'], 4, '0', STR_PAD_LEFT) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($row['tanggal_terima'])) ?></td>
                                                <td><?= $row['supplier'] ?></td>
                                                <td><?= $row['penerima'] ?: 'Sistem' ?></td>
                                                <td><span class="badge badge-success"><?= $row['status'] ?></span></td>
                                                <td class="text-center">
                                                    <a href="detailbarangmasuk.php?nomor=<?= $row['nomor'] ?>" class="btn btn-info btn-sm">Detail</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat barang masuk</td></tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
          <footer class="footer">
            <div class="d-sm-flex justify-content-center justify-content-sm-between"></div>
          </footer>
        </div>
      </div>
    </div>
    <script src="../../vendors/js/vendor.bundle.base.js"></script>
    <script src="../../js/off-canvas.js"></script>
    <script src="../../js/hoverable-collapse.js"></script>
    <script src="../../js/template.js"></script>
    <script src="../../js/settings.js"></script>
    <script src="../../js/todolist.js"></script>
    <script src="../../vendors/progressbar.js/progressbar.min.js"></script>
    <script src="../../vendors/chart.js/Chart.min.js"></script>
    <script src="../../js/dashboard.js"></script>
  </body>
</html>