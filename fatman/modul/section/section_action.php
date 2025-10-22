<?php
// FILE: heavens/fatman/modul/section/section_action.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../functions.php';
require_admin();

$pdo = db();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF hanya untuk aksi write
if (!in_array($action, ['list', 'get'], true)) {
    if (!verify_csrf($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
        die('<div class="alert alert-danger">CSRF token tidak valid.</div>');
    }
}

/* ===== Helper ===== */
function clean_filename($name) {
    $name = preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $name);
    return trim($name, '_');
}

function save_image_upload($field, &$err = null) {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // opsional
    }
    $f = $_FILES[$field];

    if ($f['error'] !== UPLOAD_ERR_OK) {
        $err = 'Upload gagal (error code ' . (int)$f['error'] . ').';
        return false;
    }

    $fi = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($f['tmp_name']) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
    ];
    if (!isset($allowed[$mime])) {
        $err = 'File harus JPG atau PNG.';
        return false;
    }

    if ($f['size'] > 3 * 1024 * 1024) {
        $err = 'Ukuran maksimal 3MB.';
        return false;
    }

    $ext  = $allowed[$mime];
    $base = pathinfo($f['name'], PATHINFO_FILENAME);
    $safe = clean_filename($base);
    $new  = $safe . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

    $dir = __DIR__ . '/../../../guwambar/modul/section';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $dest = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $new;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        $err = 'Gagal menyimpan file upload.';
        return false;
    }

    return $new; // simpan hanya filename di DB
}

