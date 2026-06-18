<?php
include "koneksi.php";
include "cek_login.php"; // tendang kalau belum login

$halaman       = "produk";
$judul_halaman = "Produk";

// halaman ini khusus Owner, jabatan lain langsung dilempar ke dashboard
if ($_SESSION['jabatan'] != 'Owner') {
    header("Location: index.php");
    exit;
}

// ===== TAMBAH PRODUK BARU =====
if (isset($_POST['btn_simpan'])) {
    $nama_produk = $_POST['nama_produk'];
    $harga       = $_POST['harga'];
    $satuan      = $_POST['satuan'];

    $sql = "INSERT INTO produk (nama_produk, harga, satuan) VALUES ('$nama_produk', '$harga', '$satuan')";
    mysqli_query($koneksi, $sql);

    header("Location: produk.php");
    exit;
}

// ===== UPDATE PRODUK =====
if (isset($_POST['btn_update'])) {
    $id          = $_POST['id_produk'];
    $nama_produk = $_POST['nama_produk'];
    $harga       = $_POST['harga'];
    $satuan      = $_POST['satuan'];

    $sql = "UPDATE produk SET nama_produk='$nama_produk', harga='$harga', satuan='$satuan' WHERE id_produk=$id";
    mysqli_query($koneksi, $sql);

    header("Location: produk.php");
    exit;
}

// ===== HAPUS PRODUK =====
// id produk yang mau dihapus dikirim lewat URL: produk.php?hapus=3
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM produk WHERE id_produk=$id");
    header("Location: produk.php");
    exit;
}

// ===== PENCARIAN =====
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';

// ===== PAGINASI =====
$per_halaman  = 7; // berapa baris per halaman
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman_aktif < 1) $halaman_aktif = 1;
$mulai = ($halaman_aktif - 1) * $per_halaman; // offset untuk LIMIT di SQL

// hitung total data dulu buat tau ada berapa halaman
$sql_hitung = "SELECT COUNT(*) as total FROM produk";
if ($cari != '') {
    $sql_hitung .= " WHERE nama_produk LIKE '%$cari%'";
}
$total_data    = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_hitung))['total'];
$total_halaman = ceil($total_data / $per_halaman); // ceil = pembulatan ke atas

// ambil data produk sesuai halaman dan pencarian
$sql_produk = "SELECT * FROM produk";
if ($cari != '') {
    $sql_produk .= " WHERE nama_produk LIKE '%$cari%'";
}
$sql_produk .= " ORDER BY id_produk ASC LIMIT $mulai, $per_halaman";

$hasil_produk = mysqli_query($koneksi, $sql_produk);

// simpan ke array dulu supaya bisa dipakai dua kali:
// 1. buat isi tabel, 2. buat generate popup edit/hapus per baris
$list_produk = [];
while ($row = mysqli_fetch_assoc($hasil_produk)) {
    $list_produk[] = $row;
}

include "header.php";
?>

