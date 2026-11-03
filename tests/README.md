# Pengujian Sistem Pesanan Laundry

Christine Simbolon — NIM **2401020158** — Paket 2, Tipe C.

## Menjalankan

Prasyarat: PHP 8.2+ dengan `pdo_mysql`, dependensi `composer install`, dan MySQL/MariaDB yang berjalan. Aplikasi telah dilengkapi backend, Blade, helper, dan fixture SQL.

Buat database khusus yang boleh dikosongkan, dari CMD:

```bat
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS laundry_2401020158_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan config:clear
php artisan test
```

`phpunit.xml` memaksa koneksi `127.0.0.1:3306`, pengguna lokal `root`, kata sandi kosong, database `laundry_2401020158_testing`. Jika akun lokal berbeda, selaraskan konfigurasi uji dan pemeriksaan kredensial di `tests/TestCase.php` secara lokal; **jangan menghapus pengaman nama database atau menyertakan kata sandi asli dalam repository**.

Jangan menggunakan `--parallel`, database utama, `migrate`, atau impor SQL utama secara langsung untuk menyiapkan tes. Pernyataan `USE` pada SQL utama memilih database aplikasi, bukan database tes.

## Pengaman data

- `TestCase` menolak lingkungan dan konfigurasi cache yang tidak sesuai sebelum provider aplikasi membuka koneksi.
- `MySqlTestCase` membaca dan memvalidasi fixture sebelum menghapus apa pun.
- `LaundrySchema` memisahkan SQL dengan mempertimbangkan komentar, tanda kutip, dan titik koma; `CREATE DATABASE`/`USE` diabaikan, bukan dieksekusi.
- Hanya DDL/DML untuk dua tabel laundry tanpa nama database eksplisit yang diterima; referensi ke tabel/database lain ditolak.
- `SELECT DATABASE()` harus sama persis dengan `laundry_2401020158_testing`.
- Hanya `pesanan_laundry`, lalu `jenis_layanan`, yang dibangun ulang pada database uji. Foreign key tidak dinonaktifkan.

Data kedua tabel uji bersifat sementara. Jangan menyimpan data penting di sana. Suite sengaja gagal, bukan melewati tes, jika MariaDB atau prasyarat tidak tersedia.

## Cakupan dan hasil aktual

Pengulangan akhir pada **3 November 2026** lulus **146 tes / 1.092 asersi**:

- CRUD, nilai unik sendiri, pembaruan identik, ID tidak ditemukan, dan metode HTTP tidak sesuai.
- Validasi tiap field, tipe array, panjang teks, batas berat, duplikat saat tambah/ubah, serta `old()`.
- `trim(strip_tags())`, escape Blade, tiga kolom pencarian, filter gabungan, hasil kosong, dan masukan menyerupai injeksi SQL.
- Seluruh metode repository dengan PDO/MariaDB nyata; tanpa Eloquent atau SQLite.
- UNIQUE, NOT NULL, CHECK/UNSIGNED, foreign key, ON UPDATE CASCADE, ON DELETE RESTRICT.
- Exception PDO yang disimulasikan: `report()`, pesan aman, HTTP 503/redirect, tanpa kebocoran detail; exception validasi tidak tertelan.
- Unit test `statusBerat()` serta parser SQL dan pengamannya.

Bukti: `docs/bukti/phpunit.xml` dan `docs/bukti/hasil_phpunit.txt`. Unit test tidak membuka koneksi database; feature test menggunakan MariaDB nyata dengan fixture yang sama seperti aplikasi.

CSRF aktif dan gangguan koneksi nyata diuji terpisah melalui Chrome/HTTP menggunakan `py tools/uji_browser.py` (**12/12 skenario lulus**). Middleware CSRF Laravel lazimnya dilewati dalam feature test; karena itu bukti form/token di PHPUnit tidak dianggap bukti penolakan CSRF HTTP nyata. Hasil browser ada di `docs/bukti/hasil_browser.json`.
