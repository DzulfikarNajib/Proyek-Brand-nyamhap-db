<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

$role_user = $_SESSION['role']; // Dari session login
$staff_id  = $_SESSION['staff_id'];

// Query (Gunakan LEFT JOIN agar staff_fin yang masih kosong tidak menghilangkan data)
$query = "
    SELECT p.*, ps.total_harga, pl.nama AS nama_pelanggan, st.nama AS nama_staff_fin
    FROM pembayaran p
    JOIN pesanan ps ON p.pesanan_id = ps.pesanan_id
    JOIN pelanggan pl ON ps.pelanggan_id = pl.pelanggan_id
    LEFT JOIN staff st ON p.staff_fin_id = st.staff_id
    ORDER BY p.tanggal DESC, p.pembayaran_id DESC
";
$result = pg_query($conn, $query);

include '../layout/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #333;">Manajemen Pembayaran</h2>
        <p style="margin: 5px 0 0; color: #666;">Role Anda: <b><?= ucfirst($role_user) ?></b></p>
    </div>
</div>

<div style="background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #f0f0f0;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background-color: #ff6700; color: white;">
                <th style="padding: 15px;">ID Bayar</th>
                <th style="padding: 15px;">Invoice & Pelanggan</th>
                <th style="padding: 15px;">Total Tagihan</th>
                <th style="padding: 15px;">Metode</th>
                <th style="padding: 15px; text-align: center;">Status</th>
                <th style="padding: 15px; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = pg_fetch_assoc($result)): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 15px;"><?= $row['pembayaran_id'] ?></td>
                <td style="padding: 15px;">
                    <div style="font-weight: bold;"><?= $row['pesanan_id'] ?></div>
                    <div style="font-size: 12px; color: #888;"><?= $row['nama_pelanggan'] ?></div>
                </td>
                <td style="padding: 15px; font-weight: bold;">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                <td style="padding: 15px;"><?= $row['metode'] ?></td>
                <td style="padding: 15px; text-align: center;">
                    <?php 
                        $status = $row['status_bayar'];
                        $color = ($status == 'Lunas') ? '#27ae60' : (($status == 'Gagal') ? '#eb4d4b' : '#ffa502');
                    ?>
                    <span style="background: <?= $color ?>; color: white; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold;">
                        <?= $status ?>
                    </span>
                </td>
                <td style="padding: 15px; text-align: center;">
                    <?php 
                        // OTORITAS TOMBOL EDIT:
                        // 1. Staff Keuangan bisa edit semua.
                        // 2. Staff Penjualan cuma bisa edit jika status masih 'Pending'.
                        $boleh_edit = ($role_user == 'keuangan') || ($role_user == 'penjualan' && $status == 'Pending');
                        
                        if ($boleh_edit): 
                    ?>
                        <a href="edit.php?id=<?= $row['pembayaran_id'] ?>" style="color: #0652dd; text-decoration: none; font-weight: bold;">Edit</a>
                    <?php else: ?>
                        <span style="color: #ccc; font-style: italic;">Selesai</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../layout/footer.php'; ?>