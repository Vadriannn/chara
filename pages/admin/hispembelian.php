<?php 
session_start(); 
$page_title = "CHARA - Histori Pembelian";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_gudang.php';

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$query = "
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
";

$params = [];
if ($start_date !== '') {
    $query .= " AND DATE(pb.tanggal) >= :start_date";
    $params['start_date'] = $start_date;
}
if ($end_date !== '') {
    $query .= " AND DATE(pb.tanggal) <= :end_date";
    $params['end_date'] = $end_date;
}

$query .= " ORDER BY pb.tanggal DESC";

$stmt = $koneksi->prepare($query);
$stmt->execute($params);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">

        <div class="card-body">
            <h4 class="card-title">Histori Pembelian</h4>
            
            <form method="GET" action="" class="mb-4 form-inline">
                <input type="date" name="start_date" class="form-control mr-2" value="<?= htmlspecialchars($start_date) ?>" placeholder="Mulai Tanggal">
                <span class="mr-2">s/d</span>
                <input type="date" name="end_date" class="form-control mr-2" value="<?= htmlspecialchars($end_date) ?>" placeholder="Sampai Tanggal">
                <button type="submit" class="btn btn-primary mr-2">Filter</button>
                <a href="hispembelian.php" class="btn btn-light">Reset</a>
            </form>

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
                            <span class="badge badge-success">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="detailhispembelian.php?nomor=<?= $row['nomor'] ?>"
                               class="btn btn-info btn-sm">
                                Detail
                            </a>
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
require_once '../includes/footer.php'; 
?>