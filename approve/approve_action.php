<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../functions.php';
require_login_and_redirect();

// 🔐 Hanya admin yang boleh memproses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('<div class="alert alert-danger">Akses ditolak: hanya admin.</div>');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ✅ CSRF check untuk semua aksi yang memodifikasi data
if ($action !== 'list') {
    if (!verify_csrf($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SESSION['csrf_token'])) {
        die('<div class="alert alert-danger">CSRF token tidak valid.</div>');
    }
}

switch ($action) {
    case 'approve':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo '<div class="alert alert-danger">ID tidak valid.</div>';
            exit;
        }

        // Ambil data pendaftar
        $stmt = $mysqli->prepare("SELECT * FROM tb_pendaftaran_akun WHERE id = ?");
        if (!$stmt) {
            echo '<div class="alert alert-danger">Prepare gagal: ' . e($mysqli->error) . '</div>';
            exit;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $akun = $res->fetch_assoc();

        if (!$akun) {
            echo '<div class="alert alert-danger">Data pendaftar tidak ditemukan.</div>';
            exit;
        }

        // Jika sudah approve, jangan diproses lagi
        if ($akun['status'] === 'approve') {
            echo '<div class="alert alert-success">Akun sudah berstatus APPROVE.</div>';
            exit;
        }

        // Cek duplikasi username atau NIM di tb_praktikan (opsional tapi baik untuk safety)
        $cek = $mysqli->prepare("SELECT COUNT(*) AS jml FROM tb_praktikan WHERE username = ? OR nim = ?");
        $cek->bind_param("ss", $akun['username'], $akun['nim']);
        $cek->execute();
        $dup = $cek->get_result()->fetch_assoc();
        if (($dup['jml'] ?? 0) > 0) {
            echo '<div class="alert alert-danger">Tidak bisa approve: username atau NIM sudah ada di tb_praktikan.</div>';
            exit;
        }

        // Transaksi: insert ke tb_praktikan + update status pendaftaran
        $mysqli->begin_transaction();
        try {
            // Insert ke tb_praktikan
            $role = 'praktikan';
            $statusPraktikan = 'aktif';
            $ins = $mysqli->prepare("
                INSERT INTO tb_praktikan (username, nama, nim, nomorhp, password, role, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$ins) {
                throw new Exception('Prepare insert gagal: ' . $mysqli->error);
            }
            $ins->bind_param(
                "sssssss",
                $akun['username'],
                $akun['nama'],
                $akun['nim'],
                $akun['nomorhp'],
                $akun['password'],   // sudah hash dari pendaftaran
                $role,
                $statusPraktikan
            );
            if (!$ins->execute()) {
                throw new Exception('Insert tb_praktikan gagal: ' . $ins->error);
            }

            // Update status pendaftaran -> approve
            $upd = $mysqli->prepare("UPDATE tb_pendaftaran_akun SET status='approve' WHERE id=?");
            if (!$upd) {
                throw new Exception('Prepare update gagal: ' . $mysqli->error);
            }
            $upd->bind_param("i", $id);
            if (!$upd->execute()) {
                throw new Exception('Update status pendaftaran gagal: ' . $upd->error);
            }

            $mysqli->commit();
            echo '<div class="alert alert-success">✅ Berhasil di-approve & dimasukkan ke tb_praktikan.</div>';

        } catch (Exception $ex) {
            $mysqli->rollback();
            echo '<div class="alert alert-danger">❌ Gagal approve: ' . e($ex->getMessage()) . '</div>';
        }
        break;

    case 'list':
        // Tampilkan semua pendaftar agar tombol yang sudah approve jadi hijau & disabled
        $result = $mysqli->query("SELECT * FROM tb_pendaftaran_akun ORDER BY id DESC");
        if ($result && $result->num_rows > 0) {
            $no = 1;
            while ($row = $result->fetch_assoc()) {
                echo '<tr data-id="' . (int)$row['id'] . '">';
                echo '<td class="text-center">' . $no++ . '</td>';
                echo '<td>' . e($row['username']) . '</td>';
                echo '<td>' . e($row['nama']) . '</td>';
                echo '<td>' . e($row['nim']) . '</td>';
                echo '<td>' . e($row['nomorhp']) . '</td>';
                echo '<td class="text-center">';
                if ($row['status'] === 'waiting') {
                    echo '<span class="badge bg-warning text-dark">waiting</span>';
                } else {
                    echo '<span class="badge bg-success">approve</span>';
                }
                echo '</td>';
                echo '<td class="text-center">' . e($row['created_at']) . '</td>';
                echo '<td class="text-center">';
                if ($row['status'] === 'waiting') {
                    echo '<button class="btn btn-danger btn-sm btnApprove" data-id="' . (int)$row['id'] . '" data-username="' . e($row['username']) . '">Approve</button>';
                } else {
                    echo '<button class="btn btn-success btn-sm" disabled>Approved</button>';
                }
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="8" class="text-center">Belum ada data pendaftaran.</td></tr>';
        }
        break;

    default:
        echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
        break;
}
