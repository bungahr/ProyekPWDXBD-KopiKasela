<?php
include "koneksi.php";
include "cek_login.php"; // tendang kalau belum login

$halaman       = "penjualan";
$judul_halaman = "Penjualan";

// hanya Owner dan Kasir yang boleh akses halaman penjualan
if ($_SESSION['jabatan'] != 'Owner' && $_SESSION['jabatan'] != 'Kasir') {
    header("Location: index.php");
    exit;
}

$pesan = ""; // buat tampil pesan error kalau stok kurang

// ===== SIMPAN TRANSAKSI PENJUALAN BARU =====
if (isset($_POST['btn_simpan'])) {
    $tanggal   = $_POST['tanggal'];
    $produk_id = $_POST['produk_id_produk'];
    $jumlah    = $_POST['jumlah'];

    // ambil harga produk dari tabel produk berdasarkan id yang dipilih
    $cek_produk = mysqli_query($koneksi, "SELECT * FROM produk WHERE id_produk='$produk_id'");
    $produk     = mysqli_fetch_assoc($cek_produk);
    $harga      = $produk['harga'];
    $total      = $harga * $jumlah; // total = harga satuan x jumlah

    // cek apakah stok produk ini cukup untuk dijual
    $cek_stok = mysqli_query($koneksi, "SELECT * FROM stok_produk WHERE produk_id_produk='$produk_id'");
    $stok     = mysqli_fetch_assoc($cek_stok);

    if (!$stok || $stok['jumlah_stok'] < $jumlah) {
        // stok tidak cukup, tampilkan pesan error (popup tetap kebuka karena action pakai #modal-tambah)
        $pesan = "Stok tidak cukup untuk produk ini!";
    } else {
        // simpan transaksi penjualan, sekaligus catat siapa yang melayani (dari session)
        $sql = "INSERT INTO penjualan (tanggal, jumlah, harga, total, produk_id_produk, pekerja_id_pekerja)
                VALUES ('$tanggal', '$jumlah', '$harga', '$total', '$produk_id', '" . $_SESSION['id_pekerja'] . "')";
        mysqli_query($koneksi, $sql);

        // kurangi stok produk setelah berhasil dijual
        $stok_baru = $stok['jumlah_stok'] - $jumlah;
        mysqli_query($koneksi, "UPDATE stok_produk SET jumlah_stok='$stok_baru' WHERE id_stok_produk='" . $stok['id_stok_produk'] . "'");

        header("Location: penjualan.php");
        exit;
    }
}

// ===== AMBIL DAFTAR PRODUK UNTUK DROPDOWN (sekalian tampilkan stok tersisa) =====
// LEFT JOIN supaya produk yang belum punya stok tetap muncul (dengan stok = 0)
$daftar_produk = mysqli_query($koneksi, "SELECT produk.*, IFNULL(stok_produk.jumlah_stok, 0) as stok
                                          FROM produk
                                          LEFT JOIN stok_produk ON produk.id_produk = stok_produk.produk_id_produk");

// ===== PENCARIAN + FILTER RENTANG TANGGAL =====
$cari   = isset($_GET['cari'])   ? $_GET['cari']   : '';
$dari   = isset($_GET['dari'])   ? $_GET['dari']   : '';
$sampai = isset($_GET['sampai']) ? $_GET['sampai'] : '';

$kondisi = " WHERE 1=1";
if ($cari != '') {
    $kondisi .= " AND produk.nama_produk LIKE '%$cari%'";
}
if ($dari != '') {
    $kondisi .= " AND penjualan.tanggal >= '$dari'";
}
if ($sampai != '') {
    $kondisi .= " AND penjualan.tanggal <= '$sampai'";
}

// ===== PAGINASI =====
$per_halaman   = 7;
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman_aktif < 1) $halaman_aktif = 1;
$mulai = ($halaman_aktif - 1) * $per_halaman;

// hitung total data dengan JOIN ke produk (karena kondisi WHERE bisa filter nama produk)
$sql_hitung = "SELECT COUNT(*) as total FROM penjualan
               JOIN produk ON penjualan.produk_id_produk = produk.id_produk" . $kondisi;
$total_data    = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_hitung))['total'];
$total_halaman = ceil($total_data / $per_halaman);

// ambil data penjualan lengkap dengan nama produk dan nama pekerja (kasir)
$sql_penjualan = "SELECT penjualan.*, produk.nama_produk, pekerja.nama as nama_pekerja
                  FROM penjualan
                  JOIN produk  ON penjualan.produk_id_produk  = produk.id_produk
                  JOIN pekerja ON penjualan.pekerja_id_pekerja = pekerja.id_pekerja"
                  . $kondisi . " ORDER BY penjualan.tanggal DESC LIMIT $mulai, $per_halaman";

$hasil_penjualan = mysqli_query($koneksi, $sql_penjualan);

// simpan ke array buat dipakai dua kali: tabel + popup detail
$list_penjualan = [];
while ($row = mysqli_fetch_assoc($hasil_penjualan)) {
    $list_penjualan[] = $row;
}

