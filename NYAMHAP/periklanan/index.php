<?php
session_start();
require_once '../config/db.php';

// Proteksi Halaman: Cek Login
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

$role_user = $_SESSION['role'] ?? ''; // Role dari session

// Query mengambil data iklan dan join dengan staff marketing untuk mendapatkan nama PIC
$query = "
    SELECT p.*, s.nama AS nama_marketing
    FROM periklanan p
    JOIN staff s ON p.staff_mkt_id = s.staff_id
    ORDER BY p.tanggal_mulai DESC
";
$result = pg_query($conn, $query);

include '../layout/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #333;">Manajemen Periklanan & Promosi</h2>
        <p style="margin: 5px 0 0; color: #666;">Kelola campaign marketing untuk meningkatkan penjualan NYAMHAP.</p>
    </div>
    
    <?php if ($role_user === 'marketing'): ?>
    <a href="create.php" style="background-color: #ff6700; color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 14px; box-shadow: 0 4px 10px rgba(255,103,0,0.3);">
        + Tambah Campaign Baru
    </a>
    <?php endif; ?>
</div>

<div style="background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #f0f0f0;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background-color: #FF8C00; color: white;">
                <th style="padding: 15px;">ID Iklan</th>
                <th style="padding: 15px;">Media Iklan</th>
                <th style="padding: 15px;">Durasi Campaign</th>
                <th style="padding: 15px;">Status</th>
                <th style="padding: 15px;">PIC Marketing</th>
                <?php if ($role_user === 'marketing'): ?>
                <th style="padding: 15px; text-align: center;">Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = pg_fetch_assoc($result)): 
                // Logika Status Berjalan/Selesai
                $today = date('Y-m-d');
                $is_expired = ($today > $row['tanggal_selesai']);
                $status_text = $is_expired ? 'Selesai' : 'Berjalan';
                $status_color = $is_expired ? '#eb4d4b' : '#27ae60';
                $status_bg = $is_expired ? '#ffeaa7' : '#e1f5fe';
            ?>
            <tr style="border-bottom: 1px solid #eee; transition: 0.2s;" onmouseover="this.style.backgroundColor='#fafafa'" onmouseout="this.style.backgroundColor='transparent'">
                <td style="padding: 15px; font-weight: bold; color: #333;"><?= $row['periklanan_id'] ?></td>
                <td style="padding: 15px;">
                    <div style="font-weight: 600; color: #2c3e50;"><?= $row['media_iklan'] ?></div>
                    <small style="color: #888;"><?= $row['detail'] ?></small>
                </td>
                <td style="padding: 15px; color: #555; font-size: 13px;">
                    <?= date('d M Y', strtotime($row['tanggal_mulai'])) ?> - <br>
                    <?= date('d M Y', strtotime($row['tanggal_selesai'])) ?>
                </td>
                <td style="padding: 15px;">
                    <span style="background: <?= $status_bg ?>; color: <?= $status_color ?>; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                        <?= $status_text ?>
                    </span>
                </td>
                <td style="padding: 15px; color: #333; font-size: 13px;">
                    <i style="color: #777;">By:</i> <?= $row['nama_marketing'] ?>
                </td>
                
                <?php if ($role_user === 'marketing'): ?>
                <td style="padding: 15px; text-align: center;">
                    <a href="edit.php?id=<?= $row['periklanan_id'] ?>" style="color: #0652dd; text-decoration: none; font-weight: 600; font-size: 13px; margin-right: 10px;">Edit</a>
                    <a href="delete.php?id=<?= $row['periklanan_id'] ?>" onclick="return confirm('Hentikan campaign ini?')" style="color: #eb4d4b; text-decoration: none; font-weight: 600; font-size: 13px;">Hapus</a>
                </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
            
            <?php if (pg_num_rows($result) == 0): ?>
            <tr>
                <td colspan="<?= ($role_user === 'marketing') ? '6' : '5' ?>" style="padding: 30px; text-align: center; color: #999;">
                    Belum ada data campaign periklanan yang tercatat.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="margin-top: 20px; font-size: 12px; color: #888;">
    * <b>Status Berjalan</b>: Tanggal selesai belum terlampaui.<br>
    * <b>Status Selesai</b>: Campaign sudah melewati batas tanggal selesai.
</div>

<?php include '../layout/footer.php'; ?>