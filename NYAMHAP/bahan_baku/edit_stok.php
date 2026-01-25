<?php
session_start();
require_once '../config/db.php';

// Proteksi: Hanya Produksi atau Pemasok yang boleh update stok
if (!isset($_SESSION['staff_id']) || ($_SESSION['role'] !== 'produksi' && $_SESSION['role'] !== 'pemasok')) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$query = "SELECT * FROM bahan_baku WHERE bahan_id = $1";
$res = pg_query_params($conn, $query, array($id));
$row = pg_fetch_assoc($res);

if (!$row) { die("Data tidak ditemukan."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stok_baru = $_POST['stok'];

    $update = "UPDATE bahan_baku SET stok = $1 WHERE bahan_id = $2";
    $result = pg_query_params($conn, $update, array($stok_baru, $id));

    if ($result) {
        echo "<script>alert('Stok Berhasil Diperbarui!'); window.location.href='index.php';</script>";
    }
}

include '../layout/header.php';
?>

<div style="max-width: 500px; margin: 0 auto;">
    <h2 style="color: #333;">Update Stok Fisik</h2>
    <p style="color: #666; margin-bottom: 20px;">Gunakan halaman ini untuk penyesuaian stok (Opname/Bahan Rusak).</p>

    <div style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #ddd; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
        <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <div style="font-size: 12px; color: #777; text-transform: uppercase;">Nama Bahan</div>
            <div style="font-size: 18px; font-weight: bold; color: #2c3e50;"><?= $row['nama_bahan'] ?></div>
            <div style="font-size: 13px; color: #555;">Harga Beli: Rp <?= number_format($row['harga_per_unit'], 0, ',', '.') ?> <span style="color: #ccc;">(Locked)</span></div>
        </div>

        <form action="" method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 8px; font-weight:bold; color: #ff6700;">Masukkan Stok Fisik Sekarang (<?= $row['satuan'] ?>)</label>
                <input type="number" step="0.01" name="stok" value="<?= (float)$row['stok'] ?>" required 
                       style="width:100%; padding:15px; border:2px solid #ff6700; border-radius:10px; font-size: 20px; font-weight: bold; text-align: center;">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex:2; background: #2c3e50; color:white; border:none; padding:12px; border-radius:8px; font-weight:bold; cursor:pointer;">Update Stok</button>
                <a href="index.php" style="flex:1; background:#eee; color:#333; text-decoration:none; text-align:center; padding:12px; border-radius:8px;">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>