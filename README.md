# Sistem Pesanan Laundry — Tipe C

Repository: https://github.com/christinesimbolon/uts-ppw-2401020158. Petunjuk pemasangan tersedia di bawah; checklist pengumpulan ada di [docs/PENGUMPULAN.md](docs/PENGUMPULAN.md).

- **Nama:** Christine Simbolon
- **NIM:** 2401020158
- **Mata kuliah:** Praktikum Pemrograman Web (INF11197)
- **Sumber:** Soal Studi Kasus Praktikum Pemrograman Web, Paket 2, Tipe C — Sistem Pesanan Laundry.
- **Basis data utama:** `laundry_2401020158`.

Aplikasi CRUD pesanan dengan Laravel, PDO, dan Blade/HTML sederhana, tanpa CSS, JavaScript, Bootstrap, Tailwind, atau proses kompilasi aset. Halaman indeks memuat form tambah, pencarian, filter layanan, umpan balik, dan daftar pesanan. Fungsi `statusBerat()` menghasilkan **Besar** untuk berat ≥ 10 kg dan **Reguler** untuk berat < 10 kg.

> **Hasil lokal terverifikasi, 3 November 2026:** 146 tes PHPUnit dengan 1.092 asersi lulus; 12 skenario peramban/HTTP nyata lulus, termasuk sepuluh skenario wajib, CSRF, dan isolasi data. Bukti tersedia di `docs/bukti/`. Publikasi GitHub sedang disiapkan menggunakan akun Christine; hasil publikasi dicatat setelah push dan pembuatan Issue berhasil.

## Lingkungan terverifikasi

Laravel **12.70.2**, PHP **8.3.33** dengan ekstensi `pdo_mysql`, MariaDB **10.4.32**, Composer, dan Git **2.52.0.windows.1**. Versi lokal diverifikasi pada **2026-11-03**; perintah pemeriksaan ulang:

```bat
php --version
composer --version
mysql --version
git --version
php artisan --version
```

## Pemasangan di Windows

Jalankan dari direktori `Christine_Simbolon_2401020158_Tipe_C`. Contoh penyalinan dan pengalihan masukan SQL berikut memakai **Command Prompt (CMD)**, bukan PowerShell.

1. Pasang dependensi dari berkas pengunci:

   ```bat
   composer install
   ```

2. Untuk pemasangan baru, salin konfigurasi contoh. **Jangan menimpa `.env` yang sudah berisi konfigurasi lokal.**

   ```bat
   copy .env.example .env
   ```

3. Edit `.env`: gunakan `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, port MariaDB setempat, dan `DB_DATABASE=laundry_2401020158`. Isi `DB_USERNAME` serta `DB_PASSWORD` sesuai akun lokal. Gunakan `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, dan `APP_DEBUG=false` agar tidak memerlukan tabel bawaan Laravel atau menampilkan detail kesalahan.

   Akun `root` tanpa kata sandi, jika memang tersedia di komputer praktikum, **hanya asumsi lingkungan lokal**, bukan kredensial yang wajib dipakai atau disimpan dalam repositori. Jangan memasukkan `.env`, kata sandi nyata, maupun keluaran konfigurasi rahasia ke GitHub.

4. Buat kunci aplikasi:

   ```bat
   php artisan key:generate
   ```

5. Pastikan MariaDB berjalan. Tinjau dan cadangkan data sebelum impor, lalu jalankan **hanya untuk pemasangan baru**:

   ```bat
   mysql -u root -p < database/sql/laundry_2401020158.sql
   ```

   Ganti pengguna `root` bila akun lokal berbeda; `-p` meminta kata sandi secara interaktif, bukan menaruhnya di perintah. Berkas SQL memuat `CREATE DATABASE`, `USE laundry_2401020158`, definisi dua tabel, serta data awal; tidak memuat `DROP`. **Tidak adanya `DROP` bukan jaminan aman untuk impor ulang:** tabel, indeks unik, atau data yang sudah ada dapat menimbulkan konflik. Jangan mengimpor membabi buta ke basis data yang telah berisi pesanan. Jangan memakai berkas impor utama untuk menyiapkan basis data pengujian karena perintah `USE` memilih basis data utama.

6. Jalankan aplikasi:

   ```bat
   php artisan serve
   ```

   Buka **http://127.0.0.1:8000/pesanan-laundry**. Route `/` mengalihkan ke halaman tersebut.

**Tidak diperlukan:** `npm install`, `npm run build`, `php artisan migrate`, atau `php artisan db:seed`. Skema dan data awal berasal dari SQL. Skrip Composer proyek tidak memerlukan npm atau migrasi; aset kerangka yang tidak relevan telah dihapus.

## Data dan aturan utama

