<?php
session_start();
$page_title = "CHARA - Tambah Produk";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$error = "";
try {
    // Ambil data kategori
    $kategori = $koneksi->query("
        SELECT *
        FROM tkategori
        ORDER BY nama
    ");
    
    // Ambil data Bahan Baku
    $bahanBaku = $koneksi->query("
        SELECT
          b.kode,
          b.nama,
          b.harga,
          s.nama AS satuan_stok,
            CASE
                WHEN LOWER(s.nama) = 'kg'
                    THEN 'Gram'
                WHEN LOWER(s.nama) = 'liter'
                    THEN 'Ml'
                ELSE s.nama
            END AS satuan_resep
        FROM tbahan b
        JOIN tsatuan s
            ON b.tSatuan_id = s.id
        ORDER BY b.nama
    ");
    
    // Ambil data satuan
    $satuanAll = $koneksi->query("SELECT * FROM tsatuan ORDER BY nama");
    $satuanList = $satuanAll->fetchAll(PDO::FETCH_ASSOC);

    // Ambil data konversi untuk JS
    $konvAll = $koneksi->query("SELECT * FROM tkonversisatuan");
    $konversiList = $konvAll->fetchAll(PDO::FETCH_ASSOC);
    
    // Ambil kode produk terakhir
    $stmtLast = $koneksi->query("
        SELECT kode
        FROM tproduct
        WHERE kode LIKE 'P%'
        ORDER BY kode DESC
        LIMIT 1
    ");

    $lastProduct = $stmtLast->fetch(PDO::FETCH_ASSOC);

    if ($lastProduct) {
        $lastNumber = (int) substr($lastProduct['kode'], 1);
        $newNumber = $lastNumber + 1;
        $kodeTerakhir = $lastProduct['kode'];
    } else {
        $newNumber = 1;
        $kodeTerakhir = '-';
    }
    $kodeOtomatis = 'P' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $kode       = $kodeOtomatis;
        $nama       = trim($_POST['nama']);
        $hargaJual  = $_POST['hargajual'];
        $kategoriId = $_POST['kategori'];
        $status     = $_POST['status'];
        
        // Membungkus dengan Transaction agar data konsisten (Product & Resep)
        $koneksi->beginTransaction();

        $sql = "
            INSERT INTO tproduct
            (
                kode,
                nama,
                hargaJual,
                tKategori_id,
                status
            )
            VALUES
            (
                ?, ?, ?, ?, ?
            )
        ";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([
            $kode,
            $nama,
            $hargaJual,
            $kategoriId,
            $status
        ]);
        
        if(isset($_POST['resep_bahan'])){
            for ($i = 0; $i < count($_POST['resep_bahan']); $i++) {
                $kodeBahan = $_POST['resep_bahan'][$i];
                $jumlahInput = $_POST['resep_jumlah'][$i];
                $satuanId = $_POST['resep_satuan'][$i];

                $stmtResep = $koneksi->prepare("
                    INSERT INTO tResep
                    (
                        tProduct_kode,
                        tBahan_kode,
                        tSatuan_id,
                        jumlah
                    )
                    VALUES
                    (
                        ?, ?, ?, ?
                    )
                ");
                $stmtResep->execute([
                    $kode,
                    $kodeBahan,
                    $satuanId,
                    $jumlahInput
                ]);
            }
        }
        
        catatLog($koneksi, "Tambah Produk", "Menambahkan produk baru: " . $nama . " beserta resepnya", "Master Data", $kode);
        
        $koneksi->commit();
        header("Location: produk.php?success=add");
        exit;
    }
}
catch(PDOException $e) {
    if($koneksi->inTransaction()){
        $koneksi->rollBack();
    }
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
                    <h4 class="card-title mb-0">Tambah Produk</h4>
                    <a href="produk.php" class="btn btn-secondary">Kembali</a>
                  </div>
                  <?php if($error != "") : ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                  <?php endif; ?>
                  <form method="POST">
                    <div class="form-group">
                      <label>Kode Produk</label>
                      <input type="text" class="form-control" value="<?= $kodeOtomatis ?>" readonly>
                    </div>
                    <div class="form-group">
                      <label>Nama Produk</label>
                      <input type="text" name="nama" class="form-control" placeholder="Masukkan nama produk" required>
                    </div>
                    <div class="form-group">
                      <label>Kategori</label>
                      <select name="kategori" class="form-control" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php while($kat = $kategori->fetch(PDO::FETCH_ASSOC)): ?>
                          <option value="<?= $kat['id']; ?>"><?= $kat['nama']; ?></option>
                        <?php endwhile; ?>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Status</label>
                      <select name="status" class="form-control" required>
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                      </select>
                    </div>
                    <hr>
                    <h5>Resep Produk</h5>
                    <div class="row">
                      <div class="col-md-5">
                        <label>Bahan Baku</label>
                        <select id="bahanSelect" class="form-control">
                          <option value="">-- Pilih Bahan --</option>
                          <?php
                          $bahanBaku->execute();
                          while($bahan = $bahanBaku->fetch(PDO::FETCH_ASSOC)):
                          ?>
                            <option value="<?= $bahan['kode']; ?>" data-nama="<?= $bahan['nama']; ?>" data-satuanid="<?= $bahan['satuan_stok'] ?>" data-harga="<?= $bahan['harga']; ?>">
                              <?= $bahan['nama']; ?>
                            </option>
                          <?php endwhile; ?>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label>Jumlah</label>
                        <input type="number" id="jumlahBahan" class="form-control" min="0.01" step="0.01" value="1">
                      </div>
                      <div class="col-md-2">
                        <label>Satuan</label>
                        <select id="satuanBahan" class="form-control">
                            <option value="">Pilih</option>
                            <?php foreach($satuanList as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= $s['nama'] ?></option>
                            <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-success btn-block" onclick="tambahBahan()">Tambah</button>
                      </div>            
                    </div>
                    <br>
                    <table class="table table-bordered">
                      <thead>
                        <tr>
                          <th>Bahan</th>
                          <th>Jumlah</th>
                          <th>Satuan</th>
                          <th>HPP</th>
                          <th width="100">Aksi</th>
                        </tr>
                      </thead>
                      <table class="table table-bordered">
                        <tbody id="tabelResepBody"></tbody>
                      </table>
                    </table>
                    <div class="text-right mt-3">
                      <h5>Total HPP : Rp <span id="totalHpp">0</span></h5>
                      <h5 class="text-success">Estimasi Laba : Rp <span id="estimasiLaba">0</span></h5>
                    </div>
                    <div id="hiddenResep"></div>
                    <div class="form-group">
                      <label>Harga Jual</label>
                      <input type="number" name="hargajual" id="hargaJual" class="form-control" min="0" placeholder="Masukkan harga jual" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="produk.php" class="btn btn-light">Batal</a>
                  </form>
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
<script>
    const dataKonversi = <?= json_encode($konversiList) ?>;
    
    // Helper utk cari rasio konversi (Stock -> Selected Unit)
    function cariKonversi(idStock, idSelected) {
        if (idStock == idSelected) return 1;
        for (let k of dataKonversi) {
            if (k.SatuanBesar_id == idStock && k.SatuanKecil_id == idSelected) {
                return k.Konversi; // misal Kg -> Gram = 1000, jumlah_di_db / 1000 = jumlah_real
            }
        }
        return 1; // Default
    }

    function getNamaSatuan(idSatuan) {
        for (let s of <?= json_encode($satuanList) ?>) {
            if (s.id == idSatuan) return s.nama;
        }
        return '-';
    }

    document.getElementById('bahanSelect').addEventListener('change', function(){
        let idSatuanStock = this.options[this.selectedIndex].dataset.satuanid;
        let selectSatuan = document.getElementById('satuanBahan');
        selectSatuan.innerHTML = '<option value="">Pilih</option>';
        if (!idSatuanStock) return;

        let baseUnitName = getNamaSatuan(idSatuanStock);
        selectSatuan.insertAdjacentHTML('beforeend', `<option value="${idSatuanStock}">${baseUnitName}</option>`);

        for (let k of dataKonversi) {
            if (k.SatuanBesar_id == idSatuanStock) {
                let unitName = getNamaSatuan(k.SatuanKecil_id);
                selectSatuan.insertAdjacentHTML('beforeend', `<option value="${k.SatuanKecil_id}">${unitName}</option>`);
            }
        }
    });
    
    let totalHpp = 0;
    
        let selectSatuan = document.getElementById('satuanBahan');
        let idSatuanDipilih = selectSatuan.value;
        if (idSatuanDipilih === '') {
            alert('Pilih satuan terlebih dahulu!');
            return;
        }

        let kode = select.value;
        if(document.querySelector(`#row_${kode}`)){
            alert('Bahan baku tersebut sudah ada di resep!');
            return;
        }

        let nama = select.options[select.selectedIndex].dataset.nama;
        let idSatuanStock = select.options[select.selectedIndex].dataset.satuanid; // Nama unit stock tapi gpp kita butuh ID nya wait, dataset.satuanid di atas diset nama
        let hargaPerSatuanStok = parseFloat(select.options[select.selectedIndex].dataset.harga) || 0;
        let jumlahInput = parseFloat(document.getElementById('jumlahBahan').value) || 0;
        let namaSatuan = selectSatuan.options[selectSatuan.selectedIndex].text;

        if(jumlahInput <= 0){
            alert('Jumlah harus lebih dari 0');
            return;
        }

        let rasio = cariKonversi(idSatuanStock, idSatuanDipilih);
        let hpp = (jumlahInput / rasio) * hargaPerSatuanStok;

        let tbody = document.getElementById('tabelResepBody');
        let row = tbody.insertRow();
        row.setAttribute('id', 'row_' + kode);

        row.innerHTML = `
            <td>${nama}</td>
            <td>${jumlahInput}</td>
            <td>${namaSatuan}</td>
            <td>Rp ${hpp.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this, ${hpp}, '${kode}')">Hapus</button>
            </td>
        `;

        totalHpp += hpp; 
        updateHppDanLaba();

        document.getElementById('hiddenResep').insertAdjacentHTML('beforeend',
            `<div id="resep_${kode}">
                <input type="hidden" name="resep_bahan[]" value="${kode}">
                <input type="hidden" name="resep_jumlah[]" value="${jumlahInput}">
                <input type="hidden" name="resep_satuan[]" value="${idSatuanDipilih}">
            </div>`
        );

        document.getElementById('bahanSelect').value = '';
        document.getElementById('jumlahBahan').value = '1';
        document.getElementById('satuanBahan').value = '';
    }

    function hapusBaris(btn, hpp, kode){
        totalHpp -= hpp;
        btn.closest('tr').remove();
        let hidden = document.getElementById('resep_' + kode);
        if(hidden) hidden.remove();
        updateHppDanLaba();
    }

    function updateHppDanLaba() {
        document.getElementById('totalHpp').innerText = totalHpp.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2});
        let inputHargaJual = document.getElementById('hargaJual').value;
        let hargaJual = inputHargaJual ? parseFloat(inputHargaJual) : 0;
        let laba = hargaJual - totalHpp;
        document.getElementById('estimasiLaba').innerText = laba.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2});
    }

    document.getElementById('hargaJual').addEventListener('input', function(){
        updateHppDanLaba();
    });
    </script>