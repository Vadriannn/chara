<?php 
session_start(); 
$page_title = "CHARA - Daftar Resep";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

try {
    require_once '../../includes/konversi_helper.php';
    $konversiGraph = getKonversiGraph($koneksi);
    
    // Get all products that have recipes
    $sql = "
        SELECT p.kode, p.nama 
        FROM tproduct p 
        WHERE EXISTS (SELECT 1 FROM tresep r WHERE r.tProduct_kode = p.kode)
        ORDER BY p.nama
    ";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute();
    $dataResep = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recipe details with prices to calculate HPP
    $sqlDetail = "
        SELECT r.tProduct_kode, r.jumlah, r.tSatuan_id AS resep_satuan, 
               b.harga, b.tSatuan_id AS stok_satuan
        FROM tresep r
        JOIN tbahan b ON r.tBahan_kode = b.kode
    ";
    $stmtDetail = $koneksi->query($sqlDetail);
    
    // Calculate HPP for each product
    $hppProduk = [];
    while($row = $stmtDetail->fetch(PDO::FETCH_ASSOC)) {
        $kode = $row['tProduct_kode'];
        if(!isset($hppProduk[$kode])) $hppProduk[$kode] = 0;
        
        $multiplier = cariKonversiPHP($konversiGraph, $row['stok_satuan'], $row['resep_satuan']);
        // The quantity in stock unit is: r.jumlah / multiplier
        $qty_stok = $row['jumlah'] / $multiplier;
        $hppProduk[$kode] += ($qty_stok * $row['harga']);
    }
    
    // Map HPP to dataResep
    foreach($dataResep as &$prod) {
        $prod['hpp'] = isset($hppProduk[$prod['kode']]) ? $hppProduk[$prod['kode']] : 0;
    }
    
} catch (PDOException $e) {
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
                                    <h4 class="card-title mb-0">
                                        Daftar Resep Produk
                                    </h4>
                                </div>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Kode Produk</th>
                                            <th>Nama Produk</th>
                                            <th>HPP</th>
                                            <th width="120">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($dataResep as $row): ?>
                                    <tr>
                                        <td><?= $row['kode']; ?></td>
                                        <td><?= $row['nama']; ?></td>
                                        <td>Rp <?= number_format($row['hpp'], 0, ',', '.'); ?></td>
                                        <td>
                                          <a
                                              href="detailresep.php?kode=<?= $row['kode']; ?>"
                                              class="btn btn-info btn-sm">
                                              Detail
                                          </a>
                                          <a
                                              href="editresep.php?kode=<?= $row['kode']; ?>"
                                              class="btn btn-warning btn-sm">
                                              Edit
                                          </a>
                                      </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div> <!-- card -->
                    </div> <!-- col-lg-12 -->
                </div> <!-- row -->
             </div> <!-- content-wrapper -->
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
<?php 
require_once '../includes/footer.php'; 
?>