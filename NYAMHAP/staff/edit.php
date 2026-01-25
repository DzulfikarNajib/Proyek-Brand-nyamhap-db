<?php
session_start();
require_once '../config/db.php';

/* ===============================
   1. Proteksi Login
================================ */
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

/* ===============================
   2. Proteksi Edit Diri Sendiri
================================ */
$id_target = $_GET['id'] ?? '';
$id_saya   = $_SESSION['staff_id'];

if ($id_target !== $id_saya) {
    echo "<script>
        alert('Akses ditolak! Anda hanya dapat mengedit profil sendiri.');
        window.location.href='index.php';
    </script>";
    exit();
}

/* ===============================
   3. Ambil Data Staff
================================ */
$q = pg_query_params($conn,
    "SELECT staff_id, nama, email, no_handphone, password 
     FROM staff WHERE staff_id=$1",
    [$id_saya]
);
$data = pg_fetch_assoc($q);
if (!$data) {
    header("Location: index.php");
    exit();
}

$pesan = "";

/* ===============================
   4. Proses Update
================================ */
if (isset($_POST['update'])) {
    $nama  = $_POST['nama'];
    $email = $_POST['email'];
    $hp    = $_POST['no_handphone'];

    // PASSWORD
    $pw_lama = $_POST['password_lama'] ?? '';
    $pw_baru = $_POST['password_baru'] ?? '';
    $pw_konf = $_POST['password_konfirmasi'] ?? '';

    // Update data umum
    pg_query_params($conn,
        "UPDATE staff SET nama=$1, email=$2, no_handphone=$3 WHERE staff_id=$4",
        [$nama, $email, $hp, $id_saya]
    );

    // Jika ingin ganti password
    if (!empty($pw_lama) || !empty($pw_baru) || !empty($pw_konf)) {

        if ($pw_lama !== $data['password']) {
            $pesan = "<div class='alert error'>Password lama salah.</div>";
        } elseif ($pw_baru !== $pw_konf) {
            $pesan = "<div class='alert error'>Konfirmasi password tidak cocok.</div>";
        } elseif (strlen($pw_baru) < 6) {
            $pesan = "<div class='alert error'>Password minimal 6 karakter.</div>";
        } else {
            pg_query_params($conn,
                "UPDATE staff SET password=$1 WHERE staff_id=$2",
                [$pw_baru, $id_saya]
            );
            $pesan = "<div class='alert success'>Profil & password berhasil diperbarui.</div>";
        }

    } else {
        $pesan = "<div class='alert success'>Profil berhasil diperbarui.</div>";
    }

    // Refresh data
    $data['nama'] = $nama;
    $data['email'] = $email;
    $data['no_handphone'] = $hp;
}

include '../layout/header.php';
?>

<style>
.container-edit {
    max-width: 720px;
    margin: 40px auto;
}
.card {
    background: #fff;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0,0,0,.06);
}
h2 {
    margin-bottom: 5px;
}
.subtitle {
    color: #777;
    margin-bottom: 25px;
}
.form-group {
    margin-bottom: 18px;
}
label {
    font-weight: 600;
    display: block;
    margin-bottom: 6px;
}
input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
input:disabled {
    background: #f5f5f5;
}
.section-title {
    margin: 30px 0 15px;
    font-weight: bold;
    border-top: 1px solid #eee;
    padding-top: 20px;
}
button {
    background: #FF6700;
    color: #fff;
    border: none;
    padding: 12px 28px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
}
.alert {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
}
.alert.success { background:#e7f7ec; color:#1b7c3a; }
.alert.error   { background:#fdecea; color:#b42318; }
</style>

<div class="container-edit">
    <div class="card">
        <h2>Edit Profil Saya</h2>
        <p class="subtitle">Perbarui data pribadi dan keamanan akun.</p>

        <?= $pesan ?>

        <form method="POST">

            <div class="form-group">
                <label>ID Staff</label>
                <input type="text" value="<?= $data['staff_id'] ?>" disabled>
            </div>

            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" value="<?= $data['nama'] ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= $data['email'] ?>" required>
            </div>

            <div class="form-group">
                <label>No. Handphone</label>
                <input type="text" name="no_handphone" value="<?= $data['no_handphone'] ?>" required>
            </div>

            <div class="section-title">Ubah Password</div>

            <div class="form-group">
                <label>Password Lama</label>
                <input type="password" name="password_lama">
            </div>

            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="password_baru">
            </div>

            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="password_konfirmasi">
            </div>

            <div style="text-align:right; margin-top:25px;">
                <button type="submit" name="update">Simpan Perubahan</button>
            </div>

        </form>
    </div>
</div>

<?php include '../layout/footer.php'; ?>
