<?php
session_start();
include '../config/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || ($_SESSION['level'] != 'admin' && $_SESSION['level'] != 'petugas')) {
    header("Location: ../login.php");
    exit();
}

// Ambil statistik dari database
// Jumlah Penduduk
$query_penduduk = "SELECT COUNT(*) as total FROM penduduk";
$result_penduduk = mysqli_query($koneksi, $query_penduduk);
$jumlah_penduduk = mysqli_fetch_assoc($result_penduduk)['total'];

// Jumlah Surat yang Diproses
$query_surat = "SELECT COUNT(*) as total FROM surat";
$result_surat = mysqli_query($koneksi, $query_surat);
$jumlah_surat = mysqli_fetch_assoc($result_surat)['total'];

// Jumlah Surat Pending
$query_pending = "SELECT COUNT(*) as total FROM surat WHERE status = 'pending'";
$result_pending = mysqli_query($koneksi, $query_pending);
$jumlah_pending = mysqli_fetch_assoc($result_pending)['total'];

// Jumlah Pegawai (hanya untuk admin)
$jumlah_pegawai = 0;
if ($_SESSION['level'] == 'admin') {
    $query_pegawai = "SELECT COUNT(*) as total FROM pegawai";
    $result_pegawai = mysqli_query($koneksi, $query_pegawai);
    $jumlah_pegawai = mysqli_fetch_assoc($result_pegawai)['total'];
}

// Ambil 5 permohonan surat terbaru
$query_surat_terbaru = "SELECT s.*, p.nama, p.nik
                      FROM surat s
                      JOIN penduduk p ON s.penduduk_id = p.id
                      ORDER BY s.tanggal_pengajuan DESC
                      LIMIT 5";
$result_surat_terbaru = mysqli_query($koneksi, $query_surat_terbaru);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Pengelolaan Kelurahan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <a href="../logout.php" class="btn btn-logout">Logout</a>
            </div>
        </header>

        <!-- Navigasi -->
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="penduduk/index.php">Data Penduduk</a></li>
                <li><a href="surat/index.php">Manajemen Surat</a></li>
                <?php if($_SESSION['level'] == 'admin'): ?>
                    <li><a href="pegawai/index.php">Data Pegawai</a></li>
                <?php endif; ?>
                <li><a href="profile.php">Profil</a></li>
            </ul>
        </nav>

        <!-- Konten -->
        <main>
            <div class="card">
                <div class="card-header">
                    <h2>Dashboard</h2>
                </div>
                <div class="card-body">
                    <div class="dashboard-stats">
                        <div class="stat-card primary">
                            <h4>Total Penduduk</h4>
                            <div class="stat-value"><?php echo $jumlah_penduduk; ?></div>
                        </div>
                        <div class="stat-card success">
                            <h4>Total Surat</h4>
                            <div class="stat-value"><?php echo $jumlah_surat; ?></div>
                        </div>
                        <div class="stat-card warning">
                            <h4>Surat Pending</h4>
                            <div class="stat-value"><?php echo $jumlah_pending; ?></div>
                        </div>
                        <?php if($_SESSION['level'] == 'admin'): ?>
                            <div class="stat-card danger">
                                <h4>Total Pegawai</h4>
                                <div class="stat-value"><?php echo $jumlah_pegawai; ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>Permohonan Surat Terbaru</h3>
                        </div>
                        <div class="card-body">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Nama Pemohon</th>
                                        <th>NIK</th>
                                        <th>Jenis Surat</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    if (mysqli_num_rows($result_surat_terbaru) > 0) {
                                        while ($row = mysqli_fetch_assoc($result_surat_terbaru)) {
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                        <td><?php echo $row['nama']; ?></td>
                                        <td><?php echo $row['nik']; ?></td>
                                        <td><?php echo $row['jenis_surat']; ?></td>
                                        <td><?php echo get_status_label($row['status']); ?></td>
                                        <td>
                                            <a href="surat/detail.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">Detail</a>
                                        </td>
                                    </tr>
                                    <?php
                                        }
                                    } else {
                                    ?>
                                    <tr>
                                        <td colspan="7" align="center">Tidak ada data permohonan surat</td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            <div style="margin-top: 20px; text-align: right;">
                                <a href="surat/index.php" class="btn btn-secondary">Lihat Semua Surat</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Sistem Pengelolaan Kelurahan. Hak Cipta Dilindungi.</p>
        </footer>
    </div>
</body>
</html>