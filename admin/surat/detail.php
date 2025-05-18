<?php
session_start();
include '../../config/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || ($_SESSION['level'] != 'admin' && $_SESSION['level'] != 'petugas')) {
    header("Location: ../../login.php");
    exit();
}

// Cek apakah ada parameter id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

// Ambil data surat
$query = "SELECT s.*, p.nama, p.nik, p.alamat, p.tempat_lahir, p.tanggal_lahir, p.jenis_kelamin, p.no_hp, p.email
          FROM surat s
          JOIN penduduk p ON s.penduduk_id = p.id
          WHERE s.id = $id";
$result = mysqli_query($koneksi, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit();
}

$data = mysqli_fetch_assoc($result);

// Ambil history surat
$query_history = "SELECT * FROM surat_history WHERE surat_id = $id ORDER BY tanggal DESC";
$result_history = mysqli_query($koneksi, $query_history);

// Proses update status
$alert = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $status = bersihkan_input($_POST['status']);
    $keterangan = bersihkan_input($_POST['keterangan']);
    
    // Update status surat
    $query_update = "UPDATE surat SET status = '$status', keterangan = '$keterangan' WHERE id = $id";
    
    if (mysqli_query($koneksi, $query_update)) {
        // Tambahkan ke history
        $query_history = "INSERT INTO surat_history (surat_id, status, keterangan, user_id, tanggal) 
                          VALUES ($id, '$status', '$keterangan', {$_SESSION['user_id']}, NOW())";
        mysqli_query($koneksi, $query_history);
        
        // Generate nomor surat jika status approved dan belum ada nomor surat
        if ($status == 'approved' && empty($data['nomor_surat'])) {
            $nomor_surat = generate_nomor_surat($data['jenis_surat']);
            $query_nomor = "UPDATE surat SET nomor_surat = '$nomor_surat' WHERE id = $id";
            mysqli_query($koneksi, $query_nomor);
        }
        
        $alert = 'Status surat berhasil diupdate';
        
        // Refresh data
        $result = mysqli_query($koneksi, $query);
        $data = mysqli_fetch_assoc($result);
        
        // Refresh history
        $result_history = mysqli_query($koneksi, $query_history);
    } else {
        $alert = 'Gagal mengupdate status surat';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Surat - Sistem Pengelolaan Kelurahan</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <div class="logo">
                <h1>SIPEKEL</h1>
                <p>Sistem Pengelolaan Kelurahan</p>
            </div>
            <div class="user-info">
                <p>Selamat datang, <?php echo $_SESSION['nama']; ?> (<?php echo ucfirst($_SESSION['level']); ?>)</p>
                <a href="../../logout.php" class="btn btn-logout">Logout</a>
            </div>
        </header>

        <!-- Navigasi -->
        <nav>
            <ul>
                <li><a href="../dashboard.php">Dashboard</a></li>
                <li><a href="../penduduk/index.php">Data Penduduk</a></li>
                <li><a href="index.php">Manajemen Surat</a></li>
                <?php if($_SESSION['level'] == 'admin'): ?>
                    <li><a href="../pegawai/index.php">Data Pegawai</a></li>
                <?php endif; ?>
                <li><a href="../profile.php">Profil</a></li>
            </ul>
        </nav>

        <!-- Konten -->
        <main>
            <div class="card">
                <div class="card-header">
                    <h2>Detail Surat</h2>
                </div>
                <div class="card-body">
                    <?php if(!empty($alert)): ?>
                        <div class="alert alert-success">
                            <?php echo $alert; ?>
                        </div>
                    <?php endif; ?>

                    <div class="action-buttons" style="margin-bottom: 20px;">
                        <a href="index.php" class="btn btn-secondary">Kembali</a>
                        <?php if($data['status'] == 'approved'): ?>
                            <a href="cetak.php?id=<?php echo $id; ?>" class="btn btn-primary" target="_blank">Cetak Surat</a>
                        <?php endif; ?>
                    </div>

                    <div class="detail-section">
                        <h3>Informasi Surat</h3>
                        <table class="detail-table">
                            <tr>
                                <th width="200">Jenis Surat</th>
                                <td><?php echo $data['jenis_surat']; ?></td>
                            </tr>
                            <tr>
                                <th>Nomor Surat</th>
                                <td><?php echo !empty($data['nomor_surat']) ? $data['nomor_surat'] : '-'; ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Pengajuan</th>
                                <td><?php echo date('d/m/Y', strtotime($data['tanggal_pengajuan'])); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><?php echo get_status_label($data['status']); ?></td>
                            </tr>
                            <tr>
                                <th>Keperluan</th>
                                <td><?php echo $data['keperluan']; ?></td>
                            </tr>
                            <tr>
                                <th>Keterangan</th>
                                <td><?php echo !empty($data['keterangan']) ? $data['keterangan'] : '-'; ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="detail-section">
                        <h3>Data Pemohon</h3>
                        <table class="detail-table">
                            <tr>
                                <th width="200">NIK</th>
                                <td><?php echo $data['nik']; ?></td>
                            </tr>
                            <tr>
                                <th>Nama Lengkap</th>
                                <td><?php echo $data['nama']; ?></td>
                            </tr>
                            <tr>
                                <th>Tempat, Tanggal Lahir</th>
                                <td><?php echo $data['tempat_lahir'] . ', ' . date('d/m/Y', strtotime($data['tanggal_lahir'])); ?></td>
                            </tr>
                            <tr>
                                <th>Jenis Kelamin</th>
                                <td><?php echo $data['jenis_kelamin']; ?></td>
                            </tr>
                            <tr>
                                <th>Alamat</th>
                                <td><?php echo $data['alamat']; ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?php echo $data['email']; ?></td>
                            </tr>
                            <tr>
                                <th>No. HP</th>
                                <td><?php echo $data['no_hp']; ?></td>
                            </tr>
                        </table>
                    </div>

                    <?php if($data['status'] != 'approved'): ?>
                    <div class="detail-section">
                        <h3>Update Status</h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="pending" <?php echo ($data['status'] == 'pending') ? 'selected' : ''; ?>>Menunggu</option>
                                    <option value="process" <?php echo ($data['status'] == 'process') ? 'selected' : ''; ?>>Diproses</option>
                                    <option value="approved" <?php echo ($data['status'] == 'approved') ? 'selected' : ''; ?>>Disetujui</option>
                                    <option value="rejected" <?php echo ($data['status'] == 'rejected') ? 'selected' : ''; ?>>Ditolak</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="keterangan">Keterangan</label>
                                <textarea name="keterangan" id="keterangan" class="form-control" rows="3"><?php echo $data['keterangan']; ?></textarea>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                    <div class="detail-section">
                        <h3>Riwayat Status</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (mysqli_num_rows($result_history) > 0) {
                                    while ($row = mysqli_fetch_assoc($result_history)) {
                                        // Ambil nama user
                                        $query_user = "SELECT nama FROM users WHERE id = {$row['user_id']}";
                                        $result_user = mysqli_query($koneksi, $query_user);
                                        $user = mysqli_fetch_assoc($result_user);
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                    <td><?php echo get_status_label($row['status']); ?></td>
                                    <td><?php echo !empty($row['keterangan']) ? $row['keterangan'] : '-'; ?></td>
                                    <td><?php echo $user['nama']; ?></td>
                                </tr>
                                <?php
                                    }
                                } else {
                                ?>
                                <tr>
                                    <td colspan="4" align="center">Tidak ada riwayat status</td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Sistem Pengelolaan Kelurahan. Hak Cipta Dilindungi.</p>
        </footer>
    </div>

    <style>
    .detail-section {
        margin-bottom: 30px;
    }
    .detail-section h3 {
        margin-bottom: 15px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    .detail-table {
        width: 100%;
        border-collapse: collapse;
    }
    .detail-table th, .detail-table td {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    .detail-table th {
        text-align: left;
        background-color: #f9f9f9;
    }
    </style>
</body>
</html>