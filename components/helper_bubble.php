<?php
// FILE: heavens/components/helper_bubble.php

// Cara pakai pada halaman:
// $currentPage = 'modul'; // atau sesuai tb_helper.halaman
// include __DIR__ . '/../components/helper_bubble.php';

if (!isset($currentPage) || trim($currentPage) === '') return;

require_once __DIR__ . '/../fatman/functions.php';
$pdo = db();

$stmt = $pdo->prepare("SELECT * FROM tb_helper WHERE halaman = ? LIMIT 1");
$stmt->execute([$currentPage]);
$helper = $stmt->fetch();

if (!$helper) return; // jika tidak ada data di DB, jangan tampilkan bubble
?>

<style>
  .helper-bubble {
    position: fixed;
    right: 20px;
    bottom: 20px;
    width: 52px;
    height: 52px;
    background: #000;
    color: white;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 24px;
    cursor: pointer;
    z-index: 9999;
    box-shadow: 0 5px 12px rgba(0,0,0,.3);
    transition: 0.2s;
  }
  .helper-bubble:hover { transform: scale(1.08); }

  .helper-popup {
    position: fixed;
    right: 90px;
    bottom: 25px;
    max-width: 320px;
    background: #e8f4ff;
    border-left: 4px solid #0d6efd;
    border-radius: 8px;
    padding: 12px 15px;
    z-index: 9998;
    box-shadow: 0 6px 16px rgba(0,0,0,.15);
    display: none;
  }
  .helper-popup h6 { margin: 0; font-weight: 600; color: #0d6efd; }
  .helper-popup p { font-size: 14px; margin-top: 6px; }
  .helper-close {
    cursor: pointer;
    float: right;
    font-size: 18px;
    margin-top: -5px;
  }
</style>

<div class="helper-bubble" id="helperToggle">
  ?
</div>

<div class="helper-popup" id="helperPopup">
  <span class="helper-close" id="closeHelper">&times;</span>
  <h6><?= e($helper['nama']) ?></h6>
  <p><?= nl2br(e($helper['deskripsi'])) ?></p>
</div>

<script>
  const helperBtn = document.getElementById('helperToggle');
  const helperPopup = document.getElementById('helperPopup');
  const closeHelper = document.getElementById('closeHelper');

  helperBtn.addEventListener('click', () => {
    helperPopup.style.display = helperPopup.style.display === 'block' ? 'none' : 'block';
  });

  closeHelper.addEventListener('click', () => {
    helperPopup.style.display = 'none';
  });
</script>
