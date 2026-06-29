<?php
session_start();
$page_title = "CHARA - Tambah Pembelian";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$pr = $_GET['pr'] ?? '';

if(isset($_POST['simpan'])){
    try{
        $koneksi->beginTransaction();
        
        $stmtNomor = $koneksi->query("
            SELECT IFNULL(MAX(nomor),0)+1 AS next_no
            FROM tPembelian
        ");
        $nextNo = $stmtNomor->fetch(PDO::FETCH_ASSOC)['next_no'];
        
        $kodePembelian = 'PBL-' . date('Ymd') . '-' . str_pad($nextNo, 4, '0', STR_PAD_LEFT);
        
        $stmt = $koneksi->prepare("
            INSERT INTO tPembelian
            (
                nomor,
                tanggal,
                total,
                status,
                kode,
                tSupplier_id,
                tPurchaseRequest_id
            )
            VALUES
            (?, NOW(), 0, 'Dipesan', ?, ?, ?)
        ");
        $stmt->execute([
            $nextNo,
            $kodePembelian,
            $_POST['supplier'],
            $pr
        ]);
        
        $nomorPembelian = $nextNo;
        $total = 0;
        
        $stmtPR = $koneksi->prepare("
            SELECT *
            FROM tDetailPurchaseRequest
            WHERE tPurchaseRequest_id = ?
            ORDER BY tBahan_kode
        ");
        $stmtPR->execute([$pr]);
        
        $hargaInput = $_POST['harga'];
        $no = 0;
        
        while($row = $stmtPR->fetch(PDO::FETCH_ASSOC))
        {
            $harga = $hargaInput[$no];
            $subtotal = $harga * $row['jumlah'];
            $total += $subtotal;
            
            $stmtDetail = $koneksi->prepare("
                INSERT INTO tDetailPembelian
                (
                    tBahan_kode,
                    tPembelian_nomor,
                    jumlah,
                    satuanBeli,
                    konversi,
                    harga,
                    subtotal
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtDetail->execute([
                $row['tBahan_kode'],
                $nomorPembelian,
                $row['jumlah'],
                $row['satuanBeli'],
                $row['konversi'],
                $harga,
                $subtotal
            ]);
            $no++;
        }
        
        $stmt = $koneksi->prepare("
            UPDATE tPembelian
            SET total = ?
            WHERE nomor = ?
        ");
        $stmt->execute([$total, $nomorPembelian]);

        // Catat ke tArusKas (Pengeluaran)
        $stmtArusKas = $koneksi->prepare("
            INSERT INTO tArusKas (tanggal, jenis, kategori, nominal, sumber, tPembelian_nomor)
            VALUES (NOW(), 'Keluar', 'Pembelian', ?, ?, ?)
        ");
        $stmtArusKas->execute([
            $total, 
            'Pembelian Bahan dari PR #' . $pr,
            $nomorPembelian
        ]);

        $stmtPRUpdate = $koneksi->prepare("
            UPDATE tPurchaseRequest
            SET status = 'Approved'
            WHERE id = ?
        ");
        $stmtPRUpdate->execute([$pr]);
        
        catatLog($koneksi, "Approve Purchase Request", "Menyetujui PR #" . $pr . " dan menjadikannya Pembelian", "Gudang", $nomorPembelian);

        $koneksi->commit();

        echo "
        <script>
            alert('Pembelian berhasil dibuat!');
            window.location='pembelian.php';
        </script>
        ";
        exit;

    }catch(PDOException $e){
        if($koneksi->inTransaction()){
            $koneksi->rollBack();
        }
        die('ERROR : '.$e->getMessage());
    }
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
<style>

    .form-control-plaintext {
        background-color: transparent !important;
        border: none !important;
        font-weight: 500;
    }
</style>
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <?php
                                $stmt = $koneksi->prepare("
                                  SELECT
                                      pr.id,
                                      pr.status,
                                      b.nama,
                                      d.tBahan_kode,
                                      d.jumlah,
                                      d.satuanBeli,
                                      d.konversi
                                  FROM tPurchaseRequest pr
                                  JOIN tDetailPurchaseRequest d
                                      ON pr.id = d.tPurchaseRequest_id
                                  JOIN tBahan b
                                      ON d.tBahan_kode = b.kode
                                  WHERE pr.id = ?
                                  ORDER BY d.tBahan_kode
                                ");
                                $stmt->execute([$pr]);
                                $detailPR = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                $supplier = $koneksi->query("
                                    SELECT *
                                    FROM tSupplier
                                    ORDER BY nama
                                ");
                                ?>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="card-title mb-0">Buat Pembelian (Berdasarkan PR)</h5>
                                    <a href="purchaserequestadmin.php" class="btn btn-secondary">Kembali</a>
                                </div>
                                
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="font-weight-bold text-muted mb-1">Nomor Purchase Request</label>
                                                <input type="text" class="form-control form-control-plaintext form-control-lg text-primary" value="<?= $pr ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label class="font-weight-bold mb-2">Pilih Supplier Penerima Order</label>
                                                <select name="supplier" class="form-control form-control-sm" required>
                                                    <option value="">-- Pilih Supplier --</option>
                                                    <?php while($s = $supplier->fetch(PDO::FETCH_ASSOC)): ?>
                                                        <option value="<?= $s['id'] ?>"><?= $s['nama'] ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="mt-2 mb-4">
                                    <h6 class="mb-3 text-info">Daftar Barang yang Diajukan</h6>
                                    <p class="text-muted small mb-3">Silakan lengkapi harga beli per satuan (*harga otomatis dikalikan dengan jumlah pengajuan*).</p>

                                    <div class="table-responsive mb-4">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Kode</th>
                                                    <th>Nama Bahan Baku</th>
                                                    <th>Jumlah Diajukan</th>
                                                    <th>Satuan Beli</th>
                                                    <th width="20%">Harga per Satuan (Rp)</th>
                                                    <th width="20%">Subtotal (Rp)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($detailPR as $index => $d): ?>
                                                  <tr>
                                                      <td class="align-middle text-muted">
                                                          <?= $d['tBahan_kode'] ?>
                                                          <input type="hidden" name="kode[]" value="<?= $d['tBahan_kode'] ?>">
                                                      </td>
                                                      <td class="align-middle font-weight-bold"><?= $d['nama'] ?></td>
                                                      <td class="align-middle">
                                                          <?= $d['jumlah'] ?>
                                                          <input type="hidden" class="jumlah" value="<?= $d['jumlah'] ?>">
                                                      </td>
                                                      <td class="align-middle"><?= $d['satuanBeli'] ?></td>
                                                      <td class="align-middle">
                                                          <input type="number" name="harga[]" class="form-control form-control-sm harga" min="0" placeholder="0" required>
                                                      </td>
                                                      <td class="align-middle">
                                                          <input type="text" class="form-control form-control-sm form-control-plaintext subtotal" value="0" readonly>
                                                      </td>
                                                  </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-5 offset-md-7">
                                            <div class="p-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0 text-dark">Grand Total</h6>
                                                    <h5 class="mb-0 text-danger font-weight-bold">Rp <span id="grandTotal">0</span></h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="mb-4 mt-2">
                                    
                                    <div class="text-right">
                                        <a href="purchaserequestadmin.php" class="btn btn-light mr-2">Batal</a>
                                        <button type="submit" name="simpan" class="btn btn-primary">Proses & Setujui Pembelian</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php require_once '../includes/footer.php'; ?>
    <script>
      function hitungTotal() {
          let grandTotal = 0;
          document.querySelectorAll(".harga").forEach(function(input){
              let row = input.closest("tr");
              let jumlahInput = row.querySelector(".jumlah");
              let subtotalInput = row.querySelector(".subtotal");

              let jumlah = parseFloat(jumlahInput.value) || 0;
              let harga = parseFloat(input.value) || 0;

              let subtotal = jumlah * harga;

              // Format subtotal ke dalam style rupiah yang rapi
              subtotalInput.value = subtotal.toLocaleString('id-ID');
              grandTotal += subtotal;
          });

          // Update Grand Total di UI
          document.getElementById("grandTotal").textContent = grandTotal.toLocaleString('id-ID');
      }

      // Pasang event listener pada setiap kolom harga
      document.querySelectorAll(".harga").forEach(function(input){
          input.addEventListener("input", hitungTotal);
      });

      // Hitung saat pertama kali halaman dimuat (berjaga-jaga)
      hitungTotal();
    </script>