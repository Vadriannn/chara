<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';


if (!isset($_GET['kode'])) {
    header('location:produk.php');
    exit;
}

$kode = $_GET['kode'];
$pesan = '';

try {

    // UPDATE DATA
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $kode       = $_POST['kode'];
        $nama       = trim($_POST['nama']);
        $kategori   = $_POST['kategori'];
        $hargaJual  = $_POST['hargajual'];
        $status     = $_POST['status'];

        $sql = "
            UPDATE tProduct
            SET
                nama = ?,
                tKategori_id = ?,
                hargaJual = ?,
                status = ?
            WHERE kode = ?
        ";

        $stmt = $koneksi->prepare($sql);

        $stmt->execute([
            $nama,
            $kategori,
            $hargaJual,
            $status,
            $kode
        ]);

        header('location:produk.php');
        exit;
    }

    // AMBIL DATA PRODUCT
    $sql = "
        SELECT *
        FROM tProduct
        WHERE kode = ?
    ";

    $stmt = $koneksi->prepare($sql);
    $stmt->execute([$kode]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('location:produk.php');
        exit;
    }

    // AMBIL DATA KATEGORI
    $kategori = $koneksi->query("
        SELECT *
        FROM tKategori
        ORDER BY nama
    ");

}
catch(PDOException $e){
    $pesan = $e->getMessage();
}

$namaUser = $_SESSION['nama'];
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
            <div class="content-wrapper">
                <div class="row">
                    <div class="col-lg-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="card-title mb-0">
                                    Edit Product
                                </h4>
                                <a href="produk.php" class="btn btn-secondary">
                                    Kembali
                                </a>
                            </div>
                            <?php if(!empty($pesan)): ?>
                                <div class="alert alert-danger">
                                    <?= $pesan ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST">
                                <input
                                    type="hidden"
                                    name="kode"
                                    value="<?= $product['kode']; ?>">

                                <div class="form-group">
                                    <label>Nama Product</label>
                                    <input
                                        type="text"
                                        name="nama"
                                        class="form-control"
                                        value="<?= htmlspecialchars($product['nama']); ?>"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <select
                                        name="kategori"
                                        class="form-control"
                                        required>
                                        <?php while($kat = $kategori->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option
                                                value="<?= $kat['id']; ?>"
                                                <?= ($kat['id'] == $product['tKategori_id']) ? 'selected' : ''; ?>>
                                                <?= $kat['nama']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Harga Jual</label>
                                    <input
                                        type="number"
                                        name="hargajual"
                                        class="form-control"
                                        value="<?= $product['hargaJual']; ?>"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select
                                        name="status"
                                        class="form-control"
                                        required>
                                        <option value="Aktif"
                                            <?= ($product['status'] == 'Aktif') ? 'selected' : ''; ?>>
                                            Aktif
                                        </option>
                                        <option value="Nonaktif"
                                            <?= ($product['status'] == 'Nonaktif') ? 'selected' : ''; ?>>
                                            Nonaktif
                                        </option>
                                    </select>
                                </div>
                                <button
                                    type="submit"
                                    class="btn btn-warning">
                                    Update
                                </button>
                                <a
                                    href="produk.php"
                                    class="btn btn-light">
                                    Batal
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
// ==========================================
// PANGGIL TEMPLATE FOOTER DI SINI
// ==========================================
require_once '../includes/footer.php'; 
?>