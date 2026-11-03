# Panduan Pengumpulan — Christine Simbolon

**NIM:** 2401020158 · **Paket 2, Tipe C:** Sistem Pesanan Laundry.

Untuk penerima ZIP yang belum pernah membuat repository, ikuti [panduan GitHub dari nol](../PANDUAN_GITHUB_UNTUK_CHRISTINE.md) dan [petunjuk awal](../MULAI_DI_SINI.md). Panduan ini adalah penjelasan teknis lebih lengkap.

**Hasil lokal 2026-11-03 sudah terverifikasi:** 146 tes/1.092 asersi PHPUnit dan 12 skenario peramban nyata lulus; tangkapan layar serta hasil JSON/JUnit tersedia. Panduan Git di bawah tetap **usulan perintah yang belum dijalankan**. Belum dilakukan `git init`, commit, pembuatan cabang, pengaturan remote, push, atau publikasi issue; tindakan tersebut memerlukan akun/URL dan izin pemilik proyek.

## 1. Pemeriksaan sebelum publikasi

- Cocokkan implementasi dengan [README](../README.md) dan [laporan](LAPORAN.md): tepat dua tabel, PDO dengan prepared statement, CRUD, sanitasi, validasi, pencarian tiga kolom, filter layanan, dan HTML tanpa CSS/JS.
- Sepuluh skenario wajib sudah lulus; [laporan](LAPORAN.md) memuat hasil aktual. Bukti tambahan CSRF aktif dan isolasi data juga lulus. Pemeriksaan ulang setelah perubahan baru mengikuti prosedur MariaDB dalam README, bukan SQLite atau basis data utama.
- `composer validate` lulus setelah penyelarasan berkas pengunci, Pint lulus pada 29 berkas, dan `php artisan view:cache` lulus. Versi lokal: PHP 8.3.33, Laravel 12.70.2, MariaDB 10.4.32, Git 2.52.0.windows.1.
- Pastikan SQL memiliki `CREATE DATABASE`, `USE`, DDL, dan data awal yang benar, tanpa `DROP`. Jangan impor ulang membabi buta ke data utama yang sudah ada.
- Pastikan `.gitignore` mengabaikan `.env`, dependensi lokal, log, dan cache. `.env.example` hanya berisi contoh tanpa rahasia. Periksa juga kredensial dalam konfigurasi uji sebelum staging.
- `root` dengan kata sandi kosong hanya kemungkinan konfigurasi komputer lokal, bukan kredensial repositori atau anjuran untuk server publik. Jangan menaruh kata sandi asli dalam perintah, commit, issue, atau tangkapan layar.
- Jika repositori Git sudah ada, gunakan riwayat tersebut. Jangan menginisialisasi ulang atau menghapus `.git`. Jika belum ada, inisialisasi hanya setelah persetujuan pemilik proyek; panduan ini tidak melakukannya.

Perintah inspeksi berikut bersifat baca-saja dan tetap **belum dijalankan pada tahap dokumentasi ini**:

```sh
git --no-pager --no-optional-locks status --short
git --no-pager check-ignore .env
git --no-pager ls-files .env
git --no-pager diff
git --no-pager diff --cached
```

`check-ignore` semestinya menunjukkan `.env`, sedangkan `ls-files .env` semestinya tidak menghasilkan nama berkas. Pengabaian tidak melindungi berkas yang sudah telanjur dilacak. Jika rahasia pernah masuk riwayat, hentikan publikasi dan tangani penghapusan riwayat serta rotasi rahasia bersama pemilik proyek; jangan sekadar mengandalkan `.gitignore`.

## 2. Usulan minimal lima commit terstruktur

**Hanya rencana; belum merupakan riwayat commit.** Perintah berikut menggunakan Git Bash, dijalankan dari akar proyek setelah ada repositori dan persetujuan. Awalan `GIT_EDITOR=true` mencegah editor interaktif; pesan commit sengaja berbahasa Indonesia.

Berkas implementasi dan bukti kini sudah tersedia dalam folder proyek terisolasi; dokumen soal DOCX asli tetap utuh. Lima kelompok berikut adalah usulan pengelompokan pekerjaan saat ini, bukan riwayat lampau. Tinjau staging per kelompok atau gunakan `git add -p` untuk berkas yang mencampur tahap. Jangan memakai `git add .` tanpa pemeriksaan. Jangan membuat commit kosong, memundurkan tanggal, atau menulis ulang riwayat orang lain demi terlihat telah bekerja bertahap. Jika commit sudah ada, lanjutkan riwayat secara wajar; lima kelompok di bawah bukan kewajiban memecah ulang commit lama.

### Tahap 1 — konfigurasi dasar dan keamanan

Tinjau konfigurasi contoh, pengabaian berkas rahasia, dependensi, dan kerangka aplikasi sebelum menambahkan kelompok ini:

```sh
git add .editorconfig .gitattributes .gitignore .env.example artisan composer.json composer.lock bootstrap config public storage routes/console.php
git --no-pager diff --cached
GIT_EDITOR=true git commit -m "Siapkan konfigurasi dasar aplikasi laundry"
```

