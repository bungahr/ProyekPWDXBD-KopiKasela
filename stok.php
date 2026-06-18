<?php
include "koneksi.php";
include "cek_login.php"; // tendang kalau belum login

$halaman       = "stok";
$judul_halaman = "Stok";

// ambil parameter tab (semua/bahan/produk) dan kata pencarian dari URL
// kalau ga ada, set ke nilai default
$tab  = isset($_GET['tab'])  ? $_GET['tab']  : 'semua';
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';

include "header.php";
?>

<div class="card">

    <!-- ===== TAB FILTER + FORM PENCARIAN ===== -->
    <div class="card-head">
        <!-- tab buat filter: Semua, Bahan, atau Produk -->
        <div class="tab-stok">
            <a href="stok.php?tab=semua"  class="<?= ($tab=='semua')   ? 'aktif' : '' ?>">Semua</a>
            <a href="stok.php?tab=bahan"  class="<?= ($tab=='bahan')   ? 'aktif' : '' ?>">Bahan</a>
            <a href="stok.php?tab=produk" class="<?= ($tab=='produk')  ? 'aktif' : '' ?>">Produk</a>
        </div>

        <!-- form pencarian, input hidden buat jaga tab tetap terpilih saat submit -->
        <form method="get" class="form-cari" style="margin:0;">
            <input type="hidden" name="tab" value="<?= $tab ?>">
            <input type="text" name="cari" placeholder="🔍 Cari Stok..." value="<?= $cari ?>">
            <button type="submit" class="btn btn-tambah">Cari</button>
        </form>
    </div>

    <!-- ===== TABEL STOK ===== -->
    <table>
        <tr>
            <th>No</th>
            <th>Jenis</th>
            <th>Nama</th>
            <th>Jumlah</th>
            <th>Satuan</th>
            <th>Status</th>
        </tr>

        <?php
        $no = 1;

        // ===== BAGIAN STOK BAHAN BAKU =====
        // ditampilkan kalau tab aktif adalah "semua" atau "bahan"
        if ($tab == 'semua' || $tab == 'bahan') {
            $sql_bahan = "SELECT * FROM stok_bahan";

            // tambah filter pencarian kalau ada keyword
            if ($cari != '') {
                $sql_bahan .= " WHERE jenis_kopi LIKE '%$cari%'";
            }
            $data_bahan = mysqli_query($koneksi, $sql_bahan);

            while ($row = mysqli_fetch_assoc($data_bahan)) {
        ?>
        <tr>
            <td><?= $no++ ?></td>
            <td>Bahan</td>
            <td><?= $row['jenis_kopi'] ?></td>
            <td><?= $row['jumlah_stok'] ?></td>
            <td><?= $row['satuan'] ?></td>
            <td>
                <!-- stok di bawah 10 dianggap "Rendah", kalau 10 ke atas "Aman" -->
                <?php if ($row['jumlah_stok'] < 10) { ?>
                    <span class="status-rendah">Rendah</span>
                <?php } else { ?>
                    <span class="status-aman">Aman</span>
                <?php } ?>
            </td>
        </tr>
        <?php
            }
        }

        // ===== BAGIAN STOK PRODUK JADI =====
        // ditampilkan kalau tab aktif adalah "semua" atau "produk"
        if ($tab == 'semua' || $tab == 'produk') {
            // JOIN ke tabel produk buat dapetin nama produk (stok_produk cuma nyimpen id)
            $sql_produk = "SELECT stok_produk.*, produk.nama_produk
                            FROM stok_produk
                            JOIN produk ON stok_produk.produk_id_produk = produk.id_produk";

            // tambah filter pencarian kalau ada keyword
            if ($cari != '') {
                $sql_produk .= " WHERE produk.nama_produk LIKE '%$cari%'";
            }
            $data_produk = mysqli_query($koneksi, $sql_produk);

            while ($row = mysqli_fetch_assoc($data_produk)) {
        ?>
        <tr>
            <td><?= $no++ ?></td>
            <td>Produk</td>
            <td><?= $row['nama_produk'] ?></td>
            <td><?= $row['jumlah_stok'] ?></td>
            <td><?= $row['satuan'] ?></td>
            <td>
                <!-- sama kayak bahan, stok di bawah 10 dianggap "Rendah" -->
                <?php if ($row['jumlah_stok'] < 10) { ?>
                    <span class="status-rendah">Rendah</span>
                <?php } else { ?>
                    <span class="status-aman">Aman</span>
                <?php } ?>
            </td>
        </tr>
        <?php
            }
        }
        ?>
    </table>

</div>

<?php include "footer.php"; ?>
