<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$error = "";

try {

    // Data supplier
    $supplier = $koneksi->query("
        SELECT id, nama
        FROM tSupplier
        ORDER BY nama
    ");

    // Data bahan baku
    $bahan_baku = $koneksi->query("
        SELECT b.kode, b.nama, s.nama AS nama_satuan
        FROM tBahan b
        JOIN tSatuan s ON b.tSatuan_id = s.id
        ORDER BY b.nama
    ");

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $tanggal    = $_POST['tanggal'];
        $supplierId = $_POST['supplier'];
        $total      = $_POST['total'];

        $koneksi->beginTransaction();

        /*
        ==========================
        SIMPAN HEADER PEMBELIAN
        ==========================
        */

        $stmt = $koneksi->prepare("
            INSERT INTO tPembelian
            (
                tanggal,
                total,
                tSupplier_id
            )
            VALUES
            (
                ?, ?, ?
            )
        ");

        $stmt->execute([
            $tanggal,
            $total,
            $supplierId
        ]);

        // Ambil ID pembelian yang baru dibuat
        $pembelianId = $koneksi->lastInsertId();

        /*
        ==========================
        SIMPAN DETAIL PEMBELIAN
        ==========================
        */

        $arrBahanKode   = $_POST['bahan_baku'];
        $arrJumlah      = $_POST['jumlah'];
        $arrHargaSatuan = $_POST['harga_satuan'];

        for ($i = 0; $i < count($arrBahanKode); $i++) {

            $bahanKode   = $arrBahanKode[$i];
            $jumlah      = (int)$arrJumlah[$i];
            $hargaSatuan = (float)$arrHargaSatuan[$i];
            $subtotal    = $jumlah * $hargaSatuan;

            // Simpan detail pembelian
            $stmtDetail = $koneksi->prepare("
                INSERT INTO tDetailPembelian
                (
                    tPembelian_id,
                    tBahan_kode,
                    jumlah,
                    harga,
                    subtotal
                )
                VALUES
                (
                    ?, ?, ?, ?, ?
                )
            ");

            $stmtDetail->execute([
                $pembelianId,
                $bahanKode,
                $jumlah,
                $hargaSatuan,
                $subtotal
            ]);

            // Update stok bahan
            $stmtStok = $koneksi->prepare("
                UPDATE tBahan
                SET stok = stok + ?,
                    harga = ?
                WHERE kode = ?
            ");

            $stmtStok->execute([
                $jumlah,
                $hargaSatuan,
                $bahanKode
            ]);
        }

        $koneksi->commit();

        header("Location: pembelian.php?success=add");
        exit;
    }

} catch(PDOException $e) {

    if ($koneksi->inTransaction()) {
        $koneksi->rollBack();
    }

    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> CHARA - Employees</title>
    <!-- base:css -->
    <link rel="stylesheet" href="../../vendors/typicons.font/font/typicons.css">
    <link rel="stylesheet" href="../../vendors/css/vendor.bundle.base.css">
    <!-- endinject --> 
    <!-- plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="../../css/vertical-layout-light/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="../../images/charaicon.png" />
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-scroller">
      <!-- partial:partials/_navbar.html -->
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
            <li class="nav-item dropdown d-flex">
              <a class="nav-link count-indicator dropdown-toggle d-flex justify-content-center align-items-center" id="messageDropdown" href="#" data-toggle="dropdown">
                <i class="typcn typcn-message-typing"></i>
                <span class="count bg-success">2</span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="messageDropdown">
                <p class="mb-0 font-weight-normal float-left dropdown-header">Messages</p>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="../images/faces/face4.jpg" alt="image" class="profile-pic">
                  </div>
                  <div class="preview-item-content flex-grow">
                    <h6 class="preview-subject ellipsis font-weight-normal">David Grey
                    </h6>
                    <p class="font-weight-light small-text mb-0">
                      The meeting is cancelled
                    </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="../../images/faces/face2.jpg" alt="image" class="profile-pic">
                  </div>
                  <div class="preview-item-content flex-grow">
                    <h6 class="preview-subject ellipsis font-weight-normal">Tim Cook
                    </h6>
                    <p class="font-weight-light small-text mb-0">
                      New product launch
                    </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="../../images/faces/face3.jpg" alt="image" class="profile-pic">
                  </div>
                  <div class="preview-item-content flex-grow">
                    <h6 class="preview-subject ellipsis font-weight-normal"> Johnson
                    </h6>
                    <p class="font-weight-light small-text mb-0">
                      Upcoming board meeting
                    </p>
                  </div>
                </a>
              </div>
            </li>
            <li class="nav-item dropdown  d-flex">
              <a class="nav-link count-indicator dropdown-toggle d-flex align-items-center justify-content-center" id="notificationDropdown" href="#" data-toggle="dropdown">
                <i class="typcn typcn-bell mr-0"></i>
                <span class="count bg-danger">2</span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
                <p class="mb-0 font-weight-normal float-left dropdown-header">Notifications</p>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <div class="preview-icon bg-success">
                      <i class="typcn typcn-info-large mx-0"></i>
                    </div>
                  </div>
                  <div class="preview-item-content">
                    <h6 class="preview-subject font-weight-normal">Application Error</h6>
                    <p class="font-weight-light small-text mb-0">
                      Just now
                    </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <div class="preview-icon bg-warning">
                      <i class="typcn typcn-cog mx-0"></i>
                    </div>
                  </div>
                  <div class="preview-item-content">
                    <h6 class="preview-subject font-weight-normal">Settings</h6>
                    <p class="font-weight-light small-text mb-0">
                      Private message
                    </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <div class="preview-icon bg-info">
                      <i class="typcn typcn-user-outline mx-0"></i>
                    </div>
                  </div>
                  <div class="preview-item-content">
                    <h6 class="preview-subject font-weight-normal">New user registration</h6>
                    <p class="font-weight-light small-text mb-0">
                      2 days ago
                    </p>
                  </div>
                </a>
              </div>
            </li>
            <li class="nav-item nav-profile dropdown">
              <a class="nav-link dropdown-toggle  pl-0 pr-0" href="#" data-toggle="dropdown" id="profileDropdown">
                <i class="typcn typcn-user-outline mr-0"></i>
                <span class="nav-profile-name"> <?php echo $_SESSION['nama']; ?></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                <a class="dropdown-item">
                <i class="typcn typcn-cog text-primary"></i>
                Settings
                </a>
                <a class="dropdown-item" href="../logout.php">
                <i class="typcn typcn-power text-primary"></i>
                Logout
                </a>
              </div>
            </li>
          </ul>
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="typcn typcn-th-menu"></span>
          </button>
        </div>
      </nav>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_settings-panel.html -->
        <div class="theme-setting-wrapper">
          <div id="settings-trigger"><i class="typcn typcn-cog-outline"></i></div>
          <div id="theme-settings" class="settings-panel">
            <i class="settings-close typcn typcn-delete-outline"></i>
            <p class="settings-heading">SIDEBAR SKINS</p>
            <div class="sidebar-bg-options" id="sidebar-light-theme">
              <div class="img-ss rounded-circle bg-light border mr-3"></div>
              Light
            </div>
            <div class="sidebar-bg-options selected" id="sidebar-dark-theme">
              <div class="img-ss rounded-circle bg-dark border mr-3"></div>
              Dark
            </div>
            <p class="settings-heading mt-2">HEADER SKINS</p>
            <div class="color-tiles mx-0 px-4">
              <div class="tiles success"></div>
              <div class="tiles warning"></div>
              <div class="tiles danger"></div>
              <div class="tiles primary"></div>
              <div class="tiles info"></div>
              <div class="tiles dark"></div>
              <div class="tiles default border"></div>
            </div>
          </div>
        </div>
        <!-- partial -->
        <!-- partial:partials/_sidebar.html -->
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <li class="nav-item">
            <div class="d-flex sidebar-profile">
              <div class="sidebar-profile-image">
                <img src="../../images/faces/face29.png" alt="image">
                <span class="sidebar-status-indicator"></span>
              </div>
              <div class="sidebar-profile-name">
                <p class="sidebar-name">
                  <?php echo $_SESSION['nama']; ?>
                </p>
                <p class="sidebar-designation">
                  <?php echo $_SESSION['role']; ?>
                </p>
              </div>
            </div>
            <div class="nav-search">
              <div class="input-group">
                <input type="text" class="form-control" placeholder="Type to search..." aria-label="search" aria-describedby="search">
                <div class="input-group-append">
                  <span class="input-group-text" id="search">
                    <i class="typcn typcn-zoom"></i>
                  </span>
                </div>
              </div>
            </div>
            <!-- SIDEBAR MODUL ADMIN -->
            <?php if ($_SESSION['role'] == 'Admin'): ?>
            <p class="sidebar-menu-title"> Admin Modules</p>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
              <i class="typcn typcn-device-desktop menu-icon"></i>
              <span class="menu-title">Dashboard</span>
            </a>
          </li>
          <li class = "nav-item">
            <a class="nav-link" href="employee.php">
              <i class="typcn typcn-user menu-icon"></i>
              <span class="menu-title">Employee</span>
            </a>
          </li>
          <li class = "nav-item">
            <a class="nav-link" href="logaktivitas.php">
              <i class="typcn typcn-group menu-icon"></i>
              <span class="menu-title">Log Aktivitas</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#stok" aria-expanded="false" aria-controls="stok">
              <i class="typcn typcn-document-text menu-icon"></i>
              <span class="menu-title">Stok</span>
              <i class="menu-arrow"></i>
            </a>
          <div class="collapse" id="stok">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item">
                <a class="nav-link" href="bahanbaku.php">Bahan Baku</a>
              </li>
              
              <li class="nav-item">
                <a class="nav-link" href="produk.php">Produk</a>
              </li>

              <li class="nav-item">
                <a class="nav-link" href="kategori.php">Kategori</a>
              </li>

              <li class="nav-item">
                <a class="nav-link" href="resep.php">Resep</a>
              </li>
            </ul>
          </div>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#pembelian" aria-expanded="false" aria-controls="stok">
              <i class="typcn typcn-shopping-cart menu-icon"></i>
              <span class="menu-title">Pembelian</span>
              <i class="menu-arrow"></i>
            </a>
          <div class="collapse" id="pembelian">
            <ul class="nav flex-column sub-menu">
              <li class ="nav-item">
                <a class="nav-link" href="purchaserequest.php">Purchase Request</a>
              </li>
              <li class ="nav-item">
                <a class="nav-link" href="hispembelian.php">Histori Pembelian</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="pembelian.php">Pengajuan Pembelian</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="daftarsupplier.php">Daftar Supplier</a>
              </li>
            </ul>
          </div>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#laporan" aria-expanded="false" aria-controls="laporan">
              <i class="typcn typcn-document-text menu-icon"></i>
              <span class="menu-title">Laporan</span>
              <i class="menu-arrow"></i>
            </a>
          <div class="collapse" id="laporan">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item">
                <a class="nav-link" href="laporanpenjualan.php">Laporan Penjualan</a>
              </li>
              
              <li class="nav-item">
                <a class="nav-link" href="laporankeuangan.php">Laporan Keuangan</a>
              </li>

              <li class="nav-item">
                <a class="nav-link" href="aruskas.php">Arus Kas</a>
              </li>

              <li class="nav-item">
                <a class="nav-link" href="labarugi.php">Laba Rugi</a>
              </li>
            </ul>
          </div>
          </li>
          <?php endif; ?>
          <?php if ($_SESSION['role'] == 'Kasir' or $_SESSION['role'] == 'Admin'): ?>
          <!-- SIDEBAR MODUL KASIR -->
            <p class = "sidebar-menu-title"> Sales Modules</p>
            <li class="nav-item">
              <a class="nav-link" href="../kasir/transaksipenjualan.php">
                <i class="typcn typcn-shopping-cart menu-icon"></i>
                <span class="menu-title"> Transaksi Penjualan</span>
              </a>
            </li>
          <li class="nav-item">
            <a class="nav-link" href="../kasir/datapenjualan.php">
              <i class="typcn typcn-chart-bar menu-icon"></i>
              <span class="menu-title"> Data Penjualan</span>
            </a>
          </li>
          <?php endif ?>
          <?php if ($_SESSION['role'] == 'Gudang' or $_SESSION['role'] == 'Admin'): ?>
           <!-- SIDEBAR MODUL GUDANG  -->
            <p class = "sidebar-menu-title"> Stock Modules</p>
            <li class = "nav-item">
              <a class="nav-link" href="../gudang/bahanbaku.php">
                <i class="typcn typcn-th-large menu-icon"></i>
                <span class="menu-title"> Bahan Baku</span>
              </a>
            </li>
            <li class = "nav-item">
              <a class="nav-link" href="../gudang/barangmasuk.php">
                <i class="typcn typcn-arrow-down menu-icon"></i>
                <span class="menu-title"> Barang Masuk </span>
              </a>
            </li>
            <li class = "nav-item">
              <a class="nav-link" href="../gudang/barangkeluar.php">
                <i class="typcn typcn-arrow-up menu-icon"></i>
                <span class="menu-title"> Barang Keluar</span>
              </a>
            </li>
            <li class = "nav-item">
              <a class="nav-link" href="../gudang/purchaserequest.php">
                <i class="typcn typcn-arrow-forward-outline menu-icon"></i>
                <span class="menu-title"> Purchase Request</span>
              </a>
            </li>
            <?php endif ?>
            <!-- SIDEBAR MENU SETTINGS -->
            <p class = "sidebar-menu-title"> Settings</p>
          <li class="nav-item">
            <a class="nav-link" href="../settings/ubahpassword.php">
              <i class="typcn typcn-key menu-icon"></i>
              <span class="menu-title"> Ubah Password</span>
            </a>
          </li>
      </nav>
        <!-- partial -->
       <div class="main-panel">
    <div class="content-wrapper">
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                Tambah Pembelian
                            </h4>
                            <a href="pembelian.php" class="btn btn-secondary">
                                Kembali
                            </a>
                        </div>
                        
                        <?php if($error != "") : ?>
                            <div class="alert alert-danger">
                                <?= $error ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="form-group">
                                <label>Nomor Pembelian</label>
                                <input
                                    type="text"
                                    name="nomor"
                                    class="form-control"
                                    placeholder="Contoh: 1001"
                                    required>
                            </div>
                            
                            <div class="form-group">
                                <label>Tanggal Pembelian</label>
                                <input
                                    type="date"
                                    name="tanggal"
                                    class="form-control"
                                    value="<?= date('Y-m-d') ?>"
                                    required>
                            </div>
                            
                            <div class="form-group">
                                <label>Supplier</label>
                                <select
                                    name="supplier"
                                    class="form-control"
                                    required>
                                    <option value="">
                                        -- Pilih Supplier --
                                    </option>
                                    <?php foreach($supplier as $row): ?>
                                        <option value="<?= $row['id'] ?>">
                                            <?= $row['nama'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <hr class="my-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Detail Bahan Baku</h5>
                                <button type="button" class="btn btn-success btn-sm" id="btn-tambah-baris">
                                    + Tambah Baris
                                </button>
                            </div>

                            <div class="table-responsive mb-3">
                                <table class="table table-bordered" id="tabel-detail-pembelian">
                                    <thead>
                                        <tr class="bg-light">
                                            <th>Bahan Baku</th>
                                            <th width="15%">Jumlah</th>
                                            <th width="25%">Harga Satuan</th>
                                            <th width="25%">Subtotal</th>
                                            <th width="8%" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <select name="bahan_baku[]" class="form-control" required>
                                                    <option value="">-- Pilih Bahan Baku --</option>
                                                    <?php foreach($bahan_baku as $bb): ?>
                                                        <option value="<?= $bb['kode'] ?>">
                                                            <?= $bb['kode'] ?> - <?= $bb['nama'] ?> (<?= $bb['nama_satuan'] ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="jumlah[]" class="form-control input-jumlah" min="1" value="1" required>
                                            </td>
                                            <td>
                                                <input type="number" name="harga_satuan[]" class="form-control input-harga" min="0" placeholder="0" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control input-subtotal" value="0" readonly>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm btn-hapus-baris" disabled>&times;</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="form-group row justify-content-end mt-4">
                                <label class="col-sm-3 col-form-label text-right font-weight-bold">Total Pembelian:</label>
                                <div class="col-sm-4">
                                    <input 
                                        type="number" 
                                        name="total" 
                                        id="total-pembelian" 
                                        class="form-control form-control-lg font-weight-bold text-danger" 
                                        value="0" 
                                        readonly>
                                </div>
                            </div>
                            
                            <button
                                type="submit"
                                name="simpan"
                                class="btn btn-primary">
                                Simpan
                            </button>
                            <a
                                href="pembelian.php"
                                class="btn btn-light">
                                Batal
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabelBody = document.querySelector('#tabel-detail-pembelian tbody');
    const btnTambahBaris = document.getElementById('btn-tambah-baris');
    const totalPembelianInput = document.getElementById('total-pembelian');

    function hitungTotal() {
        let grandTotal = 0;
        const baris = tabelBody.querySelectorAll('tr');

        baris.forEach(function (row) {
            const jumlah = parseFloat(row.querySelector('.input-jumlah').value) || 0;
            const harga = parseFloat(row.querySelector('.input-harga').value) || 0;
            const subtotal = jumlah * harga;
            
            row.querySelector('.input-subtotal').value = subtotal;
            grandTotal += subtotal;
        });

        totalPembelianInput.value = grandTotal;
    }

    tabelBody.addEventListener('input', function (e) {
        if (e.target.classList.contains('input-jumlah') || e.target.classList.contains('input-harga')) {
            hitungTotal();
        }
    });

    btnTambahBaris.addEventListener('click', function () {
        const semuaBaris = tabelBody.querySelectorAll('tr');
        const barisBaru = semuaBaris[0].cloneNode(true); 

        // Reset field value baris baru
        barisBaru.querySelector('select').value = '';
        barisBaru.querySelector('.input-jumlah').value = 1;
        barisBaru.querySelector('.input-harga').value = '';
        barisBaru.querySelector('.input-subtotal').value = 0;
        
        const btnHapus = barisBaru.querySelector('.btn-hapus-baris');
        btnHapus.removeAttribute('disabled');

        tabelBody.appendChild(barisBaru);
        
        if (semuaBaris.length >= 1) {
            semuaBaris[0].querySelector('.btn-hapus-baris').removeAttribute('disabled');
        }
    });

    tabelBody.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-hapus-baris')) {
            e.target.closest('tr').remove();
            hitungTotal();

            const sisaBaris = tabelBody.querySelectorAll('tr');
            if (sisaBaris.length === 1) {
                sisaBaris[0].querySelector('.btn-hapus-baris').setAttribute('disabled', 'disabled');
            }
        }
    });
});
</script>>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
          <footer class="footer">
            <div class="d-sm-flex justify-content-center justify-content-sm-between">
            <!-- FOOTER -->
            </div>
          </footer>
          <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- base:js -->
    <script src="../../vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page-->
    <!-- End plugin js for this page-->
    <!-- inject:js -->
    <script src="../../js/off-canvas.js"></script>
    <script src="../../js/hoverable-collapse.js"></script>
    <script src="../../js/template.js"></script>
    <script src="../../js/settings.js"></script>
    <script src="../../js/todolist.js"></script>
    <!-- endinject -->
    <!-- plugin js for this page -->
    <script src="../../vendors/progressbar.js/progressbar.min.js"></script>
    <script src="../../vendors/chart.js/Chart.min.js"></script>
    <!-- End plugin js for this page -->
    <!-- Custom js for this page-->
    <script src="../../js/dashboard.js"></script>
    <!-- End custom js for this page-->
  </body>
</html>