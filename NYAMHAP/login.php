<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $staff_id = $_POST['staff_id'];
    $password = $_POST['password'];

    $query = "SELECT * FROM staff WHERE staff_id = $1 AND password = $2";
    $result = pg_query_params($conn, $query, array($staff_id, $password));
    $user = pg_fetch_assoc($result);

    if ($user) {
        $_SESSION['staff_id'] = $user['staff_id'];
        $_SESSION['nama'] = $user['nama'];

        // CEK ROLE DI SUB-TABEL
        $role = 'umum';
        if (pg_num_rows(pg_query($conn, "SELECT 1 FROM staff_keuangan WHERE staff_id='$staff_id'"))) $role = 'keuangan';
        elseif (pg_num_rows(pg_query($conn, "SELECT 1 FROM staff_pemasok WHERE staff_id='$staff_id'"))) $role = 'pemasok';
        elseif (pg_num_rows(pg_query($conn, "SELECT 1 FROM staff_produksi WHERE staff_id='$staff_id'"))) $role = 'produksi';
        elseif (pg_num_rows(pg_query($conn, "SELECT 1 FROM staff_marketing WHERE staff_id='$staff_id'"))) $role = 'marketing';
        elseif (pg_num_rows(pg_query($conn, "SELECT 1 FROM staff_penjualan WHERE staff_id='$staff_id'"))) $role = 'penjualan';

        $_SESSION['role'] = $role;
        header("Location: index.php");
        exit();
    } else { $error = "ID Staff atau Password salah!"; }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login NYAMHAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; }
        .login-card { margin-top: 100px; max-width: 400px; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="card shadow-lg login-card border-0">
            <div class="card-body p-5">
                <h3 class="text-center fw-bold mb-4 text-primary">NYAMHAP</h3>
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger small"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">ID Staff</label>
                        <input type="text" name="staff_id" class="form-control" placeholder="STF001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>