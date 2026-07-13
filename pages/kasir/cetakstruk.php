<?php
session_start();
require_once '../../koneksi.php';
require_once '../../auth.php';

if (!isset($_GET['nomor']) || empty($_GET['nomor'])) {
    die("Nomor transaksi tidak valid.");
}

$nomor = $_GET['nomor'];

// 1. Ambil Header Penjualan
$stmtHeader = $koneksi->prepare("
    SELECT 
        p.nomor, 
        p.tanggal, 
        p.total, 
        p.diskon, 
        p.metbayar, 
        u.username AS kasir 
    FROM tpenjualan p
    LEFT JOIN tuser u ON p.tUser_id = u.id
    WHERE p.nomor = ?
");
$stmtHeader->execute([$nomor]);
$penjualan = $stmtHeader->fetch(PDO::FETCH_ASSOC);

if (!$penjualan) {
    die("Transaksi tidak ditemukan.");
}

// 2. Ambil Detail Item
$stmtDetail = $koneksi->prepare("
    SELECT 
        d.jumlah, 
        d.harga_jual, 
        d.subtotal, 
        pr.nama AS nama_produk,
        (SELECT GROUP_CONCAT(CONCAT(m.nama, ' ', m.kategori) SEPARATOR ', ')
         FROM tDetailPenjualanModifier dhm
         JOIN tmodifier m ON dhm.tModifier_id = m.id
         WHERE dhm.tDetailPenjualan_id = d.id) AS teks_modifier
    FROM tDetailPenjualan d
    JOIN tproduct pr ON d.tProduct_kode = pr.kode
    WHERE d.tPenjualan_nomor = ?
");
$stmtDetail->execute([$nomor]);
$details = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

$subtotalAwal = $penjualan['total'] + $penjualan['diskon'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #PJ-<?= str_pad($nomor, 4, '0', STR_PAD_LEFT) ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace; /* Monospace font is best for thermal receipts */
            font-size: 14px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
        }
        .struk-container {
            width: 300px; /* Lebar standar struk thermal 80mm */
            padding: 20px;
            border: 1px dashed #ccc;
            margin-top: 20px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        
        .header h2 { margin: 0; font-size: 22px; letter-spacing: 1px; }
        .header p { margin: 2px 0; font-size: 12px; }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        
        .info-table { width: 100%; font-size: 12px; }
        .info-table td { padding: 2px 0; vertical-align: top; }
        
        .item-table { width: 100%; font-size: 12px; border-collapse: collapse; }
        .item-table td { padding: 3px 0; vertical-align: top; }
        .item-name { display: block; font-weight: bold; margin-bottom: 2px; }
        .item-mod { display: block; font-size: 10px; color: #555; padding-left: 10px; }
        
        .summary-table { width: 100%; font-size: 12px; margin-top: 5px; }
        .summary-table td { padding: 3px 0; }
        .summary-total { font-size: 14px; font-weight: bold; }
        
        .footer { margin-top: 15px; font-size: 12px; }
        
        @media print {
            body { background: none; }
            .struk-container { 
                border: none; 
                margin: 0; 
                padding: 0;
                width: 100%;
                max-width: 300px;
            }
            @page { margin: 0; }
        }
    </style>
</head>
<body>

    <div class="struk-container">
        <!-- HEADER -->
        <div class="header text-center">
            <h2>Chara Drinks</h2>
            <p>Jl. Raya Kalirungkut, Surabaya</p>
            <p>Telp: 0812-3456-7890</p>
        </div>
        
        <div class="divider"></div>
        
        <!-- INFO TRANSAKSI -->
        <table class="info-table">
            <tr>
                <td width="35%">Tanggal</td>
                <td width="5%">:</td>
                <td width="60%"><?= date('d-m-Y H:i', strtotime($penjualan['tanggal'])) ?></td>
            </tr>
            <tr>
                <td>No. Struk</td>
                <td>:</td>
                <td>#PJ-<?= str_pad($penjualan['nomor'], 4, '0', STR_PAD_LEFT) ?></td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td>:</td>
                <td><?= htmlspecialchars($penjualan['kasir'] ?: 'Sistem') ?></td>
            </tr>
        </table>
        
        <div class="divider"></div>
        
        <!-- DAFTAR PRODUK -->
        <table class="item-table">
            <?php foreach($details as $item): ?>
            <tr>
                <td colspan="3">
                    <span class="item-name"><?= htmlspecialchars($item['nama_produk']) ?></span>
                    <?php if($item['teks_modifier']): ?>
                        <span class="item-mod">Add-on: <?= htmlspecialchars($item['teks_modifier']) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td width="15%" class="text-left"><?= $item['jumlah'] ?>x</td>
                <td width="45%" class="text-left"><?= number_format($item['harga_jual'], 0, ',', '.') ?></td>
                <td width="40%" class="text-right"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="divider"></div>
        
        <!-- SUMMARY -->
        <table class="summary-table">
            <tr>
                <td width="60%">Subtotal</td>
                <td width="40%" class="text-right"><?= number_format($subtotalAwal, 0, ',', '.') ?></td>
            </tr>
            <?php if($penjualan['diskon'] > 0): ?>
            <tr>
                <td>Diskon</td>
                <td class="text-right">- <?= number_format($penjualan['diskon'], 0, ',', '.') ?></td>
            </tr>
            <?php endif; ?>
            <tr class="summary-total">
                <td>TOTAL</td>
                <td class="text-right"><?= number_format($penjualan['total'], 0, ',', '.') ?></td>
            </tr>
        </table>
        
        <div class="divider"></div>
        
        <!-- PEMBAYARAN -->
        <table class="info-table text-center" style="margin: 0 auto; width: auto;">
            <tr>
                <td class="bold">Metode Pembayaran:</td>
                <td style="padding-left: 5px;"><?= $penjualan['metbayar'] ?></td>
            </tr>
        </table>
        
        <div class="divider"></div>
        
        <!-- FOOTER -->
        <div class="footer text-center">
            <p class="bold" style="font-size: 14px; margin-bottom: 5px;">Terima Kasih!</p>
            <p>Silakan berkunjung kembali</p>
            <p style="margin-top: 10px; font-size: 10px; color: #666;">-- Sistem Kasir CHARA POS --</p>
        </div>
    </div>

    <!-- Auto Print -->
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
