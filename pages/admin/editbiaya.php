<?php
session_start();
$page_title = "CHARA - Edit Biaya Operasional";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

if(!isset($_GET['id'])){
    header("Location: biayaoperasional.php");
    exit;
}

$id = $_GET['id'];

// Ambil kategori
$stmtKategori = $koneksi->prepare("SELECT * FROM tKategoriBiaya ORDER BY jenis ASC");
$stmtKategori->execute();
$kategori = $stmtKategori->fetchAll(PDO::FETCH_ASSOC);

// Ambil data biaya
$sql = "SELECT * FROM tBiayaOperasional WHERE id = :id";
$stmt = $koneksi->prepare($sql);
$stmt->bindParam(':id',$id);
$stmt->execute();

$data = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$data){
    header("Location: biayaoperasional.php");
    exit;
}

// Update data
if(isset($_POST['update'])){

    $tanggal    = $_POST['tanggal'];
    $kategoriId = $_POST['kategori'];
    $keterangan = $_POST['keterangan'];
    $nominal    = $_POST['nominal'];
    $user       = $_SESSION['id_user'];

    try{

        $sql = "UPDATE tBiayaOperasional SET
                    tanggal = :tanggal,
                    keterangan = :keterangan,
                    nominal = :nominal,
                    tKategoriBiaya_id = :kategori,
                    tUser_id = :user
                WHERE id = :id";

        $stmt = $koneksi->prepare($sql);

        $stmt->bindParam(':tanggal',$tanggal);
        $stmt->bindParam(':keterangan',$keterangan);
        $stmt->bindParam(':nominal',$nominal);
        $stmt->bindParam(':kategori',$kategoriId);
        $stmt->bindParam(':user',$user);
        $stmt->bindParam(':id',$id);

        $stmt->execute();

        // Update juga di tArusKas
        $stmtUpdateKas = $koneksi->prepare("
            UPDATE tArusKas SET
                tanggal = :tanggal,
                nominal = :nominal,
                sumber = :sumber
            WHERE tBiayaOperasional_id = :id
        ");
        $stmtUpdateKas->execute([
            ':tanggal' => $tanggal,
            ':nominal' => $nominal,
            ':sumber'  => 'Biaya Operasional: ' . $keterangan,
            ':id'      => $id
        ]);

        catatLog($koneksi, "Edit Biaya Operasional", "Mengubah biaya operasional: " . $keterangan . " menjadi Rp " . number_format($nominal, 0, ',', '.'), "Keuangan", $id);

        header("Location: biayaoperasional.php?success=edit");
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

                                <h4 class="card-title">Edit Biaya Operasional</h4>
                                <p class="card-description">
                                    Ubah data biaya operasional.
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
                                            type="datetime-local"
                                            name="tanggal"
                                            class="form-control"
                                            value="<?= date('Y-m-d\TH:i', strtotime($data['tanggal'])) ?>"
                                            required>
                                    </div>

                                    <div class="form-group">
                                        <label>Kategori Biaya</label>

                                        <select name="kategori" class="form-control" required>

                                            <?php foreach($kategori as $row): ?>

                                                <option
                                                    value="<?= $row['id'] ?>"
                                                    <?= ($row['id']==$data['tKategoriBiaya_id']) ? 'selected' : '' ?>>

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
                                            required><?= $data['keterangan'] ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Nominal</label>

                                        <input
                                            type="number"
                                            name="nominal"
                                            class="form-control"
                                            value="<?= $data['nominal'] ?>"
                                            required>
                                    </div>

                                    <button
                                        type="submit"
                                        name="update"
                                        class="btn btn-primary">

                                        Update
                                    </button>

                                    <a href="biayaoperasional.php"
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
require_once '../includes/footer.php'; 
?>
