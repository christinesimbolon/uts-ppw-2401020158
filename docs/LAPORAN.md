# Laporan Praktikum — Sistem Pesanan Laundry

## Identitas dan ruang lingkup

| Komponen | Keterangan |
| --- | --- |
| Nama | Christine Simbolon |
| NIM | 2401020158 |
| Mata kuliah | Praktikum Pemrograman Web — INF11197 |
| Fakultas | Teknik dan Teknologi Kemaritiman |
| Paket dan tipe | Paket 2, Tipe C — Sistem Pesanan Laundry |
| Sumber | Soal Studi Kasus Praktikum Pemrograman Web, Paket 2 |
| Lingkungan terverifikasi | Laravel 12.70.2; PHP 8.3.33; MariaDB 10.4.32; Composer; Git 2.52.0.windows.1 |
| Tanggal pengujian lokal | 2026-11-03 |
| Basis data utama / uji | `laundry_2401020158` / `laundry_2401020158_testing` |
| Alamat aplikasi pengguna | `http://127.0.0.1:8000/pesanan-laundry` |
| URL repositori dan issue | Belum dipublikasikan; akun, URL, dan izin diperlukan |

**Hasil:** 146 tes PHPUnit dengan 1.092 asersi lulus; 12 skenario peramban/HTTP nyata lulus, termasuk sepuluh pengujian wajib. [PDF laporan](Laporan_Christine_Simbolon_2401020158.pdf) disertai bukti asli pada [direktori bukti](bukti/). Pekerjaan GitHub belum dilakukan dan tidak dianggap selesai oleh hasil pengujian lokal.

## Format laporan PDF

Laporan PDF menggunakan kertas **A4 (21 × 29,7 cm)** dengan margin **3 cm pada sisi atas, bawah, kiri, dan kanan**. Isi memakai Times New Roman 12 pt dengan tabel sederhana. Cover tanpa nomor halaman memuat judul, logo UMRAH, Christine Simbolon, NIM 2401020158, fakultas, universitas, dan tahun 2026. Nomor halaman dimulai dari halaman isi pertama. Logo menggunakan salinan berkas `UMRAH.png` milik pengguna di `docs/aset/UMRAH.png`; berkas asli tidak diubah.

## Tujuan dan alur

Aplikasi membantu petugas menambah, melihat, mengubah, menghapus, mencari, dan memfilter pesanan berdasarkan jenis layanan. Tampilan memakai Blade dan HTML sederhana tanpa CSS, JavaScript, atau pustaka tampilan. Alur permintaan: peramban → route → controller (sanitasi dan validasi) → repositori PDO → MariaDB → Blade atau pengalihan dengan umpan balik.

Indeks menggabungkan form tambah, pencarian, filter, pesan, serta tabel pesanan dan nama layanan. Fungsi `statusBerat()` menggunakan percabangan: berat ≥ 10 kg menghasilkan **Besar**, selainnya **Reguler**. Hasil ditampilkan dalam perulangan data Blade, bukan kolom tambahan dalam basis data.

## Rancangan data

Relasi satu-ke-banyak: `jenis_layanan.id` → `pesanan_laundry.jenis_layanan_id`. Setiap pesanan wajib memiliki satu layanan. Foreign key memakai **ON UPDATE CASCADE** dan **ON DELETE RESTRICT**: perubahan ID master diteruskan dan layanan yang masih dipakai tidak boleh dihapus.

| Tabel | Kolom dan constraint |
| --- | --- |
| `jenis_layanan` | `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`; `nama_layanan VARCHAR(100) NOT NULL UNIQUE` |
| `pesanan_laundry` | `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`; `kode_pesanan CHAR(10) NOT NULL UNIQUE`; `nama_pelanggan VARCHAR(100) NOT NULL`; `nomor_telepon VARCHAR(20) NOT NULL UNIQUE`; `berat_kg SMALLINT UNSIGNED NOT NULL CHECK (berat_kg BETWEEN 1 AND 20)`; `jenis_layanan_id BIGINT UNSIGNED NOT NULL` sebagai FK; `created_at` dan `updated_at` |

Tepat dua tabel aplikasi. `database/sql/laundry_2401020158.sql` memuat `CREATE DATABASE`, `USE`, DDL, dan DML awal tanpa `DROP`; pemasangan tidak memakai migrasi atau `db:seed`. Tidak adanya `DROP` bukan alasan mengimpor ulang tanpa meninjau dan mencadangkan data lama.

