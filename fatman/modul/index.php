<?php
// FILE: heavens/fatman/modul/modul_action.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF: hanya untuk aksi write
if (!in_array($action, ['list', 'get'], true)) {
    if (!verify_csrf($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
        die('<div class="alert alert-danger">CSRF token tidak valid.</div>');
    }
}

function clean_filename($name) {
    $name = preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $name);
    return trim($name, '_');
}

function save_image_upload($field, &$err = null) {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $err = 'Upload gagal (error code ' . (int)$f['error'] . ')';
        return false;
    }
    // Validasi MIME
    $fi = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($f['tmp_name']) ?: '';
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($allowed[$mime])) {
        $err = 'File gambar harus JPG/PNG.';
        return false;
    }
    // Batas ukuran (optional): 3MB
    if ($f['size'] > 3 * 1024 * 1024) {
        $err = 'Ukuran maksimal 3MB.';
        return false;
    }
    $ext = $allowed[$mime];
    $base = pathinfo($f['name'], PATHINFO_FILENAME);
    $safe = clean_filename($base);
    $newname = $safe . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

    $targetDir = realpath(__DIR__ . '/../../guwambar/modul');
    if ($targetDir === false) {
        // jika folder belum ada, coba buat
        $targetDir = __DIR__ . '/../../guwambar/modul';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
    }
    $dest = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newname;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        $err = 'Gagal memindahkan file upload.';
        return false;
    }
    return $newname; // simpan hanya filename di DB
}

