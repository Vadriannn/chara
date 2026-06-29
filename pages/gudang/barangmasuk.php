<?php
session_start();
$page_title = "CHARA - Barang Masuk";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_gudang.php';

/*
|--------------------------------------------------------------------------
| RECEIVE BARANG
|--------------------------------------------------------------------------
*/
if(isset($_GET['receive'])){
    try{
        $koneksi->beginTransaction();
        $nomor = $_GET['receive'];
        
        $stmt = $koneksi->prepare("
            SELECT
                tBahan_kode,
                jumlah,
                konversi,
                subtotal 
            FROM tDetailPembelian
            WHERE tPembelian_nomor = ?
        ");
        $stmt->execute([$nomor]);
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $stokTambah = $row['jumlah'] * $row['konversi'];

            $cekStok = $koneksi->prepare("
                SELECT stok, harga
                FROM tBahan
                WHERE kode = ?
            ");
            $cekStok->execute([
                $row['tBahan_kode']
            ]);

            $dataBahan = $cekStok->fetch(PDO::FETCH_ASSOC);
            $stokLama = $dataBahan['stok'];
            $hargaLama = $dataBahan['harga']; 
            
            $stokBaru = $stokLama + $stokTambah;

            $nilaiLama = $stokLama * $hargaLama;
            $nilaiBaru = $row['subtotal']; 
            
            if ($stokBaru > 0) {
                $hargaBaru = ($nilaiLama + $nilaiBaru) / $stokBaru;
            } else {
                $hargaBaru = $hargaLama; 
            }

            $update = $koneksi->prepare("
                UPDATE tBahan
                SET stok = ?, harga = ?
                WHERE kode = ?
            ");

            $update->execute([
                $stokBaru,
                $hargaBaru, 
                $row['tBahan_kode']
            ]);

            $mutasi = $koneksi->prepare("
                INSERT INTO tMutasiStok
                (
                    tanggal,
                    jenis,
                    qty,
                    stokSebelum,
                    stokSesudah,
                    referensi,
                    tBahan_kode,
                    tUser_id
                )
                VALUES
                (
                    NOW(),
                    'Pembelian',
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $mutasi->execute([
                $stokTambah,
                $stokLama,
                $stokBaru,
                $nomor,
                $row['tBahan_kode'],
                $_SESSION['id_user']
            ]);
        }

        $penerimaan = $koneksi->prepare("
            INSERT INTO tPenerimaanBarang
            (
                tanggal,
                tPembelian_nomor,
                tUser_id
            )
            VALUES
            (
                NOW(),
                ?,
                ?
            )
        ");

        $penerimaan->execute([
            $nomor,
            $_SESSION['id_user']
        ]);
        
        $stmt = $koneksi->prepare("
            UPDATE tPembelian
            SET status = 'Diterima'
            WHERE nomor = ?
        ");
        $stmt->execute([$nomor]);
        
        $koneksi->commit();
        header("Location: barangmasuk.php?success=receive");
        exit;
        
    }catch(PDOException $e){
        if($koneksi->inTransaction()){
            $koneksi->rollBack();
        }
        die($e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| DATA PEMBELIAN YANG BELUM DITERIMA (MENUNGGU)
|--------------------------------------------------------------------------
*/
$stmt = $koneksi->prepare("
    SELECT
        p.nomor,
        p.tanggal,
        p.total,
        p.status,
        s.nama AS supplier
    FROM tPembelian p
    JOIN tSupplier s
        ON p.tSupplier_id = s.id
    WHERE p.status = 'Dipesan'
    ORDER BY p.tanggal DESC
");
$stmt->execute();
$dataMenunggu = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| DATA RIWAYAT BARANG MASUK (DITERIMA)
|--------------------------------------------------------------------------
*/
$stmtRiwayat = $koneksi->prepare("
    SELECT
        p.nomor,
        pb.tanggal AS tanggal_terima,
        p.total,
        p.status,
        s.nama AS supplier,
        u.username AS penerima
    FROM tPembelian p
    JOIN tSupplier s
        ON p.tSupplier_id = s.id
    JOIN tPenerimaanBarang pb
        ON p.nomor = pb.tPembelian_nomor
    LEFT JOIN tUser u
        ON pb.tUser_id = u.id
    WHERE p.status = 'Diterima'
    ORDER BY pb.tanggal DESC
");
$stmtRiwayat->execute();
$dataRiwayat = $stmtRiwayat->fetchAll(PDO::FETCH_ASSOC);
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
            <div class="content-wrapper">
                
                <?php if(isset($_GET['success']) && $_GET['success'] == 'receive') : ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Barang berhasil diterima dan stok telah diperbarui!
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- TABEL 1: BARANG MENUNGGU DITERIMA -->
                <div class="row mb-4">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title text-warning">
                                    <i class="typcn typcn-time"></i> Menunggu Diterima (Pending)
                                </h4>
                                <p class="text-muted">Daftar pembelian yang sedang dipesan dan belum masuk ke gudang.</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>No Pembelian</th>
                                                <th>Tgl Pesan</th>
                                                <th>Supplier</th>
                                                <th>Total Pembelian</th>
                                                <th>Status</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php if(count($dataMenunggu) > 0): ?>
                                            <?php foreach($dataMenunggu as $row): ?>
                                            <tr>
                                                <td class="font-weight-bold">PB-<?= str_pad($row['nomor'], 4, '0', STR_PAD_LEFT) ?></td>
                                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                                <td><?= $row['supplier'] ?></td>
                                                <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                                <td><span class="badge badge-warning"><?= $row['status'] ?></span></td>
                                                <td class="text-center">
                                                    <a href="detailbarangmasuk.php?nomor=<?= $row['nomor'] ?>" class="btn btn-info btn-sm">Detail</a>
                                                    <a href="?receive=<?= $row['nomor'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Terima barang ini ke gudang?')">Receive</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada barang yang menunggu diterima</td></tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABEL 2: RIWAYAT BARANG MASUK -->
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title text-success">
                                    <i class="typcn typcn-tick"></i> Riwayat Barang Masuk
                                </h4>
                                <p class="text-muted">Daftar barang yang sudah berhasil diterima dan masuk ke stok gudang.</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>No Pembelian</th>
                                                <th>Waktu Diterima</th>
                                                <th>Supplier</th>
                                                <th>Diterima Oleh</th>
                                                <th>Status</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php if(count($dataRiwayat) > 0): ?>
                                            <?php foreach($dataRiwayat as $row): ?>
                                            <tr>
                                                <td class="font-weight-bold">PB-<?= str_pad($row['nomor'], 4, '0', STR_PAD_LEFT) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($row['tanggal_terima'])) ?></td>
                                                <td><?= $row['supplier'] ?></td>
                                                <td><?= $row['penerima'] ?: 'Sistem' ?></td>
                                                <td><span class="badge badge-success"><?= $row['status'] ?></span></td>
                                                <td class="text-center">
                                                    <a href="detailbarangmasuk.php?nomor=<?= $row['nomor'] ?>" class="btn btn-info btn-sm">Detail</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat barang masuk</td></tr>
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