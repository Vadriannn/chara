<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';

$error = "";

try {
    // Ambil produk aktif untuk dropdown
    $produk = $koneksi->query("
        SELECT *
        FROM tProduct
        WHERE status = 'Aktif'
        ORDER BY nama
    ");

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $produkArray = $_POST['produk_kode'];
        $qtyArray = $_POST['qty_beli'];
        $metbayar = $_POST['metbayar'];
        
        // Tangkap input diskon dalam persentase (default 0 jika kosong)
        $diskonPersen = isset($_POST['diskon']) ? (float)$_POST['diskon'] : 0;

        if(empty($produkArray)) {
            throw new Exception("Keranjang belanja kosong!");
        }

        $koneksi->beginTransaction();

        // 1. Generate nomor penjualan
        $stmtNomor = $koneksi->query("
            SELECT nomor
            FROM tPenjualan
            ORDER BY nomor DESC
            LIMIT 1
        ");
        $last = $stmtNomor->fetch(PDO::FETCH_ASSOC);
        $nomorPenjualan = $last ? $last['nomor'] + 1 : 1;

        // 2. Hitung Subtotal Keranjang
        $subtotalKeranjang = 0;
        foreach($produkArray as $index => $kodeProduk) {
            $qty = (int)$qtyArray[$index];
            $stmtCekHarga = $koneksi->prepare("SELECT hargaJual FROM tProduct WHERE kode = ?");
            $stmtCekHarga->execute([$kodeProduk]);
            $hargaJual = $stmtCekHarga->fetchColumn();
            $subtotalKeranjang += ($hargaJual * $qty);
        }

        // Pastikan persentase diskon tidak lebih dari 100% atau kurang dari 0%
        if($diskonPersen > 100) $diskonPersen = 100;
        if($diskonPersen < 0) $diskonPersen = 0;
        
        // Hitung nominal diskon dari persentase
        $diskonNominal = $subtotalKeranjang * ($diskonPersen / 100);
        
        // Total akhir setelah dipotong nominal diskon
        $grandTotal = $subtotalKeranjang - $diskonNominal;

        // 3. Simpan header penjualan (Diskon yang disimpan ke database adalah nominal Rupiah-nya)
        $stmtPenjualan = $koneksi->prepare("
            INSERT INTO tPenjualan (nomor, tanggal, total, diskon, metbayar, tUser_id)
            VALUES (?, NOW(), ?, ?, ?, ?)
        ");
        $stmtPenjualan->execute([
            $nomorPenjualan,
            $grandTotal,
            $diskonNominal,
            $metbayar,
            $_SESSION['id_user']
        ]);

        // 4. Looping untuk Detail Penjualan dan Potong Stok
        $stmtDetail = $koneksi->prepare("
            INSERT INTO tDetailPenjualan (tProduct_kode, tPenjualan_nomor, hpp, harga_jual, jumlah, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmtHpp = $koneksi->prepare("
            SELECT SUM(r.jumlah * b.harga) AS hpp
            FROM tResep r
            JOIN tBahan b ON r.tBahan_kode = b.kode
            WHERE r.tProduct_kode = ?
        ");

        $stmtResep = $koneksi->prepare("
            SELECT r.tBahan_kode, r.jumlah, b.stok
            FROM tResep r
            JOIN tBahan b ON r.tBahan_kode = b.kode
            WHERE r.tProduct_kode = ?
        ");

        $updateStok = $koneksi->prepare("UPDATE tBahan SET stok = ? WHERE kode = ?");
        
        $stmtMutasi = $koneksi->prepare("
            INSERT INTO tMutasiStok (tanggal, jenis, qty, stokSebelum, stokSesudah, referensi, tBahan_kode, tUser_id)
            VALUES (NOW(), 'Penjualan', ?, ?, ?, ?, ?, ?)
        ");

        foreach($produkArray as $index => $kodeProduk) {
            $qty = (int)$qtyArray[$index];

            // Ambil data produk
            $stmtProdukInfo = $koneksi->prepare("SELECT hargaJual FROM tProduct WHERE kode = ?");
            $stmtProdukInfo->execute([$kodeProduk]);
            $hargaJual = $stmtProdukInfo->fetchColumn();
            $subtotal = $hargaJual * $qty;

            // Hitung HPP
            $stmtHpp->execute([$kodeProduk]);
            $hpp = $stmtHpp->fetch(PDO::FETCH_ASSOC)['hpp'];
            if(!$hpp) $hpp = 0;

            // Simpan Detail
            $stmtDetail->execute([
                $kodeProduk,
                $nomorPenjualan,
                $hpp,
                $hargaJual,
                $qty,
                $subtotal
            ]);

            // Potong Stok Bahan
            $stmtResep->execute([$kodeProduk]);
            while($resep = $stmtResep->fetch(PDO::FETCH_ASSOC)) {
                $qtyKeluar = $resep['jumlah'] * $qty;
                $stokSebelum = $resep['stok'];
                $stokSesudah = $stokSebelum - $qtyKeluar;

                if($stokSesudah < 0){
                    throw new Exception("Stok bahan ".$resep['tBahan_kode']." tidak mencukupi untuk produk ".$kodeProduk);
                }

                // Update & Mutasi
                $updateStok->execute([$stokSesudah, $resep['tBahan_kode']]);
                
                $stmtMutasi->execute([
                    $qtyKeluar,
                    $stokSebelum,
                    $stokSesudah,
                    'PJ-'.$nomorPenjualan,
                    $resep['tBahan_kode'],
                    $_SESSION['id_user']
                ]);
            }
        }

        $koneksi->commit();
        header("Location: datapenjualan.php?success=1");
        exit;
    }

} catch(Exception $e) {
    if($koneksi->inTransaction()){
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
    <title> CHARA - Transaksi Penjualan</title>
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
            </li>
            
            <?php if ($_SESSION['role'] == 'Admin'): ?>
            <p class="sidebar-menu-title"> Admin Modules</p>
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
              <a class="nav-link" data-toggle="collapse" href="#pembelian" aria-expanded="false" aria-controls="pembelian">
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
              <p class="sidebar-menu-title"> Sales Modules</p>
              <li class="nav-item"><a class="nav-link" href="../kasir/transaksipenjualan.php"><i class="typcn typcn-shopping-cart menu-icon"></i><span class="menu-title"> Transaksi Penjualan</span></a></li>
              <li class="nav-item"><a class="nav-link" href="../kasir/datapenjualan.php"><i class="typcn typcn-chart-bar menu-icon"></i><span class="menu-title"> Data Penjualan</span></a></li>
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
            <div class="row">
              <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <h4 class="card-title mb-0">Transaksi Penjualan</h4>
                      <a href="datapenjualan.php" class="btn btn-primary">Data Penjualan</a>
                    </div>
                    
                    <?php if($error != "") : ?>
                      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                      <div class="row bg-light p-3 mb-4 rounded align-items-end">
                          <div class="col-md-6">
                              <label>Pilih Produk</label>
                              <select id="produkSelect" class="form-control">
                                  <option value="">-- Pilih Produk --</option>
                                  <?php while($p = $produk->fetch(PDO::FETCH_ASSOC)): ?>
                                      <option 
                                          value="<?= $p['kode']; ?>" 
                                          data-nama="<?= $p['nama']; ?>"
                                          data-harga="<?= $p['hargaJual']; ?>">
                                          <?= $p['nama']; ?> - Rp <?= number_format($p['hargaJual'],0,',','.'); ?>
                                      </option>
                                  <?php endwhile; ?>
                              </select>
                          </div>
                          <div class="col-md-3">
                              <label>Qty</label>
                              <input type="number" id="qtyProduk" class="form-control" min="1" value="1">
                          </div>
                          <div class="col-md-3">
                              <button type="button" class="btn btn-success btn-block" onclick="tambahKeranjang()">
                                  Tambah ke Keranjang
                              </button>
                          </div>
                      </div>

                      <table class="table table-bordered">
                        <thead>
                          <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                            <th width="100">Aksi</th>
                          </tr>
                        </thead>
                        <tbody id="tabelKeranjangBody">
                            </tbody>
                      </table>

                      <div class="row mt-3 mb-4">
                          <div class="col-md-5 offset-md-7">
                              <table class="table table-borderless text-right">
                                  <tr>
                                      <th class="align-middle">Subtotal</th>
                                      <td><h4>Rp <span id="subtotalDisplay">0</span></h4></td>
                                  </tr>
                                  <tr>
                                      <th class="align-middle">Diskon (%)</th>
                                      <td>
                                          <input type="number" name="diskon" id="inputDiskon" class="form-control text-right" min="0" max="100" step="0.1" value="0" placeholder="0" oninput="hitungTotalAkhir()">
                                          <small id="nominalDiskonDisplay" class="text-danger d-block mt-1 font-weight-bold">- Rp 0</small>
                                      </td>
                                  </tr>
                                  <tr>
                                      <th class="align-middle">Grand Total</th>
                                      <td><h2 class="text-success mb-0">Rp <span id="grandTotalDisplay">0</span></h2></td>
                                  </tr>
                              </table>
                          </div>
                      </div>

                      <div id="hiddenCartData"></div>

                      <div class="row">
                          <div class="col-md-4">
                              <div class="form-group">
                                  <label>Metode Pembayaran</label>
                                  <select name="metbayar" class="form-control" required>
                                      <option value="Tunai">Tunai</option>
                                      <option value="QRIS">QRIS</option>
                                      <option value="Debit">Debit</option>
                                  </select>
                              </div>
                          </div>
                      </div>

                      <button type="submit" class="btn btn-danger" onclick="return validasiSubmit()">Simpan Transaksi</button>
                      <a href="datapenjualan.php" class="btn btn-light">Batal</a>
                    </form>

                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <script src="../../vendors/js/vendor.bundle.base.js"></script>
    <script src="../../js/off-canvas.js"></script>
    <script src="../../js/hoverable-collapse.js"></script>
    <script src="../../js/template.js"></script>
    <script src="../../js/settings.js"></script>
    <script src="../../js/todolist.js"></script>
    
    <script>
    let subtotalKeranjang = 0;
    
    function tambahKeranjang(){
        let select = document.getElementById('produkSelect');
        if(select.value == '') {
            alert('Pilih produk terlebih dahulu!');
            return;
        }

        let kode = select.value;
        if(document.querySelector(`#cart_${kode}`)){
            alert('Produk sudah ada di keranjang! Hapus baris terlebih dahulu jika ingin merubah quantity.');
            return;
        }

        let nama = select.options[select.selectedIndex].dataset.nama;
        let harga = parseFloat(select.options[select.selectedIndex].dataset.harga);
        let qty = parseInt(document.getElementById('qtyProduk').value) || 0;

        if(qty <= 0){
            alert('Quantity harus lebih dari 0');
            return;
        }

        let subtotalBaris = harga * qty;

        let tbody = document.getElementById('tabelKeranjangBody');
        let row = tbody.insertRow();
        row.setAttribute('id', 'row_' + kode);

        row.innerHTML = `
            <td>${nama}</td>
            <td>Rp ${harga.toLocaleString('id-ID')}</td>
            <td>${qty}</td>
            <td>Rp ${subtotalBaris.toLocaleString('id-ID')}</td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this, ${subtotalBaris}, '${kode}')">Hapus</button>
            </td>
        `;

        subtotalKeranjang += subtotalBaris; 
        hitungTotalAkhir(); 

        let hiddenCart = document.getElementById('hiddenCartData');
        hiddenCart.insertAdjacentHTML('beforeend', `
            <div id="cart_${kode}">
                <input type="hidden" name="produk_kode[]" value="${kode}">
                <input type="hidden" name="qty_beli[]" value="${qty}">
            </div>
        `);

        document.getElementById('produkSelect').value = '';
        document.getElementById('qtyProduk').value = '1';
    }

    function hapusBaris(btn, subtotalBaris, kode){
        subtotalKeranjang -= subtotalBaris;
        hitungTotalAkhir(); 
        
        btn.closest('tr').remove();
        let hiddenInput = document.getElementById('cart_' + kode);
        if(hiddenInput) hiddenInput.remove();
    }

    function hitungTotalAkhir() {
        // Ambil input persen diskon
        let diskonPersen = parseFloat(document.getElementById('inputDiskon').value) || 0;
        
        // Batasi persen agar tidak lebih dari 100 atau kurang dari 0
        if(diskonPersen > 100) {
            diskonPersen = 100;
            document.getElementById('inputDiskon').value = 100;
        } else if(diskonPersen < 0) {
            diskonPersen = 0;
            document.getElementById('inputDiskon').value = 0;
        }

        // Kalkulasi nominal rupiah yang didiskon
        let nominalDiskon = subtotalKeranjang * (diskonPersen / 100);
        
        // Kalkulasi total akhir
        let grandTotal = subtotalKeranjang - nominalDiskon;

        // Tampilkan ke UI
        document.getElementById('subtotalDisplay').innerText = subtotalKeranjang.toLocaleString('id-ID');
        document.getElementById('nominalDiskonDisplay').innerText = '- Rp ' + nominalDiskon.toLocaleString('id-ID');
        document.getElementById('grandTotalDisplay').innerText = grandTotal.toLocaleString('id-ID');
    }

    function validasiSubmit() {
        if(subtotalKeranjang === 0) {
            alert("Keranjang masih kosong!");
            return false;
        }
        return true;
    }
    </script>
  </body>
</html>