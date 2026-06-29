<?php
session_start();
$page_title = "CHARA - Edit Produk";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

if (!isset($_GET['kode'])) {
    header('location:produk.php');
    exit;
}

$kode = $_GET['kode'];
$pesan = '';

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
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $kode       = $_POST['kode'];
        $nama       = trim($_POST['nama']);
        $hargaJual  = $_POST['hargajual'];
        $kategoriId = $_POST['kategori'];
        $status     = $_POST['status'];
        
        $koneksi->beginTransaction();

        $sql = "
            UPDATE tProduct
            SET
                nama = ?,
                hargaJual = ?,
                tKategori_id = ?,
                status = ?
            WHERE kode = ?
        ";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([
            $nama,
            $hargaJual,
            $kategoriId,
            $status,
            $kode
        ]);
        
        // Hapus resep lama
        $stmtHapusResep = $koneksi->prepare("DELETE FROM tResep WHERE tProduct_kode = ?");
        $stmtHapusResep->execute([$kode]);
        
        // Simpan resep baru
        if(isset($_POST['resep'])){
            foreach($_POST['resep'] as $kodeBahan => $jumlahKonversi){
                $stmtResep = $koneksi->prepare("
                    INSERT INTO tResep (tProduct_kode, tBahan_kode, jumlah)
                    VALUES (?, ?, ?)
                ");
                $stmtResep->execute([
                    $kode,
                    $kodeBahan,
                    $jumlahKonversi // Nilai yang masuk sudah dikonversi ke Kg/Liter (desimal)
                ]);
            }
        }
        
        catatLog($koneksi, "Ubah Produk", "Mengubah data & resep produk: " . $nama, "Master Data", $kode);
        
        $koneksi->commit();
        header("Location: produk.php?success=edit");
        exit;
    }

    // AMBIL DATA PRODUCT
    $sql = "SELECT * FROM tProduct WHERE kode = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute([$kode]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('location:produk.php');
        exit;
    }

    // AMBIL DATA RESEP SAAT INI
    $stmtResepLama = $koneksi->prepare("
        SELECT r.tBahan_kode, r.jumlah, b.nama, b.harga, s.nama AS satuan_stok,
            CASE
                WHEN LOWER(s.nama) = 'kg' THEN 'Gram'
                WHEN LOWER(s.nama) = 'liter' THEN 'Ml'
                ELSE s.nama
            END AS satuan_resep
        FROM tResep r
        JOIN tBahan b ON r.tBahan_kode = b.kode
        JOIN tSatuan s ON b.tSatuan_id = s.id
        WHERE r.tProduct_kode = ?
    ");
    $stmtResepLama->execute([$kode]);
    $resepLama = $stmtResepLama->fetchAll(PDO::FETCH_ASSOC);

    $resepJsFormat = [];
    foreach($resepLama as $rl) {
        // Balikkan konversi ke gram/ml untuk UI
        $jumlahUI = (float)$rl['jumlah'];
        if (strtolower($rl['satuan_stok']) == 'kg' || strtolower($rl['satuan_stok']) == 'liter') {
            $jumlahUI = $jumlahUI * 1000;
        }
        $hpp = (float)$rl['jumlah'] * (float)$rl['harga'];
        $resepJsFormat[] = [
            'kode' => $rl['tBahan_kode'],
            'nama' => $rl['nama'],
            'satuan' => $rl['satuan_resep'],
            'jumlah_ui' => $jumlahUI,
            'jumlah_db' => (float)$rl['jumlah'],
            'hpp' => $hpp
        ];
    }
}
catch(PDOException $e) {
    if($koneksi->inTransaction()){
        $koneksi->rollBack();
    }
    $pesan = $e->getMessage();
}

