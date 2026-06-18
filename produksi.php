<?php
include "koneksi.php";
include "cek_login.php"; // tendang kalau belum login

$halaman       = "produksi";
$judul_halaman = "Produksi";

// hanya Owner dan bagian Produksi yang boleh akses
if ($_SESSION['jabatan'] != 'Owner' && $_SESSION['jabatan'] != 'Produksi') {
    header("Location: index.php");
    exit;
}

$pesan = ""; // buat tampil error kalau bahan tidak cukup

// ===== SIMPAN PRODUKSI BARU =====
if (isset($_POST['btn_simpan'])) {
    $tanggal             = $_POST['tanggal'];
    $jumlah_produksi     = $_POST['jumlah_produksi'];
    $satuan              = $_POST['satuan'];
    $produk_id           = $_POST['produk_id_produk'];
    $pekerja_id          = $_POST['pekerja_id_pekerja'];
    $stok_bahan_id       = $_POST['stok_bahan_id_stok_bahan'];
    $jumlah_bahan_dipakai = $_POST['jumlah_bahan_dipakai'];

    // cek dulu apakah stok bahan baku cukup untuk produksi ini
    $cek_bahan = mysqli_query($koneksi, "SELECT * FROM stok_bahan WHERE id_stok_bahan='$stok_bahan_id'");
    $bahan     = mysqli_fetch_assoc($cek_bahan);

    if (!$bahan || $bahan['jumlah_stok'] < $jumlah_bahan_dipakai) {
        // bahan tidak cukup, tampilkan pesan error (popup tetap kebuka karena action pakai #modal-tambah)
        $pesan = "Stok bahan tidak cukup untuk produksi ini!";
    } else {
        // simpan catatan produksi ke database
        $sql = "INSERT INTO produksi (tanggal, jumlah_produksi, satuan, produk_id_produk, pekerja_id_pekerja, stok_bahan_id_stok_bahan)
                VALUES ('$tanggal', '$jumlah_produksi', '$satuan', '$produk_id', '$pekerja_id', '$stok_bahan_id')";
        mysqli_query($koneksi, $sql);

        // kurangi stok bahan baku karena sudah dikonsumsi untuk produksi
        $stok_bahan_baru = $bahan['jumlah_stok'] - $jumlah_bahan_dipakai;
        mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah_stok='$stok_bahan_baru' WHERE id_stok_bahan='$stok_bahan_id'");

        // tambah stok produk jadi hasil produksi ini
        $cek = mysqli_query($koneksi, "SELECT * FROM stok_produk WHERE produk_id_produk='$produk_id'");

        if (mysqli_num_rows($cek) > 0) {
            // produk sudah ada stoknya: tinggal tambah jumlahnya
            $stok_lama = mysqli_fetch_assoc($cek);
            $stok_baru = $stok_lama['jumlah_stok'] + $jumlah_produksi;
            mysqli_query($koneksi, "UPDATE stok_produk SET jumlah_stok='$stok_baru' WHERE id_stok_produk='" . $stok_lama['id_stok_produk'] . "'");
        } else {
            // produk belum punya stok sama sekali: buat baris baru
            mysqli_query($koneksi, "INSERT INTO stok_produk (jumlah_stok, satuan, produk_id_produk) VALUES ('$jumlah_produksi', '$satuan', '$produk_id')");
        }

        header("Location: produksi.php");
        exit;
    }
}

// ===== AMBIL DATA UNTUK DROPDOWN DI FORM ===== 
$daftar_produk  = mysqli_query($koneksi, "SELECT * FROM produk ORDER BY nama_produk ASC");
$daftar_pekerja = mysqli_query($koneksi, "SELECT * FROM pekerja ORDER BY nama ASC");
$daftar_bahan   = mysqli_query($koneksi, "SELECT * FROM stok_bahan ORDER BY jenis_kopi ASC");