| Kode | Pelanggan | Telepon | Berat | Layanan | Status |
| --- | --- | --- | --- | --- | --- |
| LD00000001 | Ayu Lestari | 081200000001 | 5 kg | Cuci Kering | Reguler |
| LD00000002 | Rafi Akbar | 081200000002 | 8 kg | Cuci Setrika | Reguler |
| LD00000003 | Nina Putri | 081200000003 | 3 kg | Cuci Kering | Reguler |

## Route

| Metode | URI | Aksi |
| --- | --- | --- |
| GET | `/` | Pengalihan ke indeks laundry |
| GET | `/pesanan-laundry` | `index`: daftar, form tambah, pencarian, filter |
| POST | `/pesanan-laundry` | `store`: simpan pesanan |
| GET | `/pesanan-laundry/{id}/edit` | `edit`: form berisi data lama |
| PUT | `/pesanan-laundry/{id}` | `update`: perbarui pesanan |
| DELETE | `/pesanan-laundry/{id}` | `destroy`: hapus pesanan |

Tidak ada route `show`. Lima route CRUD bernama `pesanan-laundry.index`, `.store`, `.edit`, `.update`, dan `.destroy`. Pesanan yang tidak ditemukan menghasilkan 404.

## Pengolahan input dan keamanan

- Teks relevan disanitasi dengan `trim(strip_tags(...))` sebelum validasi server. Semua masukan wajib: kode tepat 10 karakter alfanumerik dan unik; nama 3–100 karakter; telepon 10–20 karakter dan unik; berat bilangan bulat 1–20; layanan harus tersedia.
- Pemeriksaan unik saat pembaruan mengecualikan ID sendiri. Constraint basis data tetap menjaga integritas terhadap konflik.
- Form memakai `@csrf`, `@method('PUT')`/`@method('DELETE')`, pesan per kolom, dan `old()`. Keluaran `{{ ... }}` mengamankan HTML; sanitasi tidak menggantikan prepared statement atau pengamanan keluaran.
- `.env` tidak boleh masuk repositori. `root` tanpa kata sandi hanya konfigurasi komputer lokal, bukan kredensial wajib proyek. Gunakan `APP_DEBUG=false` agar detail internal tidak disajikan kepada pengguna.

## PDO, pencarian, dan penanganan kesalahan

Koneksi diambil melalui **`DB::connection()->getPdo()`**. CRUD, pemeriksaan unik, pemeriksaan keberadaan layanan, serta pencarian/filter menggunakan `prepare()` dan `execute()` dengan nilai terikat; masukan pengguna tidak digabungkan langsung ke SQL.

GET `q` mencari `kode_pesanan`, `nama_pelanggan`, dan `nomor_telepon`. Kelompok OR ketiga kolom digabungkan dengan AND untuk filter `jenis_layanan_id`. Penanda native berbeda (`:kode`, `:nama`, `:telepon`) dipakai walaupun pola nilainya sama. Pembaruan identik tetap diterima jika baris ada; `rowCount() === 0` tidak dijadikan satu-satunya alasan 404.

`PDOException`/`QueryException`, termasuk pada kueri pemeriksaan validasi, ditangkap dan dicatat lewat `report()`. Kegagalan baca memberi halaman aman **503**; kegagalan mutasi memberi pengalihan/pesan aman dan masukan lama yang relevan, bukan pesan sukses. SQL, SQLSTATE, kredensial, dan jejak pemanggilan tidak dibocorkan. Kesalahan validasi biasa tetap menjadi pesan per kolom.

## Sepuluh skenario pengujian wajib

**Seluruh sepuluh skenario wajib LULUS secara lokal.** Hasil berikut mengikuti [hasil browser aktual](bukti/hasil_browser.json), bukan rencana. PHPUnit juga menguji validasi, konflik, dan kegagalan basis data secara lebih rinci.

