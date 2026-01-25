<?php
session_start();
require_once '../config/db.php';

// 1. PROTEKSI AKSES: Hanya Staff Marketing
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'marketing') {
    echo "<script>alert('Akses Ditolak! Hanya Staff Marketing yang bisa menambah iklan.'); window.location.href='index.php';</script>";
    exit();
}

// 2. LOGIKA GENERATE ID OTOMATIS (Format: AD001, AD002, dst)
$query_id = "SELECT periklanan_id FROM periklanan ORDER BY periklanan_id DESC LIMIT 1";
$res_id = pg_query($conn, $query_id);
$last_id = pg_fetch_assoc($res_id);

if ($last_id) {
    $num = (int)substr($last_id['periklanan_id'], 2) + 1;
    $new_id = "AD" . str_pad($num, 3, "0", STR_PAD_LEFT);
} else {
    $new_id = "AD001";
}

// 3. PROSES SIMPAN DATA
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $p_id = $_POST['periklanan_id'];
    $media = $_POST['media_iklan'];
    $tgl_mulai = $_POST['tanggal_mulai'];
    $tgl_selesai = $_POST['tanggal_selesai'];
    $detail = $_POST['detail'];
    $staff_id = $_SESSION['staff_id']; // Mengambil ID dari session login

    // Cek durasi (Constraint DB: selesai >= mulai)
    if ($tgl_selesai < $tgl_mulai) {
        $error = "Tanggal selesai tidak boleh lebih awal dari tanggal mulai!";
    } else {
        $query_insert = "INSERT INTO periklanan (periklanan_id, media_iklan, tanggal_mulai, tanggal_selesai, detail, staff_mkt_id) 
                         VALUES ($1, $2, $3, $4, $5, $6)";
        $result = pg_query_params($conn, $query_insert, array($p_id, $media, $tgl_mulai, $tgl_selesai, $detail, $staff_id));

        if ($result) {
            echo "<script>alert('Campaign Iklan Berhasil Diterbitkan!'); window.location.href='index.php';</script>";
        } else {
            $error = "Gagal menyimpan data. Pastikan format benar.";
        }
    }
}

include '../layout/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <h2 style="margin: 0; color: #333;">Buat Campaign Iklan</h2>
        <p style="color: #666;">Isi detail promosi untuk meningkatkan penjualan NYAMHAP.</p>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: #ffeded; color: #eb4d4b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #eb4d4b;">
            <b>Error:</b> <?= $error ?>
        </div>
    <?php endif; ?>

    <div style="background: white; padding: 30px; border-radius: 15px; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <form action="" method="POST">
            <div style="margin-bottom: 15px;">
                <label style="display:block; font-weight:bold; margin-bottom: 5px;">ID Iklan</label>
                <input type="text" name="periklanan_id" value="<?= $new_id ?>" readonly 
                       style="width:100%; padding:10px; background:#f0f0f0; border:1px solid #ccc; border-radius:5px; font-weight:bold; color:#555;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display:block; font-weight:bold; margin-bottom: 5px;">Media Iklan / Platform</label>
                <select name="media_iklan" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                    <option value="">-- Pilih Media --</option>
                    <option value="Instagram Ads">Instagram Ads</option>
                    <option value="Facebook Ads">Facebook Ads</option>
                    <option value="TikTok Influencer">TikTok Influencer</option>
                    <option value="Youtube Shorts">Youtube Shorts</option>
                    <option value="Banner Offline">Banner Offline</option>
                </select>
            </div>

            <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display:block; font-weight:bold; margin-bottom: 5px;">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" required value="<?= date('Y-m-d') ?>"
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                </div>
                <div style="flex: 1;">
                    <label style="display:block; font-weight:bold; margin-bottom: 5px;">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>"
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display:block; font-weight:bold; margin-bottom: 5px;">Detail / Deskripsi Promo</label>
                <textarea name="detail" placeholder="Contoh: Promo Beli 2 Gratis 1 untuk menu Nasi Kepal Ayam Suwir..." 
                          required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; height: 100px; font-family: sans-serif;"></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" style="flex: 2; background: #ff6700; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer;">
                    Terbitkan Campaign
                </button>
                <a href="index.php" style="flex: 1; background: #eee; color: #333; text-decoration: none; text-align: center; padding: 12px; border-radius: 8px;">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>