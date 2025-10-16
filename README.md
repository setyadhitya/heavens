# PHP Native Secure Login Starter

Ringkasan:
- Paket ini berisi contoh sistem login sederhana dan relatif aman untuk PHP Native.
- Fitur utama: `password_hash` + `password_verify`, prepared statements (mysqli), CSRF token, session_regenerate_id, session timeout, input sanitization, simple brute-force protection (session-based).
- **Catatan penting:** Sesuaikan `config.php` dengan kredensial database Anda sebelum digunakan.

Cara pakai:
1. Ekstrak zip di root public_html/shared hosting Anda (pastikan file PHP dapat diakses).
2. Edit `config.php` lalu isi `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.
3. Buat database lalu jalankan `register.php` sekali untuk membuat user admin (atau isi melalui form pendaftaran).
4. Buka `index.php` untuk masuk.

File penting:
- `config.php` — konfigurasi database dan session.
- `functions.php` — fungsi helper (CSRF, auth check, sanitize).
- `register.php` — form pendaftaran (gunakan hanya sekali; hapus setelah selesai).
- `index.php` — halaman login.
- `dashboard.php` — contoh halaman terlindungi.
- `logout.php` — logout.
- `.htaccess` — aturan dasar untuk shared hosting (opsional).

Keamanan tambahan yang direkomendasikan:
- Gunakan HTTPS (SSL).
- Simpan file sensitif di luar `public_html` jika memungkinkan.
- Implementasikan rate-limiting berbasis IP atau CAPTCHA untuk brute-force.
- Simpan login attempts di database agar bertahan antar-sesi.
- Terapkan CSP dan header keamanan (X-Frame-Options, X-Content-Type-Options, Referrer-Policy).

Jika ingin, saya bisa:
- Tambahkan reCAPTCHA.
- Tambahkan 2FA (TOTP) untuk admin.
- Modifikasi agar menggunakan PDO.

