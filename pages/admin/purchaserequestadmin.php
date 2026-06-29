<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$error = "";
try {
    if(isset($_GET['approve'])){
        $stmt = $koneksi->prepare("
            UPDATE tPurchaseRequest
            SET
                status = 'Approved',
                tanggalApprove = NOW(),
                approveBy = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $_SESSION['id'],
            $_GET['approve']
        ]);
        header("Location: purchaserequestadmin.php");
        exit;
    }
    if(isset($_GET['reject'])){
        $stmt = $koneksi->prepare("
            UPDATE tPurchaseRequest
            SET
                status = 'Rejected',
                tanggalReject = NOW(),
                approveBy = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $_SESSION['id'],
            $_GET['reject']
        ]);
        header("Location: purchaserequestadmin.php");
        exit;
    }
    $purchaseRequest = $koneksi->query("
        SELECT
            pr.id,
            pr.status,
            pr.tanggal,
            pb.nomor AS nomor_pembelian,
            u.username,

            GROUP_CONCAT(
                CONCAT(
                    b.nama,
                    ' (',
                    d.jumlah,
                    ' ',
                    d.satuanBeli,
                    ')'
                )
                SEPARATOR '<br>'
            ) AS detail_bahan

        FROM tPurchaseRequest pr

        JOIN tUser u
          ON pr.reqBy = u.id

        LEFT JOIN tPembelian pb
          ON pb.tPurchaseRequest_id = pr.id

        LEFT JOIN tDetailPurchaseRequest d
            ON pr.id = d.tPurchaseRequest_id

        LEFT JOIN tBahan b
            ON d.tBahan_kode = b.kode

        GROUP BY
          pr.id,
          pr.status,
          pr.tanggal,
          u.username,
          pb.nomor

        ORDER BY pr.tanggal DESC
    ");

}
catch(PDOException $e){

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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title mb-0">
                            Purchase Request
                        </h4>
                    </div>
                    <?php if($error != ""): ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID PR</th>
                                    <th>Tanggal</th>
                                    <th>Pengaju</th>
                                    <th>Detail Permintaan</th>
                                    <th> Detail </th>
                                    <th>Status</th>
                                    <th width="180">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while(
                                    $row =
                                    $purchaseRequest->fetch(PDO::FETCH_ASSOC)
                                ):
                                ?>
                                <tr>
                                    <td>
                                        <?= $row['id']; ?>
                                    </td>
                                    <td>
                                        <?= date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $row['tanggal']
                                            )
                                        ); ?>
                                    </td>
                                    <td>
                                        <?= $row['username']; ?>
                                    </td>
                                    <td>
                                        <?= $row['detail_bahan']; ?>
                                    </td>
                                    <td>
                                      <a href="detailpurchaserequest.php?id=<?= $row['id'] ?>"
                                                    class="btn btn-info btn-sm">
                                                    Detail
                                      </a>
                                    </td>
                                    <td>
                                        <?php
                                        if(
                                            $row['status']
                                            == 'Pending'
                                        ):
                                        ?>
                                            <span class="badge badge-warning">
                                                Pending
                                            </span>
                                        <?php
                                        elseif(
                                            $row['status']
                                            == 'Approved'
                                        ):
                                        ?>

                                            <span class="badge badge-success">
                                                Approved
                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-danger">
                                                Rejected
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if($row['status'] == 'Pending'): ?>
                                            <a
                                                href="?approve=<?= $row['id']; ?>"
                                                class="btn btn-success btn-sm"
                                                onclick="return confirm('Approve purchase request ini?')">
                                                Approve
                                            </a>
                                            <a
                                                href="?reject=<?= $row['id']; ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Reject purchase request ini?')">
                                                Reject
                                            </a>
                                        <?php elseif(
                                            $row['status'] == 'Approved'
                                            && empty($row['nomor_pembelian'])
                                        ): ?>
                                            <a
                                                href="buatpembelian.php?pr=<?= $row['id']; ?>"
                                                class="btn btn-primary btn-sm">
                                                Buat Pembelian
                                            </a>
                                        <?php elseif($row['status'] == 'Approved'): ?>
                                            <span class="badge badge-success">
                                                Sudah Dibuat Pembelian
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">
                                                Ditolak
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
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
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>