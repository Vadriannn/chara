<?php
session_start();
$page_title = "CHARA - Tambah Transaksi Penjualan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_kasir.php';
require_once '../../includes/konversi_helper.php';
require_once 'midtrans_config.php';

$error = "";

try {
    // 1. Ambil produk aktif untuk dropdown
    $produk = $koneksi->query("
        SELECT *
        FROM tproduct
        WHERE status = 'Aktif'
        ORDER BY nama
    ");

    // 2. Ambil data master modifier (kategori Sugar & Ice)
    $stmtMod = $koneksi->query("SELECT * FROM tmodifier ORDER BY kategori DESC, nama ASC");
    $allModifiers = $stmtMod->fetchAll(PDO::FETCH_ASSOC);
    
    $sugarMods = array_filter($allModifiers, function($m) { return $m['kategori'] == 'Sugar'; });
    $iceMods = array_filter($allModifiers, function($m) { return $m['kategori'] == 'Ice'; });

    // 2.1 Ambil data member
    $stmtMember = $koneksi->query("SELECT * FROM tmember ORDER BY Nama");
    $members = $stmtMember->fetchAll(PDO::FETCH_ASSOC);

    // 3. AMBIL DATA STOK BAHAN UTK LIVE VALIDASI JAVASCRIPT
    $stmtBahan = $koneksi->query("SELECT kode, stok FROM tbahan");
    $stokBahan = [];
    while($b = $stmtBahan->fetch(PDO::FETCH_ASSOC)) {
        $stokBahan[$b['kode']] = (float)$b['stok'];
    }

    // 4. AMBIL DATA RESEP UTK LIVE VALIDASI JAVASCRIPT
    $konversiGraph = getKonversiGraph($koneksi);
    $stmtResepAll = $koneksi->query("
        SELECT r.tProduct_kode, r.tBahan_kode, r.jumlah, b.nama AS nama_bahan,
               b.tSatuan_id AS stok_satuan, r.tSatuan_id AS resep_satuan
        FROM tresep r
        JOIN tbahan b ON r.tBahan_kode = b.kode
    ");
    $resepData = [];
    while($r = $stmtResepAll->fetch(PDO::FETCH_ASSOC)) {
        $nilai_konversi = cariKonversiPHP($konversiGraph, $r['stok_satuan'], $r['resep_satuan']);
        $resepData[$r['tProduct_kode']][] = [
            'bahan'  => $r['tBahan_kode'],
            'nama_bahan' => $r['nama_bahan'],
            'jumlah' => (float)$r['jumlah'] / $nilai_konversi
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
        $stmtSet = $koneksi->query("SELECT setting_value FROM tsetting WHERE setting_key = 'poin_diskon_nominal'");
        $poin_diskon_nominal = 0;
        if ($rowSet = $stmtSet->fetch(PDO::FETCH_ASSOC)) {
            $poin_diskon_nominal = (float)$rowSet['setting_value'];
        }
        $diskonNominal = $redeemPoin * $poin_diskon_nominal;
        $tMember_id = empty($_POST['member_id']) ? null : $_POST['member_id'];
        $redeemPoin = empty($_POST['redeem_poin']) ? 0 : (int)$_POST['redeem_poin'];

        if(empty($produkArray)) {
            throw new Exception("Keranjang belanja kosong!");
        }

        $koneksi->beginTransaction();

        // Generate nomor penjualan manual (jika tPenjualan nomor belum Auto Increment)
        $stmtNomor = $koneksi->query("SELECT nomor FROM tpenjualan ORDER BY nomor DESC LIMIT 1");
        $last = $stmtNomor->fetch(PDO::FETCH_ASSOC);
        $nomorPenjualan = $last ? $last['nomor'] + 1 : 1;

        // Hitung Subtotal Keranjang
        $subtotalKeranjang = 0;
        foreach($produkArray as $index => $kodeProduk) {
            $qty = (int)$qtyArray[$index];
            $stmtCekHarga = $koneksi->prepare("SELECT hargaJual FROM tproduct WHERE kode = ?");
            $stmtCekHarga->execute([$kodeProduk]);
            $hargaJual = $stmtCekHarga->fetchColumn();
            $subtotalKeranjang += ($hargaJual * $qty);
        }

        if ($diskonNominal > $subtotalKeranjang) {
            $diskonNominal = $subtotalKeranjang;
        }
        $grandTotal = $subtotalKeranjang - $diskonNominal;

        // Simpan Master Penjualan
        $stmtPenjualan = $koneksi->prepare("
            INSERT INTO tpenjualan (nomor, tanggal, total, diskon, metbayar, tUser_id, tMember_noHp)
            VALUES (?, NOW(), ?, ?, ?, ?, ?)
        ");
        $stmtPenjualan->execute([
            $nomorPenjualan, $grandTotal, $diskonNominal, $metbayar, $_SESSION['id_user'], $tMember_id
        ]);
        
        // Logika Poin Member
        if ($tMember_id) {
            // Ambil poin kelipatan dari setting
            $poinKelipatan = 50000;
            try {
                $stmtSet = $koneksi->query("SELECT setting_value FROM tsetting WHERE setting_key = 'poin_kelipatan'");
                if ($rowSet = $stmtSet->fetch(PDO::FETCH_ASSOC)) {
                    $poinKelipatan = (int)$rowSet['setting_value'];
                }
            } catch(PDOException $e) {}
            
            if ($poinKelipatan <= 0) $poinKelipatan = 1; // Cegah division by zero
            $poinDidapat = floor($grandTotal / $poinKelipatan);
            
            $stmtCekPoin = $koneksi->prepare("SELECT Poin FROM tmember WHERE noHp = ?");
            $stmtCekPoin->execute([$tMember_id]);
            $currentPoin = $stmtCekPoin->fetchColumn();
            
            if ($redeemPoin > $currentPoin) {
                throw new Exception("Poin yang diredeem melebih poin aktif member!");
            }
            
            $poinAkhir = $currentPoin + $poinDidapat - $redeemPoin;
            
            $stmtUpdateMember = $koneksi->prepare("UPDATE tmember SET Poin = ? WHERE noHp = ?");
            $stmtUpdateMember->execute([$poinAkhir, $tMember_id]);
            catatLog($koneksi, "Update Poin Member", "Poin member " . $tMember_id . " diupdate dari " . $currentPoin . " menjadi " . $poinAkhir, "Kasir", $nomorPenjualan);
        }

        // Catat ke tArusKas (Pemasukan)
        $stmtArusKas = $koneksi->prepare("
            INSERT INTO taruskas (tanggal, jenis, kategori, nominal, sumber, tPenjualan_nomor)
            VALUES (NOW(), 'Masuk', 'Penjualan', ?, ?, ?)
        ");
        $stmtArusKas->execute([
            $grandTotal, 
            'Pendapatan dari Nota #' . $nomorPenjualan,
            $nomorPenjualan
        ]);

        // Siapkan Statement untuk Detail dan Modifier
        $stmtDetail = $koneksi->prepare("
            INSERT INTO tdetailpenjualan (tProduct_kode, tPenjualan_nomor, hpp, harga_jual, jumlah, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmtInsertModifier = $koneksi->prepare("
            INSERT INTO tdetailpenjualanmodifier (tDetailPenjualan_id, tModifier_id)
            VALUES (?, ?)
        ");

        $stmtHpp = $koneksi->prepare("
            SELECT r.jumlah, b.harga, b.tSatuan_id AS stok_satuan, r.tSatuan_id AS resep_satuan
            FROM tresep r
            JOIN tbahan b ON r.tBahan_kode = b.kode
            WHERE r.tProduct_kode = ?
        ");

        $stmtResep = $koneksi->prepare("
            SELECT r.tBahan_kode, r.jumlah, b.stok,
                   b.tSatuan_id AS stok_satuan, r.tSatuan_id AS resep_satuan
            FROM tresep r
            JOIN tbahan b ON r.tBahan_kode = b.kode
            WHERE r.tProduct_kode = ?
        ");

        $updateStok = $koneksi->prepare("UPDATE tbahan SET stok = ? WHERE kode = ?");
        $stmtMutasi = $koneksi->prepare("
            INSERT INTO tmutasistok (tanggal, jenis, qty, stokSebelum, stokSesudah, referensi, tBahan_kode, tUser_id)
            VALUES (NOW(), 'Penjualan', ?, ?, ?, ?, ?, ?)
        ");

        // Aggregate kebutuhan bahan
        $totalBahanKeluar = [];
        $stmtGetStok = $koneksi->prepare("SELECT stok FROM tbahan WHERE kode = ?");

        // Proses tiap item di keranjang
        foreach($produkArray as $index => $kodeProduk) {
            $qty = (int)$qtyArray[$index];

            $stmtProdukInfo = $koneksi->prepare("SELECT hargaJual FROM tproduct WHERE kode = ?");
            $stmtProdukInfo->execute([$kodeProduk]);
            $hargaJual = $stmtProdukInfo->fetchColumn();
            $subtotal = $hargaJual * $qty;

            $stmtHpp->execute([$kodeProduk]);
            $hpp = 0;
            while ($hppRow = $stmtHpp->fetch(PDO::FETCH_ASSOC)) {
                $nilai_konversi = cariKonversiPHP($konversiGraph, $hppRow['stok_satuan'], $hppRow['resep_satuan']);
                $hpp += ($hppRow['jumlah'] / $nilai_konversi) * $hppRow['harga'];
            }

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

            // 4. Hitung Total Kebutuhan Bahan per Produk
            $stmtResep->execute([$kodeProduk]);
            while($resep = $stmtResep->fetch(PDO::FETCH_ASSOC)) {
                $bahanKode = $resep['tBahan_kode'];
                $nilai_konversi = cariKonversiPHP($konversiGraph, $resep['stok_satuan'], $resep['resep_satuan']);
                $qtyKeluar = ($resep['jumlah'] / $nilai_konversi) * $qty;
                
                if(!isset($totalBahanKeluar[$bahanKode])) {
                    $totalBahanKeluar[$bahanKode] = 0;
                }
                $totalBahanKeluar[$bahanKode] += $qtyKeluar;
            }
        }

        // 5. Potong Stok Bahan & Catat Mutasi Sekaligus
        foreach($totalBahanKeluar as $bahanKode => $qtyKeluar) {
            $stmtGetStok->execute([$bahanKode]);
            $stokSebelum = $stmtGetStok->fetchColumn();
            
            $stokSesudah = $stokSebelum - $qtyKeluar;

            if($stokSesudah < 0){
                throw new Exception("Sistem ditahan: Stok bahan ".$bahanKode." tidak mencukupi untuk memproses keseluruhan pesanan.");
            }

            $updateStok->execute([$stokSesudah, $bahanKode]);
            
            $stmtMutasi->execute([
                $qtyKeluar, $stokSebelum, $stokSesudah, 'PJ-'.$nomorPenjualan, $bahanKode, $_SESSION['id_user']
            ]);
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

                    <form method="POST" id="penjualanForm">
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
                                  <label class="mod-label mb-2 d-block">Kustom: Gula</label>
                                  <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons" id="sugarGroup">
                                      <label class="btn btn-outline-primary active flex-fill">
                                          <input type="radio" name="sugarMod" value="" checked> Normal
                                      </label>
                                      <?php foreach($sugarMods as $mod): ?>
                                      <label class="btn btn-outline-primary flex-fill">
                                          <input type="radio" name="sugarMod" value="<?= $mod['id'] ?>" data-nama="<?= htmlspecialchars($mod['nama']) ?> Sugar"> <?= htmlspecialchars($mod['nama']) ?>
                                      </label>
                                      <?php endforeach; ?>
                                  </div>
                              </div>
                              <div class="col-md-4 mt-3 mt-md-0">
                                  <label class="mod-label mb-2 d-block">Kustom: Es</label>
                                  <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons" id="iceGroup">
                                      <label class="btn btn-outline-primary active flex-fill">
                                          <input type="radio" name="iceMod" value="" checked> Normal
                                      </label>
                                      <?php foreach($iceMods as $mod): ?>
                                      <label class="btn btn-outline-primary flex-fill">
                                          <input type="radio" name="iceMod" value="<?= $mod['id'] ?>" data-nama="<?= htmlspecialchars($mod['nama']) ?> Ice"> <?= htmlspecialchars($mod['nama']) ?>
                                      </label>
                                      <?php endforeach; ?>
                                  </div>
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
                              <style>
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #ebedf2 !important;
            padding: 6px 12px;
            border-radius: 4px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px !important;
            padding-left: 0 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
    </style>
</head>
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
                                          <th class="align-middle py-2 border-bottom">Poin Tersedia</th>
                                          <td class="text-right py-2 border-bottom">
                                              <input type="text" id="poinTersedia" class="form-control form-control-sm text-right float-right w-50" value="0" readonly>
                                          </td>
                                      </tr>
                                      <tr>
                                          <th class="align-middle py-2 border-bottom">Redeem Poin</th>
                                          <td class="text-right py-2 border-bottom">
                                              <input type="number" name="redeem_poin" id="redeemPoin" class="form-control form-control-sm text-right float-right w-50" min="0" value="0" placeholder="0" oninput="hitungTotalAkhir()">
                                          </td>
                                      </tr>
                                      <tr>
                                          <th class="align-middle py-2 border-bottom">Diskon Poin</th>
                                          <td class="text-right py-2 border-bottom">
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
                      <input type="hidden" name="midtrans_token" id="midtransTokenInput">
                      <input type="hidden" name="midtrans_status" id="midtransStatusInput">

                      <div class="row">
                          <div class="col-md-3">
                              <div class="form-group mb-3">
                                  <label class="font-weight-bold d-flex justify-content-between align-items-center mb-1">
                                      <span>Member (Opsional)</span>
                                      <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" data-toggle="modal" data-target="#modalAddMember">+ Baru</button>
                                  </label>
                                  <select name="member_id" id="memberSelect" class="w-100" onchange="cekPoinMember()">
                                      <option value=""></option>
                                      <?php foreach($members as $m): ?>
                                          <option value="<?= $m['noHp'] ?>" data-poin="<?= $m['Poin'] ?>"><?= $m['Nama'] ?> (<?= htmlspecialchars($m['noHp']) ?>)</option>
                                      <?php endforeach; ?>
                                  </select>
                              </div>
                          </div>
                          <div class="col-md-3">
                              <div class="form-group mb-0">
                                  <label class="font-weight-bold">Metode Pembayaran</label>
                                  <select name="metbayar" id="metbayarSelect" class="form-control" required>
                                      <option value="Tunai">Tunai</option>
                                      <option value="Midtrans">Midtrans (Online)</option>
                                  </select>
                              </div>
                          </div>
                          <div class="col-md-6 text-right mt-4 mt-md-0">
                              <a href="transaksipenjualan.php" class="btn btn-light mr-2">Reset</a>
                              <button type="submit" class="btn btn-primary" onclick="return validasiSubmit()">Proses Pembayaran</button>
                          </div>
                      </div>

                    </form>
                  </div>
                </div>
              </div>
          </div>

<!-- Modal Add Member -->
<div class="modal fade" id="modalAddMember" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="formAddMember">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Member Baru</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="alertMemberError" class="alert alert-danger d-none"></div>
        <div class="form-group">
            <label>No. HP (ID Member)</label>
            <input type="text" name="noHp" id="addMemberNoHp" class="form-control" placeholder="Contoh: 08123456789" required>
        </div>
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" id="addMemberNama" class="form-control" placeholder="Masukkan nama pelanggan" required>
        </div>
        <div class="form-group">
            <label>Gender</label>
            <select name="gender" id="addMemberGender" class="form-control" required>
                <option value="">-- Pilih Gender --</option>
                <option value="M">Pria (Male)</option>
                <option value="F">Wanita (Female)</option>
            </select>
        </div>
        <div class="form-group">
            <label>Tanggal Lahir</label>
            <input type="date" name="birthdate" id="addMemberBirthdate" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        <button type="submit" class="btn btn-primary" id="btnSaveMember">Simpan Member</button>
      </div>
      </form>
    </div>
  </div>
</div>
<?php 
require_once '../includes/footer.php'; 
?>
            <script type="text/javascript" src="<?= getMidtransJsUrl() ?>" data-client-key="<?= MIDTRANS_CLIENT_KEY ?>"></script>
            <script>
                // Fetch dynamic discount point value
                const poinDiskonNominal = <?php 
                    $pdn = 0;
                    try {
                        $s = $koneksi->query("SELECT setting_value FROM tsetting WHERE setting_key = 'poin_diskon_nominal'");
                        if($r = $s->fetch(PDO::FETCH_ASSOC)) $pdn = (float)$r['setting_value'];
                    } catch(Exception $e){}
                    echo $pdn;
                ?>;
    // Initialize select2
    $(document).ready(function() {
        $('#produkSelect').select2({
            placeholder: "-- Pilih Produk --",
            allowClear: true
        });
        
        $('#memberSelect').select2({
            placeholder: "-- Bukan Member --",
            allowClear: true
        });
    });
    </script>
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
            Swal.fire({icon: 'warning', title: 'Oops...', text: 'Pilih produk terlebih dahulu!'});
            return;
        }

        let kode = select.value;
        let qty = parseInt(document.getElementById('qtyProduk').value) || 0;
        let max = hitungMaxPorsi(kode);

        if(qty <= 0){
            Swal.fire({icon: 'error', title: 'Gagal', text: 'Stok tidak mencukupi atau Qty tidak valid!'});
            return;
        }

        if(qty > max){
            Swal.fire({icon: 'error', title: 'Stok Terbatas', text: 'Jumlah pesanan melebihi batas ketersediaan bahan baku di gudang!'});
            return;
        }

        // TANGKAP NILAI MODIFIER
        let sugarEl = document.querySelector('input[name="sugarMod"]:checked');
        let iceEl = document.querySelector('input[name="iceMod"]:checked');
        
        let arrNamaMod = [];
        let htmlHiddenMod = '';

        if(sugarEl && sugarEl.value !== '') {
            arrNamaMod.push(sugarEl.dataset.nama);
            htmlHiddenMod += `<input type="hidden" name="modifier[${itemIndexCounter}][]" value="${sugarEl.value}">`;
        }
        if(iceEl && iceEl.value !== '') {
            arrNamaMod.push(iceEl.dataset.nama);
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
        $('#produkSelect').val('').trigger('change');
        $('input[name="sugarMod"][value=""]').parent().button('toggle');
        $('input[name="iceMod"][value=""]').parent().button('toggle');
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
        let redeemPoin = parseInt(document.getElementById('redeemPoin').value) || 0;
        let nominalDiskon = redeemPoin * poinDiskonNominal;
        
        if (nominalDiskon > subtotalKeranjang) {
            nominalDiskon = subtotalKeranjang;
        }
        
        let grandTotal = subtotalKeranjang - nominalDiskon;

        document.getElementById('subtotalDisplay').innerText = subtotalKeranjang.toLocaleString('id-ID');
        document.getElementById('nominalDiskonDisplay').innerText = '- Rp ' + nominalDiskon.toLocaleString('id-ID');
        document.getElementById('grandTotalDisplay').innerText = grandTotal.toLocaleString('id-ID');
    }

    function formatRibuan(angka, prefix) {
        let number_string = angka.toString().replace(/[^,\d]/g, ''),
        split   = number_string.split(','),
        sisa    = split[0].length % 3,
        rupiah  = split[0].substr(0, sisa),
        ribuan  = split[0].substr(sisa).match(/\d{3}/gi);
        
        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        
        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return prefix == undefined ? rupiah : (rupiah ? 'Rp. ' + rupiah : '');
    }

    // Format Rupiah untuk input Bayar
    document.querySelectorAll('#inputBayar').forEach(function(input) {
        input.type = 'text'; // change type number to text for formatting
        input.addEventListener('keyup', function(e) {
            this.value = formatRibuan(this.value);
        });
    });

    function validasiSubmit() {
        if(subtotalKeranjang === 0) {
            Swal.fire({icon: 'warning', title: 'Oops...', text: 'Keranjang masih kosong! Silakan tambahkan produk terlebih dahulu.'});
            return false;
        }
        
        // Remove formatting before submit
        let inputDiskon = document.getElementById('inputDiskon');
        if (inputDiskon) inputDiskon.value = inputDiskon.value.replace(/\./g, '');
        
        let inputBayar = document.getElementById('inputBayar');
        if (inputBayar) inputBayar.value = inputBayar.value.replace(/\./g, '');

        let redeemPoin = document.getElementById('redeemPoin').value;
        let selectMember = document.getElementById('memberSelect');
        let poinTersedia = document.getElementById('poinTersedia').value;
        if (selectMember.value !== '' && redeemPoin !== '' && parseInt(redeemPoin) > 0) {
            if (parseInt(redeemPoin) > parseInt(poinTersedia)) {
                Swal.fire({icon: 'error', title: 'Poin Tidak Cukup', text: 'Jumlah poin yang ditukarkan melebihi batas poin tersedia!'});
                return false;
            }
        }
        
        // Cek jika menggunakan metode bayar Midtrans
        let metbayar = document.getElementById('metbayarSelect').value;
        if (metbayar === 'Midtrans') {
            // Jika token transaksi sudah didapat, lanjutkan submit form
            if (document.getElementById('midtransTokenInput').value !== '') {
                return true;
            }
            
            // Proses dapatkan token pembayaran Snap
            prosesPembayaranMidtrans();
            return false; // Tahan submit form terlebih dahulu
        }
        
        Swal.fire({
            title: 'Konfirmasi Pembayaran',
            text: "Apakah Anda yakin ingin memproses transaksi ini?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Proses!'
        }).then((result) => {
            if (result.isConfirmed) {
                // If confirmed, temporarily override validasiSubmit so it submits directly
                document.getElementById('penjualanForm').onsubmit = null; 
                document.getElementById('penjualanForm').submit();
            }
        });
        return false;
    }
    
    function prosesPembayaranMidtrans() {
        let btnSubmit = document.querySelector('button[type="submit"]');
        let originalText = btnSubmit.innerHTML;
        
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menghubungi Midtrans...';

        // Susun data keranjang untuk dikirim via AJAX
        let payload = {
            produk_kode: [],
            qty_beli: [],
            redeem_poin: document.getElementById('redeemPoin').value || 0,
            member_id: document.getElementById('memberSelect').value || ''
        };
        
        // Cari seluruh hidden inputs di hiddenCartData
        let hiddenCartDivs = document.querySelectorAll('#hiddenCartData > div');
        hiddenCartDivs.forEach(div => {
            let kodeInput = div.querySelector('input[name^="produk_kode"]');
            let qtyInput = div.querySelector('input[name^="qty_beli"]');
            if (kodeInput && qtyInput) {
                payload.produk_kode.push(kodeInput.value);
                payload.qty_beli.push(qtyInput.value);
            }
        });

        fetch('get_snap_token.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw new Error(err.error || 'Terjadi kesalahan sistem'); });
            }
            return response.json();
        })
        .then(data => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
            
            if (data.token) {
                // Tampilkan Snap popup
                snap.pay(data.token, {
                    onSuccess: function(result) {
                        Swal.fire({icon: 'success', title: 'Berhasil', text: 'Pembayaran berhasil!'}).then(() => {
                            document.getElementById('midtransTokenInput').value = data.token;
                            document.getElementById('midtransStatusInput').value = 'success';
                            document.getElementById('penjualanForm').submit();
                        });
                    },
                    onPending: function(result) {
                        Swal.fire({icon: 'info', title: 'Pending', text: 'Pembayaran pending / menunggu pembayaran. Silakan selesaikan pembayaran Anda di panel Midtrans.'});
                    },
                    onError: function(result) {
                        Swal.fire({icon: 'error', title: 'Gagal', text: "Pembayaran gagal: " + (result.status_message || "Terjadi kesalahan.")});
                    },
                    onClose: function() {
                        Swal.fire({icon: 'warning', title: 'Dibatalkan', text: 'Anda menutup halaman pembayaran Midtrans.'});
                    }
                });
            } else {
                Swal.fire({icon: 'error', title: 'Gagal', text: 'Gagal mendapatkan token pembayaran.'});
            }
        })
        .catch(error => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalText;
            Swal.fire({icon: 'error', title: 'Gagal', text: "Gagal memproses pembayaran: " + error.message});
        });
    }
    
    function cekPoinMember() {
        let select = document.getElementById('memberSelect');
        let poinTersedia = document.getElementById('poinTersedia');
        let redeemPoin = document.getElementById('redeemPoin');
        
        if (select.value !== '') {
            let maxPoin = parseInt(select.options[select.selectedIndex].dataset.poin) || 0;
            poinTersedia.value = maxPoin;
            redeemPoin.max = maxPoin;
            redeemPoin.placeholder = 'Maks: ' + maxPoin;
        } else {
            poinTersedia.value = '0';
            redeemPoin.value = '0';
            redeemPoin.max = '0';
            redeemPoin.placeholder = '0';
        }
        hitungTotalAkhir();
    }

    document.getElementById('formAddMember').addEventListener('submit', function(e) {
        e.preventDefault();
        
        let btn = document.getElementById('btnSaveMember');
        btn.disabled = true;
        btn.innerHTML = 'Menyimpan...';
        
        let formData = new FormData(this);
        
        fetch('ajax_add_member.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = 'Simpan Member';
            
            let alertBox = document.getElementById('alertMemberError');
            if (data.success) {
                alertBox.classList.add('d-none');
                
                // Tambahkan ke dropdown select2
                let newOption = new Option(data.member.nama + ' (' + data.member.noHp + ')', data.member.noHp, false, false);
                newOption.setAttribute('data-poin', data.member.poin);
                
                let memberSelect = $('#memberSelect');
                memberSelect.append(newOption).val(data.member.noHp).trigger('change');
                
                // Tutup modal
                $('#modalAddMember').modal('hide');
                
                // Reset form
                document.getElementById('formAddMember').reset();
                
                Swal.fire({icon: 'success', title: 'Berhasil', text: 'Member berhasil ditambahkan!'});
            } else {
                alertBox.innerHTML = data.message;
                alertBox.classList.remove('d-none');
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = 'Simpan Member';
            Swal.fire({icon: 'error', title: 'Error', text: 'Terjadi kesalahan sistem!'});
        });
    });
    </script>