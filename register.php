<?php
session_start();
include 'config/koneksi.php';

// Jika sudah login, redirect ke halaman utama
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Proses registrasi jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = bersihkan_input($_POST['nama']);
    $nik = bersihkan_input($_POST['nik']);
    $username = bersihkan_input($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = bersihkan_input($_POST['email']);
    $no_hp = bersihkan_input($_POST['no_hp']);
    $alamat = bersihkan_input($_POST['alamat']);
    
    // Validasi
    if (empty($nama) || empty($nik) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Semua field wajib diisi";
    } else if ($password != $confirm_password) {
        $error = "Konfirmasi password tidak sesuai";
    } else if (strlen($password) < 6) {
        $error = "Password minimal 6 karakter";
    } else if (strlen($nik) != 16) {
        $error = "NIK harus 16 digit";
    } else {
        // Cek apakah username sudah ada
        $query = "SELECT * FROM users WHERE username = '$username'";
        $result = mysqli_query($koneksi, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Username sudah digunakan";
        } else {
            // Cek apakah NIK sudah ada
            $query = "SELECT * FROM penduduk WHERE nik = '$nik'";
            $result = mysqli_query($koneksi, $query);
            
            if (mysqli_num_rows($result) > 0) {
                $error = "NIK sudah terdaftar";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Simpan ke database - tabel penduduk
                $query_penduduk = "INSERT INTO penduduk (nik, nama, alamat, email, no_hp) 
                                  VALUES ('$nik', '$nama', '$alamat', '$email', '$no_hp')";
                
                if (mysqli_query($koneksi, $query_penduduk)) {
                    $penduduk_id = mysqli_insert_id($koneksi);
                    
                    // Simpan ke database - tabel users
                    $query_user = "INSERT INTO users (username, password, nama, level, penduduk_id) 
                                  VALUES ('$username', '$hashed_password', '$nama', 'warga', $penduduk_id)";
                    
                    if (mysqli_query($koneksi, $query_user)) {
                        $success = "Registrasi berhasil! Silakan login.";
                    } else {
                        $error = "Gagal menyimpan data user: " . mysqli_error($koneksi);
                    }
                } else {
                    $error = "Gagal menyimpan data penduduk: " . mysqli_error($koneksi);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Sistem Pengelolaan Kelurahan</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <div class="logo">
                <h1>SIPEKEL</h1>
                <p>Sistem Pengelolaan Kelurahan</p>
            </div>
        </header>

        <!-- Navigasi -->
        <nav>
            <ul>
                <li><a href="index.php">Beranda</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Daftar</a></li>
            </ul>
        </nav>

        <!-- Konten -->
        <main>
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2>Registrasi Penduduk</h2>
                    </div>
                    <div class="auth-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="nama">Nama Lengkap</label>
                                <input type="text" id="nama" name="nama" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="nik">NIK (16 Digit)</label>
                                <input type="text" id="nik" name="nik" class="form-control" pattern="[0-9]{16}" maxlength="16" required>
                            </div>
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                                <small>Minimal 6 karakter</small>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Konfirmasi Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="no_hp">Nomor HP</label>
                                <input type="text" id="no_hp" name="no_hp" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="alamat">Alamat</label>
                                <textarea id="alamat" name="alamat" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Daftar</button>
                            </div>
                        </form>
                    </div>
                    <div class="auth-footer">
                        <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
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