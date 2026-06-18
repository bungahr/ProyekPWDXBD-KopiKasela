<?php
include "koneksi.php";
include "cek_login.php"; // tendang kalau belum login

$halaman       = "pembelian";
$judul_halaman = "Pembelian Bahan";

// hanya Owner dan bagian Produksi yang boleh akses halaman ini
if ($_SESSION['jabatan'] != 'Owner' && $_SESSION['jabatan'] != 'Produksi') {
    header("Location: index.php");
    exit;
}

// ===== SIMPAN PEMBELIAN BAHAN BARU =====
if (isset($_POST['btn_simpan'])) {
    $jenis_kopi = $_POST['jenis_kopi'];
    $supplier   = $_POST['supplier'];
    $tanggal    = $_POST['tanggal'];
    $jumlah     = $_POST['jumlah'];
    $satuan     = $_POST['satuan'];
    $harga      = $_POST['harga'];
    $total      = $jumlah * $harga; // total otomatis dihitung di sini

    // simpan transaksi pembelian ke tabel pembelian_biji_kopi
    $sql = "INSERT INTO pembelian_biji_kopi (jenis_kopi, supplier, tanggal, jumlah, satuan, harga, total)
            VALUES ('$jenis_kopi', '$supplier', '$tanggal', '$jumlah', '$satuan', '$harga', '$total')";
    mysqli_query($koneksi, $sql);

    // ambil id pembelian yang baru saja disimpan (dibutuhkan untuk update stok_bahan)
    $id_pembelian = mysqli_insert_id($koneksi);

    // cek apakah jenis kopi ini sudah ada di tabel stok_bahan atau belum
    $cek = mysqli_query($koneksi, "SELECT * FROM stok_bahan WHERE jenis_kopi='$jenis_kopi'");

    if (mysqli_num_rows($cek) > 0) {
        // sudah ada: tinggal tambahkan jumlahnya ke stok yang lama
        $stok_lama  = mysqli_fetch_assoc($cek);
        $stok_baru  = $stok_lama['jumlah_stok'] + $jumlah;
        mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah_stok='$stok_baru', pembelian_id_pembelian='$id_pembelian' WHERE id_stok_bahan='" . $stok_lama['id_stok_bahan'] . "'");
    } else {
        // belum ada: buat baris baru di tabel stok_bahan
        mysqli_query($koneksi, "INSERT INTO stok_bahan (jenis_kopi, jumlah_stok, satuan, pembelian_id_pembelian) VALUES ('$jenis_kopi', '$jumlah', '$satuan', '$id_pembelian')");
    }

    header("Location: pembelian.php");
    exit;
}

// ===== PENCARIAN + FILTER RENTANG TANGGAL =====
$cari   = isset($_GET['cari'])   ? $_GET['cari']   : '';
$dari   = isset($_GET['dari'])   ? $_GET['dari']   : '';
$sampai = isset($_GET['sampai']) ? $_GET['sampai'] : '';

// bangun kondisi WHERE secara dinamis sesuai filter yang aktif
$kondisi = " WHERE 1=1"; // trik biar bisa bebas tambah AND tanpa khawatir kondisi kosong
if ($cari != '') {
    $kondisi .= " AND (jenis_kopi LIKE '%$cari%' OR supplier LIKE '%$cari%')";
}
if ($dari != '') {
    $kondisi .= " AND tanggal >= '$dari'";
}
if ($sampai != '') {
    $kondisi .= " AND tanggal <= '$sampai'";
}

// ===== PAGINASI =====
$per_halaman   = 7;
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman_aktif < 1) $halaman_aktif = 1;
$mulai = ($halaman_aktif - 1) * $per_halaman;

$total_data    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pembelian_biji_kopi" . $kondisi))['total'];
$total_halaman = ceil($total_data / $per_halaman);

$sql_pembelian = "SELECT * FROM pembelian_biji_kopi" . $kondisi . " ORDER BY tanggal DESC LIMIT $mulai, $per_halaman";
$hasil_pembelian = mysqli_query($koneksi, $sql_pembelian);

// simpan ke array supaya bisa dipakai lagi untuk generate popup detail
$list_pembelian = [];
while ($row = mysqli_fetch_assoc($hasil_pembelian)) {
    $list_pembelian[] = $row;
}

include "header.php";
?>

