<?php
session_start();
require_once '../config/db.php';

// Proteksi: Hanya Pemasok yang boleh menambah bahan
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'pemasok') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['bahan_id'];
    $nama = $_POST['nama_bahan'];
    $satuan = $_POST['satuan'];
    $stok = $_POST['stok'];
    $harga = $_POST['harga_per_unit'];
    $supp_id = $_SESSION['staff_id']; // Staff yang input otomatis jadi penanggung jawab

    $query = "INSERT INTO bahan_baku (bahan_id, nama_bahan, satuan, stok, harga_per_unit, staff_supp_id) 
              VALUES ($1, $2, $3, $4, $5, $6)";
    $result = pg_query_params($conn, $query, array($id, $nama, $satuan, $stok, $harga, $supp_id));

    if ($result) {
        echo "<script>alert('Bahan Baku Berhasil Ditambahkan!'); window.location.href='index.php';</script>";
    } else {
        $error = pg_last_error($conn);
    }
}

include '../layout/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <h2 style="color: #333;">Tambah Bahan Baku Baru</h2>
    <div style="background: white; padding: 30px; border-radius: 15px; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <form action="" method="POST">
            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom: 5px; font-weight:bold;">ID Bahan (Contoh: B010)</label>
                <input type="text" name="bahan_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom: 5px; font-weight:bold;">Nama Bahan</label>
                <input type="text" name="nama_bahan" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display:block; margin-bottom: 5px; font-weight:bold;">Satuan</label>
                    <select name="satuan" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                        <option value="gram">gram</option>
                        <option value="ml">ml</option>
                        <option value="pcs">pcs</option>
                        <option value="kg">kg</option>
                        <option value="liter">liter</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label style="display:block; margin-bottom: 5px; font-weight:bold;">Stok Awal</label>
                    <input type="number" step="0.01" name="stok" value="0" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom: 5px; font-weight:bold;">Harga Beli per Unit (Rp)</label>
                <input type="number" name="harga_per_unit" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex:2; background: #ff6700; color:white; border:none; padding:12px; border-radius:8px; font-weight:bold; cursor:pointer;">Simpan Bahan</button>
                <a href="index.php" style="flex:1; background:#eee; color:#333; text-decoration:none; text-align:center; padding:12px; border-radius:8px;">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>