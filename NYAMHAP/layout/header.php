<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NYAMHAP Hub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:0; padding:0; background:#f0f0f0; }
        .container-box { background:white; padding:0; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); max-width:1200px; margin:50px auto; overflow:hidden; min-height: 500px; }
        .main-navbar { background:white; padding:10px 30px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e0e0e0; }
        .header-branding { display:flex; align-items:center; padding:0; }
        .header-branding img { height:40px; margin-right:8px; }
        .header-branding h1 { font-size:28px; margin:0; color:#FF6700; font-weight:800; letter-spacing:0.5px; }
        nav { display:flex; align-items:center; gap:20px; }
        nav a { color:#4a4a4a; text-decoration:none; font-weight:600; padding:5px 0; position:relative; }
        nav a.active, nav a:hover { color:#FF6700; }
        nav a.active::after, nav a:hover::after { content:''; display:block; width:100%; height:3px; background:#FF6700; position:absolute; bottom:-8px; left:0; border-radius:2px; }
        .action-button { display:inline-block; background:#dc3545; color:white; text-decoration:none; padding:6px 20px; border-radius:6px; font-weight:500; }
        .action-button:hover { background:#c82333; }
        .content-section { padding:40px; }
        
        /* Style Profil */
        .profile-card { border: 1px solid #eee; padding: 20px; border-radius: 10px; background: #fafafa; }
        .profile-row { display: flex; border-bottom: 1px solid #eee; padding: 10px 0; }
        .profile-label { width: 150px; font-weight: bold; color: #666; }
        .badge-role { background: #FF6700; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container-box">
        <header class="main-navbar">
            <div class="header-branding">
                <img src="/NYAMHAP/assests/img/4K-removebg-preview.png" alt="Logo">
                <h1>NYAMHAP</h1>
            </div>

            <nav class="nav-links">
                <?php
                $path = $_SERVER['REQUEST_URI'];
                function check_active($uri) {
                    global $path;
                    return (strpos($path, $uri) !== false) ? 'active' : '';
                }
                ?>
                <a href="/NYAMHAP/index.php" class="<?= (basename($path) == 'index.php' && strpos($path, 'NYAMHAP/index.php') !== false) ? 'active' : '' ?>">Home</a>
                <a href="/NYAMHAP/pelanggan/index.php" class=" <?= check_active('/pelanggan/') ?>">Pelanggan</a>
                <a href="/NYAMHAP/pesanan/index.php" class=" <?= check_active('/pesanan/') ?>">Pesanan</a>
                <a href="/NYAMHAP/menu/index.php" class="<?= check_active('/menu/') ?>">Menu</a>
                <a href="/NYAMHAP/pembayaran/index.php" class="<?= check_active('/pembayaran/') ?>">Pembayaran</a>
                <a href="/NYAMHAP/staff/index.php" class="<?= check_active('/staff/') ?>">Data Staff</a>
                <a href="/NYAMHAP/bahan_baku/index.php" class="<?= check_active('/bahan_baku/') ?>">Bahan Baku</a>
                <a href="/NYAMHAP/periklanan/index.php" class="<?= check_active('/periklanan/') ?>">Iklan</a>
                <a href="/NYAMHAP/resep/index.php" class="<?= check_active('/resep/') ?>">Resep</a>
            </nav>

            <a href="/NYAMHAP/logout.php" class="action-button">Logout</a>
        </header>
        <div class="content-section">