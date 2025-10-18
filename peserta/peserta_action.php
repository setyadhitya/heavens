<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../functions.php';
require_admin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSRF untuk aksi tulis
if (!in_array($action, ['list'], true)) {
    if (!verify_csrf($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
        die('<div class="alert alert-danger">CSRF token tidak valid.</div>');
    }
}

switch ($action) {

    // Tambah banyak peserta untuk satu praktikum
    case 'tambah_multi':
        $praktikum_id   = (int)($_POST['praktikum_id'] ?? 0);
        $praktikan_ids  = $_POST['praktikan_ids'] ?? [];

        if ($praktikum_id <= 0 || empty($praktikan_ids) || !is_array($praktikan_ids)) {
            echo '<div class="alert alert-danger">Pilih praktikum dan minimal satu praktikan.</div>';
            exit;
        }

        // Bersihkan array: numeric + unik
        $clean_ids = [];
        foreach ($praktikan_ids as $pid) {
            $pid = (int)$pid;
            if ($pid > 0) $clean_ids[$pid] = true;
        }
        $praktikan_ids = array_keys($clean_ids);

        if (empty($praktikan_ids)) {
            echo '<div class="alert alert-danger">Tidak ada praktikan yang valid.</div>';
            exit;
        }

        // Cek apakah praktikum valid
        $cekP = $mysqli->prepare("SELECT COUNT(*) AS jml FROM tb_praktikum WHERE id = ?");
        $cekP->bind_param("i", $praktikum_id);
        $cekP->execute();
        $okP = $cekP->get_result()->fetch_assoc();
        if (($okP['jml'] ?? 0) == 0) {
            echo '<div class="alert alert-danger">Praktikum tidak ditemukan.</div>';
            exit;
        }

        // Siapkan cek praktikan valid & existing duplicate
        $notFound = [];
        $duplicates = [];
        $toInsert = [];

        // Cek praktikan valid
        $stmtValid = $mysqli->prepare("SELECT COUNT(*) AS jml FROM tb_praktikan WHERE id = ?");
        // Cek duplikat peserta
        $stmtDup = $mysqli->prepare("SELECT COUNT(*) AS jml FROM tb_peserta WHERE praktikum_id = ? AND praktikan_id = ?");

        foreach ($praktikan_ids as $pid) {
            // valid praktikan?
            $stmtValid->bind_param("i", $pid);
            $stmtValid->execute();
            $v = $stmtValid->get_result()->fetch_assoc();
            if (($v['jml'] ?? 0) == 0) {
                $notFound[] = $pid;
                continue;
            }

            // sudah terdaftar?
            $stmtDup->bind_param("ii", $praktikum_id, $pid);
            $stmtDup->execute();
            $d = $stmtDup->get_result()->fetch_assoc();
            if (($d['jml'] ?? 0) > 0) {
                $duplicates[] = $pid;
                continue;
            }

            $toInsert[] = $pid;
        }

        if (empty($toInsert) && (empty($duplicates) && empty($notFound))) {
            echo '<div class="alert alert-warning">Tidak ada data untuk disimpan.</div>';
            exit;
        }

        // Insert batch dalam transaksi
        if (!empty($toInsert)) {
            $mysqli->begin_transaction();
            try {
                $ins = $mysqli->prepare("INSERT INTO tb_peserta (praktikan_id, praktikum_id) VALUES (?, ?)");
                foreach ($toInsert as $pid) {
                    $ins->bind_param("ii", $pid, $praktikum_id);
                    if (!$ins->execute()) {
                        throw new Exception($ins->error);
                    }
                }
                $mysqli->commit();
                echo '<div class="alert alert-success">✅ Berhasil menambahkan ' . count($toInsert) . ' peserta.</div>';
            } catch (Exception $e) {
                $mysqli->rollback();
                echo '<div class="alert alert-danger">❌ Gagal menyimpan peserta: ' . e($e->getMessage()) . '</div>';
            }
        }

        // Info tambahan (duplikat / tidak ditemukan)
        if (!empty($duplicates)) {
            // Ambil identitas (nim-nama) untuk pesan
            $idsIn = implode(',', array_map('intval', $duplicates));
            $resD = $mysqli->query("SELECT nim, nama FROM tb_praktikan WHERE id IN ($idsIn)");
            $dupNames = [];
            if ($resD) {
                while ($r = $resD->fetch_assoc()) {
                    $dupNames[] = e($r['nim'] . ' - ' . $r['nama']);
                }
            }
            echo '<div class="alert alert-warning">⚠️ Duplikat (sudah terdaftar): ' . (empty($dupNames) ? implode(', ', $duplicates) : implode('; ', $dupNames)) . '.</div>';
        }
        if (!empty($notFound)) {
            echo '<div class="alert alert-danger">❌ ID praktikan tidak ditemukan: ' . implode(', ', array_map('intval', $notFound)) . '.</div>';
        }
        break;

    case 'hapus':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo '<div class="alert alert-danger">ID tidak valid.</div>';
            exit;
        }
        $del = $mysqli->prepare("DELETE FROM tb_peserta WHERE id = ?");
        $del->bind_param("i", $id);
        if ($del->execute()) {
            echo '<div class="alert alert-danger">Peserta berhasil dihapus.</div>';
        } else {
            echo '<div class="alert alert-danger">Gagal menghapus: ' . e($del->error) . '</div>';
        }
        break;

    case 'list':
        $q = $mysqli->query("
          SELECT ps.id,
                 m.mata_kuliah AS praktikum_nama,
                 pr.nim, pr.nama AS praktikan_nama
          FROM tb_peserta ps
          JOIN tb_praktikum p   ON ps.praktikum_id = p.id
          JOIN tb_matkul m      ON p.mata_kuliah = m.id
          JOIN tb_praktikan pr  ON ps.praktikan_id = pr.id
          ORDER BY ps.id DESC
        ");
        if ($q && $q->num_rows > 0) {
            $no=1;
            while ($row = $q->fetch_assoc()) {
                echo '<tr data-id="' . (int)$row['id'] . '">';
                echo '<td class="text-center">' . $no++ . '</td>';
                echo '<td>' . e($row['praktikum_nama']) . '</td>';
                echo '<td>' . e($row['nim'] . ' - ' . $row['praktikan_nama']) . '</td>';
                echo '<td class="text-center">
                        <button class="btn btn-danger btn-sm btnHapus" data-id="' . (int)$row['id'] . '">Hapus</button>
                      </td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="4" class="text-center">Belum ada data peserta.</td></tr>';
        }
        break;

    default:
        echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
        break;
}
