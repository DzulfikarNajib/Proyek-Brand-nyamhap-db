<?php
session_start();
require_once '../config/db.php';

// 1. Proteksi Login & Otoritas
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

$id_user = $_SESSION['staff_id'];
$cek_otoritas = pg_query_params($conn, "SELECT 1 FROM staff_produksi WHERE staff_id = $1", array($id_user));

if (pg_num_rows($cek_otoritas) == 0) {
    echo "<script>alert('Akses Ditolak! Hanya Staff Produksi yang boleh mengedit menu.'); window.location.href = 'index.php';</script>";
    exit();
}

// 2. Ambil data lama menu
$id_menu_target = $_GET['id'] ?? '';
$q_get = pg_query_params($conn, "SELECT * FROM menu WHERE menu_id = $1", array($id_menu_target));
$data = pg_fetch_assoc($q_get);

if (!$data) {
    header("Location: index.php");
    exit();
}

// 3. Proses Update
$pesan = "";
if (isset($_POST['update'])) {
    $nama  = $_POST['nama_menu'];
    $harga = $_POST['harga'];
    $desc  = $_POST['deskripsi'];

    $q_update = "UPDATE menu SET nama_menu = $1, harga = $2, deskripsi = $3 WHERE menu_id = $4";
    $res = pg_query_params($conn, $q_update, array($nama, $harga, $desc, $id_menu_target));

    if ($res) {
        echo "<script>alert('Menu berhasil diperbarui!'); window.location.href = 'index.php';</script>";
    } else {
        $pesan = "<div style='background:#f8d7da; color:#721c24; padding:10px; border-radius:6px; margin-bottom:20px;'>Gagal memperbarui data. Cek kembali inputan Anda.</div>";
    }
}

include '../layout/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin:0; color: #333;">Edit Menu: <?= $data['menu_id'] ?></h2>
        <a href="index.php" style="text-decoration: none; color: #666; font-size: 14px;">&larr; Kembali</a>
    </div>

    <?= $pesan ?>

    <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e0e0e0;">
        <form action="" method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px;">ID Menu (Permanen)</label>
                <input type="text" value="<?= $data['menu_id'] ?>" disabled style="width: 100%; padding: 10px; border-radius: 6px; background: #f5f5f5; border: 1px solid #ddd;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px;">Nama Menu</label>
                <input type="text" name="nama_menu" value="<?= $data['nama_menu'] ?>" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px;">Harga Jual (Rp)</label>
                <input type="number" name="harga" value="<?= $data['harga'] ?>" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px;">Deskripsi</label>
                <textarea name="deskripsi" rows="3" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;"><?= $data['deskripsi'] ?></textarea>
            </div>

            <div style="text-align: right;">
                <button type="submit" name="update" style="background: #FF6700; color: white; border: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; cursor: pointer;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>