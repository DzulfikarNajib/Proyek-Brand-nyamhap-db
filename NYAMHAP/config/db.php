<?php
$host     = 'localhost';
$dbname   = 'NYAMHAP2';
$user     = 'postgres';
$password = 'Najib2202';

$conn = pg_connect("host=$host dbname=$dbname user=$user password=$password");

if (!$conn) {
    die("Koneksi database gagal: " . pg_last_error());
}

pg_query($conn, "SET search_path TO public");
$koneksi = $conn;

// Fungsi Tambahan: Ambil Level Otoritas jika dia staff keuangan
function get_finance_level($conn, $staff_id) {
    $q = pg_query_params($conn, "SELECT level_otoritas FROM staff_keuangan WHERE staff_id = $1", array($staff_id));
    $data = pg_fetch_assoc($q);
    return $data ? (int)$data['level_otoritas'] : 0;
}
?>