/* ===== Router ===== */
switch ($action) {

case 'tambah': {
    $id_modul       = (int)($_POST['id_modul'] ?? 0);
    $urutan         = (int)($_POST['urutan'] ?? 1);
    $judul_section  = trim($_POST['judul_section'] ?? '');
    $isi_section    = trim($_POST['isi_section'] ?? '');

    if ($id_modul <= 0) { echo '<div class="alert alert-danger">ID modul tidak valid.</div>'; exit; }
    if ($judul_section === '' || $isi_section === '') {
        echo '<div class="alert alert-danger">Judul section dan isi wajib diisi.</div>'; exit;
    }

    $err = null;
    $filename = save_image_upload('gambar_section', $err); // boleh null
    if ($filename === false) {
        echo '<div class="alert alert-danger">'. e($err) .'</div>'; exit;
    }
// Cek urutan tidak boleh sama
$cek = $pdo->prepare("SELECT COUNT(*) FROM modul_section WHERE id_modul = ? AND urutan = ?");
$cek->execute([$id_modul, $urutan]);
if ($cek->fetchColumn() > 0) {
    echo '<div class="alert alert-danger">Nomor urutan sudah dipakai. Silakan pilih urutan lain.</div>';
    exit;
}

    $ins = $pdo->prepare("
        INSERT INTO modul_section (id_modul, judul_section, isi_section, gambar_section, urutan)
        VALUES (?, ?, ?, ?, ?)
    ");
    $ok = $ins->execute([$id_modul, $judul_section, $isi_section, $filename, $urutan]);

    if ($ok) {
        echo '<div class="alert alert-success">✅ Section berhasil ditambahkan.</div>';
    } else {
        echo '<div class="alert alert-danger">❌ Gagal menambah section.</div>';
    }
    break;
}

case 'get': {
    $id_section = (int)($_GET['id_section'] ?? 0);
    $res = $pdo->prepare("SELECT * FROM modul_section WHERE id_section = ? LIMIT 1");
    $res->execute([$id_section]);
    $row = $res->fetch();
    header('Content-Type: application/json');
    echo json_encode($row ?: []);
    break;
}

case 'edit': {
    $id_section     = (int)($_POST['id_section'] ?? 0);
    $id_modul       = (int)($_POST['id_modul'] ?? 0);
    $urutan         = (int)($_POST['urutan'] ?? 1);
    $judul_section  = trim($_POST['judul_section'] ?? '');
    $isi_section    = trim($_POST['isi_section'] ?? '');
    $hapus_gambar   = isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1';

    if ($id_section <= 0 || $id_modul <= 0) { echo '<div class="alert alert-danger">ID tidak valid.</div>'; exit; }
    if ($judul_section === '' || $isi_section === '') {
        echo '<div class="alert alert-danger">Judul section dan isi wajib diisi.</div>'; exit;
    }

    // data lama
    $cur = $pdo->prepare("SELECT gambar_section FROM modul_section WHERE id_section = ? AND id_modul = ? LIMIT 1");
    $cur->execute([$id_section, $id_modul]);
    $old = $cur->fetch();
    if (!$old) { echo '<div class="alert alert-danger">Data tidak ditemukan.</div>'; exit; }

    $oldFile = $old['gambar_section'] ?? null;

    $err = null;
    $newFile = save_image_upload('gambar_section', $err);
    if ($newFile === false) {
        echo '<div class="alert alert-danger">'. e($err) .'</div>'; exit;
    }

    $gambarToSave = $oldFile;

    if ($hapus_gambar && !$newFile) {
        if (!empty($oldFile)) {
            $path = __DIR__ . '/../../../guwambar/modul/section/' . $oldFile;
            if (is_file($path)) @unlink($path);
        }
        $gambarToSave = null;
    }

    if ($newFile) {
        if (!empty($oldFile)) {
            $path = __DIR__ . '/../../../guwambar/modul/section/' . $oldFile;
            if (is_file($path)) @unlink($path);
        }
        $gambarToSave = $newFile;
    }
// Cek urutan tidak boleh sama (kecuali dirinya sendiri)
$cek = $pdo->prepare("SELECT COUNT(*) FROM modul_section WHERE id_modul = ? AND urutan = ? AND id_section <> ?");
$cek->execute([$id_modul, $urutan, $id_section]);
if ($cek->fetchColumn() > 0) {
    echo '<div class="alert alert-danger">Nomor urutan sudah dipakai section lain.</div>';
    exit;
}

    $upd = $pdo->prepare("
        UPDATE modul_section
        SET urutan = ?, judul_section = ?, isi_section = ?, gambar_section = ?
        WHERE id_section = ? AND id_modul = ?
    ");
    $ok = $upd->execute([$urutan, $judul_section, $isi_section, $gambarToSave, $id_section, $id_modul]);

    if (!empty($ok)) {
        echo '<div class="alert alert-warning">✅ Section berhasil diperbarui.</div>';
    } else {
        echo '<div class="alert alert-danger">❌ Gagal update section.</div>';
    }
    break;
}

case 'hapus': {
    $id_section = (int)($_POST['id_section'] ?? 0);
    if ($id_section <= 0) { echo '<div class="alert alert-danger">ID tidak valid.</div>'; exit; }

    $cur = $pdo->prepare("SELECT gambar_section FROM modul_section WHERE id_section = ? LIMIT 1");
    $cur->execute([$id_section]);
    $row = $cur->fetch();
    $file = $row['gambar_section'] ?? null;

    $del = $pdo->prepare("DELETE FROM modul_section WHERE id_section = ?");
    if ($del->execute([$id_section])) {
        if (!empty($file)) {
            $path = __DIR__ . '/../../../guwambar/modul/section/' . $file;
            if (is_file($path)) @unlink($path);
        }
        echo '<div class="alert alert-danger">Section telah dihapus.</div>';
    } else {
        echo '<div class="alert alert-danger">Gagal menghapus.</div>';
    }
    break;
}

case 'list': {
    $id_modul = (int)($_GET['id_modul'] ?? 0);
    if ($id_modul <= 0) { echo '<tr><td colspan="5" class="text-center text-danger">ID modul tidak valid.</td></tr>'; break; }

    $stmt = $pdo->prepare("SELECT * FROM modul_section WHERE id_modul = ? ORDER BY urutan ASC, id_section ASC");
    $stmt->execute([$id_modul]);
    $rows = $stmt->fetchAll();

    if (!empty($rows)) {
        $no = 1;
        foreach ($rows as $row) {
            echo '<tr data-id="' . (int)$row['id_section'] . '">';
            echo '<td class="text-center">' . $no++ . '</td>';
            echo '<td class="text-center">' . e($row['urutan']) . '</td>';
            echo '<td>';
            echo    '<div class="fw-semibold">' . e($row['judul_section']) . '</div>';
            $preview = mb_substr($row['isi_section'], 0, 120) . (mb_strlen($row['isi_section'])>120 ? '…' : '');
            echo    '<div class="text-muted small text-truncate" style="max-width:500px;">' . e($preview) . '</div>';
            echo '</td>';
            echo '<td class="text-center">';
            if (!empty($row['gambar_section'])) {
                echo '<img class="img-thumb" src="' . e('../../../guwambar/modul/section/' . $row['gambar_section']) . '" alt="gambar" style="width:70px;height:50px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">';
            } else {
                echo '<span class="text-muted">-</span>';
            }
            echo '</td>';
            echo '<td class="text-center">
                    <button class="btn btn-warning btn-sm btnEdit" data-id="' . (int)$row['id_section'] . '"><i class="bi bi-pencil-square"></i> Edit</button>
                    <button class="btn btn-danger btn-sm btnHapus" data-id="' . (int)$row['id_section'] . '"><i class="bi bi-trash"></i> Hapus</button>
                  </td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="5" class="text-center">Belum ada section.</td></tr>';
    }
    break;
}

default:
    echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
}
