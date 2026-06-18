<?php
include "koneksi.php"; // butuh koneksi ke db dan session_start()

// kalau user sudah login, langsung lempar ke dashboard biar ga bisa buka halaman login lagi
if (isset($_SESSION['id_pekerja'])) {
    header("Location: index.php");
    exit;
}

$pesan = ""; // variabel buat nyimpen pesan error kalau login gagal

// proses login - dijalankan kalau form login dikirim
if (isset($_POST['btn_login'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // enkripsi password pakai MD5 sebelum dibandingkan dengan yang di db

    // cari pekerja yang cocok dengan username DAN password yang dimasukkan
    $sql    = "SELECT * FROM pekerja WHERE username = '$username' AND password = '$password'";
    $hasil  = mysqli_query($koneksi, $sql);

    if (mysqli_num_rows($hasil) > 0) {
        // kalau ketemu, ambil data pekerja dan simpan ke session
        $data = mysqli_fetch_assoc($hasil);

        $_SESSION['id_pekerja'] = $data['id_pekerja']; // buat cek login di halaman lain
        $_SESSION['nama']       = $data['nama'];        // ditampilkan di topbar
        $_SESSION['jabatan']    = $data['jabatan'];     // buat ngatur hak akses menu

        header("Location: index.php"); // login berhasil, masuk ke dashboard
        exit;
    } else {
        // username atau password salah
        $pesan = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Kopi Kasela</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-box">
        <div class="logo">☕</div>
        <div class="judul-toko">Sistem Informasi <b>Kopi Kasela</b> Gombengsari</div>

        <h2>Login</h2>

        <!-- tampilkan pesan error kalau ada -->
        <?php if ($pesan != "") { ?>
            <div class="pesan-error"><?= $pesan ?></div>
        <?php } ?>

        <form method="post">
            <label>Username</label>
            <input type="text" name="username" placeholder="Admin" required>

            <label>Password</label>
            <input type="password" name="password" placeholder="Password" required>

            <button type="submit" name="btn_login">LOGIN</button>
        </form>
    </div>
</div>

</body>
</html>
