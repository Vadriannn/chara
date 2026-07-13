<?php
session_start();
$page_title = "CHARA - Tambah Pembelian";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$error = "";

try {
    // Data supplier
    $supplier = $koneksi->query("
        SELECT id, nama
        FROM tsupplier
        ORDER BY nama
    ");

    // Data bahan baku
    $bahan_baku = $koneksi->query("
        SELECT b.kode, b.nama, s.nama AS nama_satuan
        FROM tbahan b
        JOIN tsatuan s ON b.tSatuan_id = s.id
        ORDER BY b.nama
    ");

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal    = $_POST['tanggal'];
    $supplierId = $_POST['supplier'];
    $total      = $_POST['total'];
    $status     = 'Dipesan'; 
    
    $koneksi->beginTransaction();
    
    $stmtNomor = $koneksi->query("
        SELECT IFNULL(MAX(nomor),0)+1 AS next_no
        FROM tpembelian
    ");
    $nomor = $stmtNomor->fetch(PDO::FETCH_ASSOC)['next_no'];

    $kode = 'PBL-' . date('YmdHis');
    $stmt = $koneksi->prepare("
        INSERT INTO tpembelian
        (
            nomor,
            tanggal,
            total,
            tSupplier_id,
            status,
            kode
        )
        VALUES
        (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $nomor,
        $tanggal,
        $total,
        $supplierId,
        $status,
        $kode
    ]);
    
    if(isset($_POST['bahan_baku'])){
        $arrBahanKode   = $_POST['bahan_baku'];
        $arrJumlah      = $_POST['jumlah'];
        $arrSatuanBeli  = $_POST['satuan_beli'];
        $arrHargaSatuan = $_POST['harga_satuan'];
        $arrKonversi    = $_POST['isi_konversi'];    
        
        for ($i = 0; $i < count($arrBahanKode); $i++) {
            $bahanKode   = $arrBahanKode[$i];
            $jumlahBeli  = (int)$arrJumlah[$i];
            $satuanBeli  = $arrSatuanBeli[$i];
            $hargaSatuan = (float)$arrHargaSatuan[$i];
            $konversi    = (float)$arrKonversi[$i];
            $subtotal    = $jumlahBeli * $hargaSatuan;
            
            $stmtDetail = $koneksi->prepare("
                INSERT INTO tDetailPembelian
                (
                    tPembelian_nomor,
                    tBahan_kode,
                    jumlah,
                    satuanBeli,
                    konversi,
                    harga,
                    subtotal
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtDetail->execute([
                $nomor,
                $bahanKode,
                $jumlahBeli,
                $satuanBeli,
                $konversi,
                $hargaSatuan,
                $subtotal
            ]);
        }
    }

    // Catat ke tArusKas (Pengeluaran)
    $stmtArusKas = $koneksi->prepare("
        INSERT INTO tArusKas (tanggal, jenis, kategori, nominal, sumber, tPembelian_nomor)
        VALUES (?, 'Keluar', 'Pembelian', ?, ?, ?)
    ");
    $stmtArusKas->execute([
        $tanggal . ' ' . date('H:i:s'),
        $total, 
        'Pembelian Langsung',
        $nomor
    ]);
    
    catatLog($koneksi, "Tambah Pembelian Langsung", "Melakukan pembelian langsung tanpa PR", "Pembelian", $nomor);

    $koneksi->commit();
    header("Location: pembelian.php?success=add");
    exit;
}
}
catch(PDOException $e) {
    if (isset($koneksi) && $koneksi->inTransaction()) {
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
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Tambah Pembelian (Tanpa PR)</h5>
                                    <a href="pembelian.php" class="btn btn-secondary">Kembali</a>
                                </div>
                                
                                <?php if($error != "") : ?>
                                    <div class="alert alert-danger p-2"><?= $error ?></div>
                                <?php endif; ?>
                                
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Tanggal Pembelian</label>
                                                <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Supplier</label>
                                                <select name="supplier" class="form-control form-control-sm" required>
                                                    <option value="">-- Pilih Supplier --</option>
                                                    <?php foreach($supplier as $row): ?>
                                                        <option value="<?= $row['id'] ?>"><?= $row['nama'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="mt-2 mb-3">
                                    <h6 class="mb-3 text-primary">Detail Bahan Baku</h6>
                                    
                                    <div class="mb-3">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group mb-2">
                                                    <label>Bahan Baku</label>
                                                    <select id="selectBahan" class="form-control form-control-sm">
                                                        <option value="">-- Pilih Bahan --</option>
                                                        <?php foreach($bahan_baku as $bb): ?>
                                                            <option value="<?= $bb['kode'] ?>" data-nama="<?= $bb['nama'] ?>" data-satuan="<?= $bb['nama_satuan'] ?>">
                                                                <?= $bb['nama'] ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-2">
                                                    <label>Jumlah Beli</label>
                                                    <input type="number" id="inputJumlah" class="form-control form-control-sm" min="1" value="1" placeholder="Misal: 5">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-2">
                                                    <label>Satuan Beli</label>
                                                    <input type="text" id="inputSatuanBeli" class="form-control form-control-sm" placeholder="Misal: Sak, Dus">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-2">
                                                    <label>Isi Konversi per Satuan</label>
                                                    <input type="number" id="inputKonversi" class="form-control form-control-sm" min="1" value="1" placeholder="Misal: 25">
                                                    <small id="bantuanKonversi" class="text-info" style="font-size: 0.75rem;">Dalam satuan stok</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row align-items-end mt-1">
                                            <div class="col-md-4">
                                                <div class="form-group mb-0">
                                                    <label>Harga per Satuan Beli (Rp)</label>
                                                    <input type="number" id="inputHarga" class="form-control form-control-sm" min="0" placeholder="Misal: 200000">
                                                </div>
                                            </div>
                                            <div class="col-md-8 text-right mt-2 mt-md-0">
                                                <button type="button" class="btn btn-primary" onclick="tambahkanKeTabel()">
                                                    <i class="typcn typcn-plus"></i> Tambah ke Daftar
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive mb-3">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Bahan Baku</th>
                                                    <th>Jml Beli</th>
                                                    <th>Konversi</th>
                                                    <th class="text-primary font-weight-bold">Masuk Gudang</th>
                                                    <th>Harga / Satuan</th>
                                                    <th>Subtotal</th>
                                                    <th class="text-center" width="80">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tabelDaftarBeli">
                                                </tbody>
                                        </table>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-5 offset-md-7">
                                            <div class="p-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0 text-dark">Grand Total</h6>
                                                    <h5 class="mb-0 text-danger font-weight-bold">Rp <span id="tampilanTotalText">0</span></h5>
                                                </div>
                                                <input type="hidden" name="total" id="inputHiddenTotal" value="0">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="hiddenItemContainer"></div>

                                    <hr class="mb-3">
                                    
                                    <div class="text-right">
                                        <a href="pembelian.php" class="btn btn-light mr-2">Batal</a>
                                        <button type="submit" name="simpan" class="btn btn-primary">Simpan Transaksi</button>
                                    </div>
                                    
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php 
require_once '../includes/footer.php'; 
?>
    <script>
    let grandTotalPembelian = 0;

    document.getElementById('selectBahan').addEventListener('change', function() {
        let textBantuan = document.getElementById('bantuanKonversi');
        if (this.value !== "") {
            let namaSatuan = this.options[this.selectedIndex].dataset.satuan;
            textBantuan.innerHTML = "Akan dikalikan menjadi <b>" + namaSatuan + "</b>";
        } else {
            textBantuan.innerHTML = "Dalam satuan stok";
        }
    });

    function tambahkanKeTabel() {
        let selectBahan = document.getElementById('selectBahan');
        if(selectBahan.value === "") {
            alert("Silakan pilih bahan baku terlebih dahulu!");
            return;
        }

        let kode = selectBahan.value;
        let nama = selectBahan.options[selectBahan.selectedIndex].dataset.nama;
        let satuanStok = selectBahan.options[selectBahan.selectedIndex].dataset.satuan;
        
        let jumlah = parseFloat(document.getElementById('inputJumlah').value);
        let satuanBeli = document.getElementById('inputSatuanBeli').value;
        let konversi = parseFloat(document.getElementById('inputKonversi').value);
        let hargaSatuan = parseFloat(document.getElementById('inputHarga').value);

        if(!jumlah || jumlah <= 0 || !satuanBeli || !konversi || konversi <= 0 || !hargaSatuan || hargaSatuan < 0) {
            alert("Harap lengkapi semua isian dengan benar!");
            return;
        }

        if(document.querySelector(`#input_bahan_${kode}`)) {
            alert("Barang ini sudah ada di daftar keranjang. Hapus terlebih dahulu jika ingin merevisi.");
            return;
        }

        let masukGudang = jumlah * konversi;
        let subtotal = jumlah * hargaSatuan;

        let tbody = document.getElementById('tabelDaftarBeli');
        let row = tbody.insertRow();
        row.setAttribute("id", "row_" + kode);
        
        row.innerHTML = `
            <td class="align-middle">${nama}</td>
            <td class="align-middle">${jumlah} ${satuanBeli}</td>
            <td class="text-muted align-middle" style="font-size: 0.85rem;">1 ${satuanBeli} = ${konversi} ${satuanStok}</td>
            <td class="font-weight-bold text-primary align-middle">${masukGudang} ${satuanStok}</td>
            <td class="align-middle">Rp ${hargaSatuan.toLocaleString('id-ID')}</td>
            <td class="font-weight-bold align-middle">Rp ${subtotal.toLocaleString('id-ID')}</td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-danger btn-sm py-1 px-2" onclick="hapusItem(this, '${kode}', ${subtotal})">
                    &times;
                </button>
            </td>
        `;

        let hiddenContainer = document.getElementById('hiddenItemContainer');
        hiddenContainer.insertAdjacentHTML('beforeend', `
            <div id="hidden_group_${kode}">
                <input type="hidden" id="input_bahan_${kode}" name="bahan_baku[]" value="${kode}">
                <input type="hidden" name="jumlah[]" value="${jumlah}">
                <input type="hidden" name="satuan_beli[]" value="${satuanBeli}">
                <input type="hidden" name="isi_konversi[]" value="${konversi}">
                <input type="hidden" name="harga_satuan[]" value="${hargaSatuan}">
            </div>
        `);

        grandTotalPembelian += subtotal;
        updateTampilanTotal();

        selectBahan.value = "";
        document.getElementById('inputJumlah').value = "1";
        document.getElementById('inputSatuanBeli').value = "";
        document.getElementById('inputKonversi').value = "1";
        document.getElementById('inputHarga').value = "";
        document.getElementById('bantuanKonversi').innerHTML = "Dalam satuan stok";
    }

    function hapusItem(btn, kode, subtotal) {
        grandTotalPembelian -= subtotal;
        updateTampilanTotal();
        btn.closest('tr').remove();
        let hiddenGroup = document.getElementById("hidden_group_" + kode);
        if(hiddenGroup) hiddenGroup.remove();
    }

    function updateTampilanTotal() {
        document.getElementById('tampilanTotalText').innerText = grandTotalPembelian.toLocaleString('id-ID');
        document.getElementById('inputHiddenTotal').value = grandTotalPembelian;
    }
    </script>