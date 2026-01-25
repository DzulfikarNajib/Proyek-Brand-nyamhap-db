<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['staff_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$pembayaran_id = $_GET['id'];
$staff_id_login = $_SESSION['staff_id'];
$role_user = $_SESSION['role'];

// Ambil level otoritas jika dia keuangan
$level_fin = ($role_user == 'keuangan') ? get_finance_level($conn, $staff_id_login) : 0;

// Ambil data pembayaran
$query = "SELECT p.*, ps.total_harga, pl.nama as nama_pelanggan 
          FROM pembayaran p 
          JOIN pesanan ps ON p.pesanan_id = ps.pesanan_id 
          JOIN pelanggan pl ON ps.pelanggan_id = pl.pelanggan_id 
          WHERE p.pembayaran_id = $1";
$res = pg_query_params($conn, $query, array($pembayaran_id));
$row = pg_fetch_assoc($res);

if (!$row) { echo "Data tidak ditemukan."; exit(); }

// STATUS LOCKING LOGIC:
$is_lunas = ($row['status_bayar'] == 'Lunas');
// Penjualan tidak bisa edit jika Lunas. Keuangan < Level 4 tidak bisa edit jika Lunas.
$is_locked = ($is_lunas && !($role_user == 'keuangan' && $level_fin >= 4));

// PROSES UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$is_locked) {
    $metode_baru = $_POST['metode'];
    $status_baru = $row['status_bayar']; // Default pakai status lama
    $staff_finance_update = $row['staff_fin_id']; // Default pakai data lama di DB

    // Hanya role keuangan yang boleh mengubah status bayar dan tercatat sebagai verifikator
    if ($role_user == 'keuangan') {
        $status_baru = $_POST['status_bayar'];
        $staff_finance_update = $staff_id_login; // ID Keuangan yang memverifikasi
    }

    // Query diperbaiki: staff_fin_id tidak dipaksa dari session jika role bukan keuangan
    $update = "UPDATE pembayaran SET status_bayar = $1, metode = $2, staff_fin_id = $3 WHERE pembayaran_id = $4";
    $params = array($status_baru, $metode_baru, $staff_finance_update, $pembayaran_id);
    
    if (pg_query_params($conn, $update, $params)) {
        echo "<script>alert('Data Berhasil Diperbarui!'); window.location.href='index.php';</script>";
    } else {
        echo "Error: " . pg_last_error($conn);
    }
}

include '../layout/header.php';
?>

<div style="max-width: 500px; margin: 0 auto;">
    <h2 style="color: #333;">Update Pembayaran</h2>

    <div style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #ddd; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
        <p><strong>Invoice:</strong> <?= $row['pesanan_id'] ?> (<?= $row['nama_pelanggan'] ?>)</p>
        <p style="font-size: 20px; color: #27ae60; font-weight: bold;">Total: Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></p>
        <hr>

        <form action="" method="POST">
            <label style="display:block; margin-bottom: 5px; font-weight:bold;">Metode Pembayaran</label>
            <select name="metode" <?= $is_locked ? 'disabled' : '' ?> style="width:100%; padding:10px; margin-bottom: 15px; border-radius: 6px;">
                <option value="Cash" <?= $row['metode'] == 'Cash' ? 'selected' : '' ?>>Cash</option>
                <option value="QRIS" <?= $row['metode'] == 'QRIS' ? 'selected' : '' ?>>QRIS</option>
                <option value="Transfer" <?= $row['metode'] == 'Transfer' ? 'selected' : '' ?>>Transfer</option>
                <option value="Debit" <?= $row['metode'] == 'Debit' ? 'selected' : '' ?>>Debit</option>
            </select>

            <label style="display:block; margin-bottom: 5px; font-weight:bold;">Status Konfirmasi</label>
            <select name="status_bayar" <?= ($role_user != 'keuangan' || $is_locked) ? 'disabled' : '' ?> style="width:100%; padding:10px; margin-bottom: 15px; border-radius: 6px; background: <?= $role_user != 'keuangan' ? '#f5f5f5' : 'white' ?>;">
                <option value="Pending" <?= $row['status_bayar'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Lunas" <?= $row['status_bayar'] == 'Lunas' ? 'selected' : '' ?>>Lunas (Konfirmasi Keuangan)</option>
                <option value="Gagal" <?= $row['status_bayar'] == 'Gagal' ? 'selected' : '' ?>>Gagal / Batal</option>
            </select>

            <?php if ($role_user == 'penjualan'): ?>
                <p style="font-size: 11px; color: #d35400;">* Anda hanya dapat mengubah metode pembayaran. Verifikasi status dilakukan oleh Keuangan.</p>
            <?php endif; ?>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <?php if (!$is_locked): ?>
                    <button type="submit" style="flex:2; background: #FF6700; color:white; border:none; padding:12px; border-radius:8px; font-weight:bold; cursor:pointer;">Update Transaksi</button>
                <?php endif; ?>
                <a href="index.php" style="flex:1; background:#eee; color:#333; text-decoration:none; text-align:center; padding:12px; border-radius:8px;">Kembali</a>
            </div>
        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>