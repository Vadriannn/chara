<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$nomor = $_GET['nomor'] ?? '';

if(empty($nomor)){
    die('Nomor pembelian tidak ditemukan');
}

$stmt = $koneksi->prepare("
    SELECT
        p.nomor,
        p.tanggal,
        p.total,
        p.tPurchaseRequest_id,
        s.nama AS supplier,

        pb.tanggal AS tanggal_terima,
        u.username AS penerima

    FROM tPembelian p

    JOIN tSupplier s
        ON p.tSupplier_id = s.id

    LEFT JOIN tPenerimaanBarang pb
        ON p.nomor = pb.tPembelian_nomor

    LEFT JOIN tUser u
        ON pb.tUser_id = u.id

    WHERE p.nomor = ?
");

$stmt->execute([$nomor]);

$pembelian = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$pembelian){
    die('Data pembelian tidak ditemukan');
}

$stmtDetail = $koneksi->prepare("
    SELECT
        d.*,
        b.nama
    FROM tDetailPembelian d
    JOIN tBahan b
        ON d.tBahan_kode = b.kode
    WHERE d.tPembelian_nomor = ?
");

$stmtDetail->execute([$nomor]);

$detailPembelian = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">

                            <div class="card-body">

                                <h4 class="card-title">
                                    Detail Pembelian
                                </h4>

                                <div class="row mb-4">

                                    <div class="col-md-6">

                                        <table class="table table-borderless">

                                            <tr>
                                                <th width="180">
                                                    Nomor Pembelian
                                                </th>
                                                <td>
                                                    : <?= $pembelian['nomor'] ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th>
                                                    Tanggal
                                                </th>
                                                <td>
                                                    : <?= date('d-m-Y H:i', strtotime($pembelian['tanggal'])) ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <th>
                                                    Supplier
                                                </th>
                                                <td>
                                                    : <?= $pembelian['supplier'] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Petugas Penerima</th>
                                                <td>
                                                    : <?= $pembelian['penerima'] ?? '-' ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Tanggal Diterima</th>
                                                <td>
                                                    :
                                                    <?= !empty($pembelian['tanggal_terima'])
                                                        ? date('d-m-Y H:i', strtotime($pembelian['tanggal_terima']))
                                                        : '-' ?>
                                                </td>
                                              </tr>   
                                                <th>
                                                    Purchase Request
                                                </th>
                                                <td>
                                                    : <?= $pembelian['tPurchaseRequest_id'] ?>
                                                </td>
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
                                            <?php foreach($detailPembelian as $d): ?>
                                            <tr>
                                                <td>
                                                    <?= $d['tBahan_kode'] ?>
                                                </td>
                                                <td>
                                                    <?= $d['nama'] ?>
                                                </td>
                                                <td>
                                                    <?= $d['jumlah'] ?>
                                                </td>
                                                <td>
                                                    <?= $d['satuanBeli'] ?>
                                                </td>
                                                <td>
                                                    Rp <?= number_format($d['harga'],0,',','.') ?>
                                                </td>
                                                <td>
                                                    Rp <?= number_format($d['subtotal'],0,',','.') ?>
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
                                                    Rp <?= number_format($pembelian['total'],0,',','.') ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <a href="hispembelian.php"
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