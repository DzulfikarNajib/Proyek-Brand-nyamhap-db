<?php
session_start();
require_once '../config/db.php';

// 1. PROTEKSI AKSES: Hanya Staff Marketing
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'marketing') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='index.php';</script>";
    exit();
}

// 2. AMBIL DATA LAMA
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_iklan = $_GET['id'];
$query_get = "SELECT * FROM periklanan WHERE periklanan_id = $1";
$res_get = pg_query_params($conn, $query_get, array($id_iklan));
$data = pg_fetch_assoc($res_get);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location.href='index.php';</script>";
    exit();
}

// 3. PROSES UPDATE DATA
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $media = $_POST['media_iklan'];
    $tgl_mulai = $_POST['tanggal_mulai'];
    $tgl_selesai = $_POST['tanggal_selesai'];
    $detail = $_POST['detail'];
    $staff_id = $_SESSION['staff_id']; // PIC yang melakukan perubahan terakhir

    // Validasi Tanggal (Constraint Database)
    if ($tgl_selesai < $tgl_mulai) {
        $error = "Tanggal selesai tidak boleh lebih awal dari tanggal mulai!";
    } else {
        $query_update = "UPDATE periklanan SET 
                         media_iklan = $1, 
                         tanggal_mulai = $2, 
                         tanggal_selesai = $3, 
                         detail = $4, 
                         staff_mkt_id = $5 
                         WHERE periklanan_id = $6";
        
        $result = pg_query_params($conn, $query_update, array($media, $tgl_mulai, $tgl_selesai, $detail, $staff_id, $id_iklan));

        if ($result) {
            echo "<script>alert('Campaign iklan berhasil diperbarui!'); window.location.href='index.php';</script>";
        } else {
            $error = "Gagal memperbarui data. Periksa kembali inputan Anda.";
        }
    }
}

include '../layout/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <h2 style="margin: 0; color: #333;">Edit Campaign Iklan</h2>
        <p style="color: #666;">Perbarui durasi atau detail media promosi <b><?= $id_iklan ?></b>.</p>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: #ffeded; color: #eb4d4b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #eb4d4b;">
            <b>Peringatan:</b> <?= $error ?>
        </div>
    <?php endif; ?>

    <div style="background: white; padding: 30px; border-radius: 15px; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <form action="" method="POST">
            <div style="margin-bottom: 15px;">
                <label style="display:block; font-weight:bold; margin-bottom: 5px;">ID Iklan</label>
                <input type="text" value="<?= $data['periklanan_id'] ?>" readonly 
                       style="width:100%; padding:12px; background:#f8f9fa; border:1px solid #ccc; border-radius:8px; font-weight:bold; color:#777;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display:block; font-weight:bold; margin-bottom: 5px;">Media Iklan / Platform</label>
                <select name="media_iklan" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px;">
                    <?php 
                    $options = ["Instagram Ads", "Facebook Ads", "TikTok Influencer", "Youtube Shorts", "Banner Offline"];
                    foreach ($options as $opt) {
                        $selected = ($data['media_iklan'] == $opt) ? "selected" : "";
                        echo "<option value='$opt' $selected>$opt</option>";
                    }
                    ?>
                </select>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display:block; font-weight:bold; margin-bottom: 5px;">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" required value="<?= $data['tanggal_mulai'] ?>"
                           style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px;">
                </div>
                <div style="flex: 1;">
                    <label style="display:block; font-weight:bold; margin-bottom: 5px;">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" required value="<?= $data['tanggal_selesai'] ?>"
                           style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px;">
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display:block; font-weight:bold; margin-bottom: 5px;">Detail / Deskripsi Promo</label>
                <textarea name="detail" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; height: 100px; font-family: sans-serif; resize: vertical;"><?= $data['detail'] ?></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex: 2; background: #2c3e50; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 15px;">
                    Simpan Perubahan
                </button>
                <a href="index.php" style="flex: 1; background: #eee; color: #333; text-decoration: none; text-align: center; padding: 14px; border-radius: 8px; font-size: 15px;">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>