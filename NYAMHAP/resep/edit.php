<?php
session_start();
require_once '../config/db.php';

// PROTEKSI KETAT: Hanya Produksi
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'produksi') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='index.php';</script>";
    exit();
}

$m_id = $_GET['m_id'];
$b_id = $_GET['b_id'];

// Ambil data detail resep yang mau diedit
$query = "
    SELECT r.*, m.nama_menu, bb.nama_bahan, bb.satuan 
    FROM resep r
    JOIN menu m ON r.menu_id = m.menu_id
    JOIN bahan_baku bb ON r.bahan_id = bb.bahan_id
    WHERE r.menu_id = $1 AND r.bahan_id = $2
";
$res = pg_query_params($conn, $query, array($m_id, $b_id));
$row = pg_fetch_assoc($res);

if (!$row) { die("Data resep tidak ditemukan."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $jumlah_baru = $_POST['jumlah_bahan'];

    $update = "UPDATE resep SET jumlah_bahan = $1 WHERE menu_id = $2 AND bahan_id = $3";
    $result = pg_query_params($conn, $update, array($jumlah_baru, $m_id, $b_id));

    if ($result) {
        echo "<script>alert('Takaran resep berhasil diperbarui!'); window.location.href='index.php';</script>";
    }
}

include '../layout/header.php';
?>

<div style="max-width: 500px; margin: 0 auto;">
    <h2 style="color: #333;">Edit Takaran Resep</h2>
    <div style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #ddd; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
        
        <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <div style="font-size: 12px; color: #777;">MENU:</div>
            <div style="font-size: 16px; font-weight: bold; color: #2c3e50; margin-bottom: 10px;"><?= $row['nama_menu'] ?></div>
            
            <div style="font-size: 12px; color: #777;">BAHAN BAKU:</div>
            <div style="font-size: 16px; font-weight: bold; color: #ff6700;"><?= $row['nama_bahan'] ?></div>
        </div>

        <form action="" method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight:bold;">Takaran Baru (<?= $row['satuan'] ?>)</label>
                <input type="number" step="0.001" name="jumlah_bahan" value="<?= (float)$row['jumlah_bahan'] ?>" required 
                       style="width:100%; padding:12px; border:2px solid #ddd; border-radius:8px; font-size: 18px; text-align: center;">
                <small style="color: #888;">*Gunakan titik (.) untuk angka desimal</small>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex:2; background: #2c3e50; color:white; border:none; padding:12px; border-radius:8px; font-weight:bold; cursor:pointer;">Update Takaran</button>
                <a href="index.php" style="flex:1; background:#eee; color:#333; text-decoration:none; text-align:center; padding:12px; border-radius:8px;">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>