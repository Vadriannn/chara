<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_gudang.php';
$nomor = $_GET['nomor'] ?? '';
if(empty($nomor)){
    die('Nomor pembelian tidak ditemukan');
}
/*
|--------------------------------------------------------------------------
| HEADER PENERIMAAN BARANG
|--------------------------------------------------------------------------
*/
$stmt = $koneksi->prepare("
    SELECT
        p.nomor,
        p.tanggal,
        p.total,
        p.tPurchaseRequest_id,
        p.status,
        s.nama AS supplier

    FROM tPembelian p

    JOIN tSupplier s
        ON p.tSupplier_id = s.id

    WHERE p.nomor = ?
");

$stmt->execute([$nomor]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die('Data pembelian tidak ditemukan');
}
/*
|--------------------------------------------------------------------------
| DETAIL BARANG YANG DITERIMA
|--------------------------------------------------------------------------
*/
$stmtDetail = $koneksi->prepare("
    SELECT
        d.tBahan_kode,
        b.nama,
        d.jumlah,
        d.satuanBeli,
        d.harga,
        d.subtotal

    FROM tDetailPembelian d

    JOIN tBahan b
        ON d.tBahan_kode = b.kode
    WHERE d.tPembelian_nomor = ?
");
$stmtDetail->execute([$data['nomor']]);
$detailBarang = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                              <h4 class="card-title">
                                    Detail Barang Masuk</h4>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th>No Pembelian</th>
                                                <td>: <?= $data['nomor'] ?></td>
                                            </tr>
                                            <tr>
                                                <th>Tanggal Pembelian</th>
                                                <td>
                                                    : <?= date(
                                                        'd-m-Y H:i',
                                                        strtotime($data['tanggal'])
                                                    ) ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Supplier</th>
                                                <td>: <?= $data['supplier'] ?></td>
                                            </tr>
                                            <tr>
                                                <th>Status</th>
                                                <td>: <?= $data['status'] ?></td>
                                            </tr>
                                            <tr>
                                                <th>Purchase Request</th>
                                                <td>: <?= $data['tPurchaseRequest_id'] ?? '-' ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Kode</th>
                                                <th>Nama Bahan</th>
                                                <th>Jumlah</th>
                                                <th>Satuan</th>
                                                <th>Harga</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($detailBarang as $d): ?>
                                            <tr>
                                                <td><?= $d['tBahan_kode'] ?></td>
                                                <td><?= $d['nama'] ?></td>
                                                <td><?= $d['jumlah'] ?></td>
                                                <td><?= $d['satuanBeli'] ?></td>
                                                <td>
                                                    Rp <?= number_format(
                                                        $d['harga'],
                                                        0,
                                                        ',',
                                                        '.'
                                                    ) ?>
                                                </td>
                                                <td>
                                                    Rp <?= number_format(
                                                        $d['subtotal'],
                                                        0,
                                                        ',',
                                                        '.'
                                                    ) ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-4 offset-md-8">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="70%">
                                                    Total Pembelian
                                                </th>
                                                <td>
                                                    Rp <?= number_format(
                                                        $data['total'],
                                                        0,
                                                        ',',
                                                        '.'
                                                    ) ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                    <a href="barangmasuk.php"
                                    class="btn btn-secondary">
                                        Kembali
                                    </a>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                          
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
<?php 
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>