| No. | Skenario dan tindakan aktual | Hasil aktual | Status | Bukti |
| --- | --- | --- | --- | --- |
| 1 | Buka indeks pada data awal | Tiga pesanan wajib, identitas Christine, dan nama layanan tampil | LULUS | [01](bukti/01_read.png) |
| 2 | Tambah Dina Sari, `LD00000004`, `081200000004`, 10 kg, Cuci Setrika | Data menjadi empat; pesan sukses dan status Besar tampil | LULUS | [02](bukti/02_create_valid.png) |
| 3 | Kirim `LD00000005` dengan nama kosong dan berat 0 | Server menolak; ringkasan kesalahan tampil, kode/berat lama dipertahankan, data tetap empat | LULUS | [03](bukti/03_create_tidak_valid.png) |
| 4 | Tambah kode `LD00000001` dan telepon `081200000001` | Kedua pesan duplikat tampil; data tetap empat | LULUS | [04](bukti/04_duplikat.png) |
| 5 | Buka edit pesanan 4; ubah nama menjadi Dina Sari Diperbarui, berat 12, layanan Cuci Kering | Data lama tampil; nama baru dan berat 12 tersimpan; kode/telepon sendiri diterima | LULUS | [05a](bukti/05a_form_edit.png), [05b](bukti/05b_update.png) |
| 6 | Ubah pesanan 4 memakai kode/telepon Rafi | Konflik ditolak; kode pesanan 4 tetap `LD00000004` | LULUS | [06](bukti/06_konflik_update.png) |
| 7 | Hapus pesanan 4 melalui form CSRF dan spoofing DELETE | Hanya pesanan 4 dihapus; tiga pesanan awal tetap ada | LULUS | [07](bukti/07_delete.png) |
| 8 | Cari `LD00000001`, `Rafi`, dan `081200000003` bergantian | Setiap pencarian menghasilkan satu pesanan yang sesuai pada tiga kolom berbeda | LULUS | [08](bukti/08_pencarian.png) |
| 9 | Filter Cuci Kering, lalu tambahkan kata `Nina` | Filter menghasilkan Ayu/Nina; gabungan kata menghasilkan Nina saja | LULUS | [09](bukti/09_filter.png) |
| 10 | Akses server uji dengan `DB_PORT=1` | HTTP 503 aman; SQLSTATE tidak tampil; log bertambah dan berisi SQLSTATE melalui pelaporan kesalahan | LULUS | [10](bukti/10_error_handling.png) |

### Pelaksanaan dan bukti

| Pemeriksaan | Hasil terverifikasi |
| --- | --- |
| Tanggal | 2026-11-03; waktu hasil browser `2026-11-03T15:20:02.329178+07:00` (WIB) |
| `php artisan test --log-junit docs/bukti/phpunit.xml` | 146 tes, 1.092 asersi; 0 kegagalan, 0 kesalahan, 0 dilewati; durasi konsol eksekusi akhir 11,67 detik |
| Bukti PHPUnit | [JUnit](bukti/phpunit.xml) dan [keluaran PHPUnit akhir](bukti/hasil_phpunit.txt) |
| `py tools/uji_browser.py` | 12/12 skenario LULUS, tersimpan dalam [JSON](bukti/hasil_browser.json) |
| `composer validate` | Lulus setelah penyelarasan berkas pengunci |
| Pint | Lulus pada 29 berkas |
| `php artisan view:cache` | Lulus |

Uji peramban memakai **Chrome headless nyata**, `APP_ENV=local` agar middleware CSRF aktif, serta basis data `laundry_2401020158_testing`. Port sementara **8765** untuk normal dan **8766** untuk kegagalan koneksi; keduanya dihentikan setelah uji. Aplikasi pengguna tetap dijalankan dengan `php artisan serve` di port **8000**. Python/Playwright dan ReportLab/Pillow hanya alat pengujian/pembuatan PDF, bukan JavaScript atau dependensi tampilan aplikasi.

Dua skenario tambahan yang lulus:

1. **CSRF HTTP nyata:** POST serta PUT/DELETE melalui spoofing tanpa token menghasilkan **419**. Token sah dengan kolom tidak valid menghasilkan **302** menuju penanganan validasi. `noValidate` hanya diaktifkan oleh alat uji untuk melewati validasi HTML dan mencapai server, bukan ditambahkan ke kode aplikasi.
2. **Isolasi dan uji ulang:** koneksi normal kembali **200**, fixture kembali berisi tiga pesanan awal, dan perbandingan seluruh baris kedua tabel utama sebelum/sesudah menunjukkan data identik.

