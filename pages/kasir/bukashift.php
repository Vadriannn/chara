<?php
session_start();
$page_title = "CHARA - Buka Shift Kasir";
require_once '../../koneksi.php';
require_once '../../auth.php';

// Cek apakah sudah buka shift
$stmtCekShift = $koneksi->prepare("
    SELECT id FROM tDetailShift 
    WHERE tUser_id = ? AND tanggal = CURDATE() AND jamKeluar IS NULL 
    ORDER BY id DESC LIMIT 1
");
$stmtCekShift->execute([$_SESSION['id_user']]);
if ($stmtCekShift->fetchColumn()) {
    header("Location: transaksipenjualan.php");
    exit;
}

$error = "";
$stmtShift = $koneksi->query("SELECT * FROM tshift ORDER BY idShift");
$shifts = $stmtShift->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shift_id = $_POST['shift_id'];
    $cashSebelum = (float)str_replace('.', '', $_POST['cash_sebelum']);
    
    try {
        $sql = "INSERT INTO tDetailShift (tUser_id, Shift_idShift, tanggal, cashSebelum, jamMasuk)
                VALUES (?, ?, CURDATE(), ?, CURTIME())";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([$_SESSION['id_user'], $shift_id, $cashSebelum]);
        
        catatLog($koneksi, "Buka Shift", "Kasir buka shift dengan modal awal Rp " . number_format($cashSebelum,0,',','.'), "Kasir");
        header("Location: transaksipenjualan.php");
        exit;
    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

            <div class="content-wrapper d-flex align-items-center justify-content-center">
                <div class="row w-100">
                    <div class="col-lg-6 mx-auto">
                        <div class="card text-center text-md-left">
                            <div class="card-body">
                                <h4 class="card-title text-center text-primary mb-4">Buka Shift Kasir</h4>
                                <p class="text-center text-muted mb-4">Anda harus membuka shift kerja terlebih dahulu sebelum dapat mengakses halaman transaksi penjualan.</p>
                                
                                <?php if($error != "") : ?>
                                <div class="alert alert-danger text-left"><?= $error ?></div>
                                <?php endif; ?>
                                
                                <form method="POST">
                                    <div class="form-group text-left">
                                        <label class="font-weight-bold">Pilih Shift Kerja</label>
                                        <select name="shift_id" class="form-control form-control-lg" required>
                                            <option value="">-- Pilih Shift --</option>
                                            <?php foreach($shifts as $s): ?>
                                                <option value="<?= $s['idShift'] ?>">Shift <?= $s['idShift'] ?> (<?= $s['jamMulai'] ?> - <?= $s['jamBerakhir'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group text-left mb-5">
                                        <label class="font-weight-bold">Uang Modal Laci / Kas Awal (Rp)</label>
                                        <input type="text" name="cash_sebelum" class="form-control form-control-lg rupiah-input" placeholder="Masukkan nominal uang kas awal" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg btn-block font-weight-bold">
                                        Buka Shift & Masuk Kasir
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php require_once '../includes/footer.php'; ?>
<script>
document.querySelectorAll('.rupiah-input').forEach(function(input) {
    input.addEventListener('keyup', function(e) {
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
