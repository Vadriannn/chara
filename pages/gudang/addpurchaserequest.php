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
    // Ambil bahan baku beserta nama satuannya untuk info bantuan konversi di UI
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
        
        // PERBAIKAN BACKEND: Cek apakah ada barang di daftar tabel detail
        if (!isset($_POST['detail']) || count($_POST['detail']) == 0) {
            $error = "Gagal disimpan: Anda harus menambahkan minimal 1 barang ke dalam daftar!";
        } else {
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

            header("Location: purchaserequest.php?success=add");
            exit;
        }
    }

} catch(PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> CHARA - Buat Purchase Request</title>
    <link rel="stylesheet" href="../../vendors/typicons.font/font/typicons.css">
    <link rel="stylesheet" href="../../vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="../../css/vertical-layout-light/style.css">
    <link rel="shortcut icon" href="../../images/charaicon.png" />
    <style>
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
            <li class="nav-profile dropdown">
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
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="typcn typcn-th-menu"></span>
          </button>
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
            <li class="nav-item"><a class="nav-link" href="pages/kasir/transaksipenjualan.php"><i class="typcn typcn-shopping-cart menu-icon"></i><span class="menu-title"> Transaksi Penjualan</span></a></li>
            <li class="nav-item"><a class="nav-link" href="pages/kasir/datapenjualan.php"><i class="typcn typcn-chart-bar menu-icon"></i><span class="menu-title"> Data Penjualan</span></a></li>
          <?php endif ?>
          <?php if ($_SESSION['role'] == 'Gudang' or $_SESSION['role'] == 'Admin'): ?>
            <p class = "sidebar-menu-title"> Stock Modules</p>
            <li class = "nav-item"><a class="nav-link" href="bahanbaku.php"><i class="typcn typcn-th-large menu-icon"></i><span class="menu-title"> Bahan Baku</span></a></li>
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
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                              <div class="d-flex justify-content-between align-items-center mb-3">
                                  <h5 class="card-title mb-0">Buat Purchase Request</h5>
                                  <a href="purchaserequest.php" class="btn btn-secondary btn-sm">Kembali</a>
                              </div>
                              
                              <?php if($error != "") : ?>
                                  <div class="alert alert-danger p-2"><?= $error ?></div>
                              <?php endif; ?>
                              
                              <form method="POST">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">ID Purchase Request</label>
                                    <input type="text" class="form-control form-control-sm" value="Otomatis Dibuat Sistem" readonly>
                                </div>

                                <hr class="mt-2 mb-3">
                                <h6 class="mb-3 text-primary">Form Detail Kebutuhan Barang</h6>

                                <div class="bg-light p-3 mb-3 rounded border">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-2">
                                                <label>Bahan Baku</label>
                                                <select id="bahanSelect" class="form-control form-control-sm">
                                                    <option value="">-- Pilih Bahan --</option>
                                                    <?php while($bahan = $bahanBaku->fetch(PDO::FETCH_ASSOC)): ?>
                                                        <option value="<?= $bahan['kode']; ?>" data-nama="<?= $bahan['nama']; ?>" data-satuan="<?= $bahan['satuan']; ?>">
                                                            <?= $bahan['nama']; ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-2">
                                                <label>Jumlah Permintaan</label>
                                                <input type="number" id="jumlahBahan" class="form-control form-control-sm" min="1" value="1" step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-2">
                                                <label>Satuan Beli</label>
                                                <input type="text" id="satuanBeli" class="form-control form-control-sm" placeholder="Dus / Karung / Sak">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-2">
                                                <label>Isi Konversi Satuan</label>
                                                <input type="number" id="konversi" class="form-control form-control-sm" min="1" value="1">
                                                <small id="bantuanKonversi" class="text-info" style="font-size: 0.75rem;">Dalam satuan dasar stok</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-1">
                                        <div class="col-md-12 text-right">
                                            <button type="button" class="btn btn-success btn-sm" onclick="tambahBarang()">
                                                <i class="typcn typcn-plus"></i> Tambah ke Daftar
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive mb-3">
                                    <table class="table table-bordered table-hover table-sm">
                                        <thead style="background-color: #f4f5f7;">
                                            <tr>
                                                <th>Bahan</th>
                                                <th>Jumlah Permintaan</th>
                                                <th>Satuan Beli</th>
                                                <th>Isi Konversi Gudang</th>
                                                <th class="text-center" width="80">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabelPRBody">
                                            </tbody>
                                    </table>
                                </div>
                                
                                <div id="hiddenDetail"></div>
                                <hr class="mb-3">
                                
                                <div class="text-right">
                                    <a href="purchaserequest.php" class="btn btn-light mr-2">Batal</a>
                                    <button type="submit" class="btn btn-primary">Simpan Request</button>
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
    // Asisten label satuan dinamis saat bahan baku dipilih
    document.getElementById('bahanSelect').addEventListener('change', function() {
        let textBantuan = document.getElementById('bantuanKonversi');
        if (this.value !== "") {
            let namaSatuanBase = this.options[this.selectedIndex].dataset.satuan;
            textBantuan.innerHTML = "Akan dikalikan ke satuan dasar <b>" + namaSatuanBase + "</b>";
        } else {
            textBantuan.innerHTML = "Dalam satuan dasar stok";
        }
    });

    function tambahBarang(){
        let select = document.getElementById('bahanSelect');
        if(select.value == ''){
            alert('Pilih bahan terlebih dahulu');
            return;
        }

        let kode = select.value;
        let nama = select.options[select.selectedIndex].dataset.nama;
        let satuanBase = select.options[select.selectedIndex].dataset.satuan;
        let jumlah = document.getElementById('jumlahBahan').value;
        let satuan = document.getElementById('satuanBeli').value;
        let konversi = document.getElementById('konversi').value;

        if(jumlah == '' || jumlah <= 0){
            alert('Jumlah harus diisi dengan benar');
            return;
        }
        if(satuan == ''){
            alert('Satuan beli harus diisi');
            return;
        }
        if(konversi == '' || konversi <= 0){
            alert('Konversi harus diisi dengan benar');
            return;
        }

        if(document.querySelector(`input[name="detail[${kode}][jumlah]"]`)){
            alert('Bahan baku ini sudah ditambahkan ke dalam daftar request!');
            return;
        }

        let tbody = document.getElementById('tabelPRBody');
        let row = tbody.insertRow();
        row.setAttribute('id', 'row_' + kode);    

        row.innerHTML = `
            <td class="align-middle">${nama}</td>
            <td class="align-middle">${jumlah} ${satuan}</td>
            <td class="align-middle">${satuan}</td>
            <td class="align-middle text-muted" style="font-size: 0.85rem;">1 ${satuan} = ${konversi} ${satuanBase}</td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-danger btn-sm py-1 px-2" onclick="hapusBaris(this, '${kode}')">
                    &times;
                </button>
            </td>
        `;

        document.getElementById('hiddenDetail').insertAdjacentHTML('beforeend',
            `<div id="hidden_group_${kode}">
                <input type="hidden" name="detail[${kode}][jumlah]" value="${jumlah}">
                <input type="hidden" name="detail[${kode}][satuanBeli]" value="${satuan}">
                <input type="hidden" name="detail[${kode}][konversi]" value="${konversi}">
            </div>`
        );

        // Reset forms input
        document.getElementById('jumlahBahan').value = '1';
        document.getElementById('satuanBeli').value = '';
        document.getElementById('konversi').value = '1';
        select.selectedIndex = 0;
        document.getElementById('bantuanKonversi').innerHTML = "Dalam satuan dasar stok";
    }

    function hapusBaris(btn, kode){
        btn.closest('tr').remove();
        let hiddenGroup = document.getElementById("hidden_group_" + kode);
        if(hiddenGroup) hiddenGroup.remove();
    }

    // Interseptor Tombol Enter
    window.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault(); 
            let activeId = event.target.id;
            if (activeId === 'bahanSelect' || activeId === 'jumlahBahan' || activeId === 'satuanBeli' || activeId === 'konversi') {
                tambahBarang();
            }
        }
    });
    </script>
  </body>
</html>