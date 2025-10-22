<?php
// FILE: heavens/fatman/matkul/matkul_action.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF untuk aksi tulis
if (!in_array($action, ['list', 'get'], true)) {
    if (!verify_csrf($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
        die('<div class="alert alert-danger">CSRF token tidak valid.</div>');
    }
}

switch ($action) {

case 'tambah': {
    $mata_kuliah = trim($_POST['mata_kuliah'] ?? '');
    $semester    = trim($_POST['semester'] ?? '');

    if ($mata_kuliah === '' || $semester === '') {
        echo '<div class="alert alert-danger">Semua field wajib diisi.</div>';
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO tb_matkul (mata_kuliah, semester) VALUES (?, ?)");
    $ok   = $stmt->execute([$mata_kuliah, $semester]);

    echo $ok
        ? '<div class="alert alert-success">✅ Mata kuliah berhasil ditambahkan.</div>'
        : '<div class="alert alert-danger">❌ Gagal menambah data.</div>';
    break;
}

case 'get': {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM tb_matkul WHERE id = ?");
    $stmt->execute([$id]);
    header('Content-Type: application/json');
    echo json_encode($stmt->fetch() ?: []);
    break;
}

case 'edit': {
    $id           = (int)($_POST['id'] ?? 0);
    $mata_kuliah  = trim($_POST['mata_kuliah'] ?? '');
    $semester     = trim($_POST['semester'] ?? '');

    if ($id <= 0 || $mata_kuliah === '' || $semester === '') {
        echo '<div class="alert alert-danger">Semua field wajib diisi.</div>';
        exit;
    }

    $stmt = $pdo->prepare("UPDATE tb_matkul SET mata_kuliah = ?, semester = ? WHERE id = ?");
    $ok   = $stmt->execute([$mata_kuliah, $semester, $id]);

    echo $ok
        ? '<div class="alert alert-warning">✅ Mata kuliah berhasil diperbarui.</div>'
        : '<div class="alert alert-danger">❌ Gagal update data.</div>';
    break;
}

case 'hapus': {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo '<div class="alert alert-danger">ID tidak valid.</div>';
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM tb_matkul WHERE id = ?");
    $ok   = $stmt->execute([$id]);

    echo $ok
        ? '<div class="alert alert-danger">Mata kuliah berhasil dihapus.</div>'
        : '<div class="alert alert-danger">Gagal menghapus mata kuliah.</div>';
    break;
}

case 'list': {
    $rows = $pdo->query("SELECT * FROM tb_matkul ORDER BY id DESC")->fetchAll();
    if (!$rows) {
        echo '<tr><td colspan="4" class="text-center">Belum ada data.</td></tr>';
        exit;
    }

    $no = 1;
    foreach ($rows as $row) {
        echo '<tr data-id="'.(int)$row['id'].'">
                <td class="text-center">'.$no++.'</td>
                <td>'.e($row['mata_kuliah']).'</td>
                <td class="text-center">'.e($row['semester']).'</td>
                <td class="text-center">
                    <button class="btn btn-warning btn-sm btnEdit" data-id="'.$row['id'].'">Edit</button>
                    <button class="btn btn-danger btn-sm btnHapus" data-id="'.$row['id'].'">Hapus</button>
                </td>
              </tr>';
    }
    break;
}

default:
    echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
}
