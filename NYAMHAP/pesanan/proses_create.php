<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: create.php");
    exit();
}

$staff_sales_id = $_SESSION['staff_id'];
$pesanan_id = $_POST['pesanan_id'];
$pelanggan_id = $_POST['pelanggan_id'];
$menu_ids_raw = $_POST['menu_id'];
$jumlahs_raw = $_POST['jumlah'];

// 1. Grouping Menu agar tidak melanggar PK (pesanan_id, menu_id) di detail_pesanan
$grouped_items = [];
for ($i = 0; $i < count($menu_ids_raw); $i++) {
    $m_id = $menu_ids_raw[$i];
    $qty = (int)$jumlahs_raw[$i];
    if (empty($m_id) || $qty <= 0) continue;
    
    if (isset($grouped_items[$m_id])) {
        $grouped_items[$m_id] += $qty;
    } else {
        $grouped_items[$m_id] = $qty;
    }
}

// Mulai Transaksi
pg_query($conn, "BEGIN");

try {
    // 2. Insert ke tabel PESANAN
    // total_harga diisi 0 karena akan diupdate otomatis oleh trigger trg_update_total_pesanan
    $q_pesanan = "INSERT INTO pesanan (pesanan_id, tanggal, total_harga, pelanggan_id, staff_sales_id) 
                  VALUES ($1, CURRENT_TIMESTAMP, 0, $2, $3)";
    $res_p = pg_query_params($conn, $q_pesanan, array($pesanan_id, $pelanggan_id, $staff_sales_id));
    if (!$res_p) throw new Exception("Gagal Header Pesanan: " . pg_last_error($conn));

    // 3. Insert ke tabel DETAIL_PESANAN
    // subtotal & subtotal_modal tidak perlu dikirim karena diisi oleh trigger trg_auto_subtotal
    foreach ($grouped_items as $m_id => $qty) {
        $q_detail = "INSERT INTO detail_pesanan (pesanan_id, menu_id, jumlah_menu) VALUES ($1, $2, $3)";
        $res_d = pg_query_params($conn, $q_detail, array($pesanan_id, $m_id, $qty));
        if (!$res_d) throw new Exception("Gagal Detail Menu $m_id: " . pg_last_error($conn));
    }

    // 4. Insert ke tabel PEMBAYARAN
    // Karena pembayaran_id di tabel kamu VARCHAR PRIMARY KEY (bukan serial), kita buat manual.
    // Kita pakai prefix 'PAY-' + nomor invoice agar unik.
    $pembayaran_id = "PAY-" . $pesanan_id; 
    $tanggal_sekarang = date('Y-m-d');

    $q_pembayaran = "INSERT INTO pembayaran (pembayaran_id, tanggal, metode, status_bayar, pesanan_id, staff_fin_id) 
                     VALUES ($1, $2, 'Cash', 'Pending', $3, NULL)";
    
    $res_pay = pg_query_params($conn, $q_pembayaran, array($pembayaran_id, $tanggal_sekarang, $pesanan_id));
    
    if (!$res_pay) {
        throw new Exception("Gagal Data Pembayaran: " . pg_last_error($conn));
    }

    pg_query($conn, "COMMIT");
    echo "<script>alert('Pesanan Berhasil Dibuat!'); window.location.href='../pembayaran/index.php';</script>";

} catch (Exception $e) {
    pg_query($conn, "ROLLBACK");
    $error_msg = addslashes($e->getMessage());
    echo "<script>alert('DATABASE ERROR: $error_msg'); window.history.back();</script>";
}
?>