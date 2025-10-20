<?php
// FILE: heavens/fatman/approve/approve_action.php  (✅ FULL PDO VERSION)
require_once __DIR__ . '/../functions.php';
require_admin();
$pdo = db();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action !== 'list') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        exit('<div class="alert alert-danger">CSRF token tidak valid.</div>');
    }
}

switch ($action) {

case 'approve':
    $id = (int)($_POST['id'] ?? 0);
    if(!$id) exit('<div class="alert alert-danger">ID tidak valid.</div>');

    $stmt = $pdo->prepare("SELECT * FROM tb_pendaftaran_akun WHERE id=?");
    $stmt->execute([$id]);
    $akun = $stmt->fetch();

    if(!$akun) exit('<div class="alert alert-danger">Data tidak ditemukan.</div>');
    if($akun['status']=='approve') exit('<div class="alert alert-success">Sudah approve.</div>');

    $cek = $pdo->prepare("SELECT 1 FROM tb_praktikan WHERE username=? OR nim=?");
    $cek->execute([$akun['username'], $akun['nim']]);
    if($cek->fetch()) exit('<div class="alert alert-danger">Username/NIM sudah terdaftar.</div>');

    $pdo->beginTransaction();
    try{
        $ins = $pdo->prepare("
            INSERT INTO tb_praktikan (username,nama,nim,nomorhp,password,role,status)
            VALUES (?,?,?,?,?,'praktikan','aktif')
        ");
        $ins->execute([
            $akun['username'],
            $akun['nama'],
            $akun['nim'],
            $akun['nomorhp'],
            $akun['password']
        ]);

        $upd = $pdo->prepare("UPDATE tb_pendaftaran_akun SET status='approve' WHERE id=?");
        $upd->execute([$id]);

        $pdo->commit();
        echo '<div class="alert alert-success">✅ Berhasil APPROVE akun.</div>';
    } catch(Exception $e){
        $pdo->rollBack();
        echo '<div class="alert alert-danger">Gagal approve: '.$e->getMessage().'</div>';
    }
    break;

case 'list':
    $stmt = $pdo->query("SELECT * FROM tb_pendaftaran_akun ORDER BY id DESC");
    $rows = $stmt->fetchAll();
    if($rows){
        $no=1;
        foreach($rows as $r){
            echo "<tr>
                <td>{$no}</td>
                <td>".e($r['username'])."</td>
                <td>".e($r['nama'])."</td>
                <td>".e($r['nim'])."</td>
                <td>".e($r['nomorhp'])."</td>
                <td class='text-center'>".
                    ($r['status']=='waiting'
                    ?"<span class='badge bg-warning text-dark'>waiting</span>"
                    :"<span class='badge bg-success'>approve</span>") .
                "</td>
                <td>".e($r['created_at'])."</td>
                <td class='text-center'>".
                    ($r['status']=='waiting'
                    ?"<button class='btn btn-danger btn-sm btnApprove' data-id='{$r['id']}' data-username='".e($r['username'])."'>Approve</button>"
                    :"<button class='btn btn-success btn-sm' disabled>Approved</button>") .
                "</td>
            </tr>";
            $no++;
        }
    } else {
        echo "<tr><td colspan='8' class='text-center'>Belum ada data.</td></tr>";
    }
    break;

default:
    echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
}
