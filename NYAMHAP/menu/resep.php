<?php
session_start();
require_once '../config/db.php';

// 1. Proteksi Otoritas (Hanya Staff Produksi)
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

$id_user = $_SESSION['staff_id'];
$cek_prod = pg_query_params($conn, "SELECT 1 FROM staff_produksi WHERE staff_id = $1", array($id_user));
if (pg_num_rows($cek_prod) == 0) {
    echo "<script>alert('Akses Ditolak! Hanya Staff Produksi yang boleh mengatur resep.'); window.location.href='index.php';</script>";
    exit();
}

// 2. Ambil Data Menu dan Bahan Baku untuk Dropdown
$res_menu = pg_query($conn, "SELECT menu_id, nama_menu FROM menu ORDER BY nama_menu ASC");
$res_bahan = pg_query($conn, "SELECT bahan_id, nama_bahan, satuan FROM bahan_baku ORDER BY nama_bahan ASC");

include '../layout/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 25px;">
        <h2 style="margin:0; color: #333;">Pengaturan Resep Menu</h2>
        <p style="color: #666; font-size: 14px;">Tentukan kebutuhan bahan baku untuk setiap porsi menu.</p>
    </div>

    <form action="proses_resep.php" method="POST" style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #e0e0e0; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
        
        <div style="margin-bottom: 25px;">
            <label style="font-weight: bold; display: block; margin-bottom: 8px;">Pilih Menu</label>
            <select name="menu_id" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ccc; background: #fff;">
                <option value="">-- Pilih Menu yang Akan Diatur --</option>
                <?php while($m = pg_fetch_assoc($res_menu)): ?>
                    <option value="<?= $m['menu_id'] ?>"><?= $m['nama_menu'] ?> (<?= $m['menu_id'] ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">

        <h4 style="color: #FF6700; margin-bottom: 15px;">Daftar Bahan Baku</h4>
        <div id="resep-list">
            <div class="row-bahan" style="display: flex; gap: 15px; margin-bottom: 15px; align-items: center;">
                <select name="bahan_id[]" required style="flex: 2; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                    <option value="">-- Pilih Bahan --</option>
                    <?php 
                    pg_result_seek($res_bahan, 0); 
                    while($b = pg_fetch_assoc($res_bahan)): 
                    ?>
                        <option value="<?= $b['bahan_id'] ?>"><?= $b['nama_bahan'] ?> (dlm <?= $b['satuan'] ?>)</option>
                    <?php endwhile; ?>
                </select>
                <input type="number" step="0.001" name="jumlah[]" placeholder="Jumlah" required style="flex: 1; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                <button type="button" onclick="this.parentElement.remove()" style="background: #ff4d4d; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer;">&times;</button>
            </div>
        </div>

        <button type="button" onclick="addBahan()" style="background: #f0f0f0; border: 1px dashed #bbb; width: 100%; padding: 12px; border-radius: 8px; margin-bottom: 30px; cursor: pointer; font-weight: 600; color: #555;">
            + Tambah Bahan Baku Lainnya
        </button>

        <div style="text-align: right;">
            <button type="submit" name="simpan_resep" style="background: #FF6700; color: white; border: none; padding: 12px 35px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px;">
                Simpan Resep Menu
            </button>
        </div>
    </form>
</div>

<script>
function addBahan() {
    const list = document.getElementById('resep-list');
    const firstRow = document.querySelector('.row-bahan');
    const newRow = firstRow.cloneNode(true);
    newRow.querySelector('input').value = ''; 
    list.appendChild(newRow);
}
</script>

<?php include '../layout/footer.php'; ?>