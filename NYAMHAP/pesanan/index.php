<?php
session_start();
require_once '../config/db.php'; 

// Proteksi: Pastikan sudah login
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

// Query untuk mengambil data pesanan lengkap dengan nama pelanggan dan nama sales
$q_pesanan = "SELECT p.*, c.nama as nama_pelanggan, s.nama as nama_sales
              FROM pesanan p
              LEFT JOIN pelanggan c ON p.pelanggan_id = c.pelanggan_id
              LEFT JOIN staff s ON p.staff_sales_id = s.staff_id
              ORDER BY p.tanggal DESC, p.pesanan_id DESC";

$res_pesanan = pg_query($conn, $q_pesanan);

include '../layout/header.php'; 
?>

<div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin:0; color: #333;">Daftar Pesanan (Invoice)</h2>
        <p style="color: #666; font-size: 14px;">Memantau seluruh transaksi penjualan nasi kepal NYAMHAP.</p>
    </div>
    
    <a href="create.php" style="background: #FF6700; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; font-size: 14px; box-shadow: 0 4px 6px rgba(255,103,0,0.2);">
        + Buat Pesanan Baru
    </a>
</div>

<div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e0e0e0;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #FF8C00; color: white; text-align: left;">
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0;">ID Invoice</th>
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0;">Tanggal</th>
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0;">Pelanggan</th>
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0;">Total Harga</th>
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0;">Sales (Staff)</th>
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (pg_num_rows($res_pesanan) > 0): ?>
                <?php while($row = pg_fetch_assoc($res_pesanan)): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;"><strong><?= $row['pesanan_id'] ?></strong></td>
                    <td style="padding: 12px; color: #666; font-size: 14px;">
                        <?= date('d M Y', strtotime($row['tanggal'])) ?>
                    </td>
                    <td style="padding: 12px; color: #444;">
                        <?= $row['nama_pelanggan'] ?? '<i style="color:#ccc">Umum</i>' ?>
                    </td>
                    <td style="padding: 12px; font-weight: bold; color: #2ecc71;">
                        Rp <?= number_format($row['total_harga'], 0, ',', '.') ?>
                    </td>
                    <td style="padding: 12px; font-size: 13px; color: #777;">
                        <?= $row['nama_sales'] ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <a href="detail.php?id=<?= $row['pesanan_id'] ?>" 
                           style="background: #f1f1f1; color: #333; text-decoration: none; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; border: 1px solid #ddd;">
                           Lihat Detail
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="padding: 40px; text-align: center; color: #999; font-style: italic;">
                        Belum ada transaksi pesanan yang tercatat.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../layout/footer.php'; ?>