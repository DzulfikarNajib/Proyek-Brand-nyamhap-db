<?php
session_start();
require_once '../config/db.php';

// Proteksi: Hanya Pemasok yang boleh akses edit lengkap
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'pemasok') {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$query = "SELECT * FROM bahan_baku WHERE bahan_id = $1";
$res = pg_query_params($conn, $query, array($id));
$row = pg_fetch_assoc($res);

if (!$row) { die("Data bahan baku tidak ditemukan."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_bahan'];
    $satuan = $_POST['satuan'];
    $stok = $_POST['stok'];
    $harga = $_POST['harga_per_unit'];

    $update = "UPDATE bahan_baku SET nama_bahan = $1, satuan = $2, stok = $3, harga_per_unit = $4 WHERE bahan_id = $5";
    $result = pg_query_params($conn, $update, array($nama, $satuan, $stok, $harga, $id));

    if ($result) {
        echo "<script>alert('Informasi Bahan Berhasil Diperbarui!'); window.location.href='index.php';</script>";
    } else {
        $error = pg_last_error($conn);
    }
}

include '../layout/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <h2 style="color: #333;">Edit Detail Bahan Baku</h2>
    <p style="color: #666; margin-bottom: 20px;">Role: <span style="color: #ff6700; font-weight: bold;">Pemasok</span> (Akses Penuh)</p>

    <div style="background: white; padding: 30px; border-radius: 15px; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <form action="" method="POST">
            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom: 5px; font-weight:bold;">ID Bahan (Tidak dapat diubah)</label>
                <input type="text" value="<?= $row['bahan_id'] ?>" readonly style="width:100%; padding:10px; border:1px solid #eee; border-radius:8px; background: #f9f9f9; color: #999;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom: 5px; font-weight:bold;">Nama Bahan</label>
                <input type="text" name="nama_bahan" value="<?= $row['nama_bahan'] ?>" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display:block; margin-bottom: 5px; font-weight:bold;">Satuan</label>
                    <select name="satuan" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                        <option value="gram" <?= $row['satuan'] == 'gram' ? 'selected' : '' ?>>gram</option>
                        <option value="ml" <?= $row['satuan'] == 'ml' ? 'selected' : '' ?>>ml</option>
                        <option value="pcs" <?= $row['satuan'] == 'pcs' ? 'selected' : '' ?>>pcs</option>
                        <option value="kg" <?= $row['satuan'] == 'kg' ? 'selected' : '' ?>>kg</option>
                        <option value="liter" <?= $row['satuan'] == 'liter' ? 'selected' : '' ?>>liter</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="display:block; margin-bottom: 5px; font-weight:bold;">Stok Fisik</label>
                    <input type="number" step="0.01" name="stok" value="<?= (float)$row['stok'] ?>" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display:block; margin-bottom: 5px; font-weight:bold;">Harga Beli per Unit (Rp)</label>
                <input type="number" name="harga_per_unit" value="<?= (int)$row['harga_per_unit'] ?>" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size: 16px; font-weight: bold; color: #27ae60;">
                <small style="color: #888;">* Pastikan harga akurat untuk perhitungan laba rugi.</small>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex:2; background: #ff6700; color:white; border:none; padding:12px; border-radius:8px; font-weight:bold; cursor:pointer;">Update Perubahan</button>
                <a href="index.php" style="flex:1; background:#eee; color:#333; text-decoration:none; text-align:center; padding:12px; border-radius:8px;">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>