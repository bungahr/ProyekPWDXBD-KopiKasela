<?php
// cek_login.php - file ini dipasang di awal setiap halaman yang butuh login
// kalau user belum login (session kosong), langsung dilempar ke halaman login
if (!isset($_SESSION['id_pekerja'])) {
    header("Location: login.php");
    exit; // wajib pakai exit setelah header redirect biar kode di bawahnya ga jalan
}
?>
