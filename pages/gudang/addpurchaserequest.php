<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_gudang.php';

if (!isset($_SESSION['is_auth']) || $_SESSION['is_auth'] !== true) {
    header("Location: ../../login.php");
    exit;
}

$error = "";

try {

    // Ambil bahan baku
    $bahanBaku = $koneksi->query("
        SELECT
            b.kode,
            b.nama,
            s.nama AS satuan
        FROM tbahan b
        JOIN tsatuan s
            ON b.tSatuan_id = s.id
        ORDER BY b.nama
    ");

    // Simpan purchase request
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $idPR = "PR" . date('YmdHis');

        // Simpan header PR
        $stmt = $koneksi->prepare("
            INSERT INTO tPurchaseRequest
            (
                id,
                tanggal,
                status,
                reqBy
            )
            VALUES
            (
                ?,
                NOW(),
                'Pending',
                ?
            )
        ");

        $stmt->execute([
            $idPR,
            $_SESSION['id_user']
        ]);

        // Simpan detail barang
        if(isset($_POST['detail'])){

            foreach($_POST['detail'] as $kodeBahan => $item){
                $stmtDetail = $koneksi->prepare("
                    INSERT INTO tDetailPurchaseRequest
                    (
                        tBahan_kode,
                        tPurchaseRequest_id,
                        jumlah,
                        satuanBeli,
                        konversi
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?
                    )
                ");

                $stmtDetail->execute([
                  $kodeBahan,
                  $idPR,
                  $item['jumlah'],
                  $item['satuanBeli'],
                  $item['konversi']
              ]);
            }
        }

        header("Location: purchaserequest.php?success=add");
        exit;
    }

} catch(PDOException $e) {
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
                <a class="nav-link" href="../admin/purchaserequest.php">Purchase Request</a>
              </li>
              <li class ="nav-item">
                <a class="nav-link" href="../admin/hispembelian.php">Histori Pembelian</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="../admin/pembelian.php">Pengajuan Pembelian</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="../admin/daftarsupplier.php">Daftar Supplier</a>
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
              <a class="nav-link" href="pages/kasir/transaksipenjualan.php">
                <i class="typcn typcn-shopping-cart menu-icon"></i>
                <span class="menu-title"> Transaksi Penjualan</span>
              </a>
            </li>
          <li class="nav-item">
            <a class="nav-link" href="pages/kasir/datapenjualan.php">
              <i class="typcn typcn-chart-bar menu-icon"></i>
              <span class="menu-title"> Data Penjualan</span>
            </a>
          </li>
          <?php endif ?>
          <?php if ($_SESSION['role'] == 'Gudang' or $_SESSION['role'] == 'Admin'): ?>
           <!-- SIDEBAR MODUL GUDANG  -->
            <p class = "sidebar-menu-title"> Stock Modules</p>
            <li class = "nav-item">
              <a class="nav-link" href="bahanbaku.php">
                <i class="typcn typcn-th-large menu-icon"></i>
                <span class="menu-title"> Bahan Baku</span>
              </a>
            </li>
            <li class = "nav-item">
              <a class="nav-link" href="barangmasuk.php">
                <i class="typcn typcn-arrow-down menu-icon"></i>
                <span class="menu-title"> Barang Masuk </span>
              </a>
            </li>
            <li class = "nav-item">
              <a class="nav-link" href="barangkeluar.php">
                <i class="typcn typcn-arrow-up menu-icon"></i>
                <span class="menu-title"> Barang Keluar</span>
              </a>
            </li>
            <li class = "nav-item">
              <a class="nav-link" href="purchaserequest.php">
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
                                      Buat Purchase Request
                                  </h4>
                                  <a href="purchaserequest.php" class="btn btn-secondary">
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
                                    <label>ID Purchase Request</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        value="Otomatis Dibuat Sistem"
                                        readonly>
                                </div>

                                <hr>

                                <h5>Detail Purchase Request</h5>

                                <div class="row">

                                    <div class="col-md-3">
                                        <label>Bahan</label>
                                        <select
                                            id="bahanSelect"
                                            class="form-control">

                                            <option value="">
                                                -- Pilih Bahan --
                                            </option>

                                            <?php
                                            while($bahan = $bahanBaku->fetch(PDO::FETCH_ASSOC)):
                                            ?>
                                                <option
                                                    value="<?= $bahan['kode']; ?>"
                                                    data-nama="<?= $bahan['nama']; ?>">
                                                    <?= $bahan['nama']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Jumlah</label>
                                        <input
                                            type="number"
                                            id="jumlahBahan"
                                            class="form-control"
                                            min="1"
                                            step="0.01">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Satuan Beli</label>
                                        <input
                                            type="text"
                                            id="satuanBeli"
                                            class="form-control"
                                            placeholder="Dus / Karung / Sak">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Konversi</label>
                                        <input
                                            type="number"
                                            id="konversi"
                                            class="form-control"
                                            min="1">
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button
                                            type="button"
                                            class="btn btn-success btn-block"
                                            onclick="tambahBarang()">
                                            Tambah
                                        </button>
                                    </div>
                                </div>
                                <br>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Bahan</th>
                                            <th>Jumlah</th>
                                            <th>Satuan Beli</th>
                                            <th>Konversi</th>
                                            <th width="100">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabelPRBody">
                                    </tbody>
                                </table>
                                <div id="hiddenDetail"></div>
                                  <button
                                      type="submit"
                                      class="btn btn-primary">
                                      Simpan
                                  </button>
                                  <a
                                      href="purchaserequest.php"
                                      class="btn btn-light">
                                      Batal
                                  </a>
                                </div>
                            </form> 
                          </div>
                        </div>
                    </div>
                </div>
            </div>
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
    <script>

    function tambahBarang(){

        let select =
            document.getElementById(
                'bahanSelect'
            );

        if(select.value == ''){
            alert('Pilih bahan terlebih dahulu');
            return;
        }

        let kode =
            select.value;

        let nama =
            select.options[
                select.selectedIndex
            ].dataset.nama;

        let jumlah =
            document.getElementById(
                'jumlahBahan'
            ).value;

        let satuan =
            document.getElementById(
                'satuanBeli'
            ).value;
        let konversi =
            document.getElementById(
                'konversi'
            ).value;

        if(jumlah == '' || jumlah <= 0){
            alert('Jumlah harus diisi');
            return;
        }

        if(satuan == ''){
            alert('Satuan beli harus diisi');
            return;
        }

        if(konversi == '' || konversi <= 0){
            alert('Konversi harus diisi');
            return;
        }

        let tbody =
            document.getElementById(
                'tabelPRBody'
            );

        let row =
          tbody.insertRow();
          row.setAttribute('data-kode', kode);    

        row.innerHTML = `
            <td>${nama}</td>
            <td>${jumlah}</td>
            <td>${satuan}</td>
            <td>${konversi}</td>
            <td>
                <button
                    type="button"
                    class="btn btn-danger btn-sm"
                    onclick="hapusBaris(this)">
                    Hapus
                </button>
            </td>
        `;

        document
            .getElementById(
                'hiddenDetail'
            )
            .insertAdjacentHTML(
                'beforeend',
                `
                <input
                    type="hidden"
                    name="detail[${kode}][jumlah]"
                    value="${jumlah}">

                <input
                    type="hidden"
                    name="detail[${kode}][satuanBeli]"
                    value="${satuan}">

                <input
                    type="hidden"
                    name="detail[${kode}][konversi]"
                    value="${konversi}">
                  `
            );

        // reset input
        document.getElementById(
            'jumlahBahan'
        ).value = '';

        document.getElementById(
            'satuanBeli'
        ).value = '';

        document.getElementById(
            'konversi'
        ).value = '';

        document.getElementById(
            'bahanSelect'
        ).selectedIndex = 0;
    }

    function hapusBaris(btn){
        let row = btn.closest('tr');
        let kode = row.getAttribute('data-kode');
        document
            .querySelectorAll(
                `input[name^="detail[${kode}]"]`
            )
            .forEach(el => el.remove());
        row.remove();
    }
    </script>
  </body>
</html>