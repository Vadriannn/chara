<?php 
session_start(); 
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_gudang.php';

$stmt = $koneksi->query("
    SELECT
        pb.nomor,
        pb.tanggal,
        pb.total,
        s.nama AS supplier,
        pr.id AS nomor_pr,
        pb.status
    FROM tPembelian pb
    JOIN tSupplier s
        ON pb.tSupplier_id = s.id
    LEFT JOIN tPurchaseRequest pr
        ON pb.tPurchaseRequest_id = pr.id
    WHERE pb.status = 'Diterima'
    ORDER BY pb.tanggal DESC
");
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">

        <div class="card-body">
            <h4>Histori Pembelian</h4>
            <table class="table table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>No Pembelian</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th>No PR</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= $row['nomor'] ?></td>
                        <td>
                            <?= date(
                                'd-m-Y H:i',
                                strtotime($row['tanggal'])
                            ) ?>
                        </td>
                        <td><?= $row['supplier'] ?></td>
                        <td><?= $row['nomor_pr'] ?></td>
                        
                        <td>
                            Rp <?= number_format(
                                $row['total'],
                                0,
                                ',',
                                '.'
                            ) ?>
                        </td>
                        <td>
                            <a href="detailhispembelian.php?nomor=<?= $row['nomor'] ?>"
                               class="btn btn-info btn-sm">
                                Detail
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-success">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>

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