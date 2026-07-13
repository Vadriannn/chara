<?php
session_start();
$page_title = "CHARA - Setting Chara";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

$error = "";
$success = "";

// Ambil nilai saat ini
$poin_kelipatan = 50000;
$poin_diskon_nominal = 10000;
try {
    $stmt = $koneksi->query("SELECT setting_key, setting_value FROM tsetting WHERE setting_key IN ('poin_kelipatan', 'poin_diskon_nominal')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['setting_key'] == 'poin_kelipatan') $poin_kelipatan = $row['setting_value'];
        if ($row['setting_key'] == 'poin_diskon_nominal') $poin_diskon_nominal = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Tabel mungkin belum dibuat, biarkan default
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $poinBaru = str_replace('.', '', $_POST['poin_kelipatan']);
    $diskonBaru = str_replace('.', '', $_POST['poin_diskon_nominal']);
    
    try {
        $koneksi->beginTransaction();
        
        // Update atau Insert poin_kelipatan
        $sql = "UPDATE tsetting SET setting_value = ? WHERE setting_key = 'poin_kelipatan'";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([$poinBaru]);
        if ($stmt->rowCount() == 0) {
            $sqlIns = "INSERT INTO tsetting (setting_key, setting_value, keterangan) VALUES ('poin_kelipatan', ?, 'Nominal transaksi untuk mendapatkan 1 poin')";
            $stmtIns = $koneksi->prepare($sqlIns);
            $stmtIns->execute([$poinBaru]);
        }
        
        // Update atau Insert poin_diskon_nominal
        $sql2 = "UPDATE tsetting SET setting_value = ? WHERE setting_key = 'poin_diskon_nominal'";
        $stmt2 = $koneksi->prepare($sql2);
        $stmt2->execute([$diskonBaru]);
        if ($stmt2->rowCount() == 0) {
            $sqlIns2 = "INSERT INTO tsetting (setting_key, setting_value, keterangan) VALUES ('poin_diskon_nominal', ?, 'Nominal potongan (Rp) per 1 poin yang diredeem')";
            $stmtIns2 = $koneksi->prepare($sqlIns2);
            $stmtIns2->execute([$diskonBaru]);
        }
        
        $koneksi->commit();
        
        catatLog($koneksi, "Ubah Setting", "Mengubah setting poin", "Pengaturan");
        $success = "Pengaturan berhasil disimpan.";
        $poin_kelipatan = $poinBaru;
        $poin_diskon_nominal = $diskonBaru;
        
    } catch(PDOException $e) {
        if($koneksi->inTransaction()) $koneksi->rollBack();
        $error = "Terjadi kesalahan saat menyimpan pengaturan. Pastikan tabel tSetting sudah dibuat. Error: " . $e->getMessage();
    }
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-6 grid-margin stretch-card mx-auto">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Pengaturan Chara</h4>
                                <p class="card-description">Konfigurasi nilai-nilai global aplikasi</p>
                                
                                    <?php if($error != "") : ?>
                                    <div class="alert alert-danger">
                                        <?= $error ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if($success != "") : ?>
                                    <div class="alert alert-success">
                                        <?= $success ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>Kelipatan Transaksi Poin Member (Rp)</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="text" name="poin_kelipatan" id="poin_kelipatan" class="form-control rupiah" value="<?= number_format($poin_kelipatan, 0, ',', '.') ?>" required>
                                            </div>
                                            <small class="form-text text-muted">Misal: 50.000 artinya pelanggan mendapat 1 poin setiap berbelanja kelipatan Rp 50.000.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Nilai Diskon per 1 Poin (Rp)</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp</span>
                                                </div>
                                                <input type="text" name="poin_diskon_nominal" id="poin_diskon_nominal" class="form-control rupiah" value="<?= number_format($poin_diskon_nominal, 0, ',', '.') ?>" required>
                                            </div>
                                            <small class="form-text text-muted">Misal: 10.000 artinya jika pelanggan menukar 3 poin, total diskon struk adalah Rp 30.000.</small>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
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
document.querySelectorAll('.rupiah').forEach(function(el) {
    el.addEventListener('keyup', function(e) {
        let value = this.value.replace(/[^,\d]/g, '');
        let split = value.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);
        
        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        
        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        this.value = rupiah;
    });
});
</script>