### Tahap 2 — skema dan data awal

```sh
git add database/sql/laundry_2401020158.sql
git --no-pager diff --cached
GIT_EDITOR=true git commit -m "Tambahkan skema dan data awal laundry"
```

### Tahap 3 — fitur aplikasi

Kelompokkan controller, repositori PDO, fungsi PHP, route, dan Blade. Bila perubahan lebih besar, tahap ini boleh menjadi beberapa commit logis tambahan.

```sh
git add app routes/web.php resources/views
git --no-pager diff --cached
GIT_EDITOR=true git commit -m "Terapkan CRUD pencarian dan filter laundry"
```

### Tahap 4 — pengujian dan pengaman basis data

Pastikan tidak ada kata sandi nyata pada perubahan konfigurasi uji yang akan dicatat. Sertakan alat peramban beserta perbaikan perbandingan URL yang sudah diuji ulang; masalah ini selesai lokal tetapi belum memiliki commit.

```sh
git add tests phpunit.xml tools/uji_browser.py
git --no-pager diff --cached
GIT_EDITOR=true git commit -m "Tambahkan pengujian dan isolasi basis data"
```

### Tahap 5 — dokumentasi dan bukti aktual

Sertakan dokumentasi hasil aktual, JSON/JUnit, tangkapan layar asli, laporan PDF, dan alat pembuat PDF. Tinjau seluruh artefak sebelum staging; jangan ikutkan profil peramban, log mentah berisi rahasia, atau dependensi lokal.

```sh
git add README.md docs tools/buat_laporan.py
git --no-pager diff --cached
GIT_EDITOR=true git commit -m "Lengkapi dokumentasi laporan dan bukti uji"
```

Usulan ini perlu disesuaikan dengan berkas serta riwayat aktual; jangan memasukkan perubahan orang lain yang tidak terkait. Periksa hasil nyata setelah commit:

```sh
git --no-pager log --oneline --decorate -n 10
git --no-pager --no-optional-locks status --short
```

## 3. Buat repositori GitHub dan unggah secara manual

1. Masuk memakai akun GitHub sendiri, lalu buka **https://github.com/new**. Nama yang disarankan: `Christine_Simbolon_2401020158_Tipe_C`. Sesuaikan visibilitas dengan ketentuan dosen. Untuk unggahan repositori lokal yang telah memiliki commit, jangan membuat README atau commit awal tambahan di GitHub.
2. Salin URL repositori yang benar dari halaman GitHub. Belum ada akun atau URL repositori yang ditentukan dalam dokumen ini.
3. Setelah persetujuan pemilik proyek, periksa remote/cabang, lalu tambahkan remote hanya bila belum ada. Jika sudah ada, tinjau dahulu; jangan menimpanya sembarangan.

   ```sh
   git --no-pager remote -v
   git --no-pager branch --show-current
   git remote add origin URL_REPOSITORI_ASLI
   git push -u origin NAMA_CABANG_AKTIF
   ```

   `URL_REPOSITORI_ASLI` dan `NAMA_CABANG_AKTIF` adalah penanda yang **wajib diganti** berdasarkan keluaran nyata, bukan perintah siap dijalankan apa adanya. Jangan menyertakan token akses dalam URL. Panduan ini tidak menjalankan push.

4. Buka repositori di peramban; periksa source code, SQL, `.env.example`, dokumentasi, dan riwayat minimal lima commit nyata. Pastikan `.env` tidak tersedia di repositori atau riwayat publik.
5. Salin URL repositori yang telah diverifikasi ke laporan dan daftar pengumpulan di bawah.

## 4. Publikasikan satu issue yang nyata

1. Masalah nyata sudah direproduksi dan diperbaiki pada `tools/uji_browser.py`: perbandingan URL literal gagal karena urutan parameter berbeda, walaupun hasil pencarian benar. Perbandingan kini memakai `urlsplit` dan `parse_qs(..., keep_blank_values=True)`; pengulangan penuh 12 skenario lulus.
2. Pada repositori asli, buka **Issues → New issue**. Salin [GITHUB_ISSUE.md](GITHUB_ISSUE.md) dengan judul **Perbaiki perbandingan URL pada uji pencarian**. Nyatakan bahwa masalah sudah selesai secara lokal sebelum issue dipublikasikan; jangan membuat kronologi palsu.
3. Naskah sudah berisi reproduksi, penyebab, perubahan, hasil aktual, serta tautan bukti. Setelah commit nyata dibuat, tambahkan hash/tautan perubahan alat uji dan sesuaikan tautan bukti lokal menjadi tautan repositori yang dapat dibuka.
4. Issue boleh ditutup setelah bukti perbaikan dan uji ulang terlampir. Catat URL issue serta repositori asli pada kolom status GitHub. Pembaruan tautan publik kemudian bersifat administratif; tidak perlu mengulang pengujian atau ekspor PDF hanya karena URL diisi, selama kode, hasil, dan status lokal yang dilaporkan tidak berubah. Naskah lokal saja belum menggantikan publikasi GitHub.

