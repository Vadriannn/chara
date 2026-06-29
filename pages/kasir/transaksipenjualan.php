<?php
session_start();
$page_title = "CHARA - Tambah Transaksi Penjualan";
require_once '../../koneksi.php';
require_once '../../auth.php';

$error = "";

try {
    // 1. Ambil produk aktif untuk dropdown
    $produk = $koneksi->query("
        SELECT *
        FROM tProduct
        WHERE status = 'Aktif'
        ORDER BY nama
    ");

    // 2. Ambil data master modifier (kategori Sugar & Ice)
    $stmtMod = $koneksi->query("SELECT * FROM tModifier ORDER BY kategori DESC, nama ASC");
    $allModifiers = $stmtMod->fetchAll(PDO::FETCH_ASSOC);
    
    $sugarMods = array_filter($allModifiers, function($m) { return $m['kategori'] == 'Sugar'; });
    $iceMods = array_filter($allModifiers, function($m) { return $m['kategori'] == 'Ice'; });

    // 3. AMBIL DATA STOK BAHAN UTK LIVE VALIDASI JAVASCRIPT
    $stmtBahan = $koneksi->query("SELECT kode, stok FROM tBahan");
    $stokBahan = [];
    while($b = $stmtBahan->fetch(PDO::FETCH_ASSOC)) {
        $stokBahan[$b['kode']] = (float)$b['stok'];
    }

    // 4. AMBIL DATA RESEP UTK LIVE VALIDASI JAVASCRIPT
    $stmtResepAll = $koneksi->query("
        SELECT r.tProduct_kode, r.tBahan_kode, r.jumlah, b.nama AS nama_bahan 
        FROM tResep r
        JOIN tBahan b ON r.tBahan_kode = b.kode
    ");
    $resepData = [];
    while($r = $stmtResepAll->fetch(PDO::FETCH_ASSOC)) {
        $resepData[$r['tProduct_kode']][] = [
            'bahan'  => $r['tBahan_kode'],
            'nama_bahan' => $r['nama_bahan'],
            'jumlah' => (float)$r['jumlah']
        ];
    }

    // ==========================================
    // PROSES SIMPAN TRANSAKSI KE DATABASE
    // ==========================================
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Tangkap array yang di-generate dari index JS
        $produkArray = $_POST['produk_kode'] ?? [];
        $qtyArray = $_POST['qty_beli'] ?? [];
        $metbayar = $_POST['metbayar'];
        $diskonPersen = isset($_POST['diskon']) ? (float)$_POST['diskon'] : 0;

        if(empty($produkArray)) {
            throw new Exception("Keranjang belanja kosong!");
        }

        $koneksi->beginTransaction();

        // Generate nomor penjualan manual (jika tPenjualan nomor belum Auto Increment)
        $stmtNomor = $koneksi->query("SELECT nomor FROM tPenjualan ORDER BY nomor DESC LIMIT 1");
        $last = $stmtNomor->fetch(PDO::FETCH_ASSOC);
        $nomorPenjualan = $last ? $last['nomor'] + 1 : 1;

        // Hitung Subtotal Keranjang
        $subtotalKeranjang = 0;
        foreach($produkArray as $index => $kodeProduk) {
            $qty = (int)$qtyArray[$index];
            $stmtCekHarga = $koneksi->prepare("SELECT hargaJual FROM tProduct WHERE kode = ?");
            $stmtCekHarga->execute([$kodeProduk]);
            $hargaJual = $stmtCekHarga->fetchColumn();
            $subtotalKeranjang += ($hargaJual * $qty);
        }

        if($diskonPersen > 100) $diskonPersen = 100;
        if($diskonPersen < 0) $diskonPersen = 0;
        $diskonNominal = $subtotalKeranjang * ($diskonPersen / 100);
        $grandTotal = $subtotalKeranjang - $diskonNominal;

        // Simpan Master Penjualan
        $stmtPenjualan = $koneksi->prepare("
            INSERT INTO tPenjualan (nomor, tanggal, total, diskon, metbayar, tUser_id)
            VALUES (?, NOW(), ?, ?, ?, ?)
        ");
        $stmtPenjualan->execute([
            $nomorPenjualan, $grandTotal, $diskonNominal, $metbayar, $_SESSION['id_user']
        ]);

        // Catat ke tArusKas (Pemasukan)
        $stmtArusKas = $koneksi->prepare("
            INSERT INTO tArusKas (tanggal, jenis, kategori, nominal, sumber, tPenjualan_nomor)
            VALUES (NOW(), 'Masuk', 'Penjualan', ?, ?, ?)
        ");
        $stmtArusKas->execute([
            $grandTotal, 
            'Pendapatan dari Nota #' . $nomorPenjualan,
            $nomorPenjualan
        ]);

        // Siapkan Statement untuk Detail dan Modifier
        $stmtDetail = $koneksi->prepare("
            INSERT INTO tDetailPenjualan (tProduct_kode, tPenjualan_nomor, hpp, harga_jual, jumlah, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmtInsertModifier = $koneksi->prepare("
            INSERT INTO tDetailPenjualanModifier (tDetailPenjualan_id, tModifier_id)
            VALUES (?, ?)
        ");

        $stmtHpp = $koneksi->prepare("
            SELECT SUM(r.jumlah * b.harga) AS hpp
            FROM tResep r
            JOIN tBahan b ON r.tBahan_kode = b.kode
            WHERE r.tProduct_kode = ?
        ");

        $stmtResep = $koneksi->prepare("
            SELECT r.tBahan_kode, r.jumlah, b.stok
            FROM tResep r
            JOIN tBahan b ON r.tBahan_kode = b.kode
            WHERE r.tProduct_kode = ?
        ");

        $updateStok = $koneksi->prepare("UPDATE tBahan SET stok = ? WHERE kode = ?");
        $stmtMutasi = $koneksi->prepare("
            INSERT INTO tMutasiStok (tanggal, jenis, qty, stokSebelum, stokSesudah, referensi, tBahan_kode, tUser_id)
            VALUES (NOW(), 'Penjualan', ?, ?, ?, ?, ?, ?)
        ");

        // Proses tiap item di keranjang
        foreach($produkArray as $index => $kodeProduk) {
            $qty = (int)$qtyArray[$index];

            $stmtProdukInfo = $koneksi->prepare("SELECT hargaJual FROM tProduct WHERE kode = ?");
            $stmtProdukInfo->execute([$kodeProduk]);
            $hargaJual = $stmtProdukInfo->fetchColumn();
            $subtotal = $hargaJual * $qty;

            $stmtHpp->execute([$kodeProduk]);
            $hpp = $stmtHpp->fetch(PDO::FETCH_ASSOC)['hpp'];
            if(!$hpp) $hpp = 0;

            // 1. Simpan ke tDetailPenjualan
            $stmtDetail->execute([
                $kodeProduk, $nomorPenjualan, $hpp, $hargaJual, $qty, $subtotal
            ]);

            // 2. Ambil ID auto-increment dari tDetailPenjualan yang baru saja tersimpan
            $idDetailBaru = $koneksi->lastInsertId();

            // 3. Cek apakah index ini punya modifier yang dikirim dari JS
            if (isset($_POST['modifier'][$index]) && is_array($_POST['modifier'][$index])) {
                foreach ($_POST['modifier'][$index] as $modId) {
                    $stmtInsertModifier->execute([$idDetailBaru, $modId]);
                }
            }

            // 4. Potong Stok Bahan
            $stmtResep->execute([$kodeProduk]);
            while($resep = $stmtResep->fetch(PDO::FETCH_ASSOC)) {
                $qtyKeluar = $resep['jumlah'] * $qty;
                $stokSebelum = $resep['stok'];
                $stokSesudah = $stokSebelum - $qtyKeluar;

                if($stokSesudah < 0){
                    throw new Exception("Sistem ditahan: Stok bahan ".$resep['tBahan_kode']." tidak mencukupi untuk memproses produk ".$kodeProduk.".");
                }

                $updateStok->execute([$stokSesudah, $resep['tBahan_kode']]);
                
                $stmtMutasi->execute([
                    $qtyKeluar, $stokSebelum, $stokSesudah, 'PJ-'.$nomorPenjualan, $resep['tBahan_kode'], $_SESSION['id_user']
                ]);
            }
        }
        
        catatLog($koneksi, "Transaksi Penjualan", "Melakukan penjualan dengan total Rp " . number_format($grandTotal, 0, ',', '.'), "Kasir", $nomorPenjualan);

        $koneksi->commit();
        header("Location: detailpenjualan.php?nomor=" . $nomorPenjualan . "&success=1");
        exit;
    }

} catch(Exception $e) {
    if($koneksi->inTransaction()){
        $koneksi->rollBack();
    }
    $error = $e->getMessage();
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

          <div class="content-wrapper">
              <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h4 class="card-title mb-0">Transaksi Penjualan (Kasir)</h4>
                      <a href="datapenjualan.php" class="btn btn-secondary">Daftar Penjualan</a>
                    </div>
                    
                    <?php if($error != "") : ?>
                      <div class="alert alert-danger p-2"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                      <div class="mb-4">
                          <div class="row">
                              <div class="col-md-8">
                                  <div class="form-group mb-3">
                                      <label class="font-weight-bold">Pilih Produk</label>
                                      <select id="produkSelect" class="form-control form-control-lg" onchange="updateMaxUI()">
                                          <option value="">-- Pilih Produk --</option>
                                          <?php while($p = $produk->fetch(PDO::FETCH_ASSOC)): ?>
                                              <option 
                                                  value="<?= $p['kode']; ?>" 
                                                  data-nama="<?= $p['nama']; ?>"
                                                  data-harga="<?= $p['hargaJual']; ?>">
                                                  <?= $p['nama']; ?> - Rp <?= number_format($p['hargaJual'],0,',','.'); ?>
                                              </option>
                                          <?php endwhile; ?>
                                      </select>
                                  </div>
                              </div>
                              <div class="col-md-4">
                                  <div class="form-group mb-3">
                                      <label class="font-weight-bold d-flex justify-content-between">
                                          <span>Qty</span>
                                          <span id="maxQtyLabel" class="text-danger small font-weight-bold"></span>
                                      </label>
                                      <div class="input-group qty-input-group">
                                          <div class="input-group-prepend">
                                              <button class="btn btn-outline-info" type="button" onclick="changeQty(-1)">-</button>
                                          </div>
                                          <input type="number" id="qtyProduk" class="form-control text-center" min="1" value="1" readonly>
                                          <div class="input-group-append">
                                              <button class="btn btn-outline-info" type="button" onclick="changeQty(1)">+</button>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>

                          <div class="row align-items-end">
                              <div class="col-md-4">
                                  <label class="mod-label">Kustom: Gula</label>
                                  <select id="sugarSelect" class="form-control">
                                      <option value="">Normal</option>
                                      <?php foreach($sugarMods as $mod): ?>
                                          <option value="<?= $mod['id'] ?>"><?= $mod['nama'] ?> Sugar</option>
                                      <?php endforeach; ?>
                                  </select>
                              </div>
                              <div class="col-md-4 mt-3 mt-md-0">
                                  <label class="mod-label">Kustom: Es</label>
                                  <select id="iceSelect" class="form-control">
                                      <option value="">Normal</option>
                                      <?php foreach($iceMods as $mod): ?>
                                          <option value="<?= $mod['id'] ?>"><?= $mod['nama'] ?> Ice</option>
                                      <?php endforeach; ?>
                                  </select>
                              </div>
                              <div class="col-md-4 mt-4 mt-md-0">
                                  <button type="button" id="btnTambah" class="btn btn-primary w-100" onclick="tambahKeranjang()">
                                      <i class="typcn typcn-plus"></i> Tambah Item
                                  </button>
                              </div>
                          </div>
                      </div>

                      <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                          <thead>
                            <tr>
                              <th>Produk & Kustomisasi</th>
                              <th class="text-right">Harga</th>
                              <th class="text-center">Qty</th>
                              <th class="text-right">Subtotal</th>
                              <th width="80" class="text-center">Aksi</th>
                            </tr>
                          </thead>
                          <tbody id="tabelKeranjangBody">
                            </tbody>
                        </table>
                      </div>

                      <div class="row mt-3 mb-4">
                          <div class="col-md-5 offset-md-7">
                              <div class="p-3">
                                  <table class="table table-borderless text-right mb-0">
                                      <tr>
                                          <th class="align-middle py-1">Subtotal</th>
                                          <td class="py-1"><h5 class="mb-0">Rp <span id="subtotalDisplay">0</span></h5></td>
                                      </tr>
                                      <tr>
                                          <th class="align-middle py-2 border-bottom">Diskon (%)</th>
                                          <td class="py-2 border-bottom">
                                              <input type="number" name="diskon" id="inputDiskon" class="form-control form-control-sm text-right float-right w-50" min="0" max="100" step="0.1" value="0" placeholder="0" oninput="hitungTotalAkhir()">
                                              <div class="clearfix"></div>
                                              <small id="nominalDiskonDisplay" class="text-danger d-block mt-1 font-weight-bold">- Rp 0</small>
                                          </td>
                                      </tr>
                                      <tr>
                                          <th class="align-middle py-3"><h5>Grand Total</h5></th>
                                          <td class="py-3"><h3 class="text-success mb-0 font-weight-bold">Rp <span id="grandTotalDisplay">0</span></h3></td>
                                      </tr>
                                  </table>
                              </div>
                          </div>
                      </div>

                      <div id="hiddenCartData"></div>

                      <div class="row align-items-end">
                          <div class="col-md-4">
                              <div class="form-group mb-0">
                                  <label class="font-weight-bold">Metode Pembayaran</label>
                                  <select name="metbayar" class="form-control" required>
                                      <option value="Tunai">Tunai</option>
                                      <option value="QRIS">QRIS</option>
                                      <option value="Debit">Debit</option>
                                  </select>
                              </div>
                          </div>
                          <div class="col-md-8 text-right mt-4 mt-md-0">
                              <a href="transaksipenjualan.php" class="btn btn-light mr-2">Reset</a>
                              <button type="submit" class="btn btn-primary" onclick="return validasiSubmit()">Proses Pembayaran</button>
                          </div>
                      </div>

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
    // DATA DARI BACKEND PHP
    const stokBahanAsli = <?= json_encode($stokBahan); ?>;
    const resepProduk = <?= json_encode($resepData); ?>;
    
    // VARIABEL STATE JS
    let stokBahanCurrent = Object.assign({}, stokBahanAsli); 
    let subtotalKeranjang = 0;
    let itemIndexCounter = 0; // Digunakan sebagai kunci unik untuk tiap baris keranjang
    
    let limitBahanText = "";

    // HITUNG MAKSIMAL PORSI DARI SISA BAHAN GUDANG
    function hitungMaxPorsi(kodeProduk) {
        limitBahanText = "";
        let resep = resepProduk[kodeProduk];
        if (!resep || resep.length === 0) return 999; 
        
        let maxPorsi = Infinity;
        let penyebabKurang = "";

        resep.forEach(item => {
            let sisaStok = stokBahanCurrent[item.bahan] || 0;
            let bisaBikin = Math.floor(sisaStok / item.jumlah);
            if (bisaBikin < maxPorsi) {
                maxPorsi = bisaBikin;
                penyebabKurang = item.nama_bahan;
            }
        });
        
        if (maxPorsi === Infinity) return 0;
        
        limitBahanText = penyebabKurang;
        return maxPorsi;
    }

    function updateMaxUI() {
        let select = document.getElementById('produkSelect');
        let maxLabel = document.getElementById('maxQtyLabel');
        let inputQty = document.getElementById('qtyProduk');
        let btnTambah = document.getElementById('btnTambah');
        
        if(select.value === '') {
            maxLabel.innerText = '';
            inputQty.value = 1;
            btnTambah.disabled = false;
            return;
        }
        
        let max = hitungMaxPorsi(select.value);
        
        if (max === 999) {
            maxLabel.innerText = '(Stok: Bebas)';
            maxLabel.classList.remove('text-danger');
        } else {
            if (max === 0) {
                maxLabel.innerHTML = `(Maks: 0) - <span class="text-danger">Bahan <b>${limitBahanText}</b> kurang!</span>`;
            } else {
                maxLabel.innerText = `(Maks: ${max})`;
                maxLabel.classList.remove('text-danger');
            }
        }
        
        let currentInput = parseInt(inputQty.value) || 0;
        if (max === 0) {
            inputQty.value = 0;
            btnTambah.disabled = true;
        } else {
            btnTambah.disabled = false;
            if (currentInput > max) inputQty.value = max;
            if (currentInput === 0 && max > 0) inputQty.value = 1;
        }
    }

    function changeQty(delta) {
        let select = document.getElementById('produkSelect');
        if(select.value === '') return;
        
        let max = hitungMaxPorsi(select.value);
        let input = document.getElementById('qtyProduk');
        let currentVal = parseInt(input.value) || 0;
        
        let newVal = currentVal + delta;
        
        if (max === 0) {
            input.value = 0;
            return;
        }

        if (newVal < 1) newVal = 1;
        if (newVal > max) {
            let lbl = document.getElementById('maxQtyLabel');
            lbl.style.color = 'red';
            setTimeout(() => lbl.style.color = '', 500);
            newVal = max;
        }
        
        input.value = newVal;
    }

    function tambahKeranjang(){
        let select = document.getElementById('produkSelect');
        if(select.value == '') {
            alert('Pilih produk terlebih dahulu!');
            return;
        }

        let kode = select.value;
        let qty = parseInt(document.getElementById('qtyProduk').value) || 0;
        let max = hitungMaxPorsi(kode);

        if(qty <= 0){
            alert('Stok tidak mencukupi atau Qty tidak valid!');
            return;
        }

        if(qty > max){
            alert('Jumlah pesanan melebihi batas ketersediaan bahan baku di gudang!');
            return;
        }

        // TANGKAP NILAI MODIFIER
        let sugarEl = document.getElementById('sugarSelect');
        let iceEl = document.getElementById('iceSelect');
        
        let arrNamaMod = [];
        let htmlHiddenMod = '';

        if(sugarEl.value !== '') {
            arrNamaMod.push(sugarEl.options[sugarEl.selectedIndex].text);
            htmlHiddenMod += `<input type="hidden" name="modifier[${itemIndexCounter}][]" value="${sugarEl.value}">`;
        }
        if(iceEl.value !== '') {
            arrNamaMod.push(iceEl.options[iceEl.selectedIndex].text);
            htmlHiddenMod += `<input type="hidden" name="modifier[${itemIndexCounter}][]" value="${iceEl.value}">`;
        }

        let teksModifier = arrNamaMod.length > 0 ? `<br><small class="text-danger font-weight-bold">(${arrNamaMod.join(', ')})</small>` : '';

        // POTONG LIVE STOK DI PROGRAM JS
        if (resepProduk[kode]) {
            resepProduk[kode].forEach(item => {
                stokBahanCurrent[item.bahan] -= (item.jumlah * qty);
            });
        }

        let nama = select.options[select.selectedIndex].dataset.nama;
        let harga = parseFloat(select.options[select.selectedIndex].dataset.harga);
        let subtotalBaris = harga * qty;

        let tbody = document.getElementById('tabelKeranjangBody');
        let row = tbody.insertRow();
        let barisId = 'cart_row_' + itemIndexCounter;
        row.setAttribute('id', barisId);

        row.innerHTML = `
            <td class="align-middle">
                <span class="font-weight-bold text-dark">${nama}</span>
                ${teksModifier}
            </td>
            <td class="text-right align-middle">Rp ${harga.toLocaleString('id-ID')}</td>
            <td class="text-center align-middle">${qty}</td>
            <td class="text-right font-weight-bold text-primary align-middle">Rp ${subtotalBaris.toLocaleString('id-ID')}</td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-danger btn-sm py-1 px-2" onclick="hapusBaris(this, ${subtotalBaris}, '${kode}', ${qty}, ${itemIndexCounter})">&times;</button>
            </td>
        `;

        subtotalKeranjang += subtotalBaris; 
        hitungTotalAkhir(); 

        // RENDER HIDDEN INPUT DENGAN INDEX UNIK
        let hiddenCart = document.getElementById('hiddenCartData');
        hiddenCart.insertAdjacentHTML('beforeend', `
            <div id="hidden_data_${itemIndexCounter}">
                <input type="hidden" name="produk_kode[${itemIndexCounter}]" value="${kode}">
                <input type="hidden" name="qty_beli[${itemIndexCounter}]" value="${qty}">
                ${htmlHiddenMod}
            </div>
        `);

        // Reset form atas
        select.value = '';
        sugarEl.value = '';
        iceEl.value = '';
        document.getElementById('qtyProduk').value = '1';
        
        itemIndexCounter++; // Naikkan counter agar baris berikutnya unik
        updateMaxUI(); 
    }

    function hapusBaris(btn, subtotalBaris, kode, qty, indexBaris){
        // KEMBALIKAN LIVE STOK DI PROGRAM JS
        if (resepProduk[kode]) {
            resepProduk[kode].forEach(item => {
                stokBahanCurrent[item.bahan] += (item.jumlah * qty);
            });
        }

        subtotalKeranjang -= subtotalBaris;
        hitungTotalAkhir(); 
        
        btn.closest('tr').remove(); // Hapus dari tabel UI
        let hiddenInput = document.getElementById('hidden_data_' + indexBaris);
        if(hiddenInput) hiddenInput.remove(); // Hapus dari memori form

        updateMaxUI(); 
    }

    function hitungTotalAkhir() {
        let diskonPersen = parseFloat(document.getElementById('inputDiskon').value) || 0;
        
        if(diskonPersen > 100) {
            diskonPersen = 100;
            document.getElementById('inputDiskon').value = 100;
        } else if(diskonPersen < 0) {
            diskonPersen = 0;
            document.getElementById('inputDiskon').value = 0;
        }

        let nominalDiskon = subtotalKeranjang * (diskonPersen / 100);
        let grandTotal = subtotalKeranjang - nominalDiskon;

        document.getElementById('subtotalDisplay').innerText = subtotalKeranjang.toLocaleString('id-ID');
        document.getElementById('nominalDiskonDisplay').innerText = '- Rp ' + nominalDiskon.toLocaleString('id-ID');
        document.getElementById('grandTotalDisplay').innerText = grandTotal.toLocaleString('id-ID');
    }

    function validasiSubmit() {
        if(subtotalKeranjang === 0) {
            alert("Keranjang masih kosong! Silakan tambahkan produk terlebih dahulu.");
            return false;
        }
        return confirm("Apakah Anda yakin ingin memproses transaksi ini?");
    }
    </script>