// ===== PENCARIAN + FILTER RENTANG TANGGAL =====
$cari   = isset($_GET['cari'])   ? $_GET['cari']   : '';
$dari   = isset($_GET['dari'])   ? $_GET['dari']   : '';
$sampai = isset($_GET['sampai']) ? $_GET['sampai'] : '';

$kondisi = " WHERE 1=1";
if ($cari != '') {
    $kondisi .= " AND produk.nama_produk LIKE '%$cari%'";
}
if ($dari != '') {
    $kondisi .= " AND produksi.tanggal >= '$dari'";
}
if ($sampai != '') {
    $kondisi .= " AND produksi.tanggal <= '$sampai'";
}

// ===== PAGINASI =====
$per_halaman   = 7;
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman_aktif < 1) $halaman_aktif = 1;
$mulai = ($halaman_aktif - 1) * $per_halaman;

// hitung total data (perlu JOIN produk karena kondisi bisa filter nama produk)
$sql_hitung = "SELECT COUNT(*) as total FROM produksi
               JOIN produk ON produksi.produk_id_produk = produk.id_produk" . $kondisi;
$total_data    = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_hitung))['total'];
$total_halaman = ceil($total_data / $per_halaman);

// ambil data produksi dengan join ke 3 tabel: produk, pekerja, dan stok_bahan
$sql_produksi = "SELECT produksi.*, produk.nama_produk, pekerja.nama as nama_pekerja, stok_bahan.jenis_kopi as nama_bahan
                 FROM produksi
                 JOIN produk     ON produksi.produk_id_produk          = produk.id_produk
                 JOIN pekerja    ON produksi.pekerja_id_pekerja         = pekerja.id_pekerja
                 JOIN stok_bahan ON produksi.stok_bahan_id_stok_bahan   = stok_bahan.id_stok_bahan"
                 . $kondisi . " ORDER BY produksi.tanggal DESC LIMIT $mulai, $per_halaman";

$hasil_produksi = mysqli_query($koneksi, $sql_produksi);

// simpan ke array buat dipakai dua kali: tabel + popup detail
$list_produksi = [];
while ($row = mysqli_fetch_assoc($hasil_produksi)) {
    $list_produksi[] = $row;
}

include "header.php";
?>

<div class="card">

    <!-- tampilkan error kalau bahan tidak cukup -->
    <?php if ($pesan != "") { ?>
        <div class="pesan-error"><?= $pesan ?></div>
    <?php } ?>

    <!-- ===== FORM PENCARIAN & FILTER TANGGAL ===== -->
    <div class="card-head">
        <form method="get" class="form-cari" style="margin:0;">
            <input type="text" name="cari" placeholder="🔍 Cari Produk..." value="<?= $cari ?>">
            <input type="date" name="dari"   value="<?= $dari ?>">
            <input type="date" name="sampai" value="<?= $sampai ?>">
            <button type="submit" class="btn btn-tambah">Cari</button>
        </form>
        <a href="#modal-tambah" class="btn btn-tambah">+ Produksi Baru</a>
    </div>

    <!-- ===== TABEL RIWAYAT PRODUKSI ===== -->
    <table>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Produk</th>
            <th>Bahan Dipakai</th>
            <th>Jumlah Produksi</th>
            <th>Pekerja</th>
            <th>Aksi</th>
        </tr>
        <?php
        $no = $mulai + 1;
        foreach ($list_produksi as $row) {
        ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
            <td><?= $row['nama_produk'] ?></td>
            <td><?= $row['nama_bahan'] ?></td>
            <td><?= $row['jumlah_produksi'] ?> <?= $row['satuan'] ?></td>
            <td><?= $row['nama_pekerja'] ?></td>
            <td>
                <a href="#modal-lihat-<?= $row['id_produksi'] ?>" class="btn-icon btn-lihat">👁️</a>
            </td>
        </tr>
        <?php } ?>
    </table>

    <!-- ===== PAGINASI ===== -->
    <?php if ($total_halaman > 1) { ?>
    <div class="paging">
        <?php for ($i = 1; $i <= $total_halaman; $i++) { ?>
            <a href="produksi.php?halaman=<?= $i ?>&cari=<?= $cari ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>" class="<?= ($i == $halaman_aktif) ? 'aktif' : '' ?>"><?= $i ?></a>
        <?php } ?>
    </div>
    <?php } ?>