include "header.php";
?>

<div class="card">

    <!-- ===== FORM PENCARIAN & FILTER TANGGAL ===== -->
    <div class="card-head">
        <form method="get" class="form-cari" style="margin:0;">
            <input type="text" name="cari" placeholder="🔍 Cari Produk..." value="<?= $cari ?>">
            <input type="date" name="dari"   value="<?= $dari ?>">
            <input type="date" name="sampai" value="<?= $sampai ?>">
            <button type="submit" class="btn btn-tambah">Cari</button>
        </form>
        <a href="#modal-tambah" class="btn btn-tambah">+ Penjualan Baru</a>
    </div>

    <!-- ===== TABEL RIWAYAT PENJUALAN ===== -->
    <table>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Produk</th>
            <th>Jumlah</th>
            <th>Harga</th>
            <th>Total</th>
            <th>Pekerja</th>
            <th>Aksi</th>
        </tr>
        <?php
        $no = $mulai + 1;
        foreach ($list_penjualan as $row) {
        ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
            <td><?= $row['nama_produk'] ?></td>
            <td><?= $row['jumlah'] ?> <?= $row['satuan'] ?></td>
            <td><?= number_format($row['harga'], 0, ',', '.') ?></td>
            <td><?= number_format($row['total'], 0, ',', '.') ?></td>
            <td><?= $row['nama_pekerja'] ?></td> <!-- nama kasir yang input transaksi -->
            <td>
                <a href="#modal-lihat-<?= $row['id_penjualan'] ?>" class="btn-icon btn-lihat">👁️</a>
            </td>
        </tr>
        <?php } ?>
    </table>

    <!-- ===== PAGINASI ===== -->
    <?php if ($total_halaman > 1) { ?>
    <div class="paging">
        <?php for ($i = 1; $i <= $total_halaman; $i++) { ?>
            <a href="penjualan.php?halaman=<?= $i ?>&cari=<?= $cari ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>" class="<?= ($i == $halaman_aktif) ? 'aktif' : '' ?>"><?= $i ?></a>
        <?php } ?>
    </div>
    <?php } ?>

</div>

<!-- ===== POPUP CATAT PENJUALAN BARU ===== -->
<!-- action pakai fragment #modal-tambah supaya kalau stok kurang,
     popup tetap kebuka setelah halaman reload dan pesan error tetap keliatan -->
<div class="modal-overlay" id="modal-tambah">
    <div class="modal-box">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Catat Penjualan Baru</h3>

        <!-- tampilkan error kalau stok tidak cukup -->
        <?php if ($pesan != "") { ?>
            <div class="pesan-error"><?= $pesan ?></div>
        <?php } ?>

        <form method="post" action="penjualan.php#modal-tambah" class="form-isi">
            <label>Tanggal</label>
            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>

            <label>Produk</label>
            <!-- dropdown produk, ditampilkan sekalian harga dan sisa stoknya -->
            <select name="produk_id_produk" required>
                <option value="">-- Pilih Produk --</option>
                <?php while ($p = mysqli_fetch_assoc($daftar_produk)) { ?>
                    <option value="<?= $p['id_produk'] ?>">
                        <?= $p['nama_produk'] ?> - Rp <?= number_format($p['harga'],0,',','.') ?> (stok: <?= $p['stok'] ?>)
                    </option>
                <?php } ?>
            </select>

            <label>Jumlah</label>
            <input type="number" name="jumlah" min="1" required>

            <button type="submit" name="btn_simpan" class="btn btn-simpan">Simpan</button>
            <a href="#" class="btn btn-batal">Batal</a>
        </form>
    </div>
</div>

<!-- ===== POPUP DETAIL PER PENJUALAN ===== -->
<?php foreach ($list_penjualan as $row) { ?>
<div class="modal-overlay" id="modal-lihat-<?= $row['id_penjualan'] ?>">
    <div class="modal-box lebar">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Detail Penjualan</h3>
        <div class="detail-grid">
            <div class="detail-item"><span>Tanggal</span><b><?= date('d-m-Y', strtotime($row['tanggal'])) ?></b></div>
            <div class="detail-item"><span>Produk</span> <b><?= $row['nama_produk'] ?></b></div>
            <div class="detail-item"><span>Jumlah</span> <b><?= $row['jumlah'] ?> <?= $row['satuan'] ?></b></div>
            <div class="detail-item"><span>Harga</span>  <b>Rp <?= number_format($row['harga'],0,',','.') ?></b></div>
            <div class="detail-item"><span>Total</span>  <b>Rp <?= number_format($row['total'],0,',','.') ?></b></div>
            <div class="detail-item"><span>Kasir</span>  <b><?= $row['nama_pekerja'] ?></b></div>
        </div>
        <a href="#" class="btn btn-batal">Tutup</a>
    </div>
</div>
<?php } ?>

<?php include "footer.php"; ?>
