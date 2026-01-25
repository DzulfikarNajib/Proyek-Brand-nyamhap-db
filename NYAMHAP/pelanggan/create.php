<?php
session_start();
require_once '../config/db.php';

// 1. Proteksi Login
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

// 2. CEK OTORITAS: Hanya Staff Penjualan yang bisa akses
$id_user = $_SESSION['staff_id'];
$cek_otoritas = pg_query_params($conn, "SELECT 1 FROM staff_penjualan WHERE staff_id = $1", array($id_user));

if (pg_num_rows($cek_otoritas) == 0) {
    echo "<script>
            alert('Akses Ditolak! Hanya Staff Penjualan yang diizinkan menambah data pelanggan.');
            window.location.href = 'index.php';
          </script>";
    exit();
}

// 3. Proses Simpan Data
$pesan = "";
if (isset($_POST['simpan'])) {
    $id_pel = $_POST['pelanggan_id'];
    $nama = $_POST['nama'];
    $hp = $_POST['no_handphone'];

    // Query Insert ke tabel pelanggan
    $q_insert = "INSERT INTO pelanggan (pelanggan_id, nama, no_handphone) VALUES ($1, $2, $3)";
    $res = pg_query_params($conn, $q_insert, array($id_pel, $nama, $hp));

    if ($res) {
        echo "<script>
                alert('Data Pelanggan Berhasil Ditambahkan!');
                window.location.href = 'index.php';
              </script>";
    } else {
        $pesan = "<div style='background:#f8d7da; color:#721c24; padding:10px; border-radius:6px; margin-bottom:20px;'>
                    Gagal menambah data. Pastikan ID atau No. HP belum terdaftar.
                  </div>";
    }
}

include '../layout/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin:0; color: #333;">Tambah Pelanggan Baru</h2>
            <p style="color: #666; font-size: 14px;">Gunakan form ini untuk mendaftarkan pelanggan tetap NYAMHAP.</p>
        </div>
        <a href="index.php" style="text-decoration: none; color: #666; font-size: 14px;">&larr; Kembali</a>
    </div>

    <?= $pesan ?>

    <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e0e0e0;">
        <form action="" method="POST">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444;">ID Pelanggan</label>
                <input type="text" name="pelanggan_id" placeholder="Contoh: CUS007" required 
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box;">
                <small style="color: #999;">Gunakan format unik (Maksimal 10 Karakter).</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444;">Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Masukkan nama pelanggan" required
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444;">No. Handphone</label>
                <input type="text" name="no_handphone" placeholder="Contoh: 0812XXXXXXXX" required
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div style="border-top: 1px solid #eee; padding-top: 20px; text-align: right;">
                <button type="submit" name="simpan" 
                        style="background: #FF6700; color: white; border: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px;">
                    Daftarkan Pelanggan
                </button>
            </div>

        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>