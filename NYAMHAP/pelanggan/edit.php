<?php
session_start();
require_once '../config/db.php';

// 1. Proteksi Login
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

// 2. CEK OTORITAS: Hanya Staff Penjualan yang bisa mengedit
$id_user = $_SESSION['staff_id'];
$cek_otoritas = pg_query_params($conn, "SELECT 1 FROM staff_penjualan WHERE staff_id = $1", array($id_user));

if (pg_num_rows($cek_otoritas) == 0) {
    echo "<script>
            alert('Akses Ditolak! Hanya Staff Penjualan yang berhak mengubah data pelanggan.');
            window.location.href = 'index.php';
          </script>";
    exit();
}

// 3. Ambil data lama pelanggan berdasarkan ID di URL
$id_pel_target = $_GET['id'] ?? '';
$q_get = pg_query_params($conn, "SELECT * FROM pelanggan WHERE pelanggan_id = $1", array($id_pel_target));
$data = pg_fetch_assoc($q_get);

// Jika ID tidak ditemukan, kembalikan ke index
if (!$data) {
    header("Location: index.php");
    exit();
}

// 4. Proses Update Data
$pesan = "";
if (isset($_POST['update'])) {
    $new_id = $_POST['pelanggan_id']; // Tambahan: Ambil ID baru dari form
    $nama = $_POST['nama'];
    $hp = $_POST['no_handphone'];

    // Update query: Ditambahkan pelanggan_id = $1 agar ID bisa diubah
    // WHERE menggunakan $id_pel_target (ID lama dari URL)
    $q_update = "UPDATE pelanggan SET pelanggan_id = $1, nama = $2, no_handphone = $3 WHERE pelanggan_id = $4";
    $res = pg_query_params($conn, $q_update, array($new_id, $nama, $hp, $id_pel_target));

    if ($res) {
        echo "<script>
                alert('Data pelanggan berhasil diperbarui!');
                window.location.href = 'index.php';
              </script>";
    } else {
        $pesan = "<div style='background:#f8d7da; color:#721c24; padding:10px; border-radius:6px; margin-bottom:20px;'>
                    Gagal memperbarui data. ID atau Nomor HP mungkin sudah digunakan pelanggan lain.
                  </div>";
    }
}

include '../layout/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin:0; color: #333;">Edit Data Pelanggan</h2>
            <p style="color: #666; font-size: 14px;">Sesuaikan informasi pelanggan <strong><?= $data['pelanggan_id'] ?></strong></p>
        </div>
        <a href="index.php" style="text-decoration: none; color: #666; font-size: 14px;">&larr; Kembali</a>
    </div>

    <?= $pesan ?>

    <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e0e0e0;">
        <form action="" method="POST">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444;">ID Pelanggan</label>
                <input type="text" name="pelanggan_id" value="<?= $data['pelanggan_id'] ?>" required
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box;">
                <small style="color: #999;">FORMAT ID = CUS... </small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444;">Nama Lengkap</label>
                <input type="text" name="nama" value="<?= $data['nama'] ?>" required
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444;">No. Handphone</label>
                <input type="text" name="no_handphone" value="<?= $data['no_handphone'] ?>" required
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div style="border-top: 1px solid #eee; padding-top: 20px; text-align: right;">
                <button type="submit" name="update" 
                        style="background: #FF6700; color: white; border: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px;">
                    Simpan Perubahan
                </button>
            </div>

        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>