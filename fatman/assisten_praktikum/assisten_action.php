<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!in_array($action, ['list'], true)) {
    if (!verify_csrf($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
        die('<div class="alert alert-danger">CSRF token tidak valid.</div>');
    }
}

switch ($action) {

case 'tambah_multi':
    $praktikum_id = (int)($_POST['praktikum_id'] ?? 0);
    $assisten_ids = $_POST['assisten_ids'] ?? [];

    if ($praktikum_id <= 0 || empty($assisten_ids)) {
        exit('<div class="alert alert-danger">Pilih praktikum dan minimal satu assisten.</div>');
    }

    $assisten_ids = array_unique(array_map('intval', $assisten_ids));

    $cek = $pdo->prepare("SELECT COUNT(*) FROM tb_praktikum WHERE id = ?");
    $cek->execute([$praktikum_id]);
    if (!$cek->fetchColumn()) exit('<div class="alert alert-danger">Praktikum tidak ditemukan.</div>');

    $insert = $pdo->prepare("INSERT IGNORE INTO tb_assisten_praktikum (assisten_id, praktikum_id) VALUES (?, ?)");

    $pdo->beginTransaction();
    try {
        foreach ($assisten_ids as $aid) {
            $insert->execute([$aid, $praktikum_id]);
        }
        $pdo->commit();
        echo '<div class="alert alert-success">✅ Peserta berhasil ditambahkan.</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        echo '<div class="alert alert-danger">Gagal menyimpan: '.$e->getMessage().'</div>';
    }
    break;

case 'hapus':
    $id = (int)($_POST['id'] ?? 0);
    $del = $pdo->prepare("DELETE FROM tb_assisten_praktikum WHERE id = ?");
    $del->execute([$id]);
    echo '<div class="alert alert-danger">Peserta berhasil dihapus.</div>';
    break;

case 'list':
    $stmt = $pdo->query("
        SELECT ps.id, m.mata_kuliah AS praktikum_nama, a.nim, a.nama AS assisten_nama
        FROM tb_assisten_praktikum ps
        JOIN tb_praktikum p ON ps.praktikum_id = p.id
        JOIN tb_matkul m ON p.mata_kuliah = m.id
        JOIN tb_assisten a ON ps.assisten_id = a.id
        ORDER BY ps.id DESC
    ");
    $rows = $stmt->fetchAll();
    if ($rows) {
        $no=1;
        foreach ($rows as $row) {
            echo "<tr>
                <td class='text-center'>{$no}</td>
                <td>".e($row['praktikum_nama'])."</td>
                <td>".e($row['nim'].' - '.$row['assisten_nama'])."</td>
                <td class='text-center'>
                    <button data-id='{$row['id']}' class='btn btn-danger btn-sm btnHapus'>Hapus</button>
                </td>
            </tr>";
            $no++;
        }
    } else {
        echo "<tr><td colspan='4' class='text-center'>Belum ada data peserta.</td></tr>";
    }
    break;

default:
    echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
}