## 5. Tangkapan layar dan PDF

Artefak pengumpulan:

- [PDF laporan](Laporan_Christine_Simbolon_2401020158.pdf): **11 halaman**, kertas **A4**, margin **3 cm di semua sisi**, dan Times New Roman. Cover sederhana berlogo UMRAH memuat identitas Christine; halaman isi membahas rancangan, route, PDO, hasil aktual, kendala/perbaikan, dan simpulan. Lampiran memuat **enam contoh tangkapan layar asli**. Seluruh 11 PNG tersedia terpisah.
- [Sumber laporan](LAPORAN.md) dan alat pembuat PDF `tools/buat_laporan.py`.
- [Hasil browser](bukti/hasil_browser.json): 12/12 LULUS pada `2026-11-03T15:20:02.329178+07:00`.
- [JUnit](bukti/phpunit.xml): 146 tes/1.092 asersi, tanpa kegagalan/kesalahan/tes dilewati; [keluaran PHPUnit akhir](bukti/hasil_phpunit.txt) mencatat durasi **11,67 detik**.
- **11 PNG asli**: `01_read.png`, `02_create_valid.png`, `03_create_tidak_valid.png`, `04_duplikat.png`, `05a_form_edit.png`, `05b_update.png`, `06_konflik_update.png`, `07_delete.png`, `08_pencarian.png`, `09_filter.png`, `10_error_handling.png`, seluruhnya dalam `docs/bukti/`.

PNG adalah tangkapan seluruh halaman Chrome headless nyata tanpa penimpaan, lapisan buatan, atau manipulasi gambar. **URL ditampilkan oleh Blade pada header halaman, bukan bilah alamat asli peramban.** Semua bukti berasal dari aplikasi Tipe C ini; jangan mengklaim gambar memuat bilah peramban yang tidak direkam.

Uji memakai `APP_ENV=local` untuk mengaktifkan CSRF. Server sementara normal **8765** dan simulasi **8766** dengan `DB_PORT=1` sudah dihentikan; aplikasi pengguna tetap di **8000**. Tanpa token, POST serta spoofing PUT/DELETE memberi **419**; token sah dengan kolom tidak valid memberi **302**. `noValidate` hanya diterapkan oleh alat uji agar masukan tidak valid mencapai server, bukan perubahan aplikasi. Koneksi gagal memberi **503** aman, log bertambah dengan SQLSTATE, lalu koneksi normal kembali **200**. Seluruh baris basis data utama identik sebelum/sesudah; fixture uji dipulihkan ke tiga pesanan.

Python/Playwright dan ReportLab/Pillow merupakan kebutuhan terpisah alat bukti/PDF, bukan dependensi JavaScript aplikasi. Periksa keterbacaan PDF serta kesesuaian bukti sebelum mengirim. Bukti GitHub berupa riwayat commit dan URL issue ditambahkan setelah publikasi sebenarnya; tidak ada gambar atau riwayat GitHub rekaan.

## 6. Daftar status akhir

| Kebutuhan | Status akhir lokal / tindak lanjut |
| --- | --- |
| Source code, SQL, dan `.env.example` | Tersedia dalam folder proyek; hasil uji lokal dicatat dalam laporan |
| Sepuluh skenario wajib | **10/10 LULUS** pada peramban nyata |
| PHPUnit dengan MariaDB nyata | **146 tes / 1.092 asersi LULUS** |
| CSRF dan isolasi utama | Dua skenario tambahan LULUS; total peramban/HTTP **12/12** |
| Composer, Pint, dan cache Blade | Lulus; Pint memeriksa 29 berkas |
| Tangkapan layar di `docs/bukti/` | 11 PNG asli tersedia, disertai JSON dan JUnit |
| Laporan pengumpulan | Sumber `docs/LAPORAN.md`; PDF `docs/Laporan_Christine_Simbolon_2401020158.pdf` dengan tangkapan layar |
| Masalah teknis dan perbaikan | Perbandingan URL alat uji selesai lokal; naskah issue siap ditempel |
| Minimal lima commit terstruktur | **Belum dilakukan**; lima kelompok usulan tersedia, memerlukan izin pemilik |
| Repositori, remote, push, dan URL GitHub asli | **Belum dilakukan**; akun/URL serta izin belum diberikan |
| Publikasi satu issue nyata dan URL-nya | **Belum dilakukan**; masalah lokal sudah diselesaikan, tetapi bukan issue publik |
| Keamanan berkas saat publikasi | `.env`, profil peramban, log, cache, dependensi, dan rahasia harus tetap di luar staging/riwayat; periksa saat Git diaktifkan |
| Pengiriman tautan melalui media dosen | Belum dilakukan; mengikuti publikasi manual |

Pengujian lokal selesai dan bukti aktual sudah dicatat. Yang masih terbuka adalah tindakan Git/GitHub, pemeriksaan staging sebelum publikasi, serta pengiriman melalui media dosen. Jangan menyatakan tugas sudah terkumpul sebelum tindakan tersebut benar-benar selesai.
