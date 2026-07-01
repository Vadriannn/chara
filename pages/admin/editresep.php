<?php 
session_start(); 
$page_title = "CHARA - Edit Resep Produk";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

/*
|--------------------------------------------------------------------------
| Ambil kode produk dari URL
|--------------------------------------------------------------------------
*/
if(!isset($_GET['kode'])){
    header("Location: resep.php");
    exit;
}
$kodeProduk = $_GET['kode'];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kodeProduk = $_POST['kodeProduk'];
    try {
        $koneksi->beginTransaction();
        // Hapus semua resep lama produk ini
        $sqlDelete = "
            DELETE FROM tresep
            WHERE tProduct_kode = ?
        ";
        $stmtDelete = $koneksi->prepare($sqlDelete);
        $stmtDelete->execute([$kodeProduk]);

        // Simpan ulang resep yang baru
        if(isset($_POST['bahan'])){
            $bahanSudahAda = [];
            foreach($_POST['bahan'] as $kodeBahan){
                if(in_array($kodeBahan, $bahanSudahAda)){
                    throw new Exception(
                        "Terdapat bahan baku yang sama dalam resep."
                    );
                }
                $bahanSudahAda[] = $kodeBahan;
            }
            foreach($_POST['bahan'] as $i => $kodeBahan){
                $jumlah = $_POST['jumlah'][$i];
                $satuanId = $_POST['satuan'][$i];
                if($jumlah <= 0){
                    continue;
                }
                $sqlInsert = "
                    INSERT INTO tresep
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
                ";
                $stmtInsert = $koneksi->prepare($sqlInsert);
                $stmtInsert->execute([
                    $kodeProduk,
                    $kodeBahan,
                    $satuanId,
                    $jumlah
                ]);
            }
        }
        $koneksi->commit();
        echo "
        <script>
            alert('Resep berhasil diperbarui');
            window.location='resep.php';
        </script>
        ";
        exit;
    } catch(Exception $e){
        $koneksi->rollBack();
        echo "
        <script>
            alert('".$e->getMessage()."');
            history.back();
        </script>
        ";
        exit;
    }
}
try {

    // Ambil data produk
    $sqlProduk = "
        SELECT kode,nama
        FROM tproduct
        WHERE kode = ?
    ";

    $stmtProduk = $koneksi->prepare($sqlProduk);
    $stmtProduk->execute([$kodeProduk]);

    $produk = $stmtProduk->fetch(PDO::FETCH_ASSOC);

    if(!$produk){
        header("Location: resep.php");
        exit;
    }

    // Ambil resep produk
    $sqlResep = "
        SELECT
            r.tBahan_kode,
            r.jumlah,
            r.tSatuan_id,
            b.nama AS nama_bahan,
            b.tSatuan_id as satuan_stok_id
        FROM tresep r
        INNER JOIN tbahan b
            ON r.tBahan_kode = b.kode
        WHERE r.tProduct_kode = ?
    ";

    $stmtResep = $koneksi->prepare($sqlResep);
    $stmtResep->execute([$kodeProduk]);

    $resep = $stmtResep->fetchAll(PDO::FETCH_ASSOC);

    // Ambil semua bahan baku
    $sqlBahan = "
        SELECT
            b.kode,
            b.nama,
            CASE
                WHEN LOWER(s.nama) = 'kg'
                    THEN 'Gram'
                WHEN LOWER(s.nama) = 'liter'
                    THEN 'Ml'
                ELSE s.nama
            END AS satuan
        FROM tbahan b
        INNER JOIN tsatuan s
            ON b.tSatuan_id = s.id
        ORDER BY b.nama
    ";

    $stmtBahan = $koneksi->prepare($sqlBahan);
    $stmtBahan->execute();

    $semuaBahan = $stmtBahan->fetchAll(PDO::FETCH_ASSOC);
    
    // Ambil data satuan
    $stmtSatuan = $koneksi->query("SELECT * FROM tsatuan ORDER BY nama");
    $satuanList = $stmtSatuan->fetchAll(PDO::FETCH_ASSOC);

    // Ambil data konversi untuk JS
    $konvAll = $koneksi->query("SELECT * FROM tkonversisatuan");
    $konversiList = $konvAll->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error : ".$e->getMessage());
}
try {
    $sql = "SELECT *
            FROM tkategori
            ORDER BY nama ASC";
    $stmt = $koneksi->prepare($sql);
    $stmt->execute();
    $kategori = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4>Edit Resep Produk</h4>
                                    <form action="" method="POST">
                                        <input type="hidden"
                                            name="kodeProduk"
                                            value="<?= $produk['kode']; ?>">
                                        <div class="form-group">
                                            <label>Produk</label>
                                            <input type="text"
                                                class="form-control"
                                                value="<?= $produk['nama']; ?>"
                                                readonly>
                                        </div>
                                        <h5>Daftar Bahan</h5>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Bahan</th>
                                                        <th>Satuan</th>
                                                        <th>Jumlah</th>
                                                        <th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach($resep as $index => $r): ?>
                                                <tr>
                                                    <td>
                                                        <select name="bahan[]" class="form-control" onchange="ubahSatuan(this)">
                                                            <?php foreach($semuaBahan as $b): ?>
                                                                <option value="<?= $b['kode']; ?>"
                                                                    <?= ($b['kode'] == $r['tBahan_kode']) ? 'selected' : ''; ?>>
                                                                    <?= $b['nama']; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>

                                                    <td>
                                                        <select name="satuan[]" class="form-control" required>
                                                            <option value="" disabled>Pilih</option>
                                                            <?php 
                                                            $satuanSelected = $r['tSatuan_id'] == 0 ? $r['satuan_stok_id'] : $r['tSatuan_id'];
                                                            $allowedSatuan = [$r['satuan_stok_id']];
                                                            foreach($konversiList as $k) {
                                                                if ($k['SatuanBesar_id'] == $r['satuan_stok_id']) {
                                                                    $allowedSatuan[] = $k['SatuanKecil_id'];
                                                                }
                                                            }
                                                            foreach($satuanList as $s): 
                                                                if (in_array($s['id'], $allowedSatuan)):
                                                            ?>
                                                                <option value="<?= $s['id'] ?>" <?= ($s['id'] == $satuanSelected) ? 'selected' : '' ?>><?= $s['nama'] ?></option>
                                                            <?php endif; endforeach; ?>
                                                        </select>
                                                    </td>

                                                    <td>
                                                        <input type="number"
                                                            step="0.01"
                                                            name="jumlah[]"
                                                            value="<?= $r['jumlah']; ?>"
                                                            class="form-control">
                                                    </td>

                                                    <td>
                                                        <button type="button"
                                                                class="btn btn-danger btn-sm"
                                                                onclick="this.closest('tr').remove()">
                                                            Hapus
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <button
                                                type="button"
                                                class="btn btn-success"
                                                onclick="tambahBaris()">

                                                Tambah Bahan

                                            </button>
                                            <br><br>
                                                <button
                                                    type="submit"
                                                    class="btn btn-primary">
                                                    Simpan Perubahan
                                                </button>
                                            </form>
                            </div>
                        </div>
                    </div>
                  </div>
                </div>


                    
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
<?php 
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>
    <script>
        const dataKonversi = <?= json_encode($konversiList) ?>;
        const dataSatuan = <?= json_encode($satuanList) ?>;

        function getNamaSatuan(idSatuan) {
            for (let s of dataSatuan) {
                if (s.id == idSatuan) return s.nama;
            }
            return '-';
        }

        function tambahBaris(){
            let tbody = document.querySelector("tbody");
            let row = tbody.insertRow();
            row.innerHTML = `
                <td>
                    <select name="bahan[]" class="form-control" onchange="ubahSatuan(this)" required>
                        <option value="" selected disabled>
                            -- Pilih Bahan Baku --
                        </option>

                        <?php foreach($semuaBahan as $b){ ?>
                            <option value="<?= $b['kode']; ?>" data-satuanid="<?= $b['tSatuan_id']; ?>">
                                <?= $b['nama']; ?>
                            </option>
                        <?php } ?>
                    </select>
                </td>
                <td>
                    <select name="satuan[]" class="form-control" required>
                        <option value="" selected disabled>Pilih</option>
                        <?php foreach($satuanList as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= $s['nama'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="jumlah[]"
                        class="form-control"
                        required>
                </td>
                <td>
                    <button
                        type="button"
                        class="btn btn-danger btn-sm"
                        onclick="this.closest('tr').remove()">
                        Hapus
                    </button>
                </td>
            `;
        }
        function ubahSatuan(select){
            let kodeDipilih = select.value;
            let semuaSelect = document.querySelectorAll('select[name="bahan[]"]');
            let jumlahSama = 0;
            semuaSelect.forEach(function(item){
                if(item.value === kodeDipilih){
                    jumlahSama++;
                }
            });
            if(jumlahSama > 1){
                alert("Bahan baku sudah dipilih!");
                select.value = "";
                return;
            }

            let idSatuanStock = select.options[select.selectedIndex].dataset.satuanid;
            let satuanDropdown = select.closest("tr").querySelector('select[name="satuan[]"]');
            
            satuanDropdown.innerHTML = '<option value="" selected disabled>Pilih</option>';
            if (!idSatuanStock) return;

            let baseUnitName = getNamaSatuan(idSatuanStock);
            satuanDropdown.insertAdjacentHTML('beforeend', `<option value="${idSatuanStock}">${baseUnitName}</option>`);

            for (let k of dataKonversi) {
                if (k.SatuanBesar_id == idSatuanStock) {
                    let unitName = getNamaSatuan(k.SatuanKecil_id);
                    satuanDropdown.insertAdjacentHTML('beforeend', `<option value="${k.SatuanKecil_id}">${unitName}</option>`);
                }
            }
        }
    </script>