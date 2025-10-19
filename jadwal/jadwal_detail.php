<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../fatman/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo json_encode(['error' => 'ID tidak valid']); exit; }

// Detail utama praktikum
$detail = $mysqli->prepare("
  SELECT p.id, p.jurusan, p.kelas, p.semester, p.hari, p.shift,
         DATE_FORMAT(p.jam_mulai,'%H:%i') AS jam_mulai,
         DATE_FORMAT(p.jam_ahir,'%H:%i')  AS jam_ahir,
         p.catatan,
         m.mata_kuliah
  FROM tb_praktikum p
  JOIN tb_matkul m ON p.mata_kuliah = m.id
  WHERE p.id = ?
");
$detail->bind_param("i", $id);
$detail->execute();
$head = $detail->get_result()->fetch_assoc();

if (!$head) { echo json_encode(['error'=>'Data tidak ditemukan']); exit; }

// Ambil assisten
$assisten = [];
$qa = $mysqli->prepare("
  SELECT a.nama
  FROM tb_assisten_praktikum ap
  JOIN tb_assisten a ON ap.assisten_id = a.id
  WHERE ap.praktikum_id = ?
  ORDER BY a.nama ASC
");
$qa->bind_param("i", $id);
$qa->execute();
$ra = $qa->get_result();
while ($row = $ra->fetch_assoc()) { $assisten[] = $row['nama']; }

// Ambil peserta
$peserta = [];
$qp = $mysqli->prepare("
  SELECT pr.nama
  FROM tb_peserta ps
  JOIN tb_praktikan pr ON ps.praktikan_id = pr.id
  WHERE ps.praktikum_id = ?
  ORDER BY pr.nama ASC
");
$qp->bind_param("i", $id);
$qp->execute();
$rp = $qp->get_result();
while ($row = $rp->fetch_assoc()) { $peserta[] = $row['nama']; }

// Output
echo json_encode(array_merge($head, [
  'assisten' => $assisten,
  'peserta' => $peserta
]));
