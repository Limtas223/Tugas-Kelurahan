<?php
session_start();
include '../../config/koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || ($_SESSION['level'] != 'admin' && $_SESSION['level'] != 'petugas')) {
    header("Location: ../../login.php");
    exit();
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Search and filter
$search = isset($_GET['search']) ? bersihkan_input($_GET['search']) : '';
$status = isset($_GET['status']) ? bersihkan_input($_GET['status']) : '';
$jenis_surat = isset($_GET['jenis_surat']) ? bersihkan_input($_GET['jenis_surat']) : '';

$where = "WHERE 1=1";

if (!empty($search)) {
    $where .= " AND (s.nomor_surat LIKE '%$search%' OR p.nama LIKE '%$search%' OR p.nik LIKE '%$search%')";
}

if (!empty($status)) {
    $where .= " AND s.status = '$status'";
}

if (!empty($jenis_surat)) {
    $where .= " AND s.jenis_surat = '$jenis_surat'";
}

// Count total data
$query_count = "SELECT COUNT(*) as total FROM surat s JOIN penduduk p ON s.penduduk_id = p.id $where";
$result_count = mysqli_query($koneksi, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_data = $row_count['total'];
$total_page = ceil($total_data / $limit);

// Get surat data
$query = "SELECT s.*, p.nama, p.nik
          FROM surat s
          JOIN penduduk p ON s.penduduk_id = p.id
          $where
          ORDER BY s.tanggal_pengajuan DESC
          LIMIT $start, $limit";
$result = mysqli_query($koneksi, $query);

// Ambil daftar jenis surat untuk filter
$query_jenis = "SELECT DISTINCT jenis_surat FROM surat ORDER BY jenis_surat";
$result_jenis = mysqli_query($koneksi, $query_jenis);

// Alert message
$alert = '';
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Surat - Sistem Pengelolaan Kelurahan</title>
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
                    <h2>Manajemen Surat</h2>
                </div>
                <div class="card-body">
                    <?php if(!empty($alert)): ?>
                        <div class="alert alert-success">
                            <?php echo $alert; ?>
                        </div>
                    <?php endif; ?>

                    <div class="search-filter">
                        <div class="search-box">
                            <form method="GET" action="">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <input type="text" name="search" class="form-control" placeholder="Cari nomor surat, nama, atau NIK..." value="<?php echo $search; ?>">
                                </div>
                        </div>
                        <div class="filter-box">
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Menunggu</option>
                                <option value="process" <?php echo ($status == 'process') ? 'selected' : ''; ?>>Diproses</option>
                                <option value="approved" <?php echo ($status == 'approved') ? 'selected' : ''; ?>>Disetujui</option>
                                <option value="rejected" <?php echo ($status == 'rejected') ? 'selected' : ''; ?>>Ditolak</option>
                            </select>
                            
                            <select name="jenis_surat" class="form-control">
                                <option value="">Semua Jenis Surat</option>
                                <?php while ($row_jenis = mysqli_fetch_assoc($result_jenis)): ?>
                                    <option value="<?php echo $row_jenis['jenis_surat']; ?>" <?php echo ($jenis_surat == $row_jenis['jenis_surat']) ? 'selected' : ''; ?>>
                                        <?php echo $row_jenis['jenis_surat']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            
                            <button type="submit" class="btn btn-primary">Filter</button>
                            </form>
                        </div>
                        
                        <div>
                            <a href="tambah.php" class="btn btn-primary">Tambah Surat</a>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>No. Surat</th>
                                <th>Pemohon</th>
                                <th>NIK</th>
                                <th>Jenis Surat</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($result) > 0) {
                                $no = $start + 1;
                                while ($row = mysqli_fetch_assoc($result)) {
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td><?php echo !empty($row['nomor_surat']) ? $row['nomor_surat'] : '-'; ?></td>
                                <td><?php echo $row['nama']; ?></td>
                                <td><?php echo $row['nik']; ?></td>
                                <td><?php echo $row['jenis_surat']; ?></td>
                                <td><?php echo get_status_label($row['status']); ?></td>
                                <td>
                                    <a href="detail.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">Detail</a>
                                    <a href="proses.php?id=<?php echo $row['id']; ?>" class="btn btn-success">Proses</a>
                                    <a href="hapus.php?id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                            ?>
                            <tr>
                                <td colspan="8" align="center">Tidak ada data surat</td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if($total_page > 1): ?>
                    <ul class="pagination">
                        <?php if($page > 1): ?>
                        <li>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo !empty($status) ? '&status='.$status : ''; ?><?php echo !empty($jenis_surat) ? '&jenis_surat='.$jenis_surat : ''; ?>">Prev</a>
                        </li>
                        <?php endif; ?>

                        <?php for($i = 1; $i <= $total_page; $i++): ?>
                        <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo !empty($status) ? '&status='.$status : ''; ?><?php echo !empty($jenis_surat) ? '&jenis_surat='.$jenis_surat : ''; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if($page < $total_page): ?>
                        <li>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo !empty($status) ? '&status='.$status : ''; ?><?php echo !empty($jenis_surat) ? '&jenis_surat='.$jenis_surat : ''; ?>">Next</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <?php endif; ?>
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