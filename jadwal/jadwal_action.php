<?php
// JSON detail praktikum (public, read-only) – PDO
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../fatman/functions.php';
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo json_encode(['error' => 'ID tidak valid']); exit; }

try {
  // Detail utama praktikum (+ matkul + jurusan)
  $stmt = $pdo->prepare("
    SELECT 
      p.id, p.kelas, p.semester, p.hari, p.shift,
      DATE_FORMAT(p.jam_mulai,'%H:%i') AS jam_mulai,
      DATE_FORMAT(p.jam_ahir,'%H:%i')  AS jam_ahir,
      p.catatan,
      m.mata_kuliah,
      j.jurusan
    FROM tb_praktikum p
    JOIN tb_matkul   m ON p.mata_kuliah = m.id
    LEFT JOIN tb_jurusan j ON j.id = p.jurusan_id
    WHERE p.id = ?
    LIMIT 1
  ");
  $stmt->execute([$id]);
  $head = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$head) { echo json_encode(['error'=>'Data tidak ditemukan']); exit; }

  // Ambil assisten
  $stmtA = $pdo->prepare("
    SELECT a.nama
    FROM tb_assisten_praktikum ap
    JOIN tb_assisten a ON ap.assisten_id = a.id
    WHERE ap.praktikum_id = ?
    ORDER BY a.nama ASC
  ");
  $stmtA->execute([$id]);
  $assisten = array_column($stmtA->fetchAll(PDO::FETCH_ASSOC), 'nama');

  // Ambil peserta (nama praktikan)
  $stmtP = $pdo->prepare("
    SELECT pr.nama
    FROM tb_peserta ps
    JOIN tb_praktikan pr ON ps.praktikan_id = pr.id
    WHERE ps.praktikum_id = ?
    ORDER BY pr.nama ASC
  ");
  $stmtP->execute([$id]);
  $peserta = array_column($stmtP->fetchAll(PDO::FETCH_ASSOC), 'nama');

  // Tambahkan label shift ramah tampil
  $shift_map = ['I'=>'Shift I','II'=>'Shift II','III'=>'Shift III','IV'=>'Shift IV','V'=>'Shift V'];
  $head['shift_label'] = $shift_map[$head['shift']] ?? $head['shift'];

  echo json_encode(array_merge($head, [
    'assisten' => $assisten,
    'peserta'  => $peserta
  ]));
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Terjadi kesalahan server.']);
}
