<?php
require_once __DIR__ . '/../functions.php';
require_admin();
$pdo = db();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!in_array($action,['list'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) exit('CSRF FAILED');
}

switch ($action) {
case 'tambah_multi':
    $praktikum_id = (int)$_POST['praktikum_id'];
    $praktikan_ids = $_POST['praktikan_ids'] ?? [];

    if (!$praktikum_id || !is_array($praktikan_ids))
        exit('<div class="alert alert-danger">Data tidak lengkap.</div>');

    $insert = $pdo->prepare("INSERT IGNORE INTO tb_peserta (praktikan_id, praktikum_id) VALUES (?,?)");

    $pdo->beginTransaction();
    try {
        foreach ($praktikan_ids as $pid) {
            $insert->execute([(int)$pid, $praktikum_id]);
        }
        $pdo->commit();
        echo '<div class="alert alert-success">✅ Berhasil menambahkan peserta.</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        echo '<div class="alert alert-danger">'.$e->getMessage().'</div>';
    }
    break;

case 'hapus':
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM tb_peserta WHERE id=?");
    $stmt->execute([$id]);
    echo '<div class="alert alert-success">Peserta dihapus.</div>';
    break;

case 'list':
    $data = $pdo->query("
        SELECT ps.id,m.mata_kuliah,pr.nim,pr.nama
        FROM tb_peserta ps
        JOIN tb_praktikum p ON ps.praktikum_id=p.id
        JOIN tb_matkul m ON p.mata_kuliah=m.id
        JOIN tb_praktikan pr ON ps.praktikan_id=pr.id
        ORDER BY ps.id DESC
    ")->fetchAll();
    if ($data) {
        $no=1;
        foreach ($data as $r) {
            echo "<tr>
            <td>{$no}</td>
            <td>".e($r['mata_kuliah'])."</td>
            <td>".e($r['nim'])." - ".e($r['nama'])."</td>
            <td><button data-id='{$r['id']}' class='btn btn-danger btn-sm btnHapus'>Hapus</button></td>
            </tr>";
            $no++;
        }
    } else echo "<tr><td colspan='4'>Belum ada data</td></tr>";
    break;

default:
    echo "<div class='alert alert-danger'>Aksi tidak dikenali</div>";
}
