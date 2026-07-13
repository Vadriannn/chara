<?php
session_start();
$page_title = "CHARA - Histori Pembelian";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php'; 

$error = "";
$success_msg = "";

// Cek jika ada parameter sukses dari halaman tambahpembelian.php
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'add') {
        $success_msg = "Pengajuan pembelian baru berhasil disimpan dan sedang menunggu ACC Gudang!";
    }
}

try {
    // Query untuk mengambil semua data master pembelian beserta nama supplier-nya
    $queryPembelian = $koneksi->query("
        SELECT p.*, s.nama AS nama_supplier
        FROM tPembelian p
        JOIN tSupplier s ON p.tSupplier_id = s.id
        WHERE p.status IN ('Dipesan', 'Dibatalkan')
        ORDER BY p.tanggal DESC, p.nomor DESC
    ");
    $dataPembelian = $queryPembelian->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = $e->getMessage();
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="card-title mb-1">Pengajuan Pembelian</h4>
                                <p class="text-muted mb-0">
                                    Kelola Data Transaksi dan Status Pembelian Bahan Baku.
                                </p>
                            </div>
                            <a href="tambahpembelian.php" class="btn btn-primary">
                                <i class="typcn typcn-plus"></i>
                                Tambah Pengajuan
                            </a>
                        </div>
                        
                        <?php if(isset($_GET['success']) && $_GET['success'] == 'add') : ?>
                            <div class="alert alert-success">
                                Pengajuan pembelian berhasil ditambahkan dan berstatus menunggu.
                            </div>
                        <?php endif; ?>
                        <?php if(isset($_GET['success']) && $_GET['success'] == 'acc') : ?>
                            <div class="alert alert-success">
                                Pembelian telah sukses di-ACC dan stok bahan baku bertambah!
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="15%">No. Nota</th>
                                        <th>Tanggal</th>
                                        <th>Supplier</th>
                                        <th>Total Pembelian</th>
                                        <th>Status</th>
                                        <th width="15%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if(count($dataPembelian) > 0): ?>
                                    <?php $no = 1; ?>
                                    <?php foreach($dataPembelian as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['nomor'] ?></strong></td>
                                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                        <td><?= $row['nama_supplier'] ?></td>
                                        <td class="font-weight-bold">
                                            Rp <?= number_format($row['total'], 0, ',', '.') ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status = $row['status'];
                                              if($status == 'Dipesan'){
                                                  $badge = 'warning';
                                              }
                                              elseif($status == 'Dibatalkan'){
                                                  $badge = 'danger';
                                              }
                                              elseif($status == 'Diterima'){
                                                  $badge = 'success';
                                              }
                                            ?>
                                            <span class="badge badge-<?= $badge ?>">
                                                <?= $status ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="detailpembelian.php?nomor=<?= $row['nomor'] ?>" class="btn btn-info btn-sm">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            Tidak ada pengajuan pembelian yang sedang berjalan
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
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
<?php 
require_once '../includes/footer.php'; 
?>