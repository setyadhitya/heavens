<?php
// Pastikan session aktif sebelum include navbar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Heavens</a>

        <!-- Tombol toggle untuk mode HP -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu utama -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="akun.php">Akun</a></li>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                    href="dashboard.php">Dashboard</a>
                <li class="nav-item"><a class="nav-link" href="praktikum.php">Praktikum</a></li>
                <li class="nav-item"><a class="nav-link" href="modul.php">Modul</a></li>
                <li class="nav-item"><a class="nav-link" href="isi_modul.php">Isi Modul</a></li>
                <li class="nav-item"><a class="nav-link" href="rekap_presensi.php">Rekap Presensi</a></li>
                <li class="nav-item"><a class="nav-link" href="assisten.php">Asisten</a></li>
                <li class="nav-item"><a class="nav-link" href="assisten_praktikum.php">Asisten Praktikum</a></li>
                <li class="nav-item"><a class="nav-link" href="praktikan.php">Praktikan</a></li>
                <li class="nav-item"><a class="nav-link" href="peserta_praktikum.php">Peserta Praktikum</a></li>
                <li class="nav-item"><a class="nav-link" href="approve_praktikan.php">Approve Praktikan</a></li>
            </ul>

            <!-- Info user & logout -->
            <ul class="navbar-nav">
                <li class="nav-item me-2">
                    <span class="navbar-text text-white">
                        <?php echo isset($_SESSION['user_nama']) ? "👋 " . htmlspecialchars($_SESSION['user_nama']) : "Tamu"; ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>