<?php
session_start();
require_once '../config/db.php';

// Proteksi Login
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

$role_user = $_SESSION['role'];
$staff_id_login = $_SESSION['staff_id'];

// Query mengambil data bahan baku
// Join ke tabel staff untuk melihat siapa pemasok penanggung jawabnya
$query = "
    SELECT bb.*, s.nama AS nama_pemasok
    FROM bahan_baku bb
    LEFT JOIN staff s ON bb.staff_supp_id = s.staff_id
    ORDER BY bb.nama_bahan ASC
";

$result = pg_query($conn, $query);

include '../layout/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; font-size: 28px; color: #333;">Inventaris Bahan Baku</h2>
        <p style="margin: 5px 0 0; color: #666;">Kelola stok dan harga beli bahan produksi NYAMHAP.</p>
    </div>
    
    <?php if ($role_user == 'pemasok'): ?>
        <a href="create.php" style="background-color: #ff6700; color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 14px; box-shadow: 0 4px 10px rgba(255,103,0,0.3);">
            + Tambah Bahan Baru
        </a>
    <?php endif; ?>
</div>

<div style="background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #f0f0f0;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background-color: #ff6700; color: white;">
                <th style="padding: 15px; font-size: 13px; text-transform: uppercase;">ID</th>
                <th style="padding: 15px; font-size: 13px; text-transform: uppercase;">Nama Bahan</th>
                <th style="padding: 15px; font-size: 13px; text-transform: uppercase;">Stok Tersedia</th>
                <th style="padding: 15px; font-size: 13px; text-transform: uppercase;">Harga Beli</th>
                <th style="padding: 15px; font-size: 13px; text-transform: uppercase;">PIC Pemasok</th>
                <th style="padding: 15px; font-size: 13px; text-transform: uppercase; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = pg_fetch_assoc($result)): ?>
            <tr style="border-bottom: 1px solid #eee; transition: 0.2s;" onmouseover="this.style.backgroundColor='#f9f9f9'" onmouseout="this.style.backgroundColor='transparent'">
                <td style="padding: 15px; font-size: 14px; color: #888;"><?= $row['bahan_id'] ?></td>
                <td style="padding: 15px;">
                    <div style="font-weight: bold; color: #333; font-size: 15px;"><?= $row['nama_bahan'] ?></div>
                    <div style="font-size: 11px; color: #999; text-transform: uppercase;">Satuan: <?= $row['satuan'] ?></div>
                </td>
                <td style="padding: 15px;">
                    <?php 
                        $stok = (float)$row['stok'];
                        // Alert jika stok di bawah 10 unit
                        $status_color = ($stok <= 10) ? '#eb4d4b' : '#27ae60';
                        $status_bg = ($stok <= 10) ? '#fff1f0' : '#f0fff4';
                    ?>
                    <span style="background: <?= $status_bg ?>; color: <?= $status_color ?>; padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 14px; border: 1px solid <?= $status_color ?>;">
                        <?= number_format($stok, 2, ',', '.') ?> <?= $row['satuan'] ?>
                    </span>
                    <?php if ($stok <= 10): ?>
                        <div style="font-size: 10px; color: #eb4d4b; margin-top: 5px; font-weight: bold;">⚠️ STOK TIPIS</div>
                    <?php endif; ?>
                </td>
                <td style="padding: 15px; font-weight: bold; color: #2c3e50;">
                    Rp <?= number_format($row['harga_per_unit'], 0, ',', '.') ?>
                </td>
                <td style="padding: 15px; font-size: 13px; color: #555;">
                    <?= $row['nama_pemasok'] ?: '<span style="color:#ccc">Belum Set</span>' ?>
                </td>
                <td style="padding: 15px; text-align: center;">
                    <?php if ($role_user == 'pemasok'): ?>
                        <a href="edit.php?id=<?= $row['bahan_id'] ?>" style="color: #0652dd; text-decoration: none; font-size: 13px; font-weight: 600; margin-right: 15px;">Edit</a>
                        <a href="delete.php?id=<?= $row['bahan_id'] ?>" onclick="return confirm('Hapus bahan ini dari sistem?')" style="color: #eb4d4b; text-decoration: none; font-size: 13px; font-weight: 600;">Hapus</a>
                    
                    <?php elseif ($role_user == 'produksi'): ?>
                        <a href="edit_stok.php?id=<?= $row['bahan_id'] ?>" style="background: #f1f2f6; color: #2f3542; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; border: 1px solid #ddd;">Update Stok</a>
                    
                    <?php else: ?>
                        <span style="color: #ccc; font-style: italic; font-size: 12px;">No Access</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div style="margin-top: 15px; font-size: 13px; color: #888; display: flex; justify-content: space-between;">
    <span>Total Item Bahan Baku: <strong><?= pg_num_rows($result) ?></strong> jenis.</span>
    <span>Role Akses: <b style="color: #ff6700;"><?= strtoupper($role_user) ?></b></span>
</div>

<?php include '../layout/footer.php'; ?>