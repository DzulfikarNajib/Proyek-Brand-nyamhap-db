<?php
session_start();
require_once '../config/db.php';

// PROTEKSI: Hanya Staff Produksi
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'produksi') {
    echo "<script>alert('Akses Ditolak! Hanya Staff Produksi yang boleh menambah resep.'); window.location.href='index.php';</script>";
    exit();
}

$menus = pg_query($conn, "SELECT menu_id, nama_menu FROM menu ORDER BY nama_menu");
$bahans = pg_query($conn, "SELECT bahan_id, nama_bahan, satuan FROM bahan_baku ORDER BY nama_bahan");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $menu_id = $_POST['menu_id'];
    $bahan_id = $_POST['bahan_id'];
    $jumlah = $_POST['jumlah'];

    // PERBAIKAN: Nama kolom disesuaikan menjadi jumlah_bahan
    $query = "INSERT INTO resep (menu_id, bahan_id, jumlah_bahan) VALUES ($1, $2, $3)";
    $res = pg_query_params($conn, $query, array($menu_id, $bahan_id, $jumlah));
    
    if ($res) {
        echo "<script>alert('Berhasil menambah bahan ke resep!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Gagal! Bahan mungkin sudah ada di menu ini atau ID salah.');</script>";
    }
}

include '../layout/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <h2>Racik Komposisi Resep</h2>
    <div style="background: white; padding: 30px; border-radius: 15px; border: 1px solid #ddd;">
        <form action="" method="POST">
            <div style="margin-bottom: 15px;">
                <label style="font-weight:bold;">1. Pilih Menu / Produk</label>
                <select name="menu_id" required style="width:100%; padding:10px; margin-top:5px;">
                    <option value="">-- Pilih Menu --</option>
                    <?php while($m = pg_fetch_assoc($menus)): ?>
                        <option value="<?= $m['menu_id'] ?>"><?= $m['nama_menu'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="font-weight:bold;">2. Pilih Bahan Baku</label>
                <select name="bahan_id" required style="width:100%; padding:10px; margin-top:5px;">
                    <option value="">-- Pilih Bahan --</option>
                    <?php while($b = pg_fetch_assoc($bahans)): ?>
                        <option value="<?= $b['bahan_id'] ?>"><?= $b['nama_bahan'] ?> (<?= $b['satuan'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="margin-bottom: 25px;">
                <label style="font-weight:bold;">3. Takaran per Porsi</label>
                <input type="number" step="0.001" name="jumlah" required style="width:100%; padding:10px; margin-top:5px;">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex:2; background: #ff6700; color:white; padding:12px; border:none; border-radius:8px; font-weight:bold;">Simpan ke Resep</button>
                <a href="index.php" style="flex:1; background:#eee; color:#333; text-decoration:none; text-align:center; padding:12px; border-radius:8px;">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>