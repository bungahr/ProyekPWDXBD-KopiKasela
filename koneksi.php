<?php
// koneksi.php - file ini dipanggil duluan sebelum semua halaman lain
// fungsinya buat nyambungin PHP ke database MySQL
session_start(); // mulai session biar bisa nyimpen data login

// konfigurasi database - sesuaikan kalau nama db atau passwordnya beda
$host    = "localhost";   // server database, biasanya localhost kalau di lokal
$user    = "root";        // username database
$pass    = "";            // password database (kosong kalau pakai XAMPP default)
$nama_db = "db_kopi_kasela"; // nama database yang dipakai

// coba koneksi ke database
$koneksi = mysqli_connect($host, $user, $pass, $nama_db);

// kalau koneksi gagal, hentikan program dan tampilkan pesan error
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
