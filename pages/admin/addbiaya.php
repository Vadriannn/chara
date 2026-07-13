<?php
session_start();
$page_title = "CHARA - Tambah Biaya Operasional";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';


// Ambil kategori biaya
$stmtKategori = $koneksi->prepare("SELECT * FROM tkategoribiaya ORDER BY jenis ASC");
$stmtKategori->execute();
$kategori = $stmtKategori->fetchAll(PDO::FETCH_ASSOC);

// Simpan data
if(isset($_POST['simpan'])){

    $tanggal   = $_POST['tanggal'];
    $kategori  = $_POST['kategori'];
    $keterangan= $_POST['keterangan'];
    $nominal   = $_POST['nominal'];
    $user      = $_SESSION['id_user']; // sesuaikan dengan session login

    try{

        $sql = "INSERT INTO tbiayaoperasional
                (tanggal,keterangan,nominal,tKategoriBiaya_id,tUser_id)
                VALUES
                (:tanggal,:keterangan,:nominal,:kategori,:user)";

        $stmt = $koneksi->prepare($sql);

        $stmt->bindParam(':tanggal',$tanggal);
        $stmt->bindParam(':keterangan',$keterangan);
        $stmt->bindParam(':nominal',$nominal);
        $stmt->bindParam(':kategori',$kategori);
        $stmt->bindParam(':user',$user);

        $stmt->execute();
        
        $biayaId = $koneksi->lastInsertId();

        // Catat ke tArusKas (Pengeluaran)
        $stmtArusKas = $koneksi->prepare("
            INSERT INTO taruskas (tanggal, jenis, kategori, nominal, sumber, tBiayaOperasional_id)
            VALUES (?, 'Keluar', 'Biaya Operasional', ?, ?, ?)
        ");
        $stmtArusKas->execute([
            $tanggal . ' ' . date('H:i:s'),
            $nominal, 
            'Biaya Operasional: ' . $keterangan,
            $biayaId
        ]);
        
        catatLog($koneksi, "Tambah Biaya Operasional", "Mencatat biaya operasional: " . $keterangan . " sejumlah Rp " . number_format($nominal, 0, ',', '.'), "Keuangan", $biayaId);

        header("Location: biayaoperasional.php?success=add");
        exit;

    }catch(PDOException $e){
        $error = $e->getMessage();
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
                                <h4 class="card-title">Tambah Biaya Operasional</h4>
                                <p class="card-description">
                                    Masukkan data biaya operasional.
                                </p>
                                <?php if(isset($error)): ?>
                                    <div class="alert alert-danger">
                                        <?= $error ?>
                                    </div>
                                <?php endif; ?>
                                <form method="POST">
                                    <div class="form-group">
                                        <label>Tanggal</label>
                                        <input
                                            type="date"
                                            name="tanggal"
                                            class="form-control"
                                            required>
                                    </div>
                                    <div class="form-group">
                                        <label>Kategori Biaya</label>
                                        <select name="kategori" class="form-control" required>
                                            <option value="">-- Pilih Kategori --</option>
                                            <?php foreach($kategori as $row): ?>
                                                <option value="<?= $row['id'] ?>">
                                                    <?= $row['jenis'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Keterangan</label>
                                        <textarea
                                            name="keterangan"
                                            rows="4"
                                            class="form-control"
                                            required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Nominal</label>
                                        <input
                                            type="number"
                                            name="nominal"
                                            class="form-control"
                                            min="0"
                                            required>
                                    </div>
                                    <button
                                        type="submit"
                                        name="simpan"
                                        class="btn btn-primary mr-2">
                                        Simpan
                                    </button>
                                    <a href="biayaoperasional.php" class="btn btn-light">
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
require_once '../includes/footer.php'; 
?>