# Status Pengumpulan — Christine Simbolon

**NIM:** 2401020158 · **Paket 2, Tipe C:** Sistem Pesanan Laundry.

## Tautan dan status aktual

| Kebutuhan | Status terverifikasi |
| --- | --- |
| Repositori | [christinesimbolon/uts-ppw-2401020158](https://github.com/christinesimbolon/uts-ppw-2401020158), milik akun `christinesimbolon` |
| Cabang dan push | `main`; lima commit awal berhasil di-push dengan izin Christine |
| Riwayat | [Minimal lima commit nyata](https://github.com/christinesimbolon/uts-ppw-2401020158/commits/main/), dibuat saat publikasi, tanpa memundurkan tanggal |
| Issue teknis | [Issue #1](https://github.com/christinesimbolon/uts-ppw-2401020158/issues/1), dipublikasikan dan ditutup sebagai selesai |
| Laporan | [Sumber laporan](LAPORAN.md) dan [PDF](Laporan_Christine_Simbolon_2401020158.pdf): 17 halaman, A4, margin 3 cm semua sisi, Times New Roman, cover berlogo UMRAH; kode DDL/DML dicantumkan langsung pada bagian 2.1–2.2 |
| Pengujian terbaru | `composer validate --no-check-publish && php artisan test` lulus; 146 tes / 1.092 asersi, 14,83 detik |
| Bukti PHPUnit tersimpan | [JUnit](bukti/phpunit.xml) dan [keluaran tes](bukti/hasil_phpunit.txt), dari eksekusi sebelumnya: 146 tes / 1.092 asersi, 11,67 detik; tidak ditimpa atau dilabel ulang |
| Peramban nyata | [12/12 skenario LULUS](bukti/hasil_browser.json), termasuk 10 wajib, CSRF, dan isolasi; waktu `2026-11-03T15:20:02.329178+07:00` |
| Bukti gambar | Seluruh 11 PNG asli dimuat dalam PDF untuk 10 skenario wajib; salinan tersedia di [direktori bukti](bukti/) |
| Pemeriksaan lainnya | Composer lulus, Pint lulus pada 29 berkas, cache Blade lulus |
| Pengiriman kepada dosen | **Belum dilakukan**; kirim tautan/laporan melalui media yang ditentukan dosen |

Pengujian berasal dari komputer pengembangan, bukan klaim bahwa komputer penerima sudah diuji. Format laporan dan hasil pengujian tetap dipertahankan. Logo kanonis yang dilacak berada di `docs/aset/UMRAH.png`.

## Lima commit awal yang sudah dipublikasikan

| Commit | Pesan |
| --- | --- |
| [90b7938](https://github.com/christinesimbolon/uts-ppw-2401020158/commit/90b7938) | Siapkan konfigurasi dasar aplikasi laundry |
| [8a9fb69](https://github.com/christinesimbolon/uts-ppw-2401020158/commit/8a9fb69) | Tambahkan skema dan data awal laundry |
| [697e3ba](https://github.com/christinesimbolon/uts-ppw-2401020158/commit/697e3ba) | Terapkan CRUD pencarian dan filter laundry |
| [05e65d9](https://github.com/christinesimbolon/uts-ppw-2401020158/commit/05e65d9) | Tambahkan pengujian dan isolasi basis data |
| [3c81143](https://github.com/christinesimbolon/uts-ppw-2401020158/commit/3c81143) | Lengkapi laporan dan bukti pengujian |

Kelima commit dibuat saat publikasi untuk mengelompokkan pekerjaan yang sudah tersedia, bukan riwayat lama yang direkayasa. Pembaruan berikutnya melanjutkan riwayat ini. Identitas Git disetel **hanya pada repositori ini**: `Christine Simbolon`, `190502785+christinesimbolon@users.noreply.github.com`; bukan konfigurasi global.

[Issue #1](https://github.com/christinesimbolon/uts-ppw-2401020158/issues/1) mencatat kegagalan asersi URL pada alat uji, pemeriksaan, perbaikan `urlsplit`/`parse_qs`, dan pengulangan 12/12 yang lulus. Perbaikan serta uji ulang mendahului publikasi; issue ditutup sebagai catatan teknis retrospektif. [Catatan lengkap](GITHUB_ISSUE.md) mempertahankan kronologi tersebut secara jujur.

## Pembaruan selanjutnya

Perintah berikut adalah panduan **untuk pekerjaan berikutnya**, tidak dijalankan oleh penyuntingan dokumentasi ini. Gunakan clone repositori yang sama, bukan `git init` baru. Mulai dari cabang `main` yang bersih dan periksa remote sebelum mengambil perubahan; jika ada pekerjaan lokal, simpan dengan aman dahulu.

Contoh Git Bash:

```sh
git --no-pager --no-optional-locks status --short
git --no-pager branch --show-current
git --no-pager remote -v
git pull --ff-only origin main
```

Tinjau dan pahami perubahan, lalu jalankan pengujian yang relevan sesuai [README](../README.md). Untuk perubahan empat dokumen publik, staging secara spesifik:

```sh
git add README.md docs/LAPORAN.md docs/GITHUB_ISSUE.md docs/PENGUMPULAN.md
git --no-pager diff --cached
GIT_EDITOR=true git commit -m "Perbarui dokumentasi aplikasi laundry"
git push origin main
```

Tambahkan berkas lain hanya jika memang berubah dan telah diperiksa. Autentikasi melalui Git Credential Manager/peramban dengan akun yang berhak mengakses repositori; jangan menaruh token di URL atau membagikan kata sandi. Jika pull/push ditolak, hentikan dan periksa penyebabnya; jangan memakai force push atau menulis ulang riwayat untuk mengejar jumlah commit.

## Keamanan dan pemeriksaan sebelum mengirim

- `.env` dan konfigurasi privat, `vendor/`, ZIP/folder paket kirim, cache/log/session runtime, `.tools/`, dan profil peramban tidak dipublikasikan. Panduan serah-terima lokal, pembuat ZIP, duplikat logo di akar proyek, serta gambar pratinjau PDF juga dikecualikan. Jangan memaksa berkas yang diabaikan masuk staging.
- Bukti pengujian yang memang dipublikasikan berbeda dari log runtime. Jangan menyalin rahasia ke issue atau gambar. URL pada PNG adalah header Blade, **bukan bilah alamat asli peramban**; gambar tidak dimanipulasi.
- Saat mengulang tes, mutasi hanya boleh menyasar `laundry_2401020158_testing`, bukan basis data utama; jangan menjalankan paralel. Prosedur pemasangan dan pengaman ada dalam README.
- Buka repositori, riwayat commit, issue, dan PDF untuk memastikan akses dosen sesuai kebutuhan. Lalu kirim tautan/laporan melalui media dosen; publikasi GitHub saja belum berarti tugas sudah dikumpulkan.
