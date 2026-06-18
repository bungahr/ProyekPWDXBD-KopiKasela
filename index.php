<?php
include "koneksi.php";
include "cek_login.php"; // tendang kalau belum login

$halaman       = "dashboard";
$judul_halaman = "Dashboard";

// ===== AMBIL DATA UNTUK KARTU STATISTIK =====

// hitung berapa jenis produk yang ada
$q1           = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM produk");
$total_produk = mysqli_fetch_assoc($q1)['total'];

// hitung total semua stok produk (dijumlahkan dari tabel stok_produk)
$q2         = mysqli_query($koneksi, "SELECT SUM(jumlah_stok) as total FROM stok_produk");
$total_stok = mysqli_fetch_assoc($q2)['total'];
if ($total_stok == null) {
    $total_stok = 0; // kalau belum ada stok sama sekali, tampilin 0 biar ga error
}

// hitung berapa kali transaksi pembelian bahan
$q3               = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pembelian_biji_kopi");
$total_pembelian  = mysqli_fetch_assoc($q3)['total'];

// hitung berapa kali transaksi penjualan
$q4               = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM penjualan");
$total_penjualan  = mysqli_fetch_assoc($q4)['total'];

// ===== HITUNG OMZET BULAN INI =====
$bulan_sekarang  = date("Y-m"); // format: 2025-06
$q5              = mysqli_query($koneksi, "SELECT SUM(total) as total FROM penjualan WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_sekarang'");
$omzet_bulan_ini = mysqli_fetch_assoc($q5)['total'];
if ($omzet_bulan_ini == null) {
    $omzet_bulan_ini = 0; // kalau bulan ini belum ada penjualan, tampilin 0
}

include "header.php";
?>

<!-- ===== KARTU STATISTIK (4 KOTAK DI ATAS) ===== -->
<div class="kotak-statistik">
    <div class="kotak">
        <div class="teks">
            <h3>Total Produk</h3>
            <p class="angka"><?= $total_produk ?></p>
            <p class="label">Jenis Produk</p>
        </div>
        <div class="icon-kotak">☕</div>
    </div>

    <div class="kotak">
        <div class="teks">
            <h3>Stok Produk</h3>
            <p class="angka"><?= $total_stok ?></p>
            <p class="label">pcs</p>
        </div>
        <div class="icon-kotak">📦</div>
    </div>

    <div class="kotak">
        <div class="teks">
            <h3>Total Pembelian</h3>
            <p class="angka"><?= $total_pembelian ?></p>
            <p class="label">transaksi</p>
        </div>
        <div class="icon-kotak">🛒</div>
    </div>

    <div class="kotak">
        <div class="teks">
            <h3>Total Penjualan</h3>
            <p class="angka"><?= $total_penjualan ?></p>
            <p class="label">transaksi</p>
        </div>
        <div class="icon-kotak">💰</div>
    </div>
</div>