Relasi: `jenis_layanan.id` **1 → banyak** `pesanan_laundry.jenis_layanan_id`, dengan `ON UPDATE CASCADE` dan `ON DELETE RESTRICT`. Tepat dua tabel aplikasi; rincian tipe kolom ada di [laporan](docs/LAPORAN.md#rancangan-data).

| Kode pesanan | Nama pelanggan | Nomor telepon | Berat (kg) | Jenis layanan |
| --- | --- | --- | --- | --- |
| LD00000001 | Ayu Lestari | 081200000001 | 5 | Cuci Kering |
| LD00000002 | Rafi Akbar | 081200000002 | 8 | Cuci Setrika |
| LD00000003 | Nina Putri | 081200000003 | 3 | Cuci Kering |

Semua kolom masukan wajib: kode 10 karakter alfanumerik dan unik; nama 3–100 karakter; nomor telepon 10–20 karakter dan unik; berat bilangan bulat 1–20 kg; layanan harus tersedia. Nomor telepon disimpan sebagai teks agar nol awal tidak hilang. Saat pembaruan, pemeriksaan unik mengecualikan ID pesanan itu sendiri.

Pencarian GET menggunakan `q` untuk `kode_pesanan`, `nama_pelanggan`, dan `nomor_telepon`; filter menggunakan `jenis_layanan_id`. Contoh: `/pesanan-laundry?q=Nina&jenis_layanan_id=1`. Ketiga kondisi pencarian dikelompokkan dengan OR, kemudian digabungkan dengan filter menggunakan AND.

## Peta kebutuhan dan berkas

Peta penelusuran implementasi dan bukti pengujian:

| Kebutuhan | Berkas terkait |
| --- | --- |
| Lingkungan dan koneksi | `composer.json`, `composer.lock`, `.env.example`, `config/database.php` |
| Lima route CRUD dan pengalihan `/`, tanpa route `show` | `routes/web.php` |
| Sanitasi, validasi, umpan balik, penanganan kesalahan | `app/Http/Controllers/PesananLaundryController.php` |
| PDO dari `DB::connection()->getPdo()`; prepared statement untuk CRUD, pemeriksaan unik, keberadaan layanan, dan pencarian | `app/Repositories/PesananLaundryRepository.php` |
| Percabangan fungsi status berat dan pemuatannya | `app/helpers.php`, `composer.json` |
| Dua tabel, seluruh constraint, dan data awal | `database/sql/laundry_2401020158.sql` |
| Daftar, form tambah/edit, pencarian, filter, `old()`, kesalahan per kolom, CSRF, spoofing metode | `resources/views/pesanan-laundry/index.blade.php`, `edit.blade.php`, `_form.blade.php` dalam direktori yang sama |
| HTML sederhana dan respons aman | `resources/views/layouts/`, `resources/views/errors/` |
| CRUD, validasi, pencarian, filter, dan kontrak repositori | `tests/Feature/PesananLaundryTest.php`, `tests/Feature/PesananLaundryRepositoryTest.php` |
| Constraint SQL dan kesalahan basis data | `tests/Feature/LaundrySqlConstraintsTest.php`, `tests/Feature/PesananLaundryDatabaseErrorTest.php` |
| Batas status berat dan keamanan pemuatan SQL uji | `tests/Unit/StatusBeratTest.php`, `tests/Unit/LaundrySchemaTest.php` |
| Isolasi basis data uji | `phpunit.xml`, `tests/TestCase.php`, `tests/Support/MySqlTestCase.php`, `tests/Support/LaundrySchema.php` |
| Peramban/HTTP nyata dan tangkapan layar | `tools/uji_browser.py`, `docs/bukti/hasil_browser.json`, `docs/bukti/*.png` |
| Laporan dan pembuat PDF terpisah | `docs/LAPORAN.md`, `tools/buat_laporan.py` |

Teks relevan dibersihkan dengan `trim(strip_tags(...))`, kemudian divalidasi di server. Form memakai `@csrf`, `@method('PUT')`/`@method('DELETE')`, dan keluaran Blade `{{ ... }}`. Sanitasi bukan pengganti prepared statement atau pengamanan keluaran.

Kegagalan `PDOException`/`QueryException` harus ditangkap dan dicatat memakai `report()`. Pembacaan gagal menghasilkan halaman aman HTTP 503; mutasi gagal mengalihkan dengan pesan aman dan masukan lama yang relevan, tanpa pesan sukses palsu. SQL, kredensial, dan jejak pemanggilan tidak boleh muncul pada halaman pengguna. Kesalahan validasi biasa tetap menjadi pesan per kolom, bukan kesalahan basis data.

## Pengujian dengan MariaDB nyata

**Hasil aktual 2026-11-03: LULUS — 146 tes, 1.092 asersi**, tanpa kegagalan, kesalahan, atau tes dilewati. Durasi konsol eksekusi akhir: **11,67 detik**. Bukti: [JUnit](docs/bukti/phpunit.xml) dan [keluaran PHPUnit akhir](docs/bukti/hasil_phpunit.txt).

Untuk pemeriksaan ulang:

1. Pastikan dependensi pengembangan tersedia. Siapkan basis data khusus yang boleh dikosongkan:

   ```bat
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS laundry_2401020158_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

2. Periksa `phpunit.xml` dan `tests/TestCase.php`. Pengaturan uji harus tetap mengarah ke **`laundry_2401020158_testing`**, bukan basis data utama. Bila akun, kata sandi, host, atau port lokal berbeda, selaraskan konfigurasi uji dan nilai pemeriksaan pengamannya **secara lokal**; jangan menghapus pengaman nama basis data atau menyertakan kredensial nyata dalam commit. `.env` saja tidak menggantikan nilai PHPUnit yang dipaksakan.
3. Jangan gunakan cache konfigurasi yang menunjuk ke basis data utama. Bila sebelumnya memakai cache, bersihkan sebelum pengujian:

   ```bat
   php artisan config:clear
   ```

4. Jalankan tanpa `--parallel`:

   ```bat
   php artisan test
   ```

   Perintah yang menghasilkan bukti JUnit:

   ```bat
   php artisan test --log-junit docs/bukti/phpunit.xml
   ```

Pengujian integrasi menggunakan koneksi PDO MariaDB nyata, bukan SQLite atau migrasi. Pengaman memeriksa konfigurasi dan hasil `SELECT DATABASE()`. Pemuat fixture menolak pernyataan di luar daftar yang diizinkan, tidak mengeksekusi `CREATE DATABASE`/`USE` dari SQL utama, dan membangun ulang **hanya** tabel `pesanan_laundry` lalu `jenis_layanan` pada basis data uji. Data di kedua tabel uji akan hilang; jangan menyimpan data penting di sana. Jangan melewati pengaman saat koneksi gagal.

Sepuluh skenario wajib serta hasil kasus tambahan tersedia dalam [laporan](docs/LAPORAN.md#sepuluh-skenario-pengujian-wajib). Pemeriksaan lokal lainnya juga lulus: `composer validate` setelah penyelarasan berkas pengunci, Pint pada **29 berkas**, dan `php artisan view:cache`.

### Peramban dan HTTP nyata

Perintah yang sudah dijalankan:

```bat
py tools/uji_browser.py
```

[Hasil JSON](docs/bukti/hasil_browser.json) mencatat **12/12 skenario LULUS** pada `2026-11-03T15:20:02.329178+07:00`. Pengujian memakai Chrome headless nyata dengan `APP_ENV=local` agar middleware CSRF aktif. Server sementara memakai port **8765** untuk koneksi normal dan **8766** untuk simulasi `DB_PORT=1`; keduanya dihentikan pada akhir pengujian. Port aplikasi pengguna tetap **8000** melalui `php artisan serve`.

Mutasi uji hanya menyasar `laundry_2401020158_testing`; seluruh baris kedua tabel pada basis data utama dibandingkan sebelum/sesudah dan identik. Fixture uji dikembalikan ke tiga pesanan awal. POST serta PUT/DELETE melalui spoofing tanpa token memberi **419**; token sah dengan kolom tidak valid mencapai validasi server dan memberi **302**. Penonaktifan validasi HTML (`noValidate`) hanya dilakukan alat uji pada form tidak valid, bukan perubahan kode aplikasi. Koneksi port 1 memberi **503** tanpa SQLSTATE di halaman, log bertambah dengan rincian SQLSTATE, dan koneksi normal kembali **200**.

Tersedia **11 PNG seluruh halaman asli** untuk sepuluh skenario wajib; edit dan hasil update memiliki gambar terpisah. URL permintaan dirender oleh Blade pada header halaman, **bukan bilah alamat asli peramban**. Tidak ada gambar yang ditimpa, diberi lapisan buatan, atau diubah untuk merekayasa bukti.

Python, Playwright, Chrome, serta ReportLab/Pillow untuk `tools/buat_laporan.py` adalah kebutuhan alat bukti/PDF terpisah dan opsional bagi pengguna aplikasi; bukan dependensi JavaScript aplikasi. Sebelum mengulang alat uji, tinjau asumsi akun lokal dan pastikan kedua tabel basis data uji boleh dibangun ulang.

## Dokumentasi dan pengumpulan

- [Sumber laporan dengan hasil aktual](docs/LAPORAN.md).
- [Laporan PDF](docs/Laporan_Christine_Simbolon_2401020158.pdf): 11 halaman, format A4 dengan margin 3 cm di semua sisi, Times New Roman, dan cover sederhana berlogo UMRAH. Memuat enam contoh tangkapan layar asli; seluruh 11 PNG tersedia terpisah pada direktori bukti.
- [Bukti pengujian dan tangkapan layar asli](docs/bukti/).
- [Issue siap ditempel — masalah selesai lokal, belum dipublikasikan](docs/GITHUB_ISSUE.md).
- [Panduan lima kelompok commit dan publikasi manual](docs/PENGUMPULAN.md).

Masalah nyata pada alat uji telah diperbaiki: perbandingan literal URL pencarian diganti perbandingan komponen URL serta parameter kueri agar perbedaan urutan parameter tidak menimbulkan kegagalan palsu. Pengujian ulang 12 skenario lulus; rincian ada pada dokumen issue. Ini bukan bug hasil pencarian aplikasi.

**Yang belum dilakukan:** pembuatan riwayat minimal lima commit, publikasi repositori/push, publikasi satu GitHub Issue, dan pengiriman tautan melalui media dosen. Akun, izin, serta URL GitHub belum diberikan; tidak ada nomor issue, hash commit, atau tautan publik rekaan.
