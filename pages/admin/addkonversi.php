<?php
session_start();
$page_title = "CHARA - Tambah Konversi Satuan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$pesan = "";
$error = "";

try {
    $stmtSatuan = $koneksi->query("SELECT * FROM tsatuan ORDER BY nama");
    $satuans = $stmtSatuan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Gagal memuat data satuan: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $satuan_besar = $_POST['satuan_besar'];
    $satuan_kecil = $_POST['satuan_kecil'];
    $konversi = $_POST['konversi'];
    
    if ($satuan_besar == $satuan_kecil) {
        $error = "Satuan Besar dan Satuan Kecil tidak boleh sama.";
    } else {
        try {
            // Cek sudah ada atau belum
            $cek = $koneksi->prepare("
                SELECT COUNT(*) 
                FROM tkonversisatuan 
                WHERE SatuanBesar_id = ? AND SatuanKecil_id = ?
            ");
            $cek->execute([$satuan_besar, $satuan_kecil]);
            if ($cek->fetchColumn() > 0) {
                $error = "Data konversi untuk satuan ini sudah ada.";
            } else {
                $sql = "
                    INSERT INTO tkonversisatuan
                    (SatuanBesar_id, SatuanKecil_id, Konversi)
                    VALUES (?, ?, ?)
                ";
                $stmt = $koneksi->prepare($sql);
                $stmt->execute([
                    $satuan_besar, $satuan_kecil, $konversi
                ]);
                catatLog($koneksi, "Tambah Konversi", "Menambahkan konversi satuan", "Master Data");
                header("Location: konversisatuan.php?success=1");
                exit;
            }
        } catch(PDOException $e) {
            $error = $e->getMessage();
        }
    }
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Tambah Konversi Satuan</h4>
                                    <?php if($error != "") : ?>
                                    <div class="alert alert-danger">
                                        <?= $error ?>
                                    </div>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>Satuan Besar</label>
                                            <select name="satuan_besar" id="satuanBesarSelect" class="form-control" required>
                                                <option value="">-- Pilih Satuan Besar --</option>
                                                <?php foreach ($satuans as $s): ?>
                                                    <option value="<?= $s['id'] ?>" <?= (isset($_POST['satuan_besar']) && $_POST['satuan_besar'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nama']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Satuan Kecil</label>
                                            <select name="satuan_kecil" id="satuanKecilSelect" class="form-control" required>
                                                <option value="">-- Pilih Satuan Kecil --</option>
                                                <?php foreach ($satuans as $s): ?>
                                                    <option value="<?= $s['id'] ?>" <?= (isset($_POST['satuan_kecil']) && $_POST['satuan_kecil'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['nama']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Nilai Konversi (1 Satuan Besar = ? Satuan Kecil)</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                name="konversi"
                                                id="inputKonversi"
                                                class="form-control"
                                                placeholder="Contoh: 1000"
                                                value="<?= isset($_POST['konversi']) ? htmlspecialchars($_POST['konversi']) : '' ?>"
                                                required>
                                            <small id="previewKonversi" class="text-success font-weight-bold d-block mt-2"></small>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            Simpan
                                        </button>
                                        <a href="konversisatuan.php" class="btn btn-secondary">
                                            Kembali
                                        </a>
                                    </form>
                                </div>
                        </div>
                      </div>
                    </div>
                 </div>
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
<?php 
require_once '../includes/footer.php'; 
?>
<script>
$(document).ready(function() {
    $('#satuanBesarSelect').select2({ placeholder: '-- Pilih Satuan Besar --' });
    $('#satuanKecilSelect').select2({ placeholder: '-- Pilih Satuan Kecil --' });

    function updatePreview() {
        let sb = $('#satuanBesarSelect option:selected');
        let sk = $('#satuanKecilSelect option:selected');
        let val = $('#inputKonversi').val();
        
        if(sb.val() && sk.val() && val) {
            let num = parseFloat(val);
            if(!isNaN(num)) {
                let text = `(Berarti: 1 ${sb.text()} sama dengan ${num.toLocaleString('id-ID')} ${sk.text()})`;
                $('#previewKonversi').text(text);
                return;
            }
        }
        $('#previewKonversi').text('');
    }

    $('#satuanBesarSelect, #satuanKecilSelect').on('change', updatePreview);
    $('#inputKonversi').on('input', updatePreview);
    
    // Initial call for retention
    updatePreview();
});
</script>
