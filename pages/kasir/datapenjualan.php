<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';

// 1. Ambil tanggal hari ini sebagai default
$hariIni = date('Y-m-d');

// 2. Tangkap variabel dari form filter, jika kosong otomatis pakai hari ini
$tglMulai = !empty($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : $hariIni;
$tglSelesai = !empty($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : $hariIni;

// 3. Buat query dasar
$query = "
    SELECT 
        p.nomor,
        p.tanggal,
        p.total,
        p.metbayar,
        u.username AS kasir
    FROM tPenjualan p
    LEFT JOIN tUser u ON p.tUser_id = u.id
";

$where = [];
$params = [];

// 4. Tambahkan kondisi filter tanggal
if ($tglMulai != '') {
    $where[] = "DATE(p.tanggal) >= ?";
    $params[] = $tglMulai;
}

if ($tglSelesai != '') {
    $where[] = "DATE(p.tanggal) <= ?";
    $params[] = $tglSelesai;
}

// 5. Gabungkan kondisi WHERE jika ada
if (count($where) > 0) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Tambahkan urutan (terbaru di atas)
$query .= " ORDER BY p.tanggal DESC";

// 6. Eksekusi query
$stmt = $koneksi->prepare($query);
$stmt->execute($params);
$dataPenjualan = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> CHARA - Data Penjualan</title>
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
        
        <?php include '../sidebar.php'; ?>

        <div class="main-panel">
          <div class="content-wrapper">
            
            <?php if(isset($_GET['success']) && $_GET['success'] == '1') : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Transaksi penjualan berhasil disimpan!
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="row">
              <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title text-primary mb-1">
                                <i class="typcn typcn-chart-bar"></i> Data Penjualan
                            </h4>
                            <p class="text-muted mb-0">Daftar riwayat transaksi penjualan produk.</p>
                        </div>
                        <a href="transaksipenjualan.php" class="btn btn-primary btn-sm">
                            <i class="typcn typcn-plus"></i> Transaksi Baru
                        </a>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form method="GET" class="form-inline bg-light p-3 rounded">
                                <div class="form-group mr-3">
                                    <label class="mr-2 font-weight-bold">Mulai Tanggal:</label>
                                    <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tglMulai) ?>">
                                </div>
                                <div class="form-group mr-3">
                                    <label class="mr-2 font-weight-bold">Sampai Tanggal:</label>
                                    <input type="date" name="tgl_selesai" class="form-control" value="<?= htmlspecialchars($tglSelesai) ?>">
                                </div>
                                <button type="submit" class="btn btn-info btn-sm mr-2">
                                    <i class="typcn typcn-zoom"></i> Filter
                                </button>
                                <a href="datapenjualan.php" class="btn btn-light btn-sm">
                                    <i class="typcn typcn-refresh"></i> Reset
                                </a>
                            </form>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                      <table class="table table-striped table-hover">
                        <thead class="thead-light">
                          <tr>
                            <th>No Penjualan</th>
                            <th>Tanggal Waktu</th>
                            <th>Kasir</th>
                            <th>Metode Bayar</th>
                            <th>Total Belanja</th>
                            <th class="text-center">Aksi</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if(count($dataPenjualan) > 0): ?>
                              <?php foreach($dataPenjualan as $row): ?>
                              <tr>
                                  <td class="font-weight-bold">PJ-<?= str_pad($row['nomor'], 4, '0', STR_PAD_LEFT) ?></td>
                                  <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                                  <td><?= htmlspecialchars($row['kasir'] ?: 'Tidak diketahui') ?></td>
                                  <td><span class="badge badge-info"><?= $row['metbayar'] ?></span></td>
                                  <td class="text-success font-weight-bold">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                  <td class="text-center">
                                      <a href="detailpenjualan.php?nomor=<?= $row['nomor'] ?>" class="btn btn-primary btn-sm">
                                        <i class="typcn typcn-eye"></i> Detail
                                      </a>
                                  </td>
                              </tr>
                              <?php endforeach; ?>
                          <?php else: ?>
                              <tr>
                                  <td colspan="6" class="text-center text-muted py-4">
                                      Belum ada data transaksi penjualan pada rentang tanggal tersebut.
                                  </td>
                              </tr>
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
  </body>
</html>