<?php
session_start();
$page_title = "CHARA - Tambah Purchase Request";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_gudang.php';

if (!isset($_SESSION['is_auth']) || $_SESSION['is_auth'] !== true) {
    header("Location: ../../login.php");
    exit;
}

$error = "";

try {
    // Ambil bahan baku beserta nama satuannya untuk info bantuan konversi di UI
    $bahanBaku = $koneksi->query("
        SELECT
            b.kode,
            b.nama,
            s.nama AS satuan
        FROM tbahan b
        JOIN tsatuan s
            ON b.tSatuan_id = s.id
        ORDER BY b.nama
    ");

    // Simpan purchase request
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        // PERBAIKAN BACKEND: Cek apakah ada barang di daftar tabel detail
        if (!isset($_POST['detail']) || count($_POST['detail']) == 0) {
            $error = "Gagal disimpan: Anda harus menambahkan minimal 1 barang ke dalam daftar!";
        } else {
            $idPR = "PR" . date('YmdHis');

            // Simpan header PR
            $stmt = $koneksi->prepare("
                INSERT INTO tpurchaserequest
                (
                    id,
                    tanggal,
                    status,
                    reqBy
                )
                VALUES
                (
                    ?,
                    NOW(),
                    'Pending',
                    ?
                )
            ");

            $stmt->execute([
                $idPR,
                $_SESSION['id_user']
            ]);

            // Simpan detail barang
            foreach($_POST['detail'] as $kodeBahan => $item){
                $stmtDetail = $koneksi->prepare("
                    INSERT INTO tdetailpurchaserequest
                    (
                        tBahan_kode,
                        tPurchaseRequest_id,
                        jumlah,
                        satuanBeli,
                        konversi
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?
                    )
                ");

                $stmtDetail->execute([
                  $kodeBahan,
                  $idPR,
                  $item['jumlah'],
                  $item['satuanBeli'],
                  $item['konversi']
              ]);
            }
            
            catatLog($koneksi, "Buat Purchase Request", "Membuat PR baru", "Gudang", $idPR);

            header("Location: purchaserequest.php?success=add");
            exit;
        }
    }

} catch(PDOException $e) {
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
                                  <h5 class="card-title mb-0">Buat Purchase Request</h5>
                                  <a href="purchaserequest.php" class="btn btn-secondary btn-sm">Kembali</a>
                              </div>
                              
                              <?php if($error != "") : ?>
                                  <div class="alert alert-danger p-2"><?= $error ?></div>
                              <?php endif; ?>
                              
                              <form method="POST">
                                <div class="form-group mb-3">
                                    <label class="font-weight-bold">ID Purchase Request</label>
                                    <input type="text" class="form-control form-control-sm" value="Otomatis Dibuat Sistem" readonly>
                                </div>

                                <hr class="mt-2 mb-3">
                                <h6 class="mb-3 text-primary">Form Detail Kebutuhan Barang</h6>

                                <div class="mb-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-2">
                                                <label>Bahan Baku</label>
                                                <select id="bahanSelect" class="form-control form-control-sm">
                                                    <option value="">-- Pilih Bahan --</option>
                                                    <?php while($bahan = $bahanBaku->fetch(PDO::FETCH_ASSOC)): ?>
                                                        <option value="<?= $bahan['kode']; ?>" data-nama="<?= $bahan['nama']; ?>" data-satuan="<?= $bahan['satuan']; ?>" <?= (isset($_GET['bahan']) && $_GET['bahan'] == $bahan['kode']) ? 'selected' : '' ?>>
                                                            <?= $bahan['nama']; ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-2">
                                                <label>Jumlah Permintaan</label>
                                                <input type="number" id="jumlahBahan" class="form-control form-control-sm" min="1" value="1" step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-2">
                                                <label>Satuan Beli</label>
                                                <input type="text" id="satuanBeli" class="form-control form-control-sm" placeholder="Dus / Karung / Sak">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-2">
                                                <label>Isi Konversi Satuan</label>
                                                <input type="number" id="konversi" class="form-control form-control-sm" min="1" value="1">
                                                <small id="bantuanKonversi" class="text-info" style="font-size: 0.75rem;">Dalam satuan dasar stok</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-1">
                                        <div class="col-md-12 text-right">
                                            <button type="button" class="btn btn-primary" onclick="tambahBarang()">
                                                <i class="typcn typcn-plus"></i> Tambah ke Daftar
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive mb-3">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Bahan</th>
                                                <th>Jumlah Permintaan</th>
                                                <th>Satuan Beli</th>
                                                <th>Isi Konversi Gudang</th>
                                                <th class="text-center" width="80">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabelPRBody">
                                            </tbody>
                                    </table>
                                </div>
                                
                                <div id="hiddenDetail"></div>
                                <hr class="mb-3">
                                
                                <div class="text-right">
                                    <a href="purchaserequest.php" class="btn btn-light mr-2">Batal</a>
                                    <button type="submit" class="btn btn-primary">Simpan Request</button>
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
    // Asisten label satuan dinamis saat bahan baku dipilih
    document.getElementById('bahanSelect').addEventListener('change', function() {
        let textBantuan = document.getElementById('bantuanKonversi');
        if (this.value !== "") {
            let namaSatuanBase = this.options[this.selectedIndex].dataset.satuan;
            textBantuan.innerHTML = "Akan dikalikan ke satuan dasar <b>" + namaSatuanBase + "</b>";
        } else {
            textBantuan.innerHTML = "Dalam satuan dasar stok";
        }
    });

    function tambahBarang(){
        let select = document.getElementById('bahanSelect');
        if(select.value == ''){
            alert('Pilih bahan terlebih dahulu');
            return;
        }

        let kode = select.value;
        let nama = select.options[select.selectedIndex].dataset.nama;
        let satuanBase = select.options[select.selectedIndex].dataset.satuan;
        let jumlah = document.getElementById('jumlahBahan').value;
        let satuan = document.getElementById('satuanBeli').value;
        let konversi = document.getElementById('konversi').value;

        if(jumlah == '' || jumlah <= 0){
            alert('Jumlah harus diisi dengan benar');
            return;
        }
        if(satuan == ''){
            alert('Satuan beli harus diisi');
            return;
        }
        if(konversi == '' || konversi <= 0){
            alert('Konversi harus diisi dengan benar');
            return;
        }

        if(document.querySelector(`input[name="detail[${kode}][jumlah]"]`)){
            alert('Bahan baku ini sudah ditambahkan ke dalam daftar request!');
            return;
        }

        let tbody = document.getElementById('tabelPRBody');
        let row = tbody.insertRow();
        row.setAttribute('id', 'row_' + kode);    

        row.innerHTML = `
            <td class="align-middle">${nama}</td>
            <td class="align-middle">${jumlah} ${satuan}</td>
            <td class="align-middle">${satuan}</td>
            <td class="align-middle text-muted" style="font-size: 0.85rem;">1 ${satuan} = ${konversi} ${satuanBase}</td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-danger btn-sm py-1 px-2" onclick="hapusBaris(this, '${kode}')">
                    &times;
                </button>
            </td>
        `;

        document.getElementById('hiddenDetail').insertAdjacentHTML('beforeend',
            `<div id="hidden_group_${kode}">
                <input type="hidden" name="detail[${kode}][jumlah]" value="${jumlah}">
                <input type="hidden" name="detail[${kode}][satuanBeli]" value="${satuan}">
                <input type="hidden" name="detail[${kode}][konversi]" value="${konversi}">
            </div>`
        );

        // Reset forms input
        document.getElementById('jumlahBahan').value = '1';
        document.getElementById('satuanBeli').value = '';
        document.getElementById('konversi').value = '1';
        select.selectedIndex = 0;
        document.getElementById('bantuanKonversi').innerHTML = "Dalam satuan dasar stok";
    }

    function hapusBaris(btn, kode){
        btn.closest('tr').remove();
        let hiddenGroup = document.getElementById("hidden_group_" + kode);
        if(hiddenGroup) hiddenGroup.remove();
    }

    // Interseptor Tombol Enter
    window.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault(); 
            let activeId = event.target.id;
            if (activeId === 'bahanSelect' || activeId === 'jumlahBahan' || activeId === 'satuanBeli' || activeId === 'konversi') {
                tambahBarang();
            }
        }
    });
    </script>
  </body>
</html>