<div class="card">

    <!-- ===== FORM PENCARIAN + TOMBOL TAMBAH ===== -->
    <div class="card-head">
        <form method="get" class="form-cari" style="margin:0;">
            <input type="text" name="cari" placeholder="🔍 Cari Produk..." value="<?= $cari ?>">
            <button type="submit" class="btn btn-tambah">Cari</button>
        </form>
        <!-- klik tombol ini buka popup tambah produk di bawah -->
        <a href="#modal-tambah" class="btn btn-tambah">+ Tambah Produk</a>
    </div>

    <!-- ===== TABEL DAFTAR PRODUK ===== -->
    <table>
        <tr>
            <th>No</th>
            <th>Nama Produk</th>
            <th>Harga</th>
            <th>Satuan</th>
            <th>Aksi</th>
        </tr>
        <?php
        $no = $mulai + 1; // nomor urut mulai dari posisi halaman aktif
        foreach ($list_produk as $row) {
        ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= $row['nama_produk'] ?></td>
            <td><?= number_format($row['harga'], 0, ',', '.') ?></td> <!-- format rupiah -->
            <td><?= $row['satuan'] ?></td>
            <td>
                <!-- tombol edit dan hapus pakai anchor ke popup modal masing-masing produk -->
                <a href="#modal-edit-<?= $row['id_produk'] ?>" class="btn-icon btn-edit">✏️</a>
                <a href="#modal-hapus-<?= $row['id_produk'] ?>" class="btn-icon btn-hapus">🗑️</a>
            </td>
        </tr>
        <?php } ?>
    </table>

    <!-- ===== NAVIGASI HALAMAN (PAGINASI) ===== -->
    <?php if ($total_halaman > 1) { ?>
    <div class="paging">
        <?php for ($i = 1; $i <= $total_halaman; $i++) { ?>
            <!-- halaman aktif dikasih class 'aktif' buat styling aktif -->
            <a href="produk.php?halaman=<?= $i ?>&cari=<?= $cari ?>" class="<?= ($i == $halaman_aktif) ? 'aktif' : '' ?>"><?= $i ?></a>
        <?php } ?>
    </div>
    <?php } ?>

</div>

<!-- ===== POPUP TAMBAH PRODUK ===== -->
<div class="modal-overlay" id="modal-tambah">
    <div class="modal-box">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Tambah Produk</h3>
        <form method="post" class="form-isi">
            <label>Nama Produk</label>
            <input type="text" name="nama_produk" required>

            <label>Harga</label>
            <input type="number" name="harga" required>

            <label>Satuan</label>
            <input type="text" name="satuan" value="200 gram">

            <button type="submit" name="btn_simpan" class="btn btn-simpan">Simpan</button>
            <a href="#" class="btn btn-batal">Batal</a>
        </form>
    </div>
</div>

<!-- ===== POPUP EDIT & HAPUS (digenerate per produk) ===== -->
<!-- pakai foreach supaya setiap baris tabel punya popupnya sendiri -->
<?php foreach ($list_produk as $row) { ?>

<!-- popup edit: id unik pakai id_produk biar ga bentrok antar baris -->
<div class="modal-overlay" id="modal-edit-<?= $row['id_produk'] ?>">
    <div class="modal-box">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Edit Produk</h3>
        <form method="post" class="form-isi">
            <!-- id_produk disimpen di hidden input buat dikirim ke PHP pas submit -->
            <input type="hidden" name="id_produk" value="<?= $row['id_produk'] ?>">

            <label>Nama Produk</label>
            <input type="text" name="nama_produk" value="<?= $row['nama_produk'] ?>" required>

            <label>Harga</label>
            <input type="number" name="harga" value="<?= $row['harga'] ?>" required>

            <label>Satuan</label>
            <input type="text" name="satuan" value="<?= $row['satuan'] ?>">

            <button type="submit" name="btn_update" class="btn btn-simpan">Update</button>
            <a href="#" class="btn btn-batal">Batal</a>
        </form>
    </div>
</div>

<!-- popup konfirmasi hapus: minta konfirmasi dulu sebelum beneran hapus -->
<div class="modal-overlay" id="modal-hapus-<?= $row['id_produk'] ?>">
    <div class="modal-box" style="max-width:360px; text-align:center;">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Hapus Produk</h3>
        <p style="margin-bottom:10px; font-size:14px;">
            Yakin mau hapus produk <b><?= $row['nama_produk'] ?></b> ?
        </p>
        <!-- kalau klik ini baru benar-benar dihapus lewat URL -->
        <a href="produk.php?hapus=<?= $row['id_produk'] ?>" class="btn btn-hapus-konfirm">Ya, Hapus</a>
        <a href="#" class="btn btn-batal">Batal</a>
    </div>
</div>

<?php } ?>

<?php include "footer.php"; ?>
