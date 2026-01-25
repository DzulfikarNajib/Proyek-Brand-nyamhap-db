<?php
session_start();
require_once '../config/db.php';

// 1. Proteksi Otoritas: Hanya Staff Produksi
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

$id_user = $_SESSION['staff_id'];
$cek_otoritas = pg_query_params($conn, "SELECT 1 FROM staff_produksi WHERE staff_id = $1", array($id_user));

if (pg_num_rows($cek_otoritas) == 0) {
    echo "<script>
            alert('Akses Ditolak! Hanya Staff Produksi yang berhak menambah menu baru.');
            window.location.href = 'index.php';
          </script>";
    exit();
}

// 2. Proses Simpan Data
$pesan = "";
if (isset($_POST['simpan'])) {
    $id_menu = $_POST['menu_id'];
    $nama    = $_POST['nama_menu'];
    $harga   = $_POST['harga'];
    $desc    = $_POST['deskripsi'];
    $prod_id = $id_user; // Diambil otomatis dari staff yang sedang login

    // Insert ke tabel menu (PK: menu_id, FK: staff_prod_id)
    $q_insert = "INSERT INTO menu (menu_id, nama_menu, harga, deskripsi, staff_prod_id) VALUES ($1, $2, $3, $4, $5)";
    $res = pg_query_params($conn, $q_insert, array($id_menu, $nama, $harga, $desc, $prod_id));

    if ($res) {
        echo "<script>
                alert('Menu Baru Berhasil Ditambahkan! Jangan lupa atur resepnya.');
                window.location.href = 'index.php';
              </script>";
    } else {
        $pesan = "<div style='background:#f8d7da; color:#721c24; padding:10px; border-radius:6px; margin-bottom:20px; border: 1px solid #f5c6cb;'>
                    Gagal menambah menu. Pastikan ID Menu <strong>$id_menu</strong> belum digunakan.
                  </div>";
    }
}

include '../layout/header.php';
?>

<div style="max-width: 650px; margin: 0 auto;">
    <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin:0; color: #333;">Tambah Menu Baru</h2>
            <p style="color: #666; font-size: 14px;">Masukkan detail produk nasi kepal yang akan diproduksi.</p>
        </div>
        <a href="index.php" style="text-decoration: none; color: #666; font-size: 14px; border: 1px solid #ccc; padding: 5px 12px; border-radius: 6px;">&larr; Kembali</a>
    </div>

    <?= $pesan ?>

    <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e0e0e0;">
        <form action="" method="POST">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444;">ID Menu</label>
                <input type="text" name="menu_id" placeholder="Contoh: M004" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 15px;">
                <small style="color: #999;">Gunakan format kode unik (Maksimal 10 Karakter).</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444;">Nama Produk</label>
                <input type="text" name="nama_menu" placeholder="Contoh: Nasi Kepal Tuna Pedas" required
                       style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 15px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444;">Harga Jual (Rp)</label>
                <input type="number" name="harga" placeholder="Masukkan harga tanpa titik" required
                       style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 15px;">
                <small style="color: #999;">Masukkan angka saja, misal: 5000</small>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #444;">Deskripsi Menu</label>
                <textarea name="deskripsi" rows="4" placeholder="Jelaskan bahan isi atau keunikan menu ini..."
                          style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 15px; resize: vertical;"></textarea>
            </div>

            <div style="border-top: 1px solid #eee; padding-top: 20px; text-align: right;">
                <button type="submit" name="simpan" 
                        style="background: #FF6700; color: white; border: none; padding: 14px 30px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.3s; box-shadow: 0 4px 6px rgba(255,103,0,0.2);">
                    Daftarkan Menu Baru
                </button>
            </div>

        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>