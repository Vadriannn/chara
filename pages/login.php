    <?php 
    session_start();
    require_once '../koneksi.php';
        //cek username dan password --
    if ($_SERVER['REQUEST_METHOD'] === 'POST'){
        $username = $_POST['username'];
        $password = $_POST['password'];
        try {
            $sql = "SELECT u.*, r.nama AS role
                    FROM tUser u
                    JOIN tRole r ON u.tRole_id = r.id
                    WHERE u.username = '".$username."'
                    AND u.password = SHA1('".$password."')";
            $hasil = $koneksi->query($sql);
            $baris = $hasil->fetch();
            if ($baris && isset ($baris['username'])){
                // boleh login
                $_SESSION['id_user'] = $baris['id'];
                $_SESSION['nama'] = $baris['username'];
                $_SESSION['is_auth'] = true;
                $_SESSION['role'] = $baris['role'];
                header('location:../index.php');
            }
            else {
                // data tidak ditemukan, login salah
                $pesan = "Username atau password salah!";
                    
            }
            unset($hasil);
        }
        catch (PDOException $e) {
        $pesan = 'Error: '.$e->getMessage();
        }
    }

    else {
            $pesan = 'Silahkan login terlebih dahulu';
        }

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>CHARA - Login Page</title>
    <!-- base:css -->
    <link rel="stylesheet" href="../vendors/typicons.font/font/typicons.css">
    <link rel="stylesheet" href="../vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="../css/vertical-layout-light/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="../images/favicon.png" />
    </head>

    <body>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth px-0">
            <div class="row w-100 mx-0">
            <div class="col-lg-4 mx-auto">
                <div class="auth-form-light text-left py-5 px-4 px-sm-5">
                <div class="brand-logo">
                    <img src="../images/logochara.png" alt="logo">
                </div>
                <h4>Hello! let's get started</h4>
                <h6 class="font-weight-light">Sign in to continue.</h6>
                <form class="pt-3" method = "post" action = "login.php">
                    <div class="form-group">
                        <input type="text" class="form-control form-control-lg" name = "username" placeholder="Username" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control form-control-lg" name = "password" placeholder="Password" required>
                    </div>
                    <?php if(isset($pesan)): ?>
                        <div class="alert alert-danger">
                            <?= $pesan ?>
                        </div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <button class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn" type="submit">SIGN IN</button>
                    </div>
                </form>
                </div>
            </div>
            </div>
        </div>
        <!-- content-wrapper ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- base:js -->
    <script src="../vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- inject:js -->
    <script src="../js/off-canvas.js"></script>
    <script src="../js/hoverable-collapse.js"></script>
    <script src="../js/template.js"></script>
    <script src="../js/settings.js"></script>
    <script src="../js/todolist.js"></script>
    <!-- endinject -->
    </body>
</html>
