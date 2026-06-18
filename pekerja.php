<?php
include "koneksi.php";
include "cek_login.php"; // tendang kalau belum login

$halaman       = "pekerja";
$judul_halaman = "Pekerja";

// halaman ini khusus Owner aja
if ($_SESSION['jabatan'] != 'Owner') {
    header("Location: index.php");
    exit;
}

$pesan = ""; // variabel buat tampil pesan error/validasi

// ===== TAMBAH PEKERJA BARU =====
if (isset($_POST['btn_simpan'])) {
    $nama     = $_POST['nama'];
    $jabatan  = $_POST['jabatan'];
    $username = $_POST['username'];
    $password = md5($_POST['password']); // enkripsi password sebelum disimpan ke db

    // cek dulu apakah username sudah dipakai orang lain
    $cek = mysqli_query($koneksi, "SELECT * FROM pekerja WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        $pesan = "Username sudah dipakai, pilih yang lain!";
    } else {
        $sql = "INSERT INTO pekerja (nama, jabatan, username, password) VALUES ('$nama', '$jabatan', '$username', '$password')";
        mysqli_query($koneksi, $sql);
        header("Location: pekerja.php");
        exit;
    }
}

// ===== UPDATE DATA PEKERJA =====
if (isset($_POST['btn_update'])) {
    $id       = $_POST['id_pekerja'];
    $nama     = $_POST['nama'];
    $jabatan  = $_POST['jabatan'];
    $username = $_POST['username'];

    // update data dasar (nama, jabatan, username)
    $sql = "UPDATE pekerja SET nama='$nama', jabatan='$jabatan', username='$username' WHERE id_pekerja=$id";
    mysqli_query($koneksi, $sql);

    // update password hanya kalau field password diisi (kalau kosong, biarkan password lama)
    if ($_POST['password'] != '') {
        $password = md5($_POST['password']);
        mysqli_query($koneksi, "UPDATE pekerja SET password='$password' WHERE id_pekerja=$id");
    }

    header("Location: pekerja.php");
    exit;
}

// ===== HAPUS PEKERJA =====
// id pekerja yang mau dihapus dikirim lewat URL: pekerja.php?hapus=3
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    // pengaman: tidak boleh hapus akun sendiri yang sedang login
    if ($id == $_SESSION['id_pekerja']) {
        $pesan = "Tidak bisa menghapus akun sendiri!";
    } else {
        mysqli_query($koneksi, "DELETE FROM pekerja WHERE id_pekerja=$id");
        header("Location: pekerja.php");
        exit;
    }
}

// ===== PENCARIAN =====
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';

// ===== PAGINASI =====
$per_halaman   = 7;
$halaman_aktif = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
if ($halaman_aktif < 1) $halaman_aktif = 1;
$mulai = ($halaman_aktif - 1) * $per_halaman;

// bangun kondisi WHERE untuk pencarian (bisa cari berdasarkan nama atau username)
$kondisi = "";
if ($cari != '') {
    $kondisi = " WHERE nama LIKE '%$cari%' OR username LIKE '%$cari%'";
}

// hitung total data untuk paginasi
$total_data    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pekerja" . $kondisi))['total'];
$total_halaman = ceil($total_data / $per_halaman);

$sql_pekerja   = "SELECT * FROM pekerja" . $kondisi . " ORDER BY id_pekerja ASC LIMIT $mulai, $per_halaman";
$hasil_pekerja = mysqli_query($koneksi, $sql_pekerja);

// simpan ke array buat dipakai dua kali: tabel + popup edit/hapus
$list_pekerja = [];
while ($row = mysqli_fetch_assoc($hasil_pekerja)) {
    $list_pekerja[] = $row;
}

include "header.php";
?>

