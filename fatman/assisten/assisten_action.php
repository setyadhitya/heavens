<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../functions.php';
require_admin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF hanya untuk aksi write
if (!in_array($action, ['list', 'get'], true)) {
    if (!verify_csrf($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
        die('<div class="alert alert-danger">CSRF token tidak valid.</div>');
    }
}

// helper validasi
function only_digits($s) { return $s !== '' && ctype_digit($s); }
function valid_nama($s) { return $s !== '' && preg_match('/^[A-Za-z\s]+$/u', $s); } // huruf & spasi saja

switch ($action) {

    case 'tambah':
        $username = trim($_POST['username'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $nim      = trim($_POST['nim'] ?? '');
        $nomorhp  = trim($_POST['nomorhp'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = 'assisten';
        $status   = 'aktif'; // otomatis aktif saat tambah

        // Validasi wajib
        if ($username === '' || $nama === '' || $nim === '' || $nomorhp === '' || $password === '') {
            echo '<div class="alert alert-danger">Lengkapi semua field wajib.</div>';
            exit;
        }

        // Validasi pola
        if (!valid_nama($nama)) {
            echo '<div class="alert alert-danger">Nama hanya boleh huruf dan spasi.</div>';
            exit;
        }
        if (!only_digits($nim)) {
            echo '<div class="alert alert-danger">NIM harus berupa angka saja.</div>';
            exit;
        }
        if (!only_digits($nomorhp)) {
            echo '<div class="alert alert-danger">Nomor HP harus berupa angka saja.</div>';
            exit;
        }

        // Unik username
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS jml FROM tb_assisten WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        if (($exists['jml'] ?? 0) > 0) {
            echo '<div class="alert alert-danger">Username sudah digunakan.</div>';
            exit;
        }

        // Unik NIM
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS jml FROM tb_assisten WHERE nim = ?");
        $stmt->bind_param("s", $nim);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        if (($exists['jml'] ?? 0) > 0) {
            echo '<div class="alert alert-danger">NIM sudah digunakan.</div>';
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $ins = $mysqli->prepare("
            INSERT INTO tb_assisten (username, nama, nim, nomorhp, password, role, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$ins) {
            echo '<div class="alert alert-danger">Prepare gagal: ' . e($mysqli->error) . '</div>';
            exit;
        }
        $ins->bind_param("sssssss", $username, $nama, $nim, $nomorhp, $hash, $role, $status);
        if ($ins->execute()) {
            echo '<div class="alert alert-success">✅ Assisten berhasil ditambahkan (status: aktif).</div>';
        } else {
            echo '<div class="alert alert-danger">❌ Gagal menambah: ' . e($ins->error) . '</div>';
        }
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $res = $mysqli->prepare("SELECT id, username, nama, nim, nomorhp, status FROM tb_assisten WHERE id = ?");
        $res->bind_param("i", $id);
        $res->execute();
        $row = $res->get_result()->fetch_assoc();
        echo json_encode($row ?: []);
        break;

    case 'edit':
        $id       = (int)($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $nim      = trim($_POST['nim'] ?? '');
        $nomorhp  = trim($_POST['nomorhp'] ?? '');
        $password = $_POST['password'] ?? ''; // optional
        $status   = (($_POST['status'] ?? 'nonaktif') === 'aktif') ? 'aktif' : 'nonaktif';

        if ($id <= 0) {
            echo '<div class="alert alert-danger">ID tidak valid.</div>';
            exit;
        }
        // Wajib diisi saat edit sesuai aturan A
        if ($username === '' || $nama === '' || $nim === '' || $nomorhp === '') {
            echo '<div class="alert alert-danger">Username, Nama, NIM, dan Nomor HP wajib diisi.</div>';
            exit;
        }
        // Validasi pola
        if (!valid_nama($nama)) {
            echo '<div class="alert alert-danger">Nama hanya boleh huruf dan spasi.</div>';
            exit;
        }
        if (!only_digits($nim)) {
            echo '<div class="alert alert-danger">NIM harus berupa angka saja.</div>';
            exit;
        }
        if (!only_digits($nomorhp)) {
            echo '<div class="alert alert-danger">Nomor HP harus berupa angka saja.</div>';
            exit;
        }

        // Unik username kecuali diri sendiri
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS jml FROM tb_assisten WHERE username = ? AND id <> ?");
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        if (($exists['jml'] ?? 0) > 0) {
            echo '<div class="alert alert-danger">Username sudah digunakan akun lain.</div>';
            exit;
        }

        // Unik NIM kecuali diri sendiri
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS jml FROM tb_assisten WHERE nim = ? AND id <> ?");
        $stmt->bind_param("si", $nim, $id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        if (($exists['jml'] ?? 0) > 0) {
            echo '<div class="alert alert-danger">NIM sudah digunakan akun lain.</div>';
            exit;
        }

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $mysqli->prepare("
                UPDATE tb_assisten
                SET username = ?, nama = ?, nim = ?, nomorhp = ?, password = ?, status = ?
                WHERE id = ?
            ");
            if (!$upd) {
                echo '<div class="alert alert-danger">Prepare gagal: ' . e($mysqli->error) . '</div>';
                exit;
            }
            $upd->bind_param("ssssssi", $username, $nama, $nim, $nomorhp, $hash, $status, $id);
        } else {
            $upd = $mysqli->prepare("
                UPDATE tb_assisten
                SET username = ?, nama = ?, nim = ?, nomorhp = ?, status = ?
                WHERE id = ?
            ");
            if (!$upd) {
                echo '<div class="alert alert-danger">Prepare gagal: ' . e($mysqli->error) . '</div>';
                exit;
            }
            $upd->bind_param("sssssi", $username, $nama, $nim, $nomorhp, $status, $id);
        }

        if ($upd->execute()) {
            echo '<div class="alert alert-warning">✅ Data assisten berhasil diperbarui.</div>';
        } else {
            echo '<div class="alert alert-danger">❌ Gagal update: ' . e($upd->error) . '</div>';
        }
        break;

    case 'hapus':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo '<div class="alert alert-danger">ID tidak valid.</div>';
            exit;
        }
        $del = $mysqli->prepare("DELETE FROM tb_assisten WHERE id = ?");
        $del->bind_param("i", $id);
        if ($del->execute()) {
            echo '<div class="alert alert-danger">Data assisten telah dihapus.</div>';
        } else {
            echo '<div class="alert alert-danger">Gagal menghapus: ' . e($del->error) . '</div>';
        }
        break;

    case 'list':
        $result = $mysqli->query("SELECT * FROM tb_assisten ORDER BY id DESC");
        if ($result && $result->num_rows > 0) {
            $no = 1;
            while ($row = $result->fetch_assoc()) {
                echo '<tr data-id="' . (int)$row['id'] . '">';
                echo '<td class="text-center">' . $no++ . '</td>';
                echo '<td>' . e($row['username']) . '</td>';
                echo '<td>' . e($row['nama']) . '</td>';
                echo '<td>' . e($row['nim']) . '</td>';
                echo '<td>' . e($row['nomorhp']) . '</td>';
                echo '<td class="text-center">' . (($row['status'] ?? '') === 'aktif' ? '<span class="badge bg-success">aktif</span>' : '<span class="badge bg-secondary">nonaktif</span>') . '</td>';
                echo '<td class="text-center">' . e($row['created_at']) . '</td>';
                echo '<td class="text-center">
                        <button class="btn btn-warning btn-sm btnEdit" data-id="' . (int)$row['id'] . '">Edit</button>
                        <button class="btn btn-danger btn-sm btnHapus" data-id="' . (int)$row['id'] . '">Hapus</button>
                      </td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="8" class="text-center">Belum ada data.</td></tr>';
        }
        break;

    default:
        echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
        break;
}
