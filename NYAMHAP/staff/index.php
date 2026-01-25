<?php
session_start();
require_once '../config/db.php'; 

// Proteksi: Pastikan sudah login
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

// Simpan ID staff yang sedang login untuk pengecekan hak akses edit
$current_user_id = $_SESSION['staff_id'];

// Query untuk mengambil SEMUA staff beserta kategorinya
$q_staff = "SELECT s.*, 
    CASE 
        WHEN sk.staff_id IS NOT NULL THEN 'Keuangan'
        WHEN sp.staff_id IS NOT NULL THEN 'Pemasok'
        WHEN spr.staff_id IS NOT NULL THEN 'Produksi'
        WHEN sm.staff_id IS NOT NULL THEN 'Marketing'
        WHEN sj.staff_id IS NOT NULL THEN 'Penjualan'
        ELSE '-'
    END as kategori
    FROM staff s
    LEFT JOIN staff_keuangan sk ON s.staff_id = sk.staff_id
    LEFT JOIN staff_pemasok sp ON s.staff_id = sp.staff_id
    LEFT JOIN staff_produksi spr ON s.staff_id = spr.staff_id
    LEFT JOIN staff_marketing sm ON s.staff_id = sm.staff_id
    LEFT JOIN staff_penjualan sj ON s.staff_id = sj.staff_id
    ORDER BY s.staff_id ASC";

$res_staff = pg_query($conn, $q_staff);

// Panggil Header dari folder layout
include '../layout/header.php'; 
?>

<div style="margin-bottom: 25px;">
    <h2 style="margin:0; color: #333;">Manajemen Data Staff</h2>
    <p style="color: #666; font-size: 14px;">Daftar seluruh anggota tim NYAMHAP. Anda hanya dapat mengubah data profil Anda sendiri.</p>
</div>

<div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e0e0e0;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #FF8C00; color: white; text-align: left;">
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0;">ID</th>
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0;">Nama Staff</th>
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0;">Email</th>
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0;">Divisi</th>
                <th style="padding: 15px; border-bottom: 2px solid #e0e0e0; text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = pg_fetch_assoc($res_staff)): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 12px;"><code><?= $row['staff_id'] ?></code></td>
                <td style="padding: 12px; font-weight: 600; color: #444;"><?= $row['nama'] ?></td>
                <td style="padding: 12px; color: #666;"><?= $row['email'] ?></td>
                <td style="padding: 12px;">
                    <span style="background: #FF6700; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                        <?= $row['kategori'] ?>
                    </span>
                </td>
                <td style="padding: 12px; text-align: center;">
                    <?php if($row['staff_id'] === $current_user_id): ?>
                        <a href="edit.php?id=<?= $row['staff_id'] ?>" 
                           style="background: #ffc107; color: #000; text-decoration: none; padding: 6px 15px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-block;">
                           Edit Profil
                        </a>
                    <?php else: ?>
                        <span style="color: #ccc; font-size: 12px; font-style: italic;">Terkunci</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php 
// Panggil Footer dari folder layout
include '../layout/footer.php'; 
?>