<div class="card">

    <!-- tampilkan pesan error kalau ada (username duplikat atau hapus diri sendiri) -->
    <?php if ($pesan != "") { ?>
        <div class="pesan-error"><?= $pesan ?></div>
    <?php } ?>

    <!-- ===== FORM PENCARIAN + TOMBOL TAMBAH ===== -->
    <div class="card-head">
        <form method="get" class="form-cari" style="margin:0;">
            <input type="text" name="cari" placeholder="🔍 Cari Pekerja..." value="<?= $cari ?>">
            <button type="submit" class="btn btn-tambah">Cari</button>
        </form>
        <a href="#modal-tambah" class="btn btn-tambah">+ Tambah Pekerja</a>
    </div>

    <!-- ===== TABEL DAFTAR PEKERJA ===== -->
    <table>
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Username</th>
            <th>Jabatan</th>
            <th>Aksi</th>
        </tr>
        <?php
        $no = $mulai + 1;
        foreach ($list_pekerja as $row) {
        ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= $row['nama'] ?></td>
            <td><?= $row['username'] ?></td>
            <td><?= $row['jabatan'] ?></td>
            <td>
                <a href="#modal-edit-<?= $row['id_pekerja'] ?>" class="btn-icon btn-edit">✏️</a>
                <!-- tombol hapus tidak ditampilkan untuk akun yang sedang login (biar ga hapus diri sendiri) -->
                <?php if ($row['id_pekerja'] != $_SESSION['id_pekerja']) { ?>
                    <a href="#modal-hapus-<?= $row['id_pekerja'] ?>" class="btn-icon btn-hapus">🗑️</a>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>

    <!-- ===== PAGINASI ===== -->
    <?php if ($total_halaman > 1) { ?>
    <div class="paging">
        <?php for ($i = 1; $i <= $total_halaman; $i++) { ?>
            <a href="pekerja.php?halaman=<?= $i ?>&cari=<?= $cari ?>" class="<?= ($i == $halaman_aktif) ? 'aktif' : '' ?>"><?= $i ?></a>
        <?php } ?>
    </div>
    <?php } ?>

</div>

<!-- ===== POPUP TAMBAH PEKERJA ===== -->
<!-- action pakai fragment #modal-tambah supaya kalau ada error (username duplikat)
     popup tetap terbuka setelah halaman reload -->
<div class="modal-overlay" id="modal-tambah">
    <div class="modal-box">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Tambah Pekerja</h3>

        <!-- tampilkan pesan error khusus username duplikat di dalam popup ini -->
        <?php if ($pesan != "" && $pesan != "Tidak bisa menghapus akun sendiri!") { ?>
            <div class="pesan-error"><?= $pesan ?></div>
        <?php } ?>

        <form method="post" action="pekerja.php#modal-tambah" class="form-isi">
            <label>Nama</label>
            <input type="text" name="nama" required>

            <label>Jabatan</label>
            <select name="jabatan" required>
                <option value="">-- Pilih Jabatan --</option>
                <option value="Owner">Owner</option>
                <option value="Produksi">Produksi</option>
                <option value="Kasir">Kasir</option>
            </select>

            <label>Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" name="btn_simpan" class="btn btn-simpan">Simpan</button>
            <a href="#" class="btn btn-batal">Batal</a>
        </form>
    </div>
</div>

<!-- ===== POPUP EDIT & HAPUS PER PEKERJA ===== -->
<?php foreach ($list_pekerja as $row) { ?>

<div class="modal-overlay" id="modal-edit-<?= $row['id_pekerja'] ?>">
    <div class="modal-box">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Edit Pekerja</h3>
        <form method="post" class="form-isi">
            <input type="hidden" name="id_pekerja" value="<?= $row['id_pekerja'] ?>">

            <label>Nama</label>
            <input type="text" name="nama" value="<?= $row['nama'] ?>" required>

            <label>Jabatan</label>
            <select name="jabatan" required>
                <!-- selected ditambahkan pada jabatan yang cocok dengan data saat ini -->
                <option value="Owner"    <?= ($row['jabatan']=='Owner')    ? 'selected' : '' ?>>Owner</option>
                <option value="Produksi" <?= ($row['jabatan']=='Produksi') ? 'selected' : '' ?>>Produksi</option>
                <option value="Kasir"    <?= ($row['jabatan']=='Kasir')    ? 'selected' : '' ?>>Kasir</option>
            </select>

            <label>Username</label>
            <input type="text" name="username" value="<?= $row['username'] ?>" required>

            <!-- password boleh dikosongkan, kalau kosong password lama tidak berubah -->
            <label>Password Baru (kosongkan jika tidak ganti)</label>
            <input type="password" name="password">

            <button type="submit" name="btn_update" class="btn btn-simpan">Update</button>
            <a href="#" class="btn btn-batal">Batal</a>
        </form>
    </div>
</div>

<!-- popup hapus hanya ditampilkan untuk pekerja selain diri sendiri -->
<?php if ($row['id_pekerja'] != $_SESSION['id_pekerja']) { ?>
<div class="modal-overlay" id="modal-hapus-<?= $row['id_pekerja'] ?>">
    <div class="modal-box" style="max-width:360px; text-align:center;">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Hapus Pekerja</h3>
        <p style="margin-bottom:10px; font-size:14px;">
            Yakin mau hapus pekerja <b><?= $row['nama'] ?></b> ?
        </p>
        <a href="pekerja.php?hapus=<?= $row['id_pekerja'] ?>" class="btn btn-hapus-konfirm">Ya, Hapus</a>
        <a href="#" class="btn btn-batal">Batal</a>
    </div>
</div>
<?php } ?>

<?php } ?>

<?php include "footer.php"; ?>
