<?php
include "koneksi.php"; // butuh koneksi buat akses session

// hancurkan semua data session (nama, jabatan, id pekerja, dll)
// setelah ini user harus login ulang
session_destroy();

// redirect ke halaman login
header("Location: login.php");
exit;
?>
