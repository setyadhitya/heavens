<?php
require_once __DIR__ . '/../functions.php';
require_admin();
$pdo = db();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!in_array($action,['list','get'])){
    if (!verify_csrf($_POST['csrf_token'] ?? '')) exit('CSRF FAILED');
}

function only_digits($s){return ctype_digit($s);}
function valid_name($s){return preg_match('/^[A-Za-z\s]+$/',$s);}

switch($action){

case 'list':
    $rows = $pdo->query("SELECT * FROM tb_praktikan ORDER BY id DESC")->fetchAll();
    if($rows){
        $no=1;
        foreach($rows as $r){
            echo "<tr>
            <td class='text-center'>{$no}</td>
            <td>".e($r['username'])."</td>
            <td>".e($r['nama'])."</td>
            <td>".e($r['nim'])."</td>
            <td>".e($r['nomorhp'])."</td>
            <td class='text-center'><span class='badge bg-".($r['status']=='aktif'?'success':'secondary')."'>".$r['status']."</span></td>
            <td>".e($r['created_at'])."</td>
            <td><button class='btn btn-warning btn-sm btnEdit' data-id='{$r['id']}'>Edit</button>
                <button class='btn btn-danger btn-sm btnHapus' data-id='{$r['id']}'>Hapus</button></td>
            </tr>";
            $no++;
        }
    } else echo "<tr><td colspan='8' class='text-center'>Belum ada data</td></tr>";
    break;

case 'get':
    $stmt = $pdo->prepare("SELECT * FROM tb_praktikan WHERE id=?");
    $stmt->execute([$_GET['id'] ?? 0]);
    echo json_encode($stmt->fetch() ?: []);
    break;

case 'tambah':
    $username = trim($_POST['username']);
    $nama = trim($_POST['nama']);
    $nim = trim($_POST['nim']);
    $nomorhp = trim($_POST['nomorhp']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO tb_praktikan(username,nama,nim,nomorhp,password,role,status) VALUES(?,?,?,?,?,'praktikan','aktif')");
    $stmt->execute([$username,$nama,$nim,$nomorhp,$password]);
    echo "✅ Praktikan ditambahkan";
    break;

case 'edit':
    $id = $_POST['id'];
    $username = $_POST['username'];
    $nama = $_POST['nama'];
    $nim = $_POST['nim'];
    $nomorhp = $_POST['nomorhp'];
    $status = $_POST['status'];
    if($_POST['password']){
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE tb_praktikan SET username=?,nama=?,nim=?,nomorhp=?,password=?,status=? WHERE id=?");
        $stmt->execute([$username,$nama,$nim,$nomorhp,$pass,$status,$id]);
    } else {
        $stmt = $pdo->prepare("UPDATE tb_praktikan SET username=?,nama=?,nim=?,nomorhp=?,status=? WHERE id=?");
        $stmt->execute([$username,$nama,$nim,$nomorhp,$status,$id]);
    }
    echo "✅ Praktikan diupdate";
    break;

case 'hapus':
    $stmt = $pdo->prepare("DELETE FROM tb_praktikan WHERE id=?");
    $stmt->execute([$_POST['id']]);
    echo "❌ Praktikan dihapus";
    break;

default:
    echo "Aksi tidak valid";
}
