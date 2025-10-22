<?php
// FILE: heavens/fatman/helper/helper_action.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF untuk operasi write
if (!in_array($action, ['list', 'get'], true)) {
    if (!verify_csrf($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
        die('<div class="alert alert-danger">CSRF token tidak valid.</div>');
    }
}

switch ($action) {

case 'tambah': {
    $halaman   = trim($_POST['halaman'] ?? '');
    $nama      = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($halaman === '' || $nama === '' || $deskripsi === '') {
        echo '<div class="alert alert-danger">Semua field wajib diisi.</div>';
        exit;
    }

    // Validasi halaman unik
    $cek = $pdo->prepare("SELECT COUNT(*) FROM tb_helper WHERE halaman = ?");
    $cek->execute([$halaman]);
    if ($cek->fetchColumn() > 0) {
        echo '<div class="alert alert-danger">Nama halaman sudah memiliki panduan.</div>';
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO tb_helper (halaman, nama, deskripsi) VALUES (?, ?, ?)");
    $ok   = $stmt->execute([$halaman, $nama, $deskripsi]);

    echo $ok
        ? '<div class="alert alert-success">✅ Panduan berhasil ditambahkan.</div>'
        : '<div class="alert alert-danger">❌ Gagal menambah panduan.</div>';
    break;
}

case 'get': {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM tb_helper WHERE id = ?");
    $stmt->execute([$id]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetch() ?: []);
    break;
}

case 'edit': {
    $id        = (int)($_POST['id'] ?? 0);
    $halaman   = trim($_POST['halaman'] ?? '');
    $nama      = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($id <= 0 || $halaman === '' || $nama === '' || $deskripsi === '') {
        echo '<div class="alert alert-danger">Semua field wajib diisi.</div>';
        exit;
    }

    // Validasi halaman unik kecuali id ini
    $cek = $pdo->prepare("SELECT COUNT(*) FROM tb_helper WHERE halaman = ? AND id <> ?");
    $cek->execute([$halaman, $id]);
    if ($cek->fetchColumn() > 0) {
        echo '<div class="alert alert-danger">Nama halaman sudah digunakan panduan lain.</div>';
        exit;
    }

    $stmt = $pdo->prepare("UPDATE tb_helper SET halaman = ?, nama = ?, deskripsi = ? WHERE id = ?");
    $ok   = $stmt->execute([$halaman, $nama, $deskripsi, $id]);

    echo $ok
        ? '<div class="alert alert-warning">✅ Panduan berhasil diperbarui.</div>'
        : '<div class="alert alert-danger">❌ Gagal update panduan.</div>';
    break;
}

case 'hapus': {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo '<div class="alert alert-danger">ID tidak valid.</div>';
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM tb_helper WHERE id = ?");
    $ok   = $stmt->execute([$id]);

    echo $ok
        ? '<div class="alert alert-danger">Panduan berhasil dihapus.</div>'
        : '<div class="alert alert-danger">Gagal menghapus panduan.</div>';
    break;
}

case 'list': {
    $rows = $pdo->query("SELECT * FROM tb_helper ORDER BY id DESC")->fetchAll();
    if (!$rows) {
        echo '<tr><td colspan="4" class="text-center">Belum ada data panduan.</td></tr>';
        exit;
    }
    $no = 1;
    foreach ($rows as $row) {
        echo '<tr data-id="'.(int)$row['id'].'">
                <td class="text-center">'.$no++.'</td>
                <td>'.e($row['halaman']).'</td>
                <td>'.e($row['nama']).'</td>
                <td class="text-center">
                    <button class="btn btn-warning btn-sm btnEdit" data-id="'.$row['id'].'">
                        <i class="bi bi-pencil-square"></i> Edit
                    </button>
                    <button class="btn btn-danger btn-sm btnHapus" data-id="'.$row['id'].'">
                        <i class="bi bi-trash"></i> Hapus
                    </button>
                </td>
              </tr>';
    }
    break;
}

default:
    echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
}
