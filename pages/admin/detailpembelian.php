<?php
session_start();
require_once '../../koneksi.php'; // Sesuaikan dengan letak file koneksi database Anda
require_once '../../auth.php';    // Pastikan user sudah login

$error = "";
$pembelian = null;
$items = [];

try {
    // 1. Validasi parameter 'nomor' nota yang dikirimkan lewat URL (?nomor=xxx)
    if (!isset($_GET['nomor']) || empty($_GET['nomor'])) {
        header("Location: pembelian.php");
        exit;
    }
    $nomorNota = intval($_GET['nomor']);

    // 2. Query untuk mengambil data Master / Header Pembelian
    $stmtMaster = $koneksi->prepare("
        SELECT p.*, s.nama AS nama_supplier
        FROM tPembelian p
        JOIN tSupplier s ON p.tSupplier_id = s.id
        WHERE p.nomor = ?
    ");
    $stmtMaster->execute([$nomorNota]);
    $pembelian = $stmtMaster->fetch(PDO::FETCH_ASSOC);

    // Jika nomor nota gadungan atau tidak ditemukan di database, tendang balik ke list utama
    if (!$pembelian) {
        header("Location: pembelian.php");
        exit;
    }
    // 3. Query SQL JOIN untuk menarik barang apa saja yang dibeli pada nomor nota tersebut
    // Sesuai relasi ERD: tDetailPembelian berelasi ke tBahan melalui kode bahan baku
    $stmtDetail = $koneksi->prepare("
        SELECT d.jumlah, d.satuanBeli, d.harga, d.subtotal, b.nama AS nama_bahan
        FROM tDetailPembelian d
        JOIN tBahan b ON d.tBahan_kode = b.kode
        WHERE d.tPembelian_nomor = ?
    ");
    $stmtDetail->execute([$nomorNota]);
    $items = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Terjadi kesalahan database: " . $e->getMessage();
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="card-title mb-1">Detail Pengajuan Pembelian</h4>
                                <p class="text-muted mb-0">
                                    Melihat rincian bahan baku Nota #<?= htmlspecialchars($pembelian['nomor']) ?>
                                </p>
                            </div>
                        </div>
                        <?php if($error != "") : ?>
                            <div class="alert alert-danger">
                                <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <div class="row mb-4 bg-light p-3 rounded mx-1">
                            <div class="col-md-3">
                                <label class="text-muted mb-1 d-block">Tanggal Pengajuan</label>
                                <span class="font-weight-bold text-dark">
                                    <?= date('d-m-Y H:i', strtotime($pembelian['tanggal'])) ?>
                                </span>
                            </div>
                            <div class="col-md-3">
                                <label class="text-muted mb-1 d-block">Supplier</label>
                                <span class="font-weight-bold text-dark">
                                    <?= htmlspecialchars($pembelian['nama_supplier']) ?>
                                </span>
                            </div>
                            <div class="col-md-3">
                                <label class="text-muted mb-1 d-block">Total Pembelian</label>
                                <span class="font-weight-bold text-danger" style="font-size: 1.15rem;">
                                    Rp <?= number_format($pembelian['total'], 0, ',', '.') ?>
                                </span>
                            </div>
                            <div class="col-md-3">
                                <label class="text-muted mb-1 d-block">Status Validasi</label>
                                <?php if($pembelian['status'] == 'Menunggu'): ?>
                                    <span class="badge badge-warning text-dark font-weight-bold p-2">Menunggu ACC</span>
                                <?php else: ?>
                                    <span class="badge badge-success font-weight-bold p-2">Selesai</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <hr class="my-4">
                        <h4 class="mb-3 font-weight-bold text-dark">Daftar Bahan Baku Dipesan</h4>
                        <div class="table-responsive pt-e">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Nama Bahan Baku</th>
                                        <th width="15%" class="text-center">Jumlah Beli</th>
                                        <th width="15%" class="text-center">Satuan Beli</th>
                                        <th width="20%" class="text-right">Harga / Satuan</th>
                                        <th width="20%" class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($items) > 0): ?>
                                        <?php $no = 1; ?>
                                        <?php foreach($items as $row): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td class="font-weight-bold text-dark">
                                                    <?= htmlspecialchars($row['nama_bahan']) ?>
                                                </td>
                                                <td class="text-center"><?= $row['jumlah'] ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-outline-secondary font-weight-bold">
                                                        <?= htmlspecialchars($row['satuanBeli']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    Rp <?= number_format($row['harga'], 0, ',', '.') ?>
                                                </td>
                                                <td class="text-right font-weight-bold text-primary">
                                                    Rp <?= number_format($row['subtotal'], 0, ',', '.') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">
                                                Tidak ada rincian item barang pada nota ini.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 d-flex justify-content-end">
                            <a href="pembelian.php" class="btn btn-secondary">
                                Kembali
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php 
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>
