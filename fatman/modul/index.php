<?php
// FILE: heavens/fatman/modul/index.php
require_once __DIR__ . '/../functions.php';
require_admin();

$pdo = db();

// Ambil data awal (render pertama), reload berikutnya via AJAX -> modul_action.php?action=list
$stmt = $pdo->query("SELECT * FROM modul ORDER BY id_modul DESC");
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Data Modul Praktikum</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table td,
        .table th {
            vertical-align: middle;
        }

        .modal {
            z-index: 1050 !important;
        }

        .modal-backdrop {
            z-index: 1040 !important;
        }

        .img-thumb {
            width: 70px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
</head>

<body class="bg-light">
    <?php include __DIR__ . '/../navbar.php'; ?>

    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Modul Praktikum</h5>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">+
                    Tambah</button>
            </div>
            <div class="card-body">
                <div id="alertArea"></div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>#</th>
                                <th>Judul Modul</th>
                                <th>Mata Kuliah</th>
                                <th>Gambar</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="modulData">
                            <?php if (!empty($rows)):
                                $no = 1;
                                foreach ($rows as $row): ?>
                                    <tr data-id="<?= (int) $row['id_modul']; ?>">
                                        <td class="text-center"><?= $no++; ?></td>
                                        <td><?= e($row['judul_modul']); ?></td>
                                        <td><?= e($row['mata_kuliah']); ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($row['gambar_modul'])): ?>
                                                <img class="img-thumb"
                                                    src="<?= e('../../guwambar/modul/' . $row['gambar_modul']); ?>" alt="gambar">
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= e($row['dibuat']); ?></td>
                                        <td class="text-center">
                                            <a href="section/index.php?id_modul=<?= (int) $row['id_modul']; ?>"
                                                class="btn btn-info btn-sm">
                                                <i class="bi bi-list-ul"></i> Kelola Section
                                            </a>
                                            <button class="btn btn-warning btn-sm btnEdit"
                                                data-id="<?= (int) $row['id_modul']; ?>">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm btnHapus"
                                                data-id="<?= (int) $row['id_modul']; ?>">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </td>

                                    </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Belum ada data.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- 🔹 Modal Tambah -->
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="formTambah" method="post" enctype="multipart/form-data" class="row g-2 mb-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                    <input type="hidden" name="action" value="tambah">

                    <div class="col-md-6">
                        <label class="form-label">Judul Modul</label>
                        <input type="text" name="judul_modul" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Mata Kuliah</label>
                        <input type="text" name="mata_kuliah" class="form-control" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Deskripsi Singkat</label>
                        <textarea name="deskripsi_singkat" class="form-control" rows="3"
                            placeholder="(opsional)"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Gambar Modul (jpg/jpeg/png) <span
                                class="text-muted">(opsional)</span></label>
                        <input type="file" name="gambar_modul" class="form-control" accept=".jpg,.jpeg,.png,image/*">
                    </div>

                    <div class="col-12 d-grid mt-2">
                        <button class="btn btn-primary">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 🔹 Modal Edit -->
    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="formEdit" method="post" enctype="multipart/form-data" class="row g-2 mb-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id_modul" id="edit_id_modul">

                    <div class="col-md-6">
                        <label class="form-label">Judul Modul</label>
                        <input type="text" name="judul_modul" id="edit_judul_modul" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Mata Kuliah</label>
                        <input type="text" name="mata_kuliah" id="edit_mata_kuliah" class="form-control" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Deskripsi Singkat</label>
                        <textarea name="deskripsi_singkat" id="edit_deskripsi_singkat" class="form-control" rows="3"
                            placeholder="(opsional)"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Ganti Gambar Modul (opsional)</label>
                        <input type="file" name="gambar_modul" class="form-control" accept=".jpg,.jpeg,.png,image/*">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" id="hapus_gambar"
                                name="hapus_gambar">
                            <label class="form-check-label" for="hapus_gambar">Hapus gambar saat disimpan</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block">Gambar Saat Ini</label>
                        <img id="preview_gambar" src="" class="img-thumb" alt="(kosong)">
                        <div class="text-muted small" id="preview_gambar_ket">-</div>
                    </div>

                    <div class="col-12 d-grid mt-2">
                        <button class="btn btn-warning">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Tambah
            const formTambah = document.getElementById('formTambah');
            if (formTambah) {
                formTambah.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    try {
                        const res = await fetch('modul_action.php', { method: 'POST', body: new FormData(e.target) });
                        const html = await res.text();
                        document.getElementById('alertArea').innerHTML = html;

                        const modalEl = document.getElementById('modalTambah');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                            setTimeout(async () => {
                                document.activeElement.blur();
                                await loadModulTable();
                                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                                document.body.classList.remove('modal-open');
                                document.body.style.overflow = '';
                            }, 200);
                        }
                        e.target.reset();
                    } catch (err) {
                        console.error(err);
                    }
                });
            }

            function attachTableListeners() {
                document.querySelectorAll('.btnEdit').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        const id = btn.dataset.id;
                        try {
                            const res = await fetch('modul_action.php?action=get&id_modul=' + id);
                            if (!res.ok) throw new Error('Gagal ambil data');
                            const data = await res.json();

                            document.getElementById('edit_id_modul').value = data.id_modul ?? '';
                            document.getElementById('edit_judul_modul').value = data.judul_modul || '';
                            document.getElementById('edit_mata_kuliah').value = data.mata_kuliah || '';
                            document.getElementById('edit_deskripsi_singkat').value = data.deskripsi_singkat || '';

                            const imgEl = document.getElementById('preview_gambar');
                            const ketEl = document.getElementById('preview_gambar_ket');
                            if (data.gambar_modul) {
                                imgEl.src = '../../guwambar/modul/' + data.gambar_modul;
                                ketEl.textContent = data.gambar_modul;
                            } else {
                                imgEl.src = '';
                                ketEl.textContent = '(tidak ada gambar)';
                            }
                            document.getElementById('hapus_gambar').checked = false;

                            new bootstrap.Modal(document.getElementById('modalEdit')).show();
                        } catch (err) {
                            console.error(err);
                            alert('Terjadi kesalahan saat mengambil data.');
                        }
                    });
                });

                document.querySelectorAll('.btnHapus').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (!confirm('Yakin hapus modul ini? Tindakan ini tidak bisa dibatalkan.')) return;
                        const form = new FormData();
                        form.append('action', 'hapus');
                        form.append('id_modul', btn.dataset.id);
                        form.append('csrf_token', '<?= e(csrf_token()); ?>');
                        const res = await fetch('modul_action.php', { method: 'POST', body: form });
                        const html = await res.text();
                        document.getElementById('alertArea').innerHTML = html;
                        await loadModulTable();
                    });
                });
            }

            const formEdit = document.getElementById('formEdit');
            if (formEdit) {
                formEdit.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const res = await fetch('modul_action.php', { method: 'POST', body: new FormData(e.target) });
                    const html = await res.text();
                    document.getElementById('alertArea').innerHTML = html;

                    const modalEl = document.getElementById('modalEdit');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) {
                        modal.hide();
                        setTimeout(async () => {
                            document.activeElement.blur();
                            await loadModulTable();
                            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                        }, 200);
                    }
                });
            }

            async function loadModulTable() {
                try {
                    const res = await fetch('modul_action.php?action=list');
                    const html = await res.text();
                    document.getElementById('modulData').innerHTML = html;
                    attachTableListeners();
                } catch (err) {
                    console.error('Gagal memuat tabel:', err);
                }
            }

            attachTableListeners();

            // Cleanup sisa modal
            document.addEventListener('hidden.bs.modal', () => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            });
        });
    </script>
</body>

</html>