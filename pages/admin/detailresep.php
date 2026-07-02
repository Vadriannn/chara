<?php
session_start();
$page_title = "CHARA - Detail Resep Produk";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';


if (!isset($_GET['kode'])) {
    header("Location: resep.php");
    exit;
}

$kode = $_GET['kode'];

try {

    // Ambil data produk
    $sqlProduk = "
        SELECT *
        FROM tproduct
        WHERE kode = ?
    ";

    $stmtProduk = $koneksi->prepare($sqlProduk);
    $stmtProduk->execute([$kode]);

    $produk = $stmtProduk->fetch(PDO::FETCH_ASSOC);
    $stmtProduk = $koneksi->prepare($sqlProduk);
    $stmtProduk->execute([$kode]);

    $produk = $stmtProduk->fetch(PDO::FETCH_ASSOC);

    if (!$produk) {
        die("Produk tidak ditemukan");
    }
    // Ambil detail resep
    $sql = "
        SELECT
            b.nama AS bahan,
            r.jumlah,
            s.nama AS satuan
        FROM tresep r
        JOIN tbahan b
            ON r.tBahan_kode = b.kode
        JOIN tsatuan s
            ON s.id = IF(r.tSatuan_id = 0 OR r.tSatuan_id IS NULL, b.tSatuan_id, r.tSatuan_id)
        WHERE r.tProduct_kode = ?
        ORDER BY b.nama
    ";

    $stmt = $koneksi->prepare($sql);
    $stmt->execute([$kode]);

    $detailResep = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e){
    die("Error: " . $e->getMessage());
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
                                    <h4 class="card-title">
                                        Detail Resep Produk
                                    </h4>
                                    <a href="resep.php" class="btn btn-secondary">
                                        Kembali
                                    </a>
                                </div>
                                <div class="mb-4">

                                    <strong>Kode Produk :</strong>
                                    <?= $produk['kode']; ?>
                                    <br>
                                    <strong>Nama Produk :</strong>
                                    <?= $produk['nama']; ?>

                                </div>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Bahan Baku</th>
                                            <th>Jumlah</th>
                                            <th>Satuan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        foreach($detailResep as $row):
                                        ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= $row['bahan']; ?></td>
                                            <td><?= $row['jumlah']; ?></td>
                                            <td><?= $row['satuan']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                        </div> <!-- card -->
                    </div> <!-- col-lg-12 -->
                </div> <!-- row -->
             </div> <!-- content-wrapper -->
            </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
<?php 
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>