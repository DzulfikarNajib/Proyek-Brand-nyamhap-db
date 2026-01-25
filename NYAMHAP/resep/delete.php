<?php
session_start();
require_once '../config/db.php';

// PROTEKSI: Hanya Produksi
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'produksi') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['m_id']) && isset($_GET['b_id'])) {
    $m_id = $_GET['m_id'];
    $b_id = $_GET['b_id'];

    $query = "DELETE FROM resep WHERE menu_id = $1 AND bahan_id = $2";
    $result = pg_query_params($conn, $query, array($m_id, $b_id));

    if ($result) {
        echo "<script>alert('Bahan berhasil dihapus dari resep!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data.'); window.location.href='index.php';</script>";
    }
}
?>