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
    
    // Ambil data satuan
    $satuanAll = $koneksi->query("SELECT * FROM tsatuan ORDER BY nama");
    $satuanList = $satuanAll->fetchAll(PDO::FETCH_ASSOC);

    // Ambil data konversi untuk JS
    $konvAll = $koneksi->query("SELECT * FROM tkonversisatuan");
    $konversiList = $konvAll->fetchAll(PDO::FETCH_ASSOC);
    
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
        if(isset($_POST['resep_bahan'])){
            for ($i = 0; $i < count($_POST['resep_bahan']); $i++) {
                $kodeBahan = $_POST['resep_bahan'][$i];
                $jumlahInput = $_POST['resep_jumlah'][$i];
                $satuanId = $_POST['resep_satuan'][$i];

                $stmtResep = $koneksi->prepare("
                    INSERT INTO tResep (tProduct_kode, tBahan_kode, tSatuan_id, jumlah)
                    VALUES (?, ?, ?, ?)
                ");
                $stmtResep->execute([$kode, $kodeBahan, $satuanId, $jumlahInput]);
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
        SELECT r.tBahan_kode, r.jumlah, r.tSatuan_id, b.nama, b.harga, b.tSatuan_id as satuan_stok_id,
               IFNULL(k.Konversi, 1) as nilai_konversi
        FROM tResep r
        JOIN tBahan b ON r.tBahan_kode = b.kode
        LEFT JOIN tkonversisatuan k ON k.SatuanBesar_id = b.tSatuan_id AND k.SatuanKecil_id = r.tSatuan_id
        WHERE r.tProduct_kode = ?
    ");
    $stmtResepLama->execute([$kode]);
    $resepLama = $stmtResepLama->fetchAll(PDO::FETCH_ASSOC);

    $resepJsFormat = [];
    foreach($resepLama as $rl) {
        // Jika legacy (tSatuan_id == 0), biarkan jumlahnya sesuai DB dan satuan_id diset ke satuan stok
        $satuanSelected = $rl['tSatuan_id'] == 0 ? $rl['satuan_stok_id'] : $rl['tSatuan_id'];
        $jumlahUI = (float)$rl['jumlah'];
        $hpp = ($jumlahUI / (float)$rl['nilai_konversi']) * (float)$rl['harga'];
        
        $resepJsFormat[] = [
            'kode' => $rl['tBahan_kode'],
            'nama' => $rl['nama'],
            'satuan_id' => $satuanSelected,
            'jumlah_ui' => $jumlahUI,
            'jumlah_db' => $jumlahUI, // Kita sekarang simpan raw input
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
                      <select name="kategori" id="kategoriSelect" class="form-control" required>
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
                            <option value="<?= $bahan['kode']; ?>" data-nama="<?= $bahan['nama']; ?>" data-satuanid="<?= $bahan['tSatuan_id'] ?>" data-harga="<?= $bahan['harga']; ?>">
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
    const dataKonversi = <?= json_encode($konversiList) ?>;
    const dataSatuan = <?= json_encode($satuanList) ?>;
    
    // Build undirected graph for BFS
    let konversiGraph = {};
    for (let k of dataKonversi) {
        if (!konversiGraph[k.SatuanBesar_id]) konversiGraph[k.SatuanBesar_id] = {};
        if (!konversiGraph[k.SatuanKecil_id]) konversiGraph[k.SatuanKecil_id] = {};
        konversiGraph[k.SatuanBesar_id][k.SatuanKecil_id] = parseFloat(k.Konversi);
        if (parseFloat(k.Konversi) !== 0) {
            konversiGraph[k.SatuanKecil_id][k.SatuanBesar_id] = 1.0 / parseFloat(k.Konversi);
        }
    }

    function cariKonversi(idStock, idSelected) {
        if (idStock == idSelected) return 1;
        
        let queue = [{id: idStock, multiplier: 1}];
        let visited = new Set();
        visited.add(idStock);
        
        while(queue.length > 0) {
            let curr = queue.shift();
            if (curr.id == idSelected) return curr.multiplier;
            
            if (konversiGraph[curr.id]) {
                for (let neighbor in konversiGraph[curr.id]) {
                    if (!visited.has(neighbor)) {
                        visited.add(neighbor);
                        queue.push({
                            id: neighbor, 
                            multiplier: curr.multiplier * konversiGraph[curr.id][neighbor]
                        });
                    }
                }
            }
        }
        return 1;
    }

    function getNamaSatuan(idSatuan) {
        for (let s of dataSatuan) {
            if (s.id == idSatuan) return s.nama;
        }
        return '-';
    }

    const resepDariDB = <?= json_encode($resepJsFormat); ?>;
    let totalHpp = 0;
    
    function initResepLama() {
        let tbody = document.getElementById('tabelResepBody');
        let hiddenContainer = document.getElementById('hiddenResep');
        
        resepDariDB.forEach(item => {
            let row = tbody.insertRow();
            row.setAttribute('id', 'row_' + item.kode);
            row.innerHTML = `
                <td>${item.nama}</td>
                <td>${item.jumlah_ui}</td>
                <td>
                    <select class="form-control form-control-sm" disabled>
                        <option>${getNamaSatuan(item.satuan_id)}</option>
                    </select>
                </td>
                <td>Rp ${item.hpp.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2})}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="hapusBaris(this, ${item.hpp}, '${item.kode}')">Hapus</button>
                </td>
            `;
            totalHpp += item.hpp;
            hiddenContainer.insertAdjacentHTML('beforeend',
                `<div id="resep_${item.kode}">
                    <input type="hidden" name="resep_bahan[]" value="${item.kode}">
                    <input type="hidden" name="resep_jumlah[]" value="${item.jumlah_ui}">
                    <input type="hidden" name="resep_satuan[]" value="${item.satuan_id}">
                </div>`
            );
        });
        updateHppDanLaba();
    }
    
    function tambahBahan(){
        let select = document.getElementById('bahanSelect');
        let selectSatuan = document.getElementById('satuanBahan');
        
        if (select.value === '' || selectSatuan.value === '') {
            alert('Pilih bahan dan satuan terlebih dahulu!');
            return;
        }

        let kode = select.value;
        if(document.querySelector(`#row_${kode}`)){
            alert('Bahan baku sudah ada!');
            return;
        }

        let nama = select.options[select.selectedIndex].dataset.nama;
        let idSatuanStock = select.options[select.selectedIndex].dataset.satuanid;
        let hargaPerSatuanStok = parseFloat(select.options[select.selectedIndex].dataset.harga) || 0;
        let jumlahInput = parseFloat(document.getElementById('jumlahBahan').value) || 0;
        let idSatuanDipilih = selectSatuan.value;
        let namaSatuan = selectSatuan.options[selectSatuan.selectedIndex].text;

        if(jumlahInput <= 0){
            alert('Jumlah harus > 0');
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
            <td>
                <select class="form-control form-control-sm" disabled>
                    <option>${namaSatuan}</option>
                </select>
            </td>
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

    document.getElementById('hargaJual').addEventListener('input', updateHppDanLaba);
    
    $('#bahanSelect').on('change', function(){
        let option = this.options[this.selectedIndex];
        if(!option) return;
        let idSatuanStock = option.dataset.satuanid;
        let selectSatuan = document.getElementById('satuanBahan');
        selectSatuan.innerHTML = '<option value="">Pilih</option>';
        if (!idSatuanStock) return;

        // BFS untuk mencari semua satuan yang terhubung
        let queue = [idSatuanStock];
        let visited = new Set();
        visited.add(idSatuanStock);
        
        while(queue.length > 0) {
            let curr = queue.shift();
            let unitName = getNamaSatuan(curr);
            selectSatuan.insertAdjacentHTML('beforeend', `<option value="${curr}">${unitName}</option>`);
            
            if (konversiGraph[curr]) {
                for (let neighbor in konversiGraph[curr]) {
                    if (!visited.has(neighbor)) {
                        visited.add(neighbor);
                        queue.push(neighbor);
                    }
                }
            }
        }
    });

    // Inisialisasi saat load
    initResepLama();

    $(document).ready(function() {
        $('#kategoriSelect').select2({ placeholder: '-- Pilih Kategori --' });
        $('#bahanSelect').select2({ placeholder: '-- Pilih Bahan --' });
    });
</script>