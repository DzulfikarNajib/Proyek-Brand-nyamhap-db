<?php
session_start();
require_once '../config/db.php'; 

// Proteksi: Pastikan sudah login
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

// Query untuk mengambil semua data pelanggan
$q_pelanggan = "SELECT * FROM pelanggan ORDER BY pelanggan_id ASC";
$res_pelanggan = pg_query($conn, $q_pelanggan);

// Panggil Header
include '../layout/header.php'; 
?>

<div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin:0; color: #333;">Manajemen Data Pelanggan</h2>
        <p style="color: #666; font-size: 14px;">Daftar pelanggan setia yang terdaftar di sistem NYAMHAP.</p>
    </div>
    <a href="create.php" style="background: #FF6700; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; font-size: 14px; box-shadow: 0 4px 6px rgba(255,103,0,0.2);">
        + Tambah Pelanggan
    </a>
</div>

<div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e0e0e0;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #FF8C00; color: white; text-align: left;">
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0;">ID Pelanggan</th>
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0;">Nama Lengkap</th>
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0;">Nomor Handphone</th>
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (pg_num_rows($res_pelanggan) > 0): ?>
                <?php while($row = pg_fetch_assoc($res_pelanggan)): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;"><code><?= $row['pelanggan_id'] ?></code></td>
                    <td style="padding: 12px; font-weight: 600; color: #444;"><?= $row['nama'] ?></td>
                    <td style="padding: 12px; color: #666;"><?= $row['no_handphone'] ?></td>
                    <td style="padding: 12px; text-align: center;">
                        <a href="edit.php?id=<?= $row['pelanggan_id'] ?>" 
                           style="color: #FF8C00; text-decoration: none; font-weight: bold; font-size: 13px; margin-right: 15px;">
                           Edit
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="padding: 30px; text-align: center; color: #999; font-style: italic;">
                        Belum ada data pelanggan.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php 
// Panggil Footer
include '../layout/footer.php'; 
?>