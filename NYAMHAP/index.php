<?php
session_start();
require_once 'config/db.php';

// Proteksi login
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$staff_id = $_SESSION['staff_id'];

// Ambil data profil staff yang sedang login
$q = "SELECT s.*, 
    CASE 
        WHEN sk.staff_id IS NOT NULL THEN 'Keuangan'
        WHEN sp.staff_id IS NOT NULL THEN 'Pemasok'
        WHEN spr.staff_id IS NOT NULL THEN 'Produksi'
        WHEN sm.staff_id IS NOT NULL THEN 'Marketing'
        WHEN sj.staff_id IS NOT NULL THEN 'Penjualan'
        ELSE 'Umum'
    END as kategori
    FROM staff s
    LEFT JOIN staff_keuangan sk ON s.staff_id = sk.staff_id
    LEFT JOIN staff_pemasok sp ON s.staff_id = sp.staff_id
    LEFT JOIN staff_produksi spr ON s.staff_id = spr.staff_id
    LEFT JOIN staff_marketing sm ON s.staff_id = sm.staff_id
    LEFT JOIN staff_penjualan sj ON s.staff_id = sj.staff_id
    WHERE s.staff_id = '$staff_id'";

$res = pg_query($conn, $q);
$u = pg_fetch_assoc($res);

// Panggil Header
include 'layout/header.php';
?>

<div style="margin-bottom: 30px;">
    <h2 style="margin: 0; color: #333;">Selamat Datang, <?php echo $u['nama']; ?>! 👋</h2>
    <p style="color: #777;">Berikut adalah detail profil Anda di sistem NYAMHAP.</p>
</div>

<div class="profile-card">
    <div class="profile-row">
        <div class="profile-label">ID Staff</div>
        <div class="profile-value"><code><?php echo $u['staff_id']; ?></code></div>
    </div>
    <div class="profile-row">
        <div class="profile-label">Nama Lengkap</div>
        <div class="profile-value"><?php echo $u['nama']; ?></div>
    </div>
    <div class="profile-row">
        <div class="profile-label">Divisi / Peran</div>
        <div class="profile-value"><span class="badge-role"><?php echo $u['kategori']; ?></span></div>
    </div>
    <div class="profile-row">
        <div class="profile-label">Email</div>
        <div class="profile-value"><?php echo $u['email']; ?></div>
    </div>
    <div class="profile-row">
        <div class="profile-label">No. Handphone</div>
        <div class="profile-value"><?php echo $u['no_handphone']; ?></div>
    </div>
</div>

<?php 
// Panggil Footer
include 'layout/footer.php'; 
?>