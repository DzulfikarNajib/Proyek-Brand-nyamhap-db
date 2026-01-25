<?php
session_start();
require_once '../config/db.php';

if (!isset($_POST['simpan_resep'])) {
    header("Location: resep.php");
    exit();
}

$menu_id = $_POST['menu_id'];
$bahan_ids = $_POST['bahan_id'];
$jumlahs = $_POST['jumlah'];

pg_query($conn, "BEGIN");

try {
    // 1. Hapus resep lama untuk menu ini agar tidak duplikat saat update
    pg_query_params($conn, "DELETE FROM resep WHERE menu_id = $1", array($menu_id));

    // 2. Loop dan masukkan bahan-bahan baru
    for ($i = 0; $i < count($bahan_ids); $i++) {
        $b_id = $bahan_ids[$i];
        $qty = $jumlahs[$i];

        if (empty($b_id) || $qty <= 0) continue;

        $q_insert = "INSERT INTO resep (menu_id, bahan_id, jumlah_bahan) VALUES ($1, $2, $3)";
        $res = pg_query_params($conn, $q_insert, array($menu_id, $b_id, $qty));

        if (!$res) throw new Exception("Gagal menyimpan bahan ID: $b_id");
    }

    pg_query($conn, "COMMIT");
    echo "<script>alert('Resep Berhasil Diperbarui!'); window.location.href='index.php';</script>";

} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    $err = addslashes($e->getMessage());
    echo "<script>alert('Terjadi Kesalahan: $err'); window.history.back();</script>";
}
?>