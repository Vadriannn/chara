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
    $status     = 'Dipesan'; 
    
    $koneksi->beginTransaction();
    
    $stmtNomor = $koneksi->query("
        SELECT IFNULL(MAX(nomor),0)+1 AS next_no
        FROM tPembelian
    ");
    $nomor = $stmtNomor->fetch(PDO::FETCH_ASSOC)['next_no'];

    $kode = 'PBL-' . date('YmdHis');
    $stmt = $koneksi->prepare("
        INSERT INTO tPembelian
        (
            nomor,
            tanggal,
            total,
            tSupplier_id,
            status,
            kode
        )
        VALUES
        (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $nomor,
        $tanggal,
        $total,
        $supplierId,
        $status,
        $kode
    ]);
    
    if(isset($_POST['bahan_baku'])){
        $arrBahanKode   = $_POST['bahan_baku'];
        $arrJumlah      = $_POST['jumlah'];
        $arrSatuanBeli  = $_POST['satuan_beli'];
        $arrHargaSatuan = $_POST['harga_satuan'];
        $arrKonversi    = $_POST['isi_konversi'];    
        
        for ($i = 0; $i < count($arrBahanKode); $i++) {
            $bahanKode   = $arrBahanKode[$i];
            $jumlahBeli  = (int)$arrJumlah[$i];
            $satuanBeli  = $arrSatuanBeli[$i];
            $hargaSatuan = (float)$arrHargaSatuan[$i];
            $konversi    = (float)$arrKonversi[$i];
            $subtotal    = $jumlahBeli * $hargaSatuan;
            
            $stmtDetail = $koneksi->prepare("
                INSERT INTO tDetailPembelian
                (
                    tPembelian_nomor,
                    tBahan_kode,
                    jumlah,
                    satuanBeli,
                    konversi,
                    harga,
                    subtotal
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtDetail->execute([
                $nomor,
                $bahanKode,
                $jumlahBeli,
                $satuanBeli,
                $konversi,
                $hargaSatuan,
                $subtotal
            ]);
        }
    }

    $koneksi->commit();
    header("Location: pembelian.php?success=add");
    exit;
}
}
catch(PDOException $e) {
    if (isset($koneksi) && $koneksi->inTransaction()) {
        $koneksi->rollBack();
    }
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> CHARA - Tambah Pembelian</title>
    <link rel="stylesheet" href="../../vendors/typicons.font/font/typicons.css">
    <link rel="stylesheet" href="../../vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../../css/vertical-layout-light/style.css">
    <link rel="shortcut icon" href="../../images/charaicon.png" />
    <style>
        .summary-card {
            background-color: #f8f9fa;
            border-left: 4px solid #ff4747;
            padding: 1rem;
        }
        /* Merapikan label form agar tidak terlalu memakan spasi */
        label {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
    </style>
  </head>
  <body>
    <div class="container-scroller">
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
                <a class="dropdown-item"><i class="typcn typcn-cog text-primary"></i> Settings</a>
                <a class="dropdown-item" href="../logout.php"><i class="typcn typcn-power text-primary"></i> Logout</a>
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
          <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="typcn typcn-device-desktop menu-icon"></i><span class="menu-title">Dashboard</span></a></li>
          <li class = "nav-item"><a class="nav-link" href="employee.php"><i class="typcn typcn-user menu-icon"></i><span class="menu-title">Employee</span></a></li>
          <li class = "nav-item"><a class="nav-link" href="biayaoperasional.php"><i class="typcn typcn-document-text menu-icon"></i><span class="menu-title">Biaya Operasional</span></a></li>
          <li class = "nav-item"><a class="nav-link" href="logaktivitas.php"><i class="typcn typcn-group menu-icon"></i><span class="menu-title">Log Aktivitas</span></a></li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#stok" aria-expanded="false" aria-controls="stok">
              <i class="typcn typcn-document-text menu-icon"></i><span class="menu-title">Stok</span><i class="menu-arrow"></i>
            </a>
          <div class="collapse" id="stok">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item"><a class="nav-link" href="bahanbaku.php">Bahan Baku</a></li>
              <li class="nav-item"><a class="nav-link" href="produk.php">Produk</a></li>
              <li class="nav-item"><a class="nav-link" href="kategori.php">Kategori</a></li>
              <li class="nav-item"><a class="nav-link" href="resep.php">Resep</a></li>
            </ul>
          </div>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#pembelian" aria-expanded="false" aria-controls="stok">
              <i class="typcn typcn-shopping-cart menu-icon"></i><span class="menu-title">Pembelian</span><i class="menu-arrow"></i>
            </a>
          <div class="collapse" id="pembelian">
            <ul class="nav flex-column sub-menu">
              <li class ="nav-item"><a class="nav-link" href="purchaserequestadmin.php">Purchase Request</a></li>
              <li class ="nav-item"><a class="nav-link" href="hispembelian.php">Histori Pembelian</a></li>
              <li class="nav-item"><a class="nav-link" href="pembelian.php">Pengajuan Pembelian</a></li>
              <li class="nav-item"><a class="nav-link" href="daftarsupplier.php">Daftar Supplier</a></li>
            </ul>
          </div>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#laporan" aria-expanded="false" aria-controls="laporan">
              <i class="typcn typcn-document-text menu-icon"></i><span class="menu-title">Laporan</span><i class="menu-arrow"></i>
            </a>
          <div class="collapse" id="laporan">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item"><a class="nav-link" href="laporanpenjualan.php">Laporan Penjualan</a></li>
              <li class="nav-item"><a class="nav-link" href="laporankeuangan.php">Laporan Keuangan</a></li>
              <li class="nav-item"><a class="nav-link" href="aruskas.php">Arus Kas</a></li>
              <li class="nav-item"><a class="nav-link" href="labarugi.php">Laba Rugi</a></li>
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
            <li class = "nav-item"><a class="nav-link" href="../gudang/bahanbaku.php"><i class="typcn typcn-th-large menu-icon"></i><span class="menu-title"> Bahan Baku</span></a></li>
            <li class = "nav-item"><a class="nav-link" href="../gudang/barangmasuk.php"><i class="typcn typcn-arrow-down menu-icon"></i><span class="menu-title"> Barang Masuk </span></a></li>
            <li class = "nav-item"><a class="nav-link" href="../gudang/barangkeluar.php"><i class="typcn typcn-arrow-up menu-icon"></i><span class="menu-title"> Barang Keluar</span></a></li>
            <li class = "nav-item"><a class="nav-link" href="../gudang/purchaserequest.php"><i class="typcn typcn-arrow-forward-outline menu-icon"></i><span class="menu-title"> Purchase Request</span></a></li>
          <?php endif ?>
          <p class = "sidebar-menu-title"> Settings</p>
          <li class="nav-item"><a class="nav-link" href="../settings/ubahpassword.php"><i class="typcn typcn-key menu-icon"></i><span class="menu-title"> Ubah Password</span></a></li>
        </ul>
      </nav>
      
        <div class="main-panel">
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Tambah Pembelian (Tanpa PR)</h5>
                                    <a href="pembelian.php" class="btn btn-secondary btn-sm">Kembali</a>
                                </div>
                                
                                <?php if($error != "") : ?>
                                    <div class="alert alert-danger p-2"><?= $error ?></div>
                                <?php endif; ?>
                                
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Tanggal Pembelian</label>
                                                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Supplier</label>
                                                <select name="supplier" class="form-control form-control-sm" required>
                                                    <option value="">-- Pilih Supplier --</option>
                                                    <?php foreach($supplier as $row): ?>
                                                        <option value="<?= $row['id'] ?>"><?= $row['nama'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="mt-2 mb-3">
                                    <h6 class="mb-3 text-primary">Detail Bahan Baku</h6>
                                    
                                    <div class="bg-light p-3 mb-3 rounded border">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label>Bahan Baku</label>
                                                    <select id="selectBahan" class="form-control form-control-sm">
                                                        <option value="">-- Pilih Bahan --</option>
                                                        <?php foreach($bahan_baku as $bb): ?>
                                                            <option value="<?= $bb['kode'] ?>" data-nama="<?= $bb['nama'] ?>" data-satuan="<?= $bb['nama_satuan'] ?>">
                                                                <?= $bb['nama'] ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-2">
                                                    <label>Jumlah Beli</label>
                                                    <input type="number" id="inputJumlah" class="form-control form-control-sm" min="1" value="1" placeholder="Misal: 5">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-2">
                                                    <label>Satuan Beli</label>
                                                    <input type="text" id="inputSatuanBeli" class="form-control form-control-sm" placeholder="Misal: Sak, Dus">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-2">
                                                    <label>Isi Konversi per Satuan</label>
                                                    <input type="number" id="inputKonversi" class="form-control form-control-sm" min="1" value="1" placeholder="Misal: 25">
                                                    <small id="bantuanKonversi" class="text-info" style="font-size: 0.75rem;">Dalam satuan stok</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row align-items-end mt-1">
                                            <div class="col-md-4">
                                                <div class="form-group mb-0">
                                                    <label>Harga per Satuan Beli (Rp)</label>
                                                    <input type="number" id="inputHarga" class="form-control form-control-sm" min="0" placeholder="Misal: 200000">
                                                </div>
                                            </div>
                                            <div class="col-md-8 text-right mt-2 mt-md-0">
                                                <button type="button" class="btn btn-success btn-sm" onclick="tambahkanKeTabel()">
                                                    <i class="typcn typcn-plus"></i> Tambah ke Daftar
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive mb-3">
                                        <table class="table table-bordered table-hover table-sm">
                                            <thead style="background-color: #f4f5f7;">
                                                <tr>
                                                    <th>Bahan Baku</th>
                                                    <th>Jml Beli</th>
                                                    <th>Konversi</th>
                                                    <th class="text-primary font-weight-bold">Masuk Gudang</th>
                                                    <th>Harga / Satuan</th>
                                                    <th>Subtotal</th>
                                                    <th class="text-center" width="80">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tabelDaftarBeli">
                                                </tbody>
                                        </table>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-5 offset-md-7">
                                            <div class="summary-card rounded shadow-sm">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0 text-dark">Grand Total</h6>
                                                    <h5 class="mb-0 text-danger font-weight-bold">Rp <span id="tampilanTotalText">0</span></h5>
                                                </div>
                                                <input type="hidden" name="total" id="inputHiddenTotal" value="0">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="hiddenItemContainer"></div>

                                    <hr class="mb-3">
                                    
                                    <div class="text-right">
                                        <a href="pembelian.php" class="btn btn-light mr-2">Batal</a>
                                        <button type="submit" name="simpan" class="btn btn-primary">Simpan Transaksi</button>
                                    </div>
                                    
                                </form>
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
    
    <script>
    let grandTotalPembelian = 0;

    document.getElementById('selectBahan').addEventListener('change', function() {
        let textBantuan = document.getElementById('bantuanKonversi');
        if (this.value !== "") {
            let namaSatuan = this.options[this.selectedIndex].dataset.satuan;
            textBantuan.innerHTML = "Akan dikalikan menjadi <b>" + namaSatuan + "</b>";
        } else {
            textBantuan.innerHTML = "Dalam satuan stok";
        }
    });

    function tambahkanKeTabel() {
        let selectBahan = document.getElementById('selectBahan');
        if(selectBahan.value === "") {
            alert("Silakan pilih bahan baku terlebih dahulu!");
            return;
        }

        let kode = selectBahan.value;
        let nama = selectBahan.options[selectBahan.selectedIndex].dataset.nama;
        let satuanStok = selectBahan.options[selectBahan.selectedIndex].dataset.satuan;
        
        let jumlah = parseFloat(document.getElementById('inputJumlah').value);
        let satuanBeli = document.getElementById('inputSatuanBeli').value;
        let konversi = parseFloat(document.getElementById('inputKonversi').value);
        let hargaSatuan = parseFloat(document.getElementById('inputHarga').value);

        if(!jumlah || jumlah <= 0 || !satuanBeli || !konversi || konversi <= 0 || !hargaSatuan || hargaSatuan < 0) {
            alert("Harap lengkapi semua isian dengan benar!");
            return;
        }

        if(document.querySelector(`#input_bahan_${kode}`)) {
            alert("Barang ini sudah ada di daftar keranjang. Hapus terlebih dahulu jika ingin merevisi.");
            return;
        }

        let masukGudang = jumlah * konversi;
        let subtotal = jumlah * hargaSatuan;

        let tbody = document.getElementById('tabelDaftarBeli');
        let row = tbody.insertRow();
        row.setAttribute("id", "row_" + kode);
        
        row.innerHTML = `
            <td class="align-middle">${nama}</td>
            <td class="align-middle">${jumlah} ${satuanBeli}</td>
            <td class="text-muted align-middle" style="font-size: 0.85rem;">1 ${satuanBeli} = ${konversi} ${satuanStok}</td>
            <td class="font-weight-bold text-primary align-middle">${masukGudang} ${satuanStok}</td>
            <td class="align-middle">Rp ${hargaSatuan.toLocaleString('id-ID')}</td>
            <td class="font-weight-bold align-middle">Rp ${subtotal.toLocaleString('id-ID')}</td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-danger btn-sm py-1 px-2" onclick="hapusItem(this, '${kode}', ${subtotal})">
                    &times;
                </button>
            </td>
        `;

        let hiddenContainer = document.getElementById('hiddenItemContainer');
        hiddenContainer.insertAdjacentHTML('beforeend', `
            <div id="hidden_group_${kode}">
                <input type="hidden" id="input_bahan_${kode}" name="bahan_baku[]" value="${kode}">
                <input type="hidden" name="jumlah[]" value="${jumlah}">
                <input type="hidden" name="satuan_beli[]" value="${satuanBeli}">
                <input type="hidden" name="isi_konversi[]" value="${konversi}">
                <input type="hidden" name="harga_satuan[]" value="${hargaSatuan}">
            </div>
        `);

        grandTotalPembelian += subtotal;
        updateTampilanTotal();

        selectBahan.value = "";
        document.getElementById('inputJumlah').value = "1";
        document.getElementById('inputSatuanBeli').value = "";
        document.getElementById('inputKonversi').value = "1";
        document.getElementById('inputHarga').value = "";
        document.getElementById('bantuanKonversi').innerHTML = "Dalam satuan stok";
    }

    function hapusItem(btn, kode, subtotal) {
        grandTotalPembelian -= subtotal;
        updateTampilanTotal();
        btn.closest('tr').remove();
        let hiddenGroup = document.getElementById("hidden_group_" + kode);
        if(hiddenGroup) hiddenGroup.remove();
    }

    function updateTampilanTotal() {
        document.getElementById('tampilanTotalText').innerText = grandTotalPembelian.toLocaleString('id-ID');
        document.getElementById('inputHiddenTotal').value = grandTotalPembelian;
    }
    </script>
  </body>
</html>