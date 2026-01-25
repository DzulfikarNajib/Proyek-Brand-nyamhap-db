<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

$role_user = $_SESSION['role'] ?? ''; // Ambil role dari session

// Query diperbaiki menggunakan nama kolom: jumlah_bahan
$query = "
    SELECT r.menu_id, r.bahan_id, m.nama_menu, bb.nama_bahan, r.jumlah_bahan, bb.satuan
    FROM resep r
    JOIN menu m ON r.menu_id = m.menu_id
    JOIN bahan_baku bb ON r.bahan_id = bb.bahan_id
    ORDER BY m.nama_menu ASC
";
$result = pg_query($conn, $query);

include '../layout/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0; color: #333;">Manajemen Resep NYAMHAP</h2>
        <p style="margin: 5px 0 0; color: #666;">Akses Login: <b style="color: #ff6700;"><?= strtoupper($role_user) ?></b></p>
    </div>
    
    <?php if ($role_user === 'produksi'): ?>
    <a href="create.php" style="background-color: #ff6700; color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: bold;">
        + Racik Resep Baru
    </a>
    <?php endif; ?>
</div>

<div style="background: white; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #f0f0f0;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background-color: #FF8C00; color: white;">
                <th style="padding: 15px;">Nama Menu</th>
                <th style="padding: 15px;">Bahan Baku</th>
                <th style="padding: 15px;">Takaran</th>
                <?php if ($role_user === 'produksi'): ?>
                <th style="padding: 15px; text-align: center;">Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $current_menu = "";
            while ($row = pg_fetch_assoc($result)): 
                $display_menu = ($row['nama_menu'] != $current_menu) ? "<strong>" . $row['nama_menu'] . "</strong>" : "";
                $row_style = ($row['nama_menu'] != $current_menu && $current_menu != "") ? "border-top: 2px solid #eee;" : "";
                $current_menu = $row['nama_menu'];
            ?>
            <tr style="<?= $row_style ?>">
                <td style="padding: 15px; color: #333;"><?= $display_menu ?></td>
                <td style="padding: 15px; color: #555;"><?= $row['nama_bahan'] ?></td>
                <td style="padding: 15px;">
                    <span style="background: #e1f5fe; color: #0288d1; padding: 4px 10px; border-radius: 5px; font-weight: bold;">
                        <?= (float)$row['jumlah_bahan'] ?> <?= $row['satuan'] ?>
                    </span>
                </td>
                <?php if ($role_user === 'produksi'): ?>
                <td style="padding: 15px; text-align: center;">
                    <a href="edit.php?m_id=<?= $row['menu_id'] ?>&b_id=<?= $row['bahan_id'] ?>" style="color: #0652dd; text-decoration: none; font-weight: bold; margin-right: 10px;">Edit</a>
                    <a href="delete.php?m_id=<?= $row['menu_id'] ?>&b_id=<?= $row['bahan_id'] ?>" onclick="return confirm('Hapus bahan ini dari resep?')" style="color: #eb4d4b; text-decoration: none; font-weight: bold;">Hapus</a>
                </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../layout/footer.php'; ?>