<?php
session_start();
$page_title = "CHARA - Tutup Shift Kasir";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_kasir.php';

$error = "";

// Ambil shift aktif
$stmtCekShift = $koneksi->prepare("
    SELECT * FROM tdetailshift 
    WHERE tUser_id = ? AND tanggal = CURDATE() AND jamKeluar IS NULL 
    ORDER BY id DESC LIMIT 1
");
$stmtCekShift->execute([$_SESSION['id_user']]);
$shiftAktif = $stmtCekShift->fetch(PDO::FETCH_ASSOC);

if (!$shiftAktif) {
    header("Location: transaksipenjualan.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cashSetelah = (float)str_replace('.', '', $_POST['cash_setelah']);
    
    try {
        // Hitung jumlah penjualan di jam tersebut (dari jamMasuk hingga jamKeluar yang diset CURTIME)
        $stmtCount = $koneksi->prepare("
            SELECT COUNT(*) FROM tpenjualan 
            WHERE tUser_id = ? 
            AND DATE(tanggal) = CURDATE() 
            AND TIME(tanggal) >= ? 
            AND TIME(tanggal) <= CURTIME()
        ");
        $stmtCount->execute([$_SESSION['id_user'], $shiftAktif['jamMasuk']]);
        $jumlahPenjualan = $stmtCount->fetchColumn();

        $sql = "UPDATE tdetailshift SET cashSetelah = ?, jamKeluar = CURTIME(), jumlahPenjualan = ? WHERE id = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([$cashSetelah, $jumlahPenjualan, $shiftAktif['id']]);
        
        catatLog($koneksi, "Tutup Shift", "Kasir tutup shift dengan kas akhir Rp " . number_format($cashSetelah,0,',','.'), "Kasir");
        
        // Redirect ke auth_kasir atau index (karena habis tutup shift, akan dialihkan ke bukashift lagi)
        header("Location: bukashift.php?success=tutup");
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
                                <h4 class="card-title text-center text-primary mb-4">Tutup Shift Kasir</h4>
                                <p class="text-center text-muted mb-4">Masukkan nominal kas akhir (uang fisik yang ada di laci kasir saat ini).</p>
                                
                                <?php if($error != "") : ?>
                                <div class="alert alert-danger text-left"><?= $error ?></div>
                                <?php endif; ?>
                                
                                <form method="POST">
                                    <div class="form-group text-left">
                                        <label class="font-weight-bold">Uang Modal Laci / Kas Awal</label>
                                        <input type="text" class="form-control form-control-lg text-success font-weight-bold" value="Rp <?= number_format($shiftAktif['cashSebelum'], 0, ',', '.') ?>" disabled>
                                    </div>
                                    <div class="form-group text-left mb-5">
                                        <label class="font-weight-bold">Uang Kas Akhir Aktual (Fisik) (Rp)</label>
                                        <input type="text" name="cash_setelah" class="form-control form-control-lg rupiah-input" placeholder="Masukkan total uang fisik di laci" required>
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-lg btn-block font-weight-bold" onclick="return confirm('Apakah kas akhir sudah benar dan ingin menutup shift?')">
                                        Tutup Shift Sekarang
                                    </button>
                                    <a href="transaksipenjualan.php" class="btn btn-light btn-lg btn-block mt-2">Batal</a>
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
