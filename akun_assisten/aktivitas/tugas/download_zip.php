<?php
// heavens/akun_assisten/aktivitas/tugas/download_zip.php
require_once __DIR__ . '/../../../fatman/functions.php';

if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'assisten') {
    die("Akses ditolak!");
}

$pdo = db();
$assisten_id  = (int)($_SESSION['user_id'] ?? 0);
$praktikum_id = (int)($_GET['praktikum_id'] ?? 0);
$tugas_id     = (int)($_GET['tugas_id'] ?? 0);

if ($praktikum_id <= 0 || $tugas_id <= 0) {
    die("Parameter tidak valid.");
}

// Validasi akses assisten
$stmt = $pdo->prepare("
    SELECT p.id, m.mata_kuliah, p.kelas
    FROM tb_assisten_praktikum ap
    JOIN tb_praktikum p ON p.id = ap.praktikum_id
    JOIN tb_matkul m ON m.id = p.mata_kuliah
    WHERE ap.assisten_id = ? AND p.id = ?
    LIMIT 1
");
$stmt->execute([$assisten_id, $praktikum_id]);
$praktikum = $stmt->fetch();
if (!$praktikum) {
    die("Anda tidak memiliki akses ke praktikum ini.");
}

// Ambil info tugas
$st = $pdo->prepare("SELECT pertemuan_ke FROM tb_tugas WHERE id = ? AND praktikum_id = ?");
$st->execute([$tugas_id, $praktikum_id]);
$tugas = $st->fetch();
if (!$tugas) {
    die("Tugas tidak ditemukan.");
}

// Ambil file tugas
$km = $pdo->prepare("
    SELECT kt.file_kumpul, pk.nama, pk.nim
    FROM tb_kumpul_tugas kt
    JOIN tb_praktikan pk ON pk.id = kt.praktikan_id
    WHERE kt.tugas_id = ? AND kt.praktikum_id = ? AND kt.file_kumpul IS NOT NULL
");
$km->execute([$tugas_id, $praktikum_id]);
$files = $km->fetchAll();

if (empty($files)) {
    die("Belum ada file tugas yang dikumpulkan.");
}

// Buat folder temp zip
$zipDir = __DIR__ . "/../../../uploads/tugas/zip_temp/";
if (!is_dir($zipDir)) mkdir($zipDir, 0777, true);

// Nama file ZIP
$zipName = "Tugas_Pertemuan_{$tugas['pertemuan_ke']}_" . 
           preg_replace('/[^A-Za-z0-9]/', '_', $praktikum['mata_kuliah']) .
           "_Kelas_" . preg_replace('/[^A-Za-z0-9]/', '_', $praktikum['kelas']) . ".zip";

$zipPath = $zipDir . $zipName;

// Hitung total size untuk batasan 100MB
$totalSize = 0;
foreach ($files as $f) {
    $totalSize += filesize($f['file_kumpul']);
}
if ($totalSize > 100 * 1024 * 1024) {
    die("Ukuran total file melebihi 100MB. ZIP dibatalkan.");
}

// Buat ZIP
$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Gagal membuat ZIP.");
}

foreach ($files as $file) {
    $cleanName = preg_replace('/[^A-Za-z0-9]/', '_', $file['nama']) . "_" . 
                 preg_replace('/[^A-Za-z0-9]/', '_', $file['nim']) . "_" . 
                 basename($file['file_kumpul']);
    $zip->addFile($file['file_kumpul'], $cleanName);
}
$zip->close();

// Download ZIP
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($zipName) . '"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);

// Hapus file zip setelah download
unlink($zipPath);
exit;
