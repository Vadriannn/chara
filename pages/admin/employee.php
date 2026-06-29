<?php 
session_start(); 
$page_title = "CHARA - Daftar Karyawan";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

try {
    $dataku = array();
    $sql = "select u.id, u.username, trole.nama as role
            from tuser u inner join trole on u.tRole_id = trole.id";
    $hasil = $koneksi->query($sql);
    if ($hasil->rowCount() > 0) {
        while ($baris = $hasil->fetch()) {
            $kolom = array();
            $kolom[] = $baris['id'];
            $kolom[] = $baris['username'];
            $kolom[] = $baris['role'];
            $dataku[] = $kolom;
        }
    unset($hasil);
    }
  else {
    $pesan = 'Data tidak ditemukan';
  }
}
catch (PDOException $e) {
  $pesan = 'Error: '.$e->getMessage();
}

$nama = $_SESSION['nama'];
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
          <div class="content-wrapper">
            <!-- TABEL -->
             <?php if(empty($dataku)): ?>
                <i style = "color: red"> <b> Data Belum Tersedia</b> </i>
            <?php else: ?>
            <div class="col-lg-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="card-title mb-0">Data User</h4>
                <a href= "addemployee.php" class="btn btn-primary">
                    <i class="typcn typcn-plus"></i>
                    Tambah User
                </a> 
                </div>
                  <div class="table-responsive pt-3">
                    <table class="table table-bordered">
                      <thead>
                        <tr>
                          <th>
                            Username
                          </th>
                          <th>
                            Role
                          </th>
                          <th>
                            Aksi
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($dataku as $item): ?>
                            <tr> 
                                <td> <?php echo $item[1] ?></td>
                                <td> <?php echo $item[2] ?></td>
                                <td>
                                    <a href="editemployee.php?id=<?php echo $item[0]; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="delemployee.php?id=<?php echo $item[0]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah anda yakin ingin menghapus user ini?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        <?php endif ?>
                      </tbody>
                    </table>
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