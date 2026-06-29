<?php 
session_start(); 
$page_title = "CHARA - Detail Histori Pembelian";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_gudang.php';

// Fungsi untuk menghilangkan .00 jika angka bulat, sekaligus format gaya Indonesia
function formatAngka($angka) {
    if (fmod((float)$angka, 1) !== 0.0) {
        // Jika ada desimal, tampilkan dengan 2 angka di belakang koma (misal: 10,50)
        return number_format($angka, 2, ',', '.');
    }
    // Jika bulat, tampilkan tanpa koma (misal: 10)
    return number_format($angka, 0, ',', '.');
}

try {

    if(!isset($_GET['id'])){
        header('location:purchaserequest.php');
        exit;
    }

    $id = $_GET['id'];

    // Header Purchase Request
    $stmt = $koneksi->prepare("
        SELECT
            pr.id,
            pr.tanggal,
            pr.status,
            u.username
        FROM tPurchaseRequest pr
        INNER JOIN tUser u
            ON pr.reqBy = u.id
        WHERE pr.id = ?
    ");

    $stmt->execute([$id]);

    $pr = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$pr){
        header('location:purchaserequest.php');
        exit;
    }

    // Detail barang
    $stmtDetail = $koneksi->prepare("
    SELECT
        d.tBahan_kode,
        b.nama,
        d.jumlah,
        d.satuanBeli,
        d.konversi,
        (d.jumlah * d.konversi) AS totalKonversi
    FROM tDetailPurchaseRequest d
    INNER JOIN tBahan b
        ON d.tBahan_kode = b.kode
    WHERE d.tPurchaseRequest_id = ?
");
    $stmtDetail->execute([$id]);

    $detail = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
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
                                        <h4 class="card-title mb-1">
                                            Detail Purchase Request
                                        </h4>

                                        <p class="text-muted mb-0">
                                            Informasi detail purchase request.
                                        </p>
                                    </div>
                                    <a
                                        href="purchaserequest.php"
                                        class="btn btn-secondary">
                                        Kembali
                                    </a>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="200">
                                                    ID Purchase Request
                                                </th>
                                                <td>
                                                    <?= $pr['id'] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>
                                                    Tanggal
                                                </th>
                                                <td>
                                                    <?= date('d/m/Y H:i', strtotime($pr['tanggal'])) ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>
                                                    Pengajuan Oleh
                                                </th>
                                                <td>
                                                    <?= $pr['username'] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>
                                                    Status
                                                </th>
                                                <td>
                                                    <?php
                                                    if($pr['status'] == 'Pending'){
                                                        echo '<span class="badge badge-warning">Pending</span>';
                                                    }
                                                    elseif($pr['status'] == 'Approved'){
                                                        echo '<span class="badge badge-success">Approved</span>';
                                                    }
                                                    else{
                                                        echo '<span class="badge badge-danger">Rejected</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <hr>
                                <h5 class="mb-3">
                                    Detail Barang
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Kode</th>
                                                <th>Nama Bahan</th>
                                                <th>Jumlah</th>
                                                <th>Satuan Beli</th>
                                                <th>Konversi (Kg/L)</th>
                                                <th>Total Kuantitas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php if(count($detail) > 0): ?>
                                            <?php foreach($detail as $row): ?>
                                            <tr>
                                                <td>
                                                    <?= $row['tBahan_kode'] ?>
                                                </td>
                                                <td>
                                                    <?= $row['nama'] ?>
                                                </td>
                                                <td>
                                                    <?= formatAngka($row['jumlah']) ?>
                                                </td>
                                                <td>
                                                    <?= $row['satuanBeli'] ?>
                                                </td>
                                                <td>
                                                    <?= formatAngka($row['konversi']) ?>
                                                </td>
                                                <td>
                                                    <?= formatAngka($row['totalKonversi']) ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    Tidak ada detail barang
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
<?php 
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>