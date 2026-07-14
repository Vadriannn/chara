<?php
session_start();
$page_title = "CHARA - Edit Bahan Baku";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

if (!isset($_SESSION['is_auth']) || $_SESSION['is_auth'] !== true) {
    header("Location: ../../login.php");
    exit;
}
$error = "";
$pesan = "";
/*Cek kode bahan */
if (!isset($_GET['kode'])) {
    header("Location: bahanbaku.php");
    exit;
}
$kode = $_GET['kode'];
try {
    /* Ambil data bahan */
    $stmt = $koneksi->prepare("
        SELECT *
        FROM tbahan
        WHERE kode = ?
    ");
    $stmt->execute([$kode]);
    $bahan = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$bahan) {
        die("Data bahan tidak ditemukan.");
    }
    /* Ambil data satuan */
    $stmtSatuan = $koneksi->prepare("
        SELECT *
        FROM tsatuan
        ORDER BY nama ASC
    ");
    $stmtSatuan->execute();
    $satuan = $stmtSatuan->fetchAll(PDO::FETCH_ASSOC);
    /*Proses Update */
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $nama       = trim($_POST['nama']);
        $satuanId   = $_POST['satuan'];
        /*Cek nama bahan duplikat*/
        $cek = $koneksi->prepare("
            SELECT COUNT(*)
            FROM tbahan
            WHERE nama = ?
            AND kode != ?
        ");
        $cek->execute([
            $nama,
            $kode
        ]);
        if ($cek->fetchColumn() > 0) {
            $error = "Nama bahan sudah digunakan.";
        } else {
            $update = $koneksi->prepare("
                UPDATE tbahan
                SET
                    nama = ?,
                    tSatuan_id = ?
                WHERE kode = ?
            ");
            $update->execute([
                $nama,
                $satuanId,
                $kode
            ]);
            catatLog($koneksi, "Edit Bahan Baku", "Mengubah data bahan baku: " . $nama, "Master Data");
            header("Location: bahanbaku.php?success=edit");
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
                        <h4 class="card-title">Edit Bahan Baku</h4>
                        <?php if($pesan != "") : ?>
                        <div class="alert alert-success">
                            <?= $pesan ?>
                        </div>
                        <?php endif; ?>
                        <?php if($error != "") : ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>Kode Bahan</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?= htmlspecialchars($bahan['kode']) ?>"
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label>Nama Bahan</label>
                                <input
                                    type="text"
                                    name="nama"
                                    class="form-control"
                                    value="<?= htmlspecialchars($bahan['nama']) ?>"
                                    required>
                            </div>
                            <div class="form-group">
                                <label>Satuan</label>
                                <select
                                    name="satuan"
                                    class="form-control"
                                    required>
                                    <option value="">
                                        -- Pilih Satuan --
                                    </option>
                                    <?php foreach($satuan as $row): ?>
                                        <option
                                            value="<?= $row['id'] ?>"
                                            <?= ($row['id'] == $bahan['tSatuan_id']) ? 'selected' : '' ?>>
                                            <?= $row['nama'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Stok Saat Ini</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?= $bahan['stok'] ?>"
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label>Harga Saat Ini</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    value="Rp <?= number_format($bahan['harga'],0,',','.') ?>"
                                    readonly>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                Simpan Perubahan
                            </button>
                            <a href="bahanbaku.php" class="btn btn-secondary">
                                Kembali
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php 
require_once '../includes/footer.php'; 
?>
