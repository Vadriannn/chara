<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <ul class="nav">
    <li class="nav-item">
      <div class="d-flex sidebar-profile">
        <div class="sidebar-profile-image">
          <?php if($_SESSION['role'] == 'Admin'): ?>
            <img src="../../images/faces/face29.png" alt="image">
          <?php elseif($_SESSION['role'] == 'Kasir'): ?>
            <img src="../../images/faces/face32.png" alt="image">
          <?php elseif($_SESSION['role'] == 'Gudang'): ?>
            <img src="../../images/faces/face33.png" alt="image">
          <?php endif; ?>
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
    </li>
    <li class="nav-item">
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
    </li>

    <?php if ($_SESSION['role'] == 'Admin'): ?>
    <p class="sidebar-menu-title"> Admin Modules</p>

    <!-- 1. Dashboards -->
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#dashboards" aria-expanded="false" aria-controls="dashboards">
        <i class="typcn typcn-chart-pie menu-icon"></i>
        <span class="menu-title">Dashboards</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="dashboards">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="../admin/dashboard.php">Dashboard Utama</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/analytics.php">Analytics Dashboard</a></li>
        </ul>
      </div>
    </li>

    <!-- 2. Master Data -->
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#masterdata" aria-expanded="false" aria-controls="masterdata">
        <i class="typcn typcn-folder menu-icon"></i>
        <span class="menu-title">Master Data</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="masterdata">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="../admin/employee.php">Karyawan</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/member.php">Pelanggan</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/daftarsupplier.php">Supplier</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/shift.php">Master Shift Kasir</a></li>
        </ul>
      </div>
    </li>

    <!-- 3. Manajemen Produk & Bahan -->
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#manajemenproduk" aria-expanded="false" aria-controls="manajemenproduk">
        <i class="typcn typcn-book menu-icon"></i>
        <span class="menu-title">Katalog & Resep</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="manajemenproduk">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="../admin/kategori.php">Kategori</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/satuan.php">Satuan</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/konversisatuan.php">Konversi Satuan</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/bahanbaku.php">Bahan Baku</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/produk.php">Produk</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/resep.php">Resep</a></li>
        </ul>
      </div>
    </li>

    <!-- 4. Pembelian -->
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#pembelian" aria-expanded="false" aria-controls="pembelian">
        <i class="typcn typcn-shopping-cart menu-icon"></i>
        <span class="menu-title">Pembelian</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="pembelian">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="../admin/purchaserequestadmin.php">Purchase Request</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/pembelian.php">Pengajuan Pembelian</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/hispembelian.php">Histori Pembelian</a></li>
        </ul>
      </div>
    </li>

    <!-- 5. Operasional & Keuangan -->
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#operasional" aria-expanded="false" aria-controls="operasional">
        <i class="typcn typcn-briefcase menu-icon"></i>
        <span class="menu-title">Operasional</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="operasional">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="../admin/biayaoperasional.php">Biaya Operasional</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/logaktivitas.php">Log Aktivitas</a></li>
        </ul>
      </div>
    </li>

    <!-- 6. Laporan -->
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#laporan" aria-expanded="false" aria-controls="laporan">
        <i class="typcn typcn-document-text menu-icon"></i>
        <span class="menu-title">Laporan</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="laporan">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="../admin/laporanpenjualan.php">Penjualan</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/aruskas.php">Arus Kas</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/labarugi.php">Laba Rugi</a></li>
        </ul>
      </div>
    </li>
    <?php endif; ?>

    <?php if ($_SESSION['role'] == 'Kasir' or $_SESSION['role'] == 'Admin'): ?>
    <p class="sidebar-menu-title"> Sales Modules</p>
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
    <li class="nav-item">
      <a class="nav-link" href="../kasir/tutupshift.php">
        <i class="typcn typcn-arrow-right-thick menu-icon"></i>
        <span class="menu-title"> Buka / Tutup Shift</span>
      </a>
    </li>
    <?php endif ?>

    <?php if ($_SESSION['role'] == 'Gudang' or $_SESSION['role'] == 'Admin'): ?>
    <p class="sidebar-menu-title"> Stock Modules</p>
    <li class="nav-item">
      <a class="nav-link" href="../gudang/bahanbaku.php">
        <i class="typcn typcn-th-large menu-icon"></i>
        <span class="menu-title"> Bahan Baku</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../gudang/barangmasuk.php">
        <i class="typcn typcn-arrow-down menu-icon"></i>
        <span class="menu-title"> Barang Masuk </span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../gudang/barangkeluar.php">
        <i class="typcn typcn-arrow-up menu-icon"></i>
        <span class="menu-title"> Barang Keluar</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="../gudang/purchaserequest.php">
        <i class="typcn typcn-arrow-forward-outline menu-icon"></i>
        <span class="menu-title"> Purchase Request</span>
      </a>
    </li>
    <?php endif ?>

    <p class="sidebar-menu-title"> Settings</p>
    <?php if ($_SESSION['role'] == 'Admin'): ?>
    <li class="nav-item">
      <a class="nav-link" href="../admin/settingchara.php">
        <i class="typcn typcn-cog-outline menu-icon"></i>
        <span class="menu-title"> Setting Chara</span>
      </a>
    </li>
    <?php endif; ?>
    <li class="nav-item">
      <a class="nav-link" href="../settings/ubahpassword.php">
        <i class="typcn typcn-key menu-icon"></i>
        <span class="menu-title"> Ubah Password</span>
      </a>
    </li>
  </ul>
</nav>

<div class="main-panel">