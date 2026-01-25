<?php
session_start();
require_once '../config/db.php';

// 1. PROTEKSI AKSES: Hanya Staff Marketing yang boleh menghapus
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'marketing') {
    // Jika bukan marketing, lempar kembali ke index tanpa melakukan apa-apa
    header("Location: index.php");
    exit();
}

// 2. CEK PARAMETER ID
if (isset($_GET['id'])) {
    $id_iklan = $_GET['id'];

    // 3. EKSEKUSI PENGHAPUSAN
    // Menggunakan pg_query_params untuk keamanan dari SQL Injection
    $query = "DELETE FROM periklanan WHERE periklanan_id = $1";
    $result = pg_query_params($conn, $query, array($id_iklan));

    if ($result) {
        // Jika berhasil, beri notifikasi dan balik ke index
        echo "<script>
                alert('Campaign iklan " . $id_iklan . " telah berhasil dihapus.');
                window.location.href='index.php';
              </script>";
    } else {
        // Jika gagal (misal karena constraint database)
        echo "<script>
                alert('Gagal menghapus iklan. Data mungkin masih terikat dengan record lain.');
                window.location.href='index.php';
              </script>";
    }
} else {
    // Jika tidak ada ID yang dikirim melalui URL
    header("Location: index.php");
    exit();
}
?>