<?php
session_start();
require_once '../config/db.php';

// Proteksi Otoritas: Hanya Staff Penjualan
$id_user = $_SESSION['staff_id'];
$cek_otoritas = pg_query_params($conn, "SELECT 1 FROM staff_penjualan WHERE staff_id = $1", array($id_user));
if (pg_num_rows($cek_otoritas) == 0) {
    echo "<script>alert('Akses Ditolak! Hanya Staff Penjualan yang bisa input pesanan.'); window.location.href='index.php';</script>";
    exit();
}

// UPDATE QUERY: Mengurutkan pelanggan berdasarkan pelanggan_id dari yang teratas (ASC)
$res_pelanggan = pg_query($conn, "SELECT * FROM pelanggan ORDER BY pelanggan_id ASC");
$res_menu = pg_query($conn, "SELECT * FROM menu ORDER BY nama_menu");

include '../layout/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <h2 style="color: #333;">Buat Pesanan Baru</h2>
    <p class="text-muted">Input transaksi nasi kepal pelanggan.</p>

    <form action="proses_create.php" method="POST" style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #e0e0e0; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">ID Invoice</label>
                <input type="text" name="pesanan_id" placeholder="Contoh: INV007" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
            </div>
            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Pilih Pelanggan</label>
                <select name="pelanggan_id" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                    <option value="">-- Pilih Pelanggan --</option>
                    <?php while($p = pg_fetch_assoc($res_pelanggan)): ?>
                        <option value="<?= $p['pelanggan_id'] ?>">
                            [<?= $p['pelanggan_id'] ?>] - <?= $p['nama'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <hr>

        <h4 style="color: #FF6700; margin: 20px 0 10px 0;">Item Pesanan</h4>
        <div id="item-list">
            <div class="row-item" style="display: flex; gap: 10px; margin-bottom: 10px;">
                <select name="menu_id[]" required style="flex: 2; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                    <option value="">-- Pilih Menu --</option>
                    <?php 
                    pg_result_seek($res_menu, 0); // Reset pointer menu
                    while($m = pg_fetch_assoc($res_menu)): 
                    ?>
                        <option value="<?= $m['menu_id'] ?>"><?= $m['nama_menu'] ?> (Rp <?= number_format($m['harga'],0,',','.') ?>)</option>
                    <?php endwhile; ?>
                </select>
                <input type="number" name="jumlah[]" placeholder="Qty" min="1" required style="flex: 0.5; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                <button type="button" onclick="this.parentElement.remove()" style="background: #ff4d4d; color: white; border: none; padding: 0 15px; border-radius: 6px; cursor: pointer;">X</button>
            </div>
        </div>

        <button type="button" onclick="addItem()" style="background: #eee; border: 1px dashed #ccc; width: 100%; padding: 10px; border-radius: 6px; margin-bottom: 20px; cursor: pointer;">
            + Tambah Menu Lain
        </button>

        <div style="text-align: right; border-top: 1px solid #eee; padding-top: 20px;">
            <button type="submit" style="background: #FF6700; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: bold; cursor: pointer;">
                Simpan & Proses Pesanan
            </button>
        </div>
    </form>
</div>

<script>
function addItem() {
    const list = document.getElementById('item-list');
    const firstItem = document.querySelector('.row-item');
    const newItem = firstItem.cloneNode(true);
    newItem.querySelector('input').value = ''; // Reset Qty
    list.appendChild(newItem);
}
</script>

<?php include '../layout/footer.php'; ?>