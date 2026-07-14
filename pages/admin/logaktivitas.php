<?php
session_start();
$page_title = "CHARA - Log Aktivitas";
require_once '../../koneksi.php';
require_once '../../auth.php';
require_once '../auth_admin.php';

// AI

date_default_timezone_set('Asia/Jakarta');
$hariIni = date('Y-m-d');
$tglMulai = !empty($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : $hariIni;
$tglSelesai = !empty($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : $hariIni;
$filterUser = !empty($_GET['filter_user']) ? $_GET['filter_user'] : '';
$filterModul = !empty($_GET['filter_modul']) ? $_GET['filter_modul'] : '';

// Data dropdown
$stmtUsers = $koneksi->query("SELECT u.id, u.username, r.nama as role FROM tuser u JOIN trole r ON u.tRole_id = r.id ORDER BY u.username ASC");
$users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

$stmtModuls = $koneksi->query("SELECT DISTINCT modul FROM tlog ORDER BY modul ASC");
$moduls = $stmtModuls->fetchAll(PDO::FETCH_ASSOC);

// Query utama
$query = "
    SELECT l.*, u.username, r.nama as role
    FROM tlog l
    LEFT JOIN tuser u ON l.tUser_id = u.id
    LEFT JOIN trole r ON u.tRole_id = r.id
    WHERE DATE(l.waktu) >= ? AND DATE(l.waktu) <= ?
";
$params = [$tglMulai, $tglSelesai];

if ($filterUser != '') {
    $query .= " AND l.tUser_id = ?";
    $params[] = $filterUser;
}

if ($filterModul != '') {
    $query .= " AND l.modul = ?";
    $params[] = $filterModul;
}

$query .= " ORDER BY l.waktu DESC";

$stmt = $koneksi->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>
<div class="content-wrapper">
    <div class="row">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-dark mb-1">Log Aktivitas Sistem</h4>
                    <p class="text-muted mb-4">Memantau seluruh pergerakan pengguna dalam sistem untuk pencegahan kecurangan (Anti-Fraud).</p>
                    
                    <!-- Filter -->
                    <form method="GET" class="mb-4">
                        <div class="row align-items-end">
                            <div class="col-md-2 mb-3">
                                <label class="font-weight-bold text-dark">Mulai Tanggal</label>
                                <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($tglMulai) ?>">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="font-weight-bold text-dark">Sampai Tanggal</label>
                                <input type="date" name="tgl_selesai" class="form-control form-control-sm" value="<?= htmlspecialchars($tglSelesai) ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold text-dark">Berdasarkan User</label>
                                <select name="filter_user" class="form-control form-control-sm">
                                    <option value="">Semua User</option>
                                    <?php foreach($users as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= ($filterUser == $u['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['role']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold text-dark">Modul / Fitur</label>
                                <select name="filter_modul" class="form-control form-control-sm">
                                    <option value="">Semua Modul</option>
                                    <?php foreach($moduls as $m): ?>
                                        <?php if($m['modul']): ?>
                                        <option value="<?= htmlspecialchars($m['modul']) ?>" <?= ($filterModul == $m['modul']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['modul']) ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <button type="submit" class="btn btn-info btn-sm btn-block mb-2">
                                    <i class="typcn typcn-zoom"></i> Filter
                                </button>
                                <a href="logaktivitas.php" class="btn btn-secondary btn-sm btn-block">
                                    <i class="typcn typcn-refresh"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="bg-light">
                                <tr align="center">
                                    <th width="5%">No</th>
                                    <th width="15%">Waktu</th>
                                    <th width="15%">Pengguna</th>
                                    <th width="10%">Modul</th>
                                    <th width="15%">Aktivitas</th>
                                    <th width="30%">Keterangan</th>
                                    <th width="10%">Referensi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($logs) > 0): ?>
                                    <?php $no = 1; foreach($logs as $row): ?>
                                    <tr>
                                        <td align="center"><?= $no++ ?></td>
                                        <td><?= date('d/m/Y H:i:s', strtotime($row['waktu'])) ?></td>
                                        <td>
                                            <span class="font-weight-bold text-primary"><?= htmlspecialchars($row['username']) ?></span><br>
                                            <small class="text-muted"><?= htmlspecialchars($row['role']) ?></small>
                                        </td>
                                        <td align="center"><span class="badge badge-outline-dark"><?= htmlspecialchars($row['modul']) ?></span></td>
                                        <td><?= htmlspecialchars($row['aktivitas']) ?></td>
                                        <td style="white-space: normal; line-height: 1.4;"><?= htmlspecialchars($row['keterangan']) ?></td>
                                        <td align="center"><span class="font-weight-bold text-dark"><?= htmlspecialchars($row['referensi']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted font-italic">Tidak ada catatan aktivitas yang ditemukan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
