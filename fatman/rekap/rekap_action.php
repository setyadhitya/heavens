<?php
// FILE: heavens/fatman/rekap/rekap_action.php
require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();

// Always return JSON (avoid HTML error output)
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$action = $_GET['action'] ?? '';

try {
  switch ($action) {

    // === DROPDOWN: KELAS (distinct) ===
    case 'kelas_list': {
      $stmt = $pdo->query("SELECT DISTINCT kelas FROM tb_praktikum WHERE kelas IS NOT NULL AND kelas <> '' ORDER BY kelas ASC");
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $out = [];
      foreach ($rows as $r) {
        $out[] = ['kelas' => $r['kelas']];
      }
      echo json_encode($out);
      break;
    }

    // === DROPDOWN: PRAKTIKUM (optional filter by kelas) ===
    case 'praktikum_dropdown': {
      $kelas = trim($_GET['kelas'] ?? '');
      if ($kelas !== '') {
        $stmt = $pdo->prepare("
          SELECT p.id, m.mata_kuliah, p.kelas
          FROM tb_praktikum p
          JOIN tb_matkul m ON p.mata_kuliah = m.id
          WHERE p.kelas = ?
          ORDER BY p.id DESC
        ");
        $stmt->execute([$kelas]);
      } else {
        $stmt = $pdo->query("
          SELECT p.id, m.mata_kuliah, p.kelas
          FROM tb_praktikum p
          JOIN tb_matkul m ON p.mata_kuliah = m.id
          ORDER BY p.id DESC
        ");
      }
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode($rows);
      break;
    }

    // === REKAP DATA ===
    case 'rekap': {
      $praktikum_id = (int)($_GET['praktikum_id'] ?? 0);
      if ($praktikum_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'praktikum_id invalid']);
        break;
      }

      // INFO PRAKTIKUM + MATKUL + JURUSAN (via jurusan_id)
      $stmt = $pdo->prepare("
        SELECT 
          p.id, p.jurusan_id, p.kelas, p.semester, p.shift, p.jam_mulai, p.jam_ahir,
          m.mata_kuliah,
          j.jurusan
        FROM tb_praktikum p
        JOIN tb_matkul m ON p.mata_kuliah = m.id
        LEFT JOIN tb_jurusan j ON j.id = p.jurusan_id
        WHERE p.id = ?
        LIMIT 1
      ");
      $stmt->execute([$praktikum_id]);
      $info = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$info) {
        http_response_code(404);
        echo json_encode(['error' => 'Praktikum tidak ditemukan']);
        break;
      }

      // Asisten list (untuk header)
      $stmt = $pdo->prepare("
        SELECT a.nama
        FROM tb_assisten_praktikum ap
        JOIN tb_assisten a ON a.id = ap.assisten_id
        WHERE ap.praktikum_id = ?
        ORDER BY ap.id ASC
      ");
      $stmt->execute([$praktikum_id]);
      $asistenArr = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nama');
      $asistenStr = $asistenArr ? implode(', ', $asistenArr) : '-';

      // Daftar pertemuan dari tb_kode_presensi
      $stmt = $pdo->prepare("
        SELECT pertemuan_ke, lokasi, DATE(created_at) AS tgl
        FROM tb_kode_presensi
        WHERE praktikum_id = ?
        ORDER BY pertemuan_ke ASC
      ");
      $stmt->execute([$praktikum_id]);
      $pertemuanRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $pertemuanKeys = [];
      $tabel2 = [];
      $minDate = null; 
      $maxDate = null;

      foreach ($pertemuanRows as $r) {
        $pk = (int)($r['pertemuan_ke'] ?? 0);
        if ($pk > 0 && !in_array($pk, $pertemuanKeys, true)) {
          $pertemuanKeys[] = $pk;
        }
        $tgl = $r['tgl'] ?? null;
        $tabel2[] = [
          'pertemuan_ke' => $pk ?: '-',
          'lokasi'       => $r['lokasi'] ?? '-',
          'tanggal'      => $tgl ?: '-',
        ];
        if ($tgl) {
          if (!$minDate || $tgl < $minDate) $minDate = $tgl;
          if (!$maxDate || $tgl > $maxDate) $maxDate = $tgl;
        }
      }
      sort($pertemuanKeys, SORT_NUMERIC);
      if (count($pertemuanKeys) > 10) $pertemuanKeys = array_slice($pertemuanKeys, 0, 10); // max 10 kolom

      // Peserta pada praktikum ini
      $stmt = $pdo->prepare("
        SELECT ps.praktikan_id, pr.nama
        FROM tb_peserta ps
        JOIN tb_praktikan pr ON pr.id = ps.praktikan_id
        WHERE ps.praktikum_id = ?
        ORDER BY pr.nama ASC
      ");
      $stmt->execute([$praktikum_id]);
      $peserta = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Presensi (status: 'Hadir' atau 'Tidak Hadir')
      $stmt = $pdo->prepare("
        SELECT praktikan_id, pertemuan_ke, status
        FROM tb_presensi
        WHERE praktikum_id = ?
      ");
      $stmt->execute([$praktikum_id]);
      $presensiRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Index presensi [praktikan_id][pertemuan_ke] = 1|0 (✅ = 1 kalau Hadir)
      $presIndex = [];
      foreach ($presensiRows as $r) {
        $pid = (int)$r['praktikan_id'];
        $pk  = (int)$r['pertemuan_ke'];
        $status = trim($r['status'] ?? '');
        $val = (strcasecmp($status, 'Hadir') === 0) ? 1 : 0;
        $presIndex[$pid][$pk] = $val;
      }

      // Tabel 1: Rekap Praktikan
      $tabel1 = [];
      foreach ($peserta as $p) {
        $pid = (int)$p['praktikan_id'];
        $detail = [];
        $total = 0;
        foreach ($pertemuanKeys as $k) {
          $v = $presIndex[$pid][$k] ?? 0;
          $detail[$k] = $v;
          if ($v === 1) $total++;
        }
        $maksPert = max(1, count($pertemuanKeys)); // antisipasi bagi 0
        $persen = round(($total / $maksPert) * 100, 0);
        $tabel1[] = [
          'praktikan_id'   => $pid,
          'nama_praktikan' => $p['nama'],
          'detail'         => $detail,
          'total_hadir'    => $total,
          'persen_hadir'   => $persen,
        ];
      }

      // Tabel 3: Asisten (kolom pertemuan dibiarkan strip — sesuai permintaan)
      $tabel3 = [];
      foreach ($asistenArr as $nama) {
        $tabel3[] = ['assisten_id' => null, 'nama' => $nama];
      }

      // Compose info
      $jamStr = trim(($info['jam_mulai'] ?? '-') . ' - ' . ($info['jam_ahir'] ?? '-'));
      $infoOut = [
        'jurusan'       => $info['jurusan'] ?? '-', // dari tb_jurusan
        'semester'      => $info['semester'] ?? '-',
        'shift'         => $info['shift'] ?? '-',
        'jam'           => $jamStr,
        'asisten'       => $asistenStr,
        'tanggal_awal'  => $minDate ?: '-',
        'tanggal_akhir' => $maxDate ?: '-',
      ];

      echo json_encode([
        'info'           => $infoOut,
        'pertemuan_keys' => $pertemuanKeys,
        'tabel1'         => $tabel1,
        'tabel2'         => $tabel2,
        'tabel3'         => $tabel3,
      ]);
      break;
    }

    default: {
      http_response_code(400);
      echo json_encode(['error' => 'Aksi tidak dikenali']);
      break;
    }
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'error'  => 'Terjadi kesalahan server.',
    'detail' => $e->getMessage()
  ]);
}
