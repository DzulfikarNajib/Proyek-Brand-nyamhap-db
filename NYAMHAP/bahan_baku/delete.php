<?php
session_start();
require_once '../config/db.php';

// 1. Proteksi Otoritas: Hanya Pemasok yang boleh menghapus
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'pemasok') {
    echo "<script>alert('Akses Ditolak! Hanya Pemasok yang bisa menghapus data.'); window.location.href='index.php';</script>";
    exit();
}

// 2. Cek apakah ada ID yang dikirim
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 3. Proses hapus dengan parameter binding (mencegah SQL Injection)
    $query = "DELETE FROM bahan_baku WHERE bahan_id = $1";
    $result = @pg_query_params($conn, $query, array($id));

    if ($result) {
        // Jika berhasil
        echo "<script>alert('Bahan Baku berhasil dihapus!'); window.location.href='index.php';</script>";
    } else {
        // 4. Penanganan Error (Terutama jika bahan masih dipakai di tabel Resep)
        $error = pg_last_error($conn);
        
        if (strpos($error, 'foreign key constraint') !== false) {
            echo "<script>
                alert('GAGAL MENGHAPUS: Bahan ini masih digunakan dalam data RESEP menu. Hapus dulu resep yang menggunakan bahan ini sebelum menghapus bahannya.');
                window.location.href='index.php';
            </script>";
        } else {
            echo "<script>alert('Terjadi kesalahan database: " . addslashes($error) . "'); window.location.href='index.php';</script>";
        }
    }
} else {
    header("Location: index.php");
}
?>