    </div><!-- akhir div.konten -->

</div><!-- akhir div.wrapper -->

<!-- ===== POPUP KONFIRMASI KELUAR ===== -->
<!-- modal ini dipanggil dari tombol "Keluar" di topbar pakai href="#modal-logout" -->
<div class="modal-overlay" id="modal-logout">
    <div class="modal-box" style="max-width:340px; text-align:center;">
        <a href="#" class="modal-tutup">✕</a>
        <h3>Konfirmasi Keluar</h3>
        <p style="margin-bottom:15px; font-size:14px;">Apakah Anda yakin ingin keluar?</p>
        <!-- kalau klik ini baru beneran logout, kalau klik batal modal langsung tutup -->
        <a href="logout.php" class="btn btn-hapus-konfirm">Ya, Keluar</a>
        <a href="#" class="btn btn-batal">Batal</a>
    </div>
</div>

</body>
</html>
