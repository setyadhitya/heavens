<?php
require_once __DIR__ . '/../functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
if (!verify_csrf($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SESSION['csrf_token'])) {
  die('<div class="alert alert-danger">CSRF token tidak valid.</div>');
}

switch ($action) {
  case 'tambah':
    $stmt = $mysqli->prepare("INSERT INTO tb_praktikum (mata_kuliah, jurusan, kelas, semester, hari, jam_mulai, jam_ahir, shift, assisten, catatan)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $_POST['mata_kuliah'], $_POST['jurusan'], $_POST['kelas'], $_POST['semester'],
                      $_POST['hari'], $_POST['jam_mulai'], $_POST['jam_ahir'], $_POST['shift'], $_POST['assisten'], $_POST['catatan']);
    $stmt->execute();
    echo '<div class="alert alert-success">Data berhasil ditambahkan.</div>';
    break;

  case 'get':
    $id = (int)$_GET['id'];
    $res = $mysqli->query("SELECT * FROM tb_praktikum WHERE id=$id");
    echo json_encode($res->fetch_assoc());
    break;

  case 'edit':
    $id = (int)$_POST['id'];
    $stmt = $mysqli->prepare("UPDATE tb_praktikum SET mata_kuliah=?, jurusan=?, kelas=?, semester=?, hari=?, jam_mulai=?, jam_ahir=?, shift=?, assisten=?, catatan=? WHERE id=?");
    $stmt->bind_param("ssssssssssi", $_POST['mata_kuliah'], $_POST['jurusan'], $_POST['kelas'], $_POST['semester'], $_POST['hari'],
                      $_POST['jam_mulai'], $_POST['jam_ahir'], $_POST['shift'], $_POST['assisten'], $_POST['catatan'], $id);
    $stmt->execute();
    echo '<div class="alert alert-warning">Data berhasil diperbarui.</div>';
    break;

  case 'hapus':
    $id = (int)$_POST['id'];
    $stmt = $mysqli->prepare("DELETE FROM tb_praktikum WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo '<div class="alert alert-danger">Data telah dihapus.</div>';
    break;

  default:
    echo '<div class="alert alert-danger">Aksi tidak dikenali.</div>';
}
