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
        
        if(isset($_POST['resep'])){
            foreach($_POST['resep'] as $kodeBahan => $jumlahKonversi){
                $stmtResep = $koneksi->prepare("
                    INSERT INTO tResep
                    (
                        tProduct_kode,
                        tBahan_kode,
                        jumlah
                    )
                    VALUES
                    (
                        ?, ?, ?
                    )
                ");
                $stmtResep->execute([
                    $kode,
                    $kodeBahan,
                    $jumlahKonversi // Nilai yang masuk sudah dikonversi ke Kg/Liter (desimal)
                ]);
            }
        }
        
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
                            <option value="<?= $bahan['kode']; ?>" data-nama="<?= $bahan['nama']; ?>" data-satuan="<?= $bahan['satuan_resep']; ?>" data-satuanstok="<?= $bahan['satuan_stok']; ?>" data-harga="<?= $bahan['harga']; ?>">
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
                        <input type="text" id="satuanBahan" class="form-control" readonly>
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
    document.getElementById('bahanSelect').addEventListener('change', function(){
        let satuan = this.options[this.selectedIndex].dataset.satuan;
        document.getElementById('satuanBahan').value = satuan || '';
    });
    
    let totalHpp = 0;
    
    function tambahBahan(){
        let select = document.getElementById('bahanSelect');
        if(select.value == '') return;

        let kode = select.value;

        if(document.querySelector(`#resep_${kode}`)){
            alert('Bahan baku tersebut sudah ada di resep!');
            return;
        }

        let nama = select.options[select.selectedIndex].dataset.nama;
        let satuan = select.options[select.selectedIndex].dataset.satuan;
        let satuanStok = select.options[select.selectedIndex].dataset.satuanstok;
        let jumlahInput = parseFloat(document.getElementById('jumlahBahan').value) || 0;
        let hargaPerSatuanStok = parseFloat(select.options[select.selectedIndex].dataset.harga) || 0;

        if(jumlahInput <= 0){
            alert('Jumlah harus lebih dari 0');
            return;
        }

        // PERBAIKAN LOGIKA: Konversi Gram/Ml ke KG/Liter di balik layar sebelum disimpan
        let jumlahUntukResepDB = jumlahInput;
        if (satuan.toLowerCase() === 'gram' || satuan.toLowerCase() === 'ml') {
            jumlahUntukResepDB = jumlahInput / 1000;
        }

        // Hitung HPP riil item resep
        let hpp = jumlahUntukResepDB * hargaPerSatuanStok;

        let tbody = document.getElementById('tabelResepBody');
        let row = tbody.insertRow();
        row.setAttribute('id', 'row_' + kode);

        row.innerHTML = `
            <td>${nama}</td>
            <td>${jumlahInput}</td>
            <td>${satuan}</td>
            <td>Rp ${hpp.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this, ${hpp}, '${kode}')">Hapus</button>
            </td>
        `;

        totalHpp += hpp; 
        updateHppDanLaba();

        // Menyimpan nilai konversi desimal (KG/Liter) agar modul penjualan Kasir bisa langsung memotong stok
        document.getElementById('hiddenResep').insertAdjacentHTML('beforeend',
            `<input type="hidden" id="resep_${kode}" name="resep[${kode}]" value="${jumlahUntukResepDB}">`
        );

        // Reset Form input bahan
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