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
        
        catatLog($koneksi, "Terima Barang", "Menerima barang dari pembelian #" . $nomor, "Gudang", $nomor);
        
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
$search_bahan = isset($_GET['search_bahan']) ? trim($_GET['search_bahan']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$queryRiwayat = "
    SELECT
        m.tanggal AS tanggal_terima,
        m.referensi AS nomor,
        m.qty,
        m.stokSebelum,
        m.stokSesudah,
        b.nama AS nama_bahan,
        st.nama AS satuan,
        u.username AS penerima
    FROM tMutasiStok m
    JOIN tBahan b ON m.tBahan_kode = b.kode
    JOIN tsatuan st ON b.tSatuan_id = st.id
    LEFT JOIN tUser u ON m.tUser_id = u.id
    WHERE m.jenis = 'Pembelian'
";
$paramsRiwayat = [];

if ($search_bahan !== '') {
    $queryRiwayat .= " AND b.nama LIKE :search_bahan";
    $paramsRiwayat['search_bahan'] = "%$search_bahan%";
}

if ($start_date !== '') {
    $queryRiwayat .= " AND DATE(m.tanggal) >= :start_date";
    $paramsRiwayat['start_date'] = $start_date;
}

if ($end_date !== '') {
    $queryRiwayat .= " AND DATE(m.tanggal) <= :end_date";
    $paramsRiwayat['end_date'] = $end_date;
}

$queryRiwayat .= " ORDER BY m.tanggal DESC";

$stmtRiwayat = $koneksi->prepare($queryRiwayat);
$stmtRiwayat->execute($paramsRiwayat);
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
                                                <td><span class="badge badge-warning"><?= $row['status'] ?></span></td>
                                                <td class="text-center">
                                                    <a href="detailbarangmasuk.php?nomor=<?= $row['nomor'] ?>" class="btn btn-info btn-sm">Detail</a>
                                                    <a href="?receive=<?= $row['nomor'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Terima barang ini ke gudang?')">Receive</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada barang yang menunggu diterima</td></tr>
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
                                
                                <form method="GET" class="form-inline mb-4">
                                    <input type="text" name="search_bahan" class="form-control mr-2 mb-2" placeholder="Nama Bahan Baku" value="<?= htmlspecialchars($search_bahan) ?>">
                                    <input type="date" name="start_date" class="form-control mr-2 mb-2" value="<?= htmlspecialchars($start_date) ?>">
                                    <span class="mr-2 mb-2"> s/d </span>
                                    <input type="date" name="end_date" class="form-control mr-2 mb-2" value="<?= htmlspecialchars($end_date) ?>">
                                    <button type="submit" class="btn btn-primary mb-2 mr-2">Filter</button>
                                    <a href="barangmasuk.php" class="btn btn-secondary mb-2">Reset</a>
                                </form>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="5%">No</th>
                                                <th width="15%">Tanggal Terima</th>
                                                <th width="15%">No. Referensi (PB)</th>
                                                <th>Nama Bahan Baku</th>
                                                <th class="text-center">Stok Awal</th>
                                                <th class="text-center text-success">Penambahan (Qty)</th>
                                                <th class="text-center">Sisa Stok</th>
                                                <th>Penerima</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php if(count($dataRiwayat) > 0): ?>
                                            <?php $no = 1; foreach($dataRiwayat as $row): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($row['tanggal_terima'])) ?></td>
                                                <td class="font-weight-bold">PB-<?= str_pad($row['nomor'], 4, '0', STR_PAD_LEFT) ?></td>
                                                <td class="font-weight-bold"><?= htmlspecialchars($row['nama_bahan']) ?></td>
                                                <td class="text-center text-muted">
                                                    <?= rtrim(rtrim($row['stokSebelum'], '0'), '.') ?>
                                                    <small><?= htmlspecialchars($row['satuan']) ?></small>
                                                </td>
                                                <td class="text-center font-weight-bold text-success">
                                                    + <?= rtrim(rtrim($row['qty'], '0'), '.') ?>
                                                    <small><?= htmlspecialchars($row['satuan']) ?></small>
                                                </td>
                                                <td class="text-center font-weight-bold">
                                                    <?= rtrim(rtrim($row['stokSesudah'], '0'), '.') ?>
                                                    <small><?= htmlspecialchars($row['satuan']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($row['penerima'] ?: 'Sistem') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada riwayat barang masuk</td></tr>
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
require_once '../includes/footer.php'; 
?>