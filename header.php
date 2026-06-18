<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kopi Kasela</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="wrapper">

    <!-- ===== SIDEBAR KIRI ===== -->
    <!-- variabel $halaman diset di masing-masing halaman, buat kasih class 'aktif' pada menu yang sedang dibuka -->
    <div class="sidebar">
        <div class="brand">
            <div class="logo">☕</div>
            <h2>Kopi Kasela</h2>
            <span>Gombengsari</span>
        </div>

        <!-- Dashboard bisa diakses semua jabatan -->
        <a href="index.php" class="<?= ($halaman == 'dashboard') ? 'aktif' : '' ?>">📊 Dashboard</a>

        <!-- Menu Produk hanya untuk Owner -->
        <?php if ($_SESSION['jabatan'] == 'Owner') { ?>
            <a href="produk.php" class="<?= ($halaman == 'produk') ? 'aktif' : '' ?>">☕ Produk</a>
        <?php } ?>

        <!-- Menu Pembelian & Produksi untuk Owner dan bagian Produksi -->
        <?php if ($_SESSION['jabatan'] == 'Owner' || $_SESSION['jabatan'] == 'Produksi') { ?>
            <a href="pembelian.php" class="<?= ($halaman == 'pembelian') ? 'aktif' : '' ?>">🛒 Pembelian</a>
            <a href="produksi.php" class="<?= ($halaman == 'produksi') ? 'aktif' : '' ?>">⚙️ Produksi</a>
        <?php } ?>

        <!-- Menu Penjualan untuk Owner dan Kasir -->
        <?php if ($_SESSION['jabatan'] == 'Owner' || $_SESSION['jabatan'] == 'Kasir') { ?>
            <a href="penjualan.php" class="<?= ($halaman == 'penjualan') ? 'aktif' : '' ?>">💰 Penjualan</a>
        <?php } ?>

        <!-- Stok bisa dilihat semua jabatan -->
        <a href="stok.php" class="<?= ($halaman == 'stok') ? 'aktif' : '' ?>">📦 Stok</a>

        <!-- Menu Pekerja hanya untuk Owner -->
        <?php if ($_SESSION['jabatan'] == 'Owner') { ?>
            <a href="pekerja.php" class="<?= ($halaman == 'pekerja') ? 'aktif' : '' ?>">👥 Pekerja</a>
        <?php } ?>
    </div>

    <!-- ===== AREA KONTEN UTAMA ===== -->
    <!-- semua konten halaman ditaruh di sini, antara header.php dan footer.php -->
    <div class="konten">

        <!-- topbar atas: judul halaman + info akun yang login -->
        <div class="topbar">
            <h1><?= $judul_halaman ?></h1> <!-- judulnya diset di masing-masing halaman -->
            <div class="akun">
                <div class="lingkaran">👤</div>
                <div class="teks-akun">
                    <small>Login:</small><br>
                    <b><?= $_SESSION['nama'] ?></b> - <?= $_SESSION['jabatan'] ?>
                </div>
                <!-- tombol keluar pakai anchor ke modal biar muncul popup konfirmasi dulu -->
                <a href="#modal-logout" class="btn-keluar">Keluar</a>
            </div>
        </div>