$namaUser = $_SESSION['nama'];
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
        <div class="content-wrapper">
          <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Edit Produk & Resep</h4>
                    <a href="produk.php" class="btn btn-secondary">Kembali</a>
                  </div>
                  <?php if(!empty($pesan)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($pesan) ?></div>
                  <?php endif; ?>
                  
                  <form method="POST">
                    <input type="hidden" name="kode" value="<?= $product['kode']; ?>">
                    <div class="form-group">
                      <label>Kode Produk</label>
                      <input type="text" class="form-control" value="<?= htmlspecialchars($product['kode']) ?>" readonly>
                    </div>
                    <div class="form-group">
                      <label>Nama Produk</label>
                      <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($product['nama']) ?>" required>
                    </div>
                    <div class="form-group">
                      <label>Kategori</label>
                      <select name="kategori" class="form-control" required>
                        <?php while($kat = $kategori->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?= $kat['id']; ?>" <?= ($kat['id'] == $product['tKategori_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($kat['nama']); ?>
                            </option>
                        <?php endwhile; ?>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Status</label>
                      <select name="status" class="form-control" required>
                        <option value="Aktif" <?= ($product['status'] == 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="Nonaktif" <?= ($product['status'] == 'Nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
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
                            <option value="<?= $bahan['kode']; ?>" data-nama="<?= htmlspecialchars($bahan['nama']); ?>" data-satuan="<?= htmlspecialchars($bahan['satuan_resep']); ?>" data-satuanstok="<?= htmlspecialchars($bahan['satuan_stok']); ?>" data-harga="<?= $bahan['harga']; ?>">
                              <?= htmlspecialchars($bahan['nama']); ?>
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
                      <tbody id="tabelResepBody"></tbody>
                    </table>
                    <div class="text-right mt-3">
                      <h5>Total HPP : Rp <span id="totalHpp">0</span></h5>
                      <h5 class="text-success">Estimasi Laba : Rp <span id="estimasiLaba">0</span></h5>
                    </div>
                    <div id="hiddenResep"></div>
                    <div class="form-group">
                      <label>Harga Jual</label>
                      <input type="number" name="hargajual" id="hargaJual" class="form-control" min="0" value="<?= $product['hargaJual'] ?>" required>
                    </div>
                    <button type="submit" class="btn btn-warning">Update Produk</button>
                    <a href="produk.php" class="btn btn-light">Batal</a>
                  </form>
                </div>
              </div>
            </div>
          </div>
<?php require_once '../includes/footer.php'; ?>

<script>
    document.getElementById('bahanSelect').addEventListener('change', function(){
        let satuan = this.options[this.selectedIndex].dataset.satuan;
        document.getElementById('satuanBahan').value = satuan || '';
    });
    
    let totalHpp = 0;
    
    // Injeksi data resep lama dari PHP ke JS
    const resepLama = <?= json_encode($resepJsFormat) ?>;
    
    function initResepLama() {
        let tbody = document.getElementById('tabelResepBody');
        let hiddenContainer = document.getElementById('hiddenResep');
        
        resepLama.forEach(item => {
            let row = tbody.insertRow();
            row.setAttribute('id', 'row_' + item.kode);
            row.innerHTML = `
                <td>${item.nama}</td>
                <td>${item.jumlah_ui}</td>
                <td>${item.satuan}</td>
                <td>Rp ${item.hpp.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this, ${item.hpp}, '${item.kode}')">Hapus</button>
                </td>
            `;
            
            hiddenContainer.insertAdjacentHTML('beforeend',
                `<input type="hidden" id="resep_${item.kode}" name="resep[${item.kode}]" value="${item.jumlah_db}">`
            );
            
            totalHpp += item.hpp;
        });
        updateHppDanLaba();
    }
    
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

        let jumlahUntukResepDB = jumlahInput;
        if (satuan.toLowerCase() === 'gram' || satuan.toLowerCase() === 'ml') {
            jumlahUntukResepDB = jumlahInput / 1000;
        }

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

        document.getElementById('hiddenResep').insertAdjacentHTML('beforeend',
            `<input type="hidden" id="resep_${kode}" name="resep[${kode}]" value="${jumlahUntukResepDB}">`
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

    document.getElementById('hargaJual').addEventListener('input', updateHppDanLaba);
    
    // Inisialisasi saat load
    initResepLama();
</script>