Suite PHPUnit memakai PDO/MariaDB nyata, bukan SQLite. Pengaman memeriksa konfigurasi serta `SELECT DATABASE()`; pemuat fixture tidak mengeksekusi `CREATE DATABASE`/`USE` dari SQL utama dan membangun ulang hanya `pesanan_laundry` lalu `jenis_layanan` pada basis data uji. Pengujian tidak dijalankan paralel. Prosedur pemeriksaan ulang ada dalam [README](../README.md#pengujian-dengan-mariadb-nyata).

PDF laporan terdiri dari **11 halaman** (satu cover, empat halaman isi, dan enam halaman lampiran) dengan **enam contoh tangkapan layar asli**; seluruh **11 PNG** tersedia terpisah pada direktori bukti sebagai tangkapan **seluruh halaman asli**. URL permintaan dirender oleh Blade di header halaman; **bukan bilah alamat asli peramban**. Tidak ada lapisan bilah alamat buatan, penimpaan, atau manipulasi gambar. Dua gambar dipakai untuk form edit dan hasil update; kasus lain masing-masing satu gambar.

## Kendala, perbaikan, dan uji ulang

Masalah nyata terjadi pada alat `tools/uji_browser.py`, bukan hasil pencarian aplikasi. Pada percobaan awal, asersi tangkapan layar skenario 08 membandingkan `page.url` dengan URL dari `url()->full()` secara literal. Peramban memuat `?q=081200000003&jenis_layanan_id=`, sedangkan Laravel menormalkan urutan menjadi `?jenis_layanan_id=&q=081200000003`. Hasil pencarian sudah benar, tetapi urutan berbeda membuat asersi gagal.

Perbaikan pada helper `screenshot()`: pisahkan URL menggunakan `urlsplit` dari `urllib.parse`, bandingkan skema/host/path, lalu bandingkan parameter memakai `parse_qs(..., keep_blank_values=True)`. Nilai filter kosong tetap diperhitungkan tanpa menganggap urutan parameter sebagai perbedaan makna. Setelah perbaikan, pengujian penuh diulang dan **12/12 skenario lulus**. Detail reproduksi, perubahan, dan bukti tersedia pada [naskah issue siap ditempel](GITHUB_ISSUE.md).

**Status masalah: selesai secara lokal, belum dipublikasikan sebagai GitHub Issue.** Tidak ada nomor issue, hash commit, atau URL GitHub yang dibuat-buat; belum dilakukan `git init`, commit, atau push. Publikasi memerlukan akun/URL dan izin pemilik proyek.

## Simpulan

Implementasi memenuhi sepuluh skenario wajib Tipe C pada pengujian lokal 2026-11-03. PHPUnit lulus 146 tes/1.092 asersi; peramban nyata lulus 12 skenario, termasuk CSRF aktif dan pembuktian isolasi basis data utama. Kendala pemeriksaan URL pada alat uji diperbaiki dan diuji ulang. Source code, SQL, dokumentasi, serta bukti berada dalam direktori proyek terisolasi; dokumen soal DOCX asli tidak diubah.

**Kewajiban pengumpulan yang masih terbuka:** minimal lima commit terstruktur, publikasi repositori/push, publikasi satu issue nyata, dan pengiriman tautan melalui media dosen. Hasil lokal dan PDF bukan pengganti kewajiban GitHub tersebut; lihat [panduan pengumpulan](PENGUMPULAN.md).

## Lampiran kasus tambahan

| Kasus | Hasil aktual dan sumber pemeriksaan | Status |
| --- | --- | --- |
| Batas berat dan fungsi | 1/20 diterima; 0, 21, negatif, pecahan ditolak; 9 Reguler dan 10 Besar — PHPUnit | LULUS |
| Panjang dan tipe masukan | Kode/nama/telepon di luar batas serta tipe larik ditolak — PHPUnit | LULUS |
| Sanitasi dan keluaran | Teks di-trim, tag dibuang, keluaran Blade di-escape — PHPUnit | LULUS |
| Pencarian aman | Tiga kolom, hasil kosong, gabungan filter, dan masukan menyerupai injeksi SQL diuji — PHPUnit dan peramban | LULUS |
| Pembaruan identik | Nilai unik sendiri dan pembaruan tanpa perubahan diterima — PHPUnit | LULUS |
| ID dan layanan | Pesanan hilang 404; layanan tidak ada ditolak; metode tidak sesuai ditolak — PHPUnit | LULUS |
| Constraint SQL | UNIQUE, NOT NULL, CHECK/UNSIGNED, FK, UPDATE CASCADE, DELETE RESTRICT diuji langsung di MariaDB — PHPUnit | LULUS |
| Pelaporan kesalahan | Kegagalan baca/mutasi/kueri validasi dilaporkan tanpa membocorkan detail — PHPUnit; koneksi gagal nyata memberi 503 — peramban | LULUS |
| Isolasi dan CSRF | Seluruh baris utama identik; fixture tiga pesanan; permintaan tanpa token 419 — peramban/HTTP nyata | LULUS |

Status LULUS terbatas pada lingkungan dan kasus yang dicatat, bukan jaminan untuk semua konfigurasi komputer atau kondisi produksi.
