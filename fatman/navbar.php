<?php
// Pastikan session aktif sebelum include navbar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="/heavens/index.php">Heavens</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="akunDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Akun
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="akunDropdown">
                        <li><a class="dropdown-item" href="/heavens/akun.php">Profil</a></li>
                        <li><a class="dropdown-item" href="/heavens/register.php">Tambah Akun (Register)</a></li>
                    </ul>
                </li>

                <li class="nav-item"><a class="nav-link" href="/heavens/praktikum">Praktikum</a></li>
                <li class="nav-item"><a class="nav-link" href="/heavens/modul">Modul</a></li>
                <li class="nav-item"><a class="nav-link" href="/heavens/isi_modul">Isi Modul</a></li>
                <li class="nav-item"><a class="nav-link" href="/heavens/rekap">Rekap Presensi</a></li>
                <li class="nav-item"><a class="nav-link" href="/heavens/assisten">Asisten</a></li>
                <li class="nav-item"><a class="nav-link" href="/heavens/assisten_praktikum">Asisten Praktikum</a></li>
                <li class="nav-item"><a class="nav-link" href="/heavens/praktikan">Praktikan</a></li>
                <li class="nav-item"><a class="nav-link" href="/heavens/peserta">Peserta Praktikum</a></li>
                <li class="nav-item"><a class="nav-link" href="/heavens/approve">Approve Praktikan</a></li>

            </ul>

            <ul class="navbar-nav">
                <li class="nav-item me-2">
                    <span class="navbar-text text-white">
                        <?php echo isset($_SESSION['user_nama']) ? "👋 " . htmlspecialchars($_SESSION['user_nama']) : "Tamu"; ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a href="/heavens/fatman/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
