<?php
session_start();
require_once '../config/db.php';

// 1. Proteksi Login & Otoritas
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

$id_user = $_SESSION['staff_id'];
$cek_otoritas = pg_query_params($conn, "SELECT 1 FROM staff_produksi WHERE staff_id = $1", array($id_user));

if (pg_num_rows($cek_otoritas) == 0) {
    echo "<script>alert('Akses Ditolak!'); window.location.href = 'index.php';</script>";
    exit();
}

// 2. Proses Hapus
if (isset($_GET['id'])) {
    $id_menu = $_GET['id'];

    // Cek apakah menu pernah digunakan di transaksi (pesanan)
    // Jika ada di detail_pesanan, menu tidak boleh dihapus agar laporan keuangan tidak rusak
    $q_hapus = "DELETE FROM menu WHERE menu_id = $1";
    $res = pg_query_params($conn, $q_hapus, array($id_menu));

    if ($res) {
        echo "<script>alert('Menu berhasil dihapus!'); window.location.href = 'index.php';</script>";
    } else {
        // Biasanya gagal karena Foreign Key di tabel detail_pesanan
        echo "<script>
                alert('Gagal menghapus! Menu ini sudah memiliki data transaksi penjualan. Gunakan fitur edit jika ingin mengubah informasi.');
                window.location.href = 'index.php';
              </script>";
    }
} else {
    header("Location: index.php");
}
?>