<div class="card">

    <!-- ===== FORM PENCARIAN & FILTER TANGGAL ===== -->
    <div class="card-head">
        <form method="get" class="form-cari" style="margin:0;">
            <input type="text" name="cari" placeholder="🔍 Cari Bahan..." value="<?= $cari ?>">
            <input type="date" name="dari"   value="<?= $dari ?>">   <!-- tanggal mulai -->
            <input type="date" name="sampai" value="<?= $sampai ?>"> <!-- tanggal akhir -->
            <button type="submit" class="btn btn-tambah">Cari</button>
        </form>
        <a href="#modal-tambah" class="btn btn-tambah">+ Tambah Bahan</a>
    </div>

    <!-- ===== TABEL RIWAYAT PEMBELIAN ===== -->
    <table>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Nama Biji</th>
            <th>Supplier</th>
            <th>Jumlah</th>
            <th>Total</th>
            <th>Aksi</th>
        </tr>
        <?php
        $no = $mulai + 1;
        foreach ($list_pembelian as $row) {
        ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td> <!-- format tanggal jadi dd-mm-yyyy -->
            <td><?= $row['jenis_kopi'] ?></td>
            <td><?= $row['supplier'] ?></td>
            <td><?= $row['jumlah'] ?> <?= $row['satuan'] ?></td>
            <td><?= number_format($row['total'], 0, ',', '.') ?></td>
            <td>
                <!-- tombol lihat detail, buka popup modal detail pembelian -->
                <a href="#modal-lihat-<?= $row['id_pembelian'] ?>" class="btn-icon btn-lihat">👁️</a>
            </td>
        </tr>
        <?php } ?>
    </table>

    <!-- ===== PAGINASI ===== -->
    <!-- parameter cari, dari, sampai ikut dibawa di link paginasi supaya filter ga hilang -->
    <?php if ($total_halaman > 1) { ?>
    <div class="paging">
        <?php for ($i = 1; $i <= $total_halaman; $i++) { ?>
            <a href="pembelian.php?halaman=<?= $i ?>&cari=<?= $cari ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>" class="<?= ($i == $halaman_aktif) ? 'aktif' : '' ?>"><?= $i ?></a>
        <?php } ?>
    </div>
    <?php } ?>

</div>

<!-- ===== POPUP TAMBAH PEMBELIAN BAHAN ===== -->
<div class="modal-overlay" id="modal-tambah">
    <div class="modal-box">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Tambah Pembelian Bahan</h3>
        <form method="post" class="form-isi">
            <label>Jenis Kopi</label>
            <input type="text" name="jenis_kopi" placeholder="contoh: Biji Arabika" required>

            <label>Supplier</label>
            <input type="text" name="supplier" required>

            <label>Tanggal</label>
            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>

            <label>Jumlah</label>
            <input type="number" name="jumlah" required>

            <label>Satuan</label>
            <input type="text" name="satuan" value="kg">

            <label>Harga per Satuan</label>
            <input type="number" name="harga" required>

            <button type="submit" name="btn_simpan" class="btn btn-simpan">Simpan</button>
            <a href="#" class="btn btn-batal">Batal</a>
        </form>
    </div>
</div>

<!-- ===== POPUP DETAIL PER PEMBELIAN ===== -->
<!-- digenerate untuk setiap baris di tabel, id modal unik pakai id_pembelian -->
<?php foreach ($list_pembelian as $row) { ?>
<div class="modal-overlay" id="modal-lihat-<?= $row['id_pembelian'] ?>">
    <div class="modal-box lebar">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Detail Pembelian</h3>
        <div class="detail-grid">
            <div class="detail-item"><span>Tanggal</span>      <b><?= date('d-m-Y', strtotime($row['tanggal'])) ?></b></div>
            <div class="detail-item"><span>Jenis Kopi</span>   <b><?= $row['jenis_kopi'] ?></b></div>
            <div class="detail-item"><span>Supplier</span>     <b><?= $row['supplier'] ?></b></div>
            <div class="detail-item"><span>Jumlah</span>       <b><?= $row['jumlah'] ?> <?= $row['satuan'] ?></b></div>
            <div class="detail-item"><span>Harga / satuan</span><b>Rp <?= number_format($row['harga'],0,',','.') ?></b></div>
            <div class="detail-item"><span>Total</span>        <b>Rp <?= number_format($row['total'],0,',','.') ?></b></div>
        </div>
        <a href="#" class="btn btn-batal">Tutup</a>
    </div>
</div>
<?php } ?>

<?php include "footer.php"; ?>
