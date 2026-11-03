# Perbaiki perbandingan URL pada uji pencarian

**Status: [issue #1](https://github.com/christinesimbolon/uts-ppw-2401020158/issues/1) sudah dipublikasikan dan ditutup sebagai selesai** pada repositori [christinesimbolon/uts-ppw-2401020158](https://github.com/christinesimbolon/uts-ppw-2401020158), cabang `main`. Masalah direproduksi, diperbaiki, dan diuji ulang secara lokal pada 2026-11-03 sebelum publikasi. Issue adalah catatan teknis retrospektif yang transparan, bukan kronologi seolah perbaikan baru terjadi setelah pelaporan.

## Identitas dan lingkungan

- Christine Simbolon — NIM 2401020158 — Paket 2, Tipe C.
- PHP 8.3.33; Laravel 12.70.2; MariaDB 10.4.32; Git 2.52.0.windows.1.
- Chrome headless nyata melalui Playwright/Python; `APP_ENV=local` agar CSRF aktif.
- Basis data mutasi uji: `laundry_2401020158_testing`.
- Server normal: `http://127.0.0.1:8765`; simulasi koneksi gagal: `http://127.0.0.1:8766`.
- Berkas terdampak: `tools/uji_browser.py`; perbaikan pada alat uji, bukan controller, kueri pencarian, atau Blade aplikasi.

## Masalah dan reproduksi awal

1. Jalankan alat uji dari akar proyek dengan `py tools/uji_browser.py` setelah prasyarat lokal tersedia.
2. Pada skenario 08, lakukan pencarian berturut-turut berdasarkan kode, nama, dan nomor telepon. Permintaan terakhir mencari `081200000003` dengan filter layanan kosong.
3. Peramban memuat URL:

   ```text
   http://127.0.0.1:8765/pesanan-laundry?q=081200000003&jenis_layanan_id=
   ```

4. URL yang dirender Laravel melalui `url()->full()` pada header halaman memiliki urutan parameter berbeda:

   ```text
   http://127.0.0.1:8765/pesanan-laundry?jenis_layanan_id=&q=081200000003
   ```

5. Asersi tangkapan layar pada versi awal membandingkan kedua URL sebagai string literal. **Hasil pencarian sudah benar**, tetapi asersi gagal pada skenario 08 karena urutan parameter berbeda.

Reproduksi ini berasal dari percobaan lokal yang benar-benar dilakukan. Temuan adalah kegagalan asersi alat uji, bukan kesalahan hasil pencarian aplikasi.

## Hasil yang diharapkan

Alat uji menerima kedua URL karena skema, host/port, path, dan parameter kuerinya sama. Parameter kosong tetap diperiksa. Pengujian tidak boleh gagal hanya akibat urutan parameter, tetapi tetap harus menolak perbedaan nilai atau tujuan URL yang nyata.

## Pemeriksaan dan akar penyebab

- Pencarian menghasilkan satu baris pesanan Nina sesuai nomor telepon; kegagalan bukan pada pencarian atau filter.
- Pemeriksaan gagal di helper `screenshot()` setelah pemeriksaan hasil pencarian, sebelum pencatatan hasil skenario selesai.
- Perbedaan terletak pada representasi string URL oleh peramban dan normalisasi Laravel, bukan makna parameter.
- Perbandingan literal terlalu ketat untuk kueri ini; menghapus pemeriksaan URL seluruhnya akan melemahkan bukti dan bukan solusi yang dipilih.

## Perbaikan yang sudah diterapkan

Pada `tools/uji_browser.py`, helper `screenshot()` kini membandingkan komponen dan parameter menggunakan `urlsplit` dan `parse_qs` dari `urllib.parse`:

```python
href = page.locator("header a").last.get_attribute("href")
assert isinstance(href, str)
shown = urlsplit(href)
actual = urlsplit(page.url)
assert (shown.scheme, shown.netloc, shown.path) == (actual.scheme, actual.netloc, actual.path)
assert parse_qs(shown.query, keep_blank_values=True) == parse_qs(actual.query, keep_blank_values=True)
```

`keep_blank_values=True` mempertahankan `jenis_layanan_id=` sehingga keberadaan filter kosong tidak diabaikan. Pengambilan PNG tetap memakai `full_page=True`; gambar tidak diberi bilah alamat buatan atau diubah.

## Uji ulang aktual

Perintah pengulangan penuh yang dijalankan:

```bat
py tools/uji_browser.py
```

| Pemeriksaan | Hasil aktual |
| --- | --- |
| Waktu hasil akhir | `2026-11-03T15:20:02.329178+07:00` |
| Seluruh skenario | **12/12 LULUS** |
| Regresi pencarian | Kode `LD00000001`, nama `Rafi`, dan telepon `081200000003` masing-masing menghasilkan satu pesanan sesuai |
| Filter | Cuci Kering menghasilkan Ayu/Nina; digabung kata Nina menghasilkan Nina |
| CSRF nyata | POST serta spoofing PUT/DELETE tanpa token: 419; token sah dengan kolom tidak valid: 302 |
| Gangguan koneksi | Port basis data 1: 503 aman, rincian SQLSTATE tercatat di log dan tidak tampil di halaman |
| Pemulihan dan isolasi | Koneksi normal kembali 200; fixture tiga pesanan; seluruh baris kedua tabel utama identik sebelum/sesudah |
| Penghentian proses | Kedua server sementara dihentikan pada akhir alat uji |

Bukti setelah perbaikan: [hasil browser lengkap](bukti/hasil_browser.json), [tangkapan layar pencarian](bukti/08_pencarian.png), dan [filter](bukti/09_filter.png). JSON tersebut mencatat hasil pengulangan yang lulus, bukan log percobaan awal yang gagal. URL pada gambar adalah keluaran header Blade, **bukan bilah alamat asli peramban**.

Pemeriksaan aplikasi terpisah juga lulus: `php artisan test --log-junit docs/bukti/phpunit.xml` menghasilkan **146 tes dan 1.092 asersi**; [JUnit](bukti/phpunit.xml) mencatat tanpa kegagalan/kesalahan/tes dilewati. Hal ini melengkapi, bukan menggantikan, uji ulang alat peramban.

## Status publikasi dan penutupan

- [x] Masalah nyata dan langkah reproduksi dijelaskan.
- [x] Akar penyebab serta perubahan alat uji dicatat.
- [x] Pengulangan penuh lulus dan bukti lokal tersedia.
- [x] Versi alat uji yang telah diperbaiki tercatat pada [commit 05e65d9](https://github.com/christinesimbolon/uts-ppw-2401020158/commit/05e65d9); laporan/bukti awal pada [commit 3c81143](https://github.com/christinesimbolon/uts-ppw-2401020158/commit/3c81143).
- [x] [Issue #1](https://github.com/christinesimbolon/uts-ppw-2401020158/issues/1) dipublikasikan pada akun Christine dan ditutup sebagai selesai karena perbaikan serta uji ulang sudah tersedia.

Lima commit awal dibuat dengan izin Christine saat publikasi dan berhasil di-push; tanggal tidak dimundurkan. [Riwayat `main`](https://github.com/christinesimbolon/uts-ppw-2401020158/commits/main/) mencatat pengelompokan berkas yang sudah tersedia, bukan rekonstruksi palsu riwayat debugging. Pemeriksaan aplikasi terbaru `composer validate --no-check-publish && php artisan test` juga lulus 146 tes/1.092 asersi dalam 14,83 detik; log bukti sebelumnya tetap merekam 11,67 detik dan tidak dilabel ulang. Status pengumpulan ada di [PENGUMPULAN.md](PENGUMPULAN.md).