</div>

<!-- ===== POPUP CATAT PRODUKSI BARU ===== -->
<!-- action pakai fragment #modal-tambah supaya kalau bahan kurang,
     popup tetap kebuka setelah halaman reload dan error tetap keliatan -->
<div class="modal-overlay" id="modal-tambah">
    <div class="modal-box">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Catat Produksi Baru</h3>

        <?php if ($pesan != "") { ?>
            <div class="pesan-error"><?= $pesan ?></div>
        <?php } ?>

        <form method="post" action="produksi.php#modal-tambah" class="form-isi">
            <label>Tanggal</label>
            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>

            <label>Produk</label>
            <!-- dropdown produk yang akan diproduksi -->
            <select name="produk_id_produk" required>
                <option value="">-- Pilih Produk --</option>
                <?php while ($p = mysqli_fetch_assoc($daftar_produk)) { ?>
                    <option value="<?= $p['id_produk'] ?>"><?= $p['nama_produk'] ?></option>
                <?php } ?>
            </select>

            <label>Jumlah Produksi</label>
            <input type="number" name="jumlah_produksi" required>

            <label>Satuan</label>
            <input type="text" name="satuan" value="pcs">

            <label>Bahan Baku yang Dipakai</label>
            <!-- dropdown bahan, ditampilkan sekalian sisa stoknya -->
            <select name="stok_bahan_id_stok_bahan" required>
                <option value="">-- Pilih Bahan --</option>
                <?php while ($b = mysqli_fetch_assoc($daftar_bahan)) { ?>
                    <option value="<?= $b['id_stok_bahan'] ?>"><?= $b['jenis_kopi'] ?> (stok: <?= $b['jumlah_stok'] ?> <?= $b['satuan'] ?>)</option>
                <?php } ?>
            </select>

            <label>Jumlah Bahan yang Dipakai</label>
            <input type="number" name="jumlah_bahan_dipakai" placeholder="contoh: 2" required>

            <label>Pekerja</label>
            <!-- dropdown pekerja yang menjalankan produksi ini -->
            <select name="pekerja_id_pekerja" required>
                <option value="">-- Pilih Pekerja --</option>
                <?php while ($pk = mysqli_fetch_assoc($daftar_pekerja)) { ?>
                    <option value="<?= $pk['id_pekerja'] ?>"><?= $pk['nama'] ?> (<?= $pk['jabatan'] ?>)</option>
                <?php } ?>
            </select>

            <button type="submit" name="btn_simpan" class="btn btn-simpan">Simpan</button>
            <a href="#" class="btn btn-batal">Batal</a>
        </form>
    </div>
</div>

<!-- ===== POPUP DETAIL PER PRODUKSI ===== -->
<?php foreach ($list_produksi as $row) { ?>
<div class="modal-overlay" id="modal-lihat-<?= $row['id_produksi'] ?>">
    <div class="modal-box lebar">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Detail Produksi</h3>
        <div class="detail-grid">
            <div class="detail-item"><span>Tanggal</span>         <b><?= date('d-m-Y', strtotime($row['tanggal'])) ?></b></div>
            <div class="detail-item"><span>Produk</span>          <b><?= $row['nama_produk'] ?></b></div>
            <div class="detail-item"><span>Bahan Dipakai</span>   <b><?= $row['nama_bahan'] ?></b></div>
            <div class="detail-item"><span>Jumlah Produksi</span> <b><?= $row['jumlah_produksi'] ?> <?= $row['satuan'] ?></b></div>
            <div class="detail-item"><span>Pekerja</span>         <b><?= $row['nama_pekerja'] ?></b></div>
        </div>
        <a href="#" class="btn btn-batal">Tutup</a>
    </div>
</div>
<?php } ?>

<?php include "footer.php"; ?>
