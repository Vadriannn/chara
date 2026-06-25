<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$pr = $_GET['pr'] ?? '';

if(isset($_POST['simpan'])){
    try{
        $koneksi->beginTransaction();
        
        $stmtNomor = $koneksi->query("
            SELECT IFNULL(MAX(nomor),0)+1 AS next_no
            FROM tPembelian
        ");
        $nextNo = $stmtNomor->fetch(PDO::FETCH_ASSOC)['next_no'];
        
        $kodePembelian = 'PBL-' . date('Ymd') . '-' . str_pad($nextNo, 4, '0', STR_PAD_LEFT);
        
        $stmt = $koneksi->prepare("
            INSERT INTO tPembelian
            (
                nomor,
                tanggal,
                total,
                status,
                kode,
                tSupplier_id,
                tPurchaseRequest_id
            )
            VALUES
            (?, NOW(), 0, 'Dipesan', ?, ?, ?)
        ");
        $stmt->execute([
            $nextNo,
            $kodePembelian,
            $_POST['supplier'],
            $pr
        ]);
        
        $nomorPembelian = $nextNo;
        $total = 0;
        
        $stmtPR = $koneksi->prepare("
            SELECT *
            FROM tDetailPurchaseRequest
            WHERE tPurchaseRequest_id = ?
            ORDER BY tBahan_kode
        ");
        $stmtPR->execute([$pr]);
        
        $hargaInput = $_POST['harga'];
        $no = 0;
        
        while($row = $stmtPR->fetch(PDO::FETCH_ASSOC))
        {
            $harga = $hargaInput[$no];
            $subtotal = $harga * $row['jumlah'];
            $total += $subtotal;
            
            $stmtDetail = $koneksi->prepare("
                INSERT INTO tDetailPembelian
                (
                    tBahan_kode,
                    tPembelian_nomor,
                    jumlah,
                    satuanBeli,
                    konversi,
                    harga,
                    subtotal
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtDetail->execute([
                $row['tBahan_kode'],
                $nomorPembelian,
                $row['jumlah'],
                $row['satuanBeli'],
                $row['konversi'],
                $harga,
                $subtotal
            ]);
            $no++;
        }
        
        $stmt = $koneksi->prepare("
            UPDATE tPembelian
            SET total = ?
            WHERE nomor = ?
        ");
        $stmt->execute([$total, $nomorPembelian]);

        $stmtPRUpdate = $koneksi->prepare("
            UPDATE tPurchaseRequest
            SET status = 'Approved'
            WHERE id = ?
        ");
        $stmtPRUpdate->execute([$pr]);

        $koneksi->commit();

        echo "
        <script>
            alert('Pembelian berhasil dibuat!');
            window.location='pembelian.php';
        </script>
        ";
        exit;

    }catch(PDOException $e){
        if($koneksi->inTransaction()){
            $koneksi->rollBack();
        }
        die('ERROR : '.$e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> CHARA - Buat Pembelian</title>
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
        .form-control-plaintext {
            background-color: transparent !important;
            border: none !important;
            font-weight: 500;
        }
    </style>
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
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <?php
                                $stmt = $koneksi->prepare("
                                  SELECT
                                      pr.id,
                                      pr.status,
                                      b.nama,
                                      d.tBahan_kode,
                                      d.jumlah,
                                      d.satuanBeli,
                                      d.konversi
                                  FROM tPurchaseRequest pr
                                  JOIN tDetailPurchaseRequest d
                                      ON pr.id = d.tPurchaseRequest_id
                                  JOIN tBahan b
                                      ON d.tBahan_kode = b.kode
                                  WHERE pr.id = ?
                                  ORDER BY d.tBahan_kode
                                ");
                                $stmt->execute([$pr]);
                                $detailPR = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                $supplier = $koneksi->query("
                                    SELECT *
                                    FROM tSupplier
                                    ORDER BY nama
                                ");
                                ?>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Buat Pembelian (Berdasarkan PR)</h5>
                                    <a href="purchaserequestadmin.php" class="btn btn-secondary btn-sm">Kembali</a>
                                </div>
                                
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="font-weight-bold text-muted mb-1">Nomor Purchase Request</label>
                                                <input type="text" class="form-control form-control-plaintext form-control-lg text-primary" value="<?= $pr ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label class="font-weight-bold mb-2">Pilih Supplier Penerima Order</label>
                                                <select name="supplier" class="form-control form-control-sm" required>
                                                    <option value="">-- Pilih Supplier --</option>
                                                    <?php while($s = $supplier->fetch(PDO::FETCH_ASSOC)): ?>
                                                        <option value="<?= $s['id'] ?>"><?= $s['nama'] ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="mt-2 mb-4">
                                    <h6 class="mb-3 text-info">Daftar Barang yang Diajukan</h6>
                                    <p class="text-muted small mb-3">Silakan lengkapi harga beli per satuan (*harga otomatis dikalikan dengan jumlah pengajuan*).</p>

                                    <div class="table-responsive mb-4">
                                        <table class="table table-bordered table-hover table-sm">
                                            <thead style="background-color: #f4f5f7;">
                                                <tr>
                                                    <th>Kode</th>
                                                    <th>Nama Bahan Baku</th>
                                                    <th>Jumlah Diajukan</th>
                                                    <th>Satuan Beli</th>
                                                    <th width="20%">Harga per Satuan (Rp)</th>
                                                    <th width="20%">Subtotal (Rp)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($detailPR as $index => $d): ?>
                                                  <tr>
                                                      <td class="align-middle text-muted">
                                                          <?= $d['tBahan_kode'] ?>
                                                          <input type="hidden" name="kode[]" value="<?= $d['tBahan_kode'] ?>">
                                                      </td>
                                                      <td class="align-middle font-weight-bold"><?= $d['nama'] ?></td>
                                                      <td class="align-middle">
                                                          <?= $d['jumlah'] ?>
                                                          <input type="hidden" class="jumlah" value="<?= $d['jumlah'] ?>">
                                                      </td>
                                                      <td class="align-middle"><?= $d['satuanBeli'] ?></td>
                                                      <td class="align-middle">
                                                          <input type="number" name="harga[]" class="form-control form-control-sm harga" min="0" placeholder="0" required>
                                                      </td>
                                                      <td class="align-middle">
                                                          <input type="text" class="form-control form-control-sm form-control-plaintext subtotal" value="0" readonly>
                                                      </td>
                                                  </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-5 offset-md-7">
                                            <div class="summary-card rounded shadow-sm">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0 text-dark">Grand Total</h6>
                                                    <h5 class="mb-0 text-danger font-weight-bold">Rp <span id="grandTotal">0</span></h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="mb-4 mt-2">
                                    
                                    <div class="text-right">
                                        <a href="purchaserequestadmin.php" class="btn btn-light mr-2">Batal</a>
                                        <button type="submit" name="simpan" class="btn btn-primary">Proses & Setujui Pembelian</button>
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
      function hitungTotal() {
          let grandTotal = 0;
          document.querySelectorAll(".harga").forEach(function(input){
              let row = input.closest("tr");
              let jumlahInput = row.querySelector(".jumlah");
              let subtotalInput = row.querySelector(".subtotal");

              let jumlah = parseFloat(jumlahInput.value) || 0;
              let harga = parseFloat(input.value) || 0;

              let subtotal = jumlah * harga;

              // Format subtotal ke dalam style rupiah yang rapi
              subtotalInput.value = subtotal.toLocaleString('id-ID');
              grandTotal += subtotal;
          });

          // Update Grand Total di UI
          document.getElementById("grandTotal").textContent = grandTotal.toLocaleString('id-ID');
      }

      // Pasang event listener pada setiap kolom harga
      document.querySelectorAll(".harga").forEach(function(input){
          input.addEventListener("input", hitungTotal);
      });

      // Hitung saat pertama kali halaman dimuat (berjaga-jaga)
      hitungTotal();
    </script>
  </body>
</html>