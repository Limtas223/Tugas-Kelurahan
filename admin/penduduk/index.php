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
$where = "WHERE 1=1";

if (!empty($search)) {
    $where .= " AND (nama LIKE '%$search%' OR nik LIKE '%$search%' OR alamat LIKE '%$search%')";
}

// Count total data
$query_count = "SELECT COUNT(*) as total FROM penduduk $where";
$result_count = mysqli_query($koneksi, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_data = $row_count['total'];
$total_page = ceil($total_data / $limit);

// Get penduduk data
$query = "SELECT * FROM penduduk $where ORDER BY id DESC LIMIT $start, $limit";
$result = mysqli_query($koneksi, $query);

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
    <title>Data Penduduk - Sistem Pengelolaan Kelurahan</title>
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
                <li><a href="index.php">Data Penduduk</a></li>
                <li><a href="../surat/index.php">Manajemen Surat</a></li>
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
                    <h2>Data Penduduk</h2>
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
                                    <input type="text" name="search" class="form-control" placeholder="Cari nama, NIK, atau alamat..." value="<?php echo $search; ?>">
                                </div>
                            </form>
                        </div>
                        <div>
                            <a href="tambah.php" class="btn btn-primary">Tambah Penduduk</a>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Alamat</th>
                                <th>Email</th>
                                <th>No. HP</th>
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
                                <td><?php echo $row['nik']; ?></td>
                                <td><?php echo $row['nama']; ?></td>
                                <td><?php echo $row['alamat']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['no_hp']; ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">Edit</a>
                                    <a href="hapus.php?id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                            ?>
                            <tr>
                                <td colspan="7" align="center">Tidak ada data penduduk</td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if($total_page > 1): ?>
                    <ul class="pagination">
                        <?php if($page > 1): ?>
                        <li>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">Prev</a>
                        </li>
                        <?php endif; ?>

                        <?php for($i = 1; $i <= $total_page; $i++): ?>
                        <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if($page < $total_page): ?>
                        <li>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">Next</a>
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