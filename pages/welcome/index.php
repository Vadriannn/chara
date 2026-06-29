<?php
session_start();
$page_title = "CHARA - Welcome";
require_once '../../koneksi.php';

if (!isset($_SESSION['is_auth']) || $_SESSION['is_auth'] !== true) {
    header('location:../login.php');
    exit;
}

$nama = $_SESSION['nama'];
$role = $_SESSION['role'];

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
<div class="content-wrapper d-flex flex-column align-items-center justify-content-center bg-light" style="min-height: 80vh;">
    <div class="text-center w-100" style="max-width: 600px;">
        <div class="mb-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-light shadow-sm" style="width: 120px; height: 120px; background: rgba(75, 73, 172, 0.1);">
                <i class="typcn typcn-coffee text-primary" style="font-size: 5rem; line-height: 1; margin-top: 5px;"></i>
            </div>
        </div>
        <h1 class="display-4 font-weight-bold text-dark mb-3">Selamat Datang di CHARA!</h1>
        <h4 class="text-muted font-weight-light mb-4" style="line-height: 1.6;">
            Halo <span class="text-primary font-weight-bold"><?= htmlspecialchars($nama) ?></span>,<br>
            Anda masuk sebagai <span class="badge badge-info rounded-pill px-3 py-2 mt-2" style="font-size: 1rem;"><?= htmlspecialchars($role) ?></span>.
        </h4>
        
        <div class="mt-5">
            <?php if ($role == 'Admin'): ?>
                <a href="../admin/dashboard.php" class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-sm font-weight-bold transition-all"><i class="typcn typcn-device-desktop mr-2"></i> Buka Dashboard Admin</a>
            <?php elseif ($role == 'Kasir'): ?>
                <a href="../kasir/transaksipenjualan.php" class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-sm font-weight-bold transition-all"><i class="typcn typcn-shopping-cart mr-2"></i> Mulai Transaksi Penjualan</a>
            <?php elseif ($role == 'Gudang'): ?>
                <a href="../gudang/bahanbaku.php" class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-sm font-weight-bold transition-all"><i class="typcn typcn-archive mr-2"></i> Cek Stok Gudang</a>
            <?php else: ?>
                <p class="text-muted mt-4">Silakan pilih menu di samping (sidebar) untuk mulai bekerja.</p>
            <?php endif; ?>
        </div>
        
        <p class="text-muted mt-5 small">&copy; <?= date('Y') ?> CHARA Point of Sales System. All rights reserved.</p>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