<!-- ===== 2 KOLOM: GRAFIK PENJUALAN + TABEL STOK TERENDAH ===== -->
<div class="grid-2">

    <!-- KOLOM KIRI: omzet bulan ini + grafik bar 7 hari terakhir -->
    <div class="card">
        <h3>Penjualan Bulan Ini</h3>
        <p style="font-size:26px; color:#3b2415; font-weight:bold; margin-bottom:20px;">
            Rp <?= number_format($omzet_bulan_ini, 0, ',', '.') ?>
        </p>

        <?php
        // ===== SIAPKAN DATA GRAFIK BAR 7 HARI TERAKHIR =====
        $data_grafik = [];
        for ($i = 6; $i >= 0; $i--) {
            // loop dari 6 hari lalu sampai hari ini
            $tgl       = date('Y-m-d', strtotime("-$i days"));
            $tgl_tampil = date('d/m', strtotime("-$i days")); // format tampilan: 14/06
            $q         = mysqli_query($koneksi, "SELECT SUM(total) as total FROM penjualan WHERE tanggal = '$tgl'");
            $hasil     = mysqli_fetch_assoc($q);
            $data_grafik[] = [
                'tanggal' => $tgl_tampil,
                'total'   => $hasil['total'] ? $hasil['total'] : 0 // kalau hari itu ga ada penjualan, isi 0
            ];
        }

        // cari nilai tertinggi buat ngitung proporsi tinggi batang grafik
        $max_nilai = 0;
        foreach ($data_grafik as $d) {
            if ($d['total'] > $max_nilai) $max_nilai = $d['total'];
        }
        ?>

        <!-- render grafik batang pakai div, tingginya dihitung dari proporsi nilai -->
        <div style="display:flex; align-items:flex-end; gap:10px; height:160px; padding-top:10px;">
            <?php foreach ($data_grafik as $d) {
                // hitung tinggi batang proporsional terhadap nilai tertinggi (max 120px)
                $tinggi = ($max_nilai > 0) ? ($d['total'] / $max_nilai) * 120 : 0;
                if ($tinggi < 4 && $d['total'] > 0) $tinggi = 4; // minimal 4px biar keliatan kalau ada penjualan
            ?>
            <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%;">
                <!-- label nilai di atas batang, diformat jadi ribuan/jutaan biar ga terlalu panjang -->
                <div style="font-size:10px; color:#8a6a52; margin-bottom:4px; text-align:center;">
                    <?php if ($d['total'] > 0) {
                        if ($d['total'] >= 1000000)      echo number_format($d['total']/1000000, 1) . 'jt';
                        else if ($d['total'] >= 1000)    echo number_format($d['total']/1000, 0) . 'rb';
                        else                             echo $d['total'];
                    } ?>
                </div>
                <div style="width:100%; background-color:#a85d23; border-radius:4px 4px 0 0; height:<?= $tinggi ?>px;"></div>
                <div style="font-size:10px; color:#5a3621; margin-top:5px; text-align:center;"><?= $d['tanggal'] ?></div>
            </div>
            <?php } ?>
        </div>
        <div style="border-top:2px solid #eee2d8; margin-top:4px;"></div>
        <p style="font-size:11px; color:#8a6a52; margin-top:6px;">7 hari terakhir</p>
    </div>

    <!-- KOLOM KANAN: tabel stok yang jumlahnya paling sedikit -->
    <div class="card">
        <h3>Stok Terendah</h3>
        <table>
            <tr>
                <th>Nama</th>
                <th>Jenis</th>
                <th>Jumlah</th>
            </tr>
            <?php
            // ambil 2 bahan baku dengan stok paling sedikit
            $stok_bahan = mysqli_query($koneksi, "SELECT jenis_kopi as nama, jumlah_stok, satuan FROM stok_bahan ORDER BY jumlah_stok ASC LIMIT 2");
            while ($row = mysqli_fetch_assoc($stok_bahan)) {
            ?>
            <tr>
                <td><?= $row['nama'] ?></td>
                <td>Bahan</td>
                <td><?= $row['jumlah_stok'] ?> <?= $row['satuan'] ?></td>
            </tr>
            <?php } ?>

            <?php
            // ambil 2 produk jadi dengan stok paling sedikit (JOIN supaya bisa nampilin nama produk)
            $stok_produk = mysqli_query($koneksi, "SELECT produk.nama_produk as nama, stok_produk.jumlah_stok, stok_produk.satuan
                                                     FROM stok_produk
                                                     JOIN produk ON stok_produk.produk_id_produk = produk.id_produk
                                                     ORDER BY stok_produk.jumlah_stok ASC LIMIT 2");
            while ($row = mysqli_fetch_assoc($stok_produk)) {
            ?>
            <tr>
                <td><?= $row['nama'] ?></td>
                <td>Produk</td>
                <td><?= $row['jumlah_stok'] ?> <?= $row['satuan'] ?></td>
            </tr>
            <?php } ?>
        </table>
    </div>

</div>

<?php include "footer.php"; ?>
