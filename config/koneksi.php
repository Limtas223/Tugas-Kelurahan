<?php
// Konfigurasi Database
$db_host = 'localhost';
$db_name = 'db_kelurahan';
$db_user = 'root';
$db_pass = '';

// Membuat koneksi
$koneksi = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Cek koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Fungsi untuk membersihkan input
function bersihkan_input($data) {
    global $koneksi;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($koneksi, $data);
    return $data;
}

// Fungsi untuk menghasilkan nomor surat
function generate_nomor_surat($jenis_surat) {
    global $koneksi;
    $tahun = date('Y');
    $bulan = date('m');
    
    // Mendapatkan nomor urut terakhir berdasarkan jenis surat dan bulan/tahun saat ini
    $query = "SELECT MAX(SUBSTRING_INDEX(nomor_surat, '/', 1)) as last_num 
              FROM surat 
              WHERE jenis_surat = '$jenis_surat' 
              AND YEAR(tanggal_pengajuan) = $tahun 
              AND MONTH(tanggal_pengajuan) = $bulan";
    
    $result = mysqli_query($koneksi, $query);
    $row = mysqli_fetch_assoc($result);
    
    $last_num = $row['last_num'] ? $row['last_num'] : 0;
    $new_num = $last_num + 1;
    
    // Format: Nomor/Kode-Surat/Bulan/Tahun
    $kode_surat = '';
    switch ($jenis_surat) {
        case 'Surat Keterangan Domisili':
            $kode_surat = 'SKD';
            break;
        case 'Surat Keterangan Usaha':
            $kode_surat = 'SKU';
            break;
        case 'Surat Keterangan Tidak Mampu':
            $kode_surat = 'SKTM';
            break;
        case 'Surat Pengantar':
            $kode_surat = 'SP';
            break;
        default:
            $kode_surat = 'SK';
    }
    
    $nomor_surat = sprintf('%03d', $new_num) . '/' . $kode_surat . '/' . $bulan . '/' . $tahun;
    
    return $nomor_surat;
}

// Fungsi untuk mendapatkan status permohonan surat
function get_status_label($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge badge-warning">Menunggu</span>';
        case 'process':
            return '<span class="badge badge-info">Diproses</span>';
        case 'approved':
            return '<span class="badge badge-success">Disetujui</span>';
        case 'rejected':
            return '<span class="badge badge-danger">Ditolak</span>';
        default:
            return '<span class="badge badge-secondary">Tidak Diketahui</span>';
    }
}
?>