switch ($action) {

case 'tambah': {
    $judul_modul = trim($_POST['judul_modul'] ?? '');
    $mata_kuliah = trim($_POST['mata_kuliah'] ?? '');
    $deskripsi_singkat = trim($_POST['deskripsi_singkat'] ?? '');

    if ($judul_modul === '' || $mata_kuliah === '') {
        echo '<div class="alert alert-danger">Judul modul dan Mata Kuliah wajib diisi.</div>'; exit;
    }

    $err = null;
    $filename = save_image_upload('gambar_modul', $err);
    if ($filename === false) {
        echo '<div class="alert alert-danger">'. e($err) .'</div>'; exit;
    }

    $ins = $pdo->prepare("
        INSERT INTO modul (judul_modul, mata_kuliah, deskripsi_singkat, gambar_modul, dibuat, diupdate)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $ok = $ins->execute([$judul_modul, $mata_kuliah, $deskripsi_singkat, $filename]);

    if ($ok) {
        echo '<div class="alert alert-success">✅ Modul berhasil ditambahkan.</div>';
    } else {
        echo '<div class="alert alert-danger">❌ Gagal menambah modul.</div>';
    }
    break;
}

case 'get': {
    $id = (int)($_GET['id_modul'] ?? 0);
    $res = $pdo->prepare("SELECT * FROM modul WHERE id_modul = ? LIMIT 1");
    $res->execute([$id]);
    $row = $res->fetch();
    header('Content-Type: application/json');
    echo json_encode($row ?: []);
    break;
}

case 'edit': {
    $id = (int)($_POST['id_modul'] ?? 0);
    $judul_modul = trim($_POST['judul_modul'] ?? '');
    $mata_kuliah = trim($_POST['mata_kuliah'] ?? '');
    $deskripsi_singkat = trim($_POST['deskripsi_singkat'] ?? '');
    $hapus_gambar = isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1';

    if ($id <= 0) { echo '<div class="alert alert-danger">ID tidak valid.</div>'; exit; }
    if ($judul_modul === '' || $mata_kuliah === '') {
        echo '<div class="alert alert-danger">Judul modul dan Mata Kuliah wajib diisi.</div>'; exit;
    }

    // Ambil data lama
    $cur = $pdo->prepare("SELECT gambar_modul FROM modul WHERE id_modul = ? LIMIT 1");
    $cur->execute([$id]);
    $old = $cur->fetch();
    if (!$old) { echo '<div class="alert alert-danger">Data tidak ditemukan.</div>'; exit; }
    $oldFile = $old['gambar_modul'] ?? null;

    // Upload baru kalau ada
    $err = null;
    $newFile = save_image_upload('gambar_modul', $err);
    if ($newFile === false) {
        echo '<div class="alert alert-danger">'. e($err) .'</div>'; exit;
    }

    $gambarToSave = $oldFile;

    if ($hapus_gambar && !$newFile) {
        // hapus gambar lama
        if (!empty($oldFile)) {
            $path = __DIR__ . '/../../guwambar/modul/' . $oldFile;
            if (is_file($path)) @unlink($path);
        }
        $gambarToSave = null;
    }

    if ($newFile) {
        // ada upload baru -> ganti, hapus lama
        if (!empty($oldFile)) {
            $path = __DIR__ . '/../../guwambar/modul/' . $oldFile;
            if (is_file($path)) @unlink($path);
        }
        $gambarToSave = $newFile;
    }

    $upd = $pdo->prepare("
        UPDATE modul
        SET judul_modul = ?, mata_kuliah = ?, deskripsi_singkat = ?, gambar_modul = ?, diupdate = NOW()
        WHERE id_modul = ?
    ");
    $ok = $upd->execute([$judul_modul, $mata_kuliah, $deskripsi_singkat, $gambarToSave, $id]);

    if (!empty($ok)) {
        echo '<div class="alert alert-warning">✅ Modul berhasil diperbarui.</div>';
    } else {
        echo '<div class="alert alert-danger">❌ Gagal update modul.</div>';
    }
    break;
}

case 'hapus': {
    $id = (int)($_POST['id_modul'] ?? 0);
    if ($id <= 0) { echo '<div class="alert alert-danger">ID tidak valid.</div>'; exit; }

    // ambil file untuk dihapus
    $cur = $pdo->prepare("SELECT gambar_modul FROM modul WHERE id_modul = ? LIMIT 1");
    $cur->execute([$id]);
    $row = $cur->fetch();
    $file = $row['gambar_modul'] ?? null;

    $del = $pdo->prepare("DELETE FROM modul WHERE id_modul = ?");
    if ($del->execute([$id])) {
        if (!empty($file)) {
            $path = __DIR__ . '/../../guwambar/modul/' . $file;
            if (is_file($path)) @unlink($path);
        }
        echo '<div class="alert alert-danger">Data modul telah dihapus.</div>';
    } else {
        echo '<div class="alert alert-danger">Gagal menghapus.</div>';
    }
    break;
}

case 'list': {
    $stmt = $pdo->query("SELECT * FROM modul ORDER BY id_modul DESC");
    $rows = $stmt->fetchAll();
    if (!empty($rows)) {
        $no = 1;
        foreach ($rows as $row) {
            echo '<tr data-id="' . (int)$row['id_modul'] . '">';
            echo '<td class="text-center">' . $no++ . '</td>';
            echo '<td>' . e($row['judul_modul']) . '</td>';
            echo '<td>' . e($row['mata_kuliah']) . '</td>';
            echo '<td class="text-center">';
            if (!empty($row['gambar_modul'])) {
                echo '<img class="img-thumb" src="' . e('../../guwambar/modul/' . $row['gambar_modul']) . '" alt="gambar">';
            } else {
                echo '<span class="text-muted">-</span>';
            }
            echo '</td>';
            echo '<td class="text-center">' . e($row['dibuat']) . '</td>';
            echo '<td class="text-center">
                <button class="btn btn-warning btn-sm btnEdit" data-id="' . (int)$row['id_modul'] . '">Edit</button>
                <button class="btn btn-danger btn-sm btnHapus" data-id="' . (int)$row['id_modul'] . '">Hapus</button>
            </td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6" class="text-center">Belum ada data.</td></tr>';
    }
    break;
}

default:
    echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
}
