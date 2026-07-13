<?php
session_start();
$page_title = "CHARA - Detail Penjualan";
require_once '../../koneksi.php';
require_once '../../auth.php';

if (!isset($_GET['nomor']) || empty($_GET['nomor'])) {
    header("Location: datapenjualan.php");
    exit;
}

$nomor = $_GET['nomor'];

// 1. Ambil Data Header Penjualan
$stmtHeader = $koneksi->prepare("
    SELECT 
        p.nomor, 
        p.tanggal, 
        p.total, 
        p.diskon, 
        p.metbayar, 
        u.username AS kasir 
    FROM tPenjualan p
    LEFT JOIN tUser u ON p.tUser_id = u.id
    WHERE p.nomor = ?
");
$stmtHeader->execute([$nomor]);
$penjualan = $stmtHeader->fetch(PDO::FETCH_ASSOC);

if (!$penjualan) {
    die("Data penjualan tidak ditemukan.");
}

// 2. Ambil Data Detail Item beserta kustomisasinya via Subquery (GROUP_CONCAT)
$stmtDetail = $koneksi->prepare("
    SELECT 
        d.jumlah, 
        d.harga_jual, 
        d.subtotal, 
        pr.nama AS nama_produk,
        pr.kode AS kode_produk,
        (SELECT GROUP_CONCAT(CONCAT(m.nama, ' ', m.kategori) SEPARATOR ', ')
         FROM tDetailPenjualanModifier dhm
         JOIN tModifier m ON dhm.tModifier_id = m.id
         WHERE dhm.tDetailPenjualan_id = d.id) AS teks_modifier
    FROM tDetailPenjualan d
    JOIN tProduct pr ON d.tProduct_kode = pr.kode
    WHERE d.tPenjualan_nomor = ?
");
$stmtDetail->execute([$nomor]);
$details = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

$subtotalAwal = $penjualan['total'] + $penjualan['diskon'];
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

          <div class="content-wrapper">
            
            <?php if(isset($_GET['success']) && $_GET['success'] == '1') : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Transaksi berhasil diproses! Struk pembelian dapat dilihat di bawah ini.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

              <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title mb-1">Rincian Penjualan</h4>
                            <p class="text-muted mb-0">Detail item produk untuk Nota #PJ-<?= str_pad($penjualan['nomor'], 4, '0', STR_PAD_LEFT) ?></p>
                        </div>
                        <div>
                            <a href="cetakstruk.php?nomor=<?= $penjualan['nomor'] ?>" target="_blank" class="btn btn-primary mr-2"><i class="typcn typcn-printer mr-1"></i> Cetak Struk</a>
                            <a href="datapenjualan.php" class="btn btn-secondary">Kembali</a>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <p class="text-muted mb-1">Tanggal & Waktu</p>
                            <h6 class="font-weight-bold text-dark"><?= date('d-m-Y H:i', strtotime($penjualan['tanggal'])) ?> WIB</h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1">Kasir Bertugas</p>
                            <h6 class="font-weight-bold text-dark"><?= htmlspecialchars($penjualan['kasir'] ?: 'Sistem') ?></h6>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1">Metode Bayar</p>
                            <span class="badge badge-info"><?= $penjualan['metbayar'] ?></span>
                        </div>
                    </div>

                    <div class="table-responsive pt-3">
                      <table class="table table-bordered">
                        <thead>
                          <tr>
                            <th width="5%" class="font-weight-bold text-dark">No</th>
                            <th class="font-weight-bold text-dark">Produk & Kustomisasi</th>
                            <th width="15%" class="text-right font-weight-bold text-dark">Harga Jual</th>
                            <th width="10%" class="text-center font-weight-bold text-dark">Qty</th>
                            <th width="20%" class="text-right font-weight-bold text-dark">Subtotal</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php $no = 1; foreach($details as $row): ?>
                          <tr>
                              <td class="text-center"><?= $no++ ?></td>
                              <td>
                                  <span class="font-weight-bold text-dark d-block"><?= htmlspecialchars($row['nama_produk']) ?></span>
                                  <?php if (!empty($row['teks_modifier'])): ?>
                                      <small class="text-danger font-weight-bold">(<?= htmlspecialchars($row['teks_modifier']) ?>)</small>
                                  <?php endif; ?>
                              </td>
                              <td class="text-right">Rp <?= number_format($row['harga_jual'], 0, ',', '.') ?></td>
                              <td class="text-center"><?= $row['jumlah'] ?></td>
                              <td class="text-right font-weight-bold">Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-right border-top border-bottom-0 py-2">Subtotal Awal</th>
                                <td class="text-right border-top border-bottom-0 py-2">Rp <?= number_format($subtotalAwal, 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-right border-top-0 border-bottom-0 py-2">Diskon / Potongan</th>
                                <td class="text-right border-top-0 border-bottom-0 py-2 text-danger">- Rp <?= number_format($penjualan['diskon'], 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-right border-top font-weight-bold text-dark">Grand Total</th>
                                <td class="text-right font-weight-bold text-success" style="font-size: 1.15rem;">Rp <?= number_format($penjualan['total'], 0, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                      </table>
                    </div>

                  </div>
                </div>
              </div>

          </div>
<?php 
require_once '../includes/footer.php'; 
?>
    