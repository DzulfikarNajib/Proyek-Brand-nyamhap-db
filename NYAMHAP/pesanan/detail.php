<?php
session_start();
require_once '../config/db.php';

// 1. Proteksi Login
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

$pesanan_id = $_GET['id'] ?? '';

if (!$pesanan_id) {
    header("Location: index.php");
    exit();
}

// 2. Query Info Utama Pesanan (Header Invoice)
$q_header = "SELECT p.*, c.nama as nama_pelanggan, s.nama as nama_sales 
             FROM pesanan p
             LEFT JOIN pelanggan c ON p.pelanggan_id = c.pelanggan_id
             LEFT JOIN staff s ON p.staff_sales_id = s.staff_id
             WHERE p.pesanan_id = $1";
$res_header = pg_query_params($conn, $q_header, array($pesanan_id));
$header = pg_fetch_assoc($res_header);

if (!$header) {
    echo "Data pesanan tidak ditemukan.";
    exit();
}

// 3. Query Detail Item (Daftar Menu yang Dibeli)
$q_items = "SELECT dp.*, m.nama_menu, m.harga as harga_satuan
            FROM detail_pesanan dp
            JOIN menu m ON dp.menu_id = m.menu_id
            WHERE dp.pesanan_id = $1";
$res_items = pg_query_params($conn, $q_items, array($pesanan_id));

include '../layout/header.php';
?>

<div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin:0; color: #333;">Detail Invoice: <?= $header['pesanan_id'] ?></h2>
        <p style="color: #666; font-size: 14px;">Dibuat pada <?= date('d F Y', strtotime($header['tanggal'])) ?></p>
    </div>
    <a href="index.php" style="text-decoration: none; color: #666; font-size: 14px; border: 1px solid #ccc; padding: 8px 15px; border-radius: 6px;">&larr; Kembali</a>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #eee; box-shadow: 0 2px 5px rgba(0,0,0,0.03);">
        <h4 style="margin-top:0; color: #FF6700; border-bottom: 1px solid #eee; padding-bottom: 10px;">Informasi Pelanggan</h4>
        <p style="margin: 5px 0;"><strong>Nama:</strong> <?= $header['nama_pelanggan'] ?? 'Pelanggan Umum' ?></p>
        <p style="margin: 5px 0; color: #666;">ID: <?= $header['pelanggan_id'] ?? '-' ?></p>
    </div>

    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #eee; box-shadow: 0 2px 5px rgba(0,0,0,0.03);">
        <h4 style="margin-top:0; color: #FF6700; border-bottom: 1px solid #eee; padding-bottom: 10px;">Dilayani Oleh</h4>
        <p style="margin: 5px 0;"><strong>Nama Staff:</strong> <?= $header['nama_sales'] ?></p>
        <p style="margin: 5px 0; color: #666;">ID Staff: <?= $header['staff_sales_id'] ?></p>
    </div>
</div>

<div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e0e0e0;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f8f9fa; text-align: left; border-bottom: 2px solid #eee;">
                <th style="padding: 15px; color: #444;">Menu</th>
                <th style="padding: 15px; color: #444;">Harga Satuan</th>
                <th style="padding: 15px; color: #444; text-align: center;">Jumlah</th>
                <th style="padding: 15px; color: #444; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while($item = pg_fetch_assoc($res_items)): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 15px;">
                    <span style="font-weight: 600; color: #333;"><?= $item['nama_menu'] ?></span>
                </td>
                <td style="padding: 15px; color: #666;">Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                <td style="padding: 15px; text-align: center;"><?= $item['jumlah_menu'] ?></td>
                <td style="padding: 15px; text-align: right; font-weight: 600;">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr style="background: #FFF9F5;">
                <td colspan="3" style="padding: 20px; text-align: right; font-size: 18px; font-weight: bold;">Grand Total:</td>
                <td style="padding: 20px; text-align: right; font-size: 20px; font-weight: 800; color: #FF6700;">
                    Rp <?= number_format($header['total_harga'], 0, ',', '.') ?>
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<?php include '../layout/footer.php'; ?>