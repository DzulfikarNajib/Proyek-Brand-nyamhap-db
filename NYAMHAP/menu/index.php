<?php
session_start();
require_once '../config/db.php';

// 1. Proteksi Login
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

// 2. Cek Otoritas untuk Modifikasi (Hanya Staff Produksi)
$id_user = $_SESSION['staff_id'];
$cek_prod = pg_query_params($conn, "SELECT 1 FROM staff_produksi WHERE staff_id = $1", array($id_user));
$is_produksi = (pg_num_rows($cek_prod) > 0);

// 3. Ambil Data Menu (Menggunakan nama kolom yang benar sesuai skema: menu_id, nama_menu)
$q_menu = "SELECT * FROM menu ORDER BY menu_id ASC";
$res_menu = pg_query($conn, $q_menu);

include '../layout/header.php';
?>

<div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin:0; color: #333;">Daftar Menu Makanan</h2>
        <p style="color: #666; font-size: 14px;">* Kelola varian nasi kepal dan harga jual NYAMHAP.</p>
    </div>
    
    <?php if ($is_produksi): ?>
    <a href="create.php" style="background: #FF6700; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; font-size: 14px;">
        + Tambah Menu Baru
    </a>
    <?php endif; ?>
</div>

<div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e0e0e0;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #FF8C00; color: white; text-align: left;">
                <th style="padding: 15px;">ID Menu</th>
                <th style="padding: 15px;">Nama Menu</th>
                <th style="padding: 15px;">Deskripsi</th>
                <th style="padding: 15px;">Harga (Rp)</th>
                <?php if ($is_produksi): ?>
                <th style="padding: 15px; text-align: center;">Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (pg_num_rows($res_menu) > 0): ?>
                <?php while($row = pg_fetch_assoc($res_menu)): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px; font-weight: bold; color: #555;"><?= $row['menu_id'] ?></td>
                    <td style="padding: 15px; color: #333; font-weight: 600;"><?= $row['nama_menu'] ?></td>
                    <td style="padding: 15px; color: #666; font-size: 14px; max-width: 300px;"><?= $row['deskripsi'] ?></td>
                    <td style="padding: 15px; font-weight: bold; color: #2ecc71;">
                        Rp <?= number_format($row['harga'], 0, ',', '.') ?>
                    </td>
                    <?php if ($is_produksi): ?>
                    <td style="padding: 15px; text-align: center;">
                        <a href="edit.php?id=<?= $row['menu_id'] ?>" style="color: #3498db; text-decoration: none; font-size: 13px; font-weight: bold;">Edit</a>
                        <span style="color: #ddd;"> | </span>
                        <a href="delete.php?id=<?= $row['menu_id'] ?>" 
                           onclick="return confirm('Hapus menu ini?')" 
                           style="color: #e74c3c; text-decoration: none; font-size: 13px; font-weight: bold;">Hapus</a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding: 30px; text-align: center; color: #999;">Belum ada data menu.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="margin-top: 15px; color: #777; font-size: 13px;">
    Total Menu Tersedia: <strong><?= pg_num_rows($res_menu) ?></strong>
</div>

<?php include '../layout/footer.php'; ?>