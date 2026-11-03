"""Buat laporan A4 sederhana dengan margin 3 cm dan cover berlogo UMRAH.

Jalankan dari akar proyek: py tools/buat_laporan.py
Memerlukan ReportLab/Pillow. Tidak menjalankan tes atau mengubah bukti asli.
"""

import json
from html import escape
from pathlib import Path
from xml.etree import ElementTree

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.units import cm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate,
    Frame,
    Image,
    NextPageTemplate,
    PageBreak,
    PageTemplate,
    Paragraph,
    Preformatted,
    Spacer,
    Table,
    TableStyle,
)

ROOT = Path(__file__).resolve().parents[1]
DOCS = ROOT / "docs"
BUKTI = DOCS / "bukti"
LOGO = DOCS / "aset" / "UMRAH.png"
OUTPUT = DOCS / "Laporan_Christine_Simbolon_2401020158.pdf"
SQL_SOURCE = ROOT / "database" / "sql" / "laundry_2401020158.sql"
MARGIN = 3 * cm
WIDTH = A4[0] - 2 * MARGIN
HEIGHT = A4[1] - 2 * MARGIN


def main():
    browser = json.loads((BUKTI / "hasil_browser.json").read_text(encoding="utf-8"))
    suite = ElementTree.parse(BUKTI / "phpunit.xml").getroot()[0]
    if any(int(suite.attrib[key]) != 0 for key in ("errors", "failures", "skipped")):
        raise RuntimeError("Laporan hasil lulus memerlukan JUnit tanpa kegagalan atau tes dilewati.")
    if len(browser["hasil"]) != 12 or any(row["status"] != "LULUS" for row in browser["hasil"]):
        raise RuntimeError("Lengkapi seluruh 12 hasil browser terlebih dahulu.")
    if not LOGO.is_file():
        raise RuntimeError("Logo belum tersedia di docs/aset/UMRAH.png.")
    sql = SQL_SOURCE.read_text(encoding="utf-8")
    ddl, marker, dml = sql.partition("INSERT INTO jenis_layanan")
    if not marker or "CREATE DATABASE" not in ddl:
        raise RuntimeError("Berkas SQL harus memuat DDL dan INSERT data awal jenis layanan.")
    ddl = ddl[ddl.index("CREATE DATABASE"):].strip()
    dml = (marker + dml).strip()

    for name, filename in [("TimesLaporan", "times.ttf"), ("TimesLaporanBold", "timesbd.ttf"), ("Kode", "consola.ttf")]:
        path = Path("C:/Windows/Fonts") / filename
        if not path.is_file():
            raise RuntimeError(f"Font lokal tidak ditemukan: {filename}")
        pdfmetrics.registerFont(TTFont(name, str(path)))
    pdfmetrics.registerFontFamily("TimesLaporan", normal="TimesLaporan", bold="TimesLaporanBold")
    styles = {
        "isi": ParagraphStyle("Isi", fontName="TimesLaporan", fontSize=12, leading=16,
                              alignment=TA_JUSTIFY, spaceAfter=8),
        "judul": ParagraphStyle("Judul", fontName="TimesLaporanBold", fontSize=14, leading=20,
                                alignment=TA_CENTER, spaceAfter=6),
        "cover": ParagraphStyle("Cover", fontName="TimesLaporan", fontSize=12, leading=18,
                                alignment=TA_CENTER, spaceAfter=0),
        "cover_bold": ParagraphStyle("CoverBold", fontName="TimesLaporanBold", fontSize=12, leading=18,
                                     alignment=TA_CENTER, spaceAfter=0),
        "subjudul": ParagraphStyle("Subjudul", fontName="TimesLaporanBold", fontSize=12, leading=17,
                                   spaceBefore=8, spaceAfter=7, keepWithNext=True),
        "sel": ParagraphStyle("Sel", fontName="TimesLaporan", fontSize=9.5, leading=12),
        "kecil": ParagraphStyle("Kecil", fontName="TimesLaporan", fontSize=10, leading=13, spaceAfter=7),
        "kode": ParagraphStyle("Kode", fontName="Kode", fontSize=8, leading=11, spaceAfter=8),
        "kode_sql": ParagraphStyle("KodeSQL", fontName="Kode", fontSize=9, leading=11, spaceAfter=8),
    }
    story = []

    def paragraph(text, style="isi"):
        return Paragraph(text, styles[style])

    def add(text, style="isi"):
        story.append(paragraph(text, style))

    def heading(text):
        add(text, "subjudul")

    def table(rows, weights):
        data = []
        for index, row in enumerate(rows):
            cells = []
            for cell in row:
                text = escape(str(cell)).replace("\n", "<br/>")
                cells.append(paragraph(f"<b>{text}</b>" if index == 0 else text, "sel"))
            data.append(cells)
        result = Table(data, colWidths=[WIDTH * weight / sum(weights) for weight in weights],
                       repeatRows=1, hAlign="LEFT")
        result.setStyle(TableStyle([
            ("VALIGN", (0, 0), (-1, -1), "TOP"),
            ("GRID", (0, 0), (-1, -1), 0.4, colors.black),
            ("LEFTPADDING", (0, 0), (-1, -1), 4),
            ("RIGHTPADDING", (0, 0), (-1, -1), 4),
            ("TOPPADDING", (0, 0), (-1, -1), 4),
            ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
        ]))
        story.extend([result, Spacer(1, 7)])

    def sql_block(text):
        # Pecah hanya antarargumen, bukan di dalam literal nama atau nomor telepon.
        formatted = text.replace(", created_at, updated_at)", ",\n     created_at, updated_at)")
        formatted = formatted.replace(", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                                      ",\n     CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)")
        if any(pdfmetrics.stringWidth(line, "Kode", 9) > WIDTH for line in formatted.splitlines()):
            raise RuntimeError("Baris kode SQL melebihi margin laporan; tata ulang antarargumennya.")
        story.append(Preformatted(formatted, styles["kode_sql"]))

    # Cover berdiri sendiri dan tidak diberi nomor halaman.
    story.append(Spacer(1, 1.2 * cm))
    add("LAPORAN PRAKTIKUM PEMROGRAMAN WEB<br/>SISTEM PESANAN LAUNDRY", "judul")
    story.append(Spacer(1, 0.3 * cm))
    add("Studi Kasus Paket 2 — Tipe C", "cover")
    story.append(Spacer(1, 1.2 * cm))
    logo = Image(str(LOGO), width=5.5 * cm, height=5.5 * cm, kind="proportional")
    logo.hAlign = "CENTER"
    story.append(logo)
    story.append(Spacer(1, 1.3 * cm))
    add("Disusun oleh:", "cover")
    story.append(Spacer(1, 0.3 * cm))
    add("Christine Simbolon<br/>NIM 2401020158", "cover_bold")
    story.append(Spacer(1, 2.4 * cm))
    add("FAKULTAS TEKNIK DAN TEKNOLOGI KEMARITIMAN<br/>UNIVERSITAS MARITIM RAJA ALI HAJI<br/>2026", "cover_bold")
    story.extend([NextPageTemplate("Isi"), PageBreak()])

    heading("1. Pendahuluan")
    add("Aplikasi Sistem Pesanan Laundry dibuat untuk membantu pencatatan pesanan berdasarkan jenis layanan. Fitur yang disediakan meliputi tambah, tampil, ubah, hapus, pencarian, dan filter. Aplikasi menggunakan Laravel, PHP, PDO, serta MariaDB dengan tampilan Blade dan HTML sederhana tanpa CSS atau JavaScript.")
    heading("2. Rancangan Basis Data")
    add("Nama basis data adalah <b>laundry_2401020158</b>. Relasinya satu-ke-banyak: satu jenis layanan dapat digunakan oleh banyak pesanan. Kolom <b>jenis_layanan_id</b> menjadi foreign key dengan ON UPDATE CASCADE dan ON DELETE RESTRICT.")
    table([
        ["Tabel", "Kolom dan ketentuan"],
        ["jenis_layanan", "id: BIGINT UNSIGNED, AUTO_INCREMENT, PRIMARY KEY.\nnama_layanan: VARCHAR(100), NOT NULL, UNIQUE."],
        ["pesanan_laundry", "id: BIGINT UNSIGNED, AUTO_INCREMENT, PRIMARY KEY.\nkode_pesanan: CHAR(10), NOT NULL, UNIQUE.\nnama_pelanggan: VARCHAR(100), NOT NULL.\nnomor_telepon: VARCHAR(20), NOT NULL, UNIQUE.\nberat_kg: SMALLINT UNSIGNED, NOT NULL, CHECK 1–20.\njenis_layanan_id: BIGINT UNSIGNED, NOT NULL, FK.\ncreated_at dan updated_at: TIMESTAMP."],
    ], [1.15, 3.25])
    add("Berkas SQL memuat DDL, DML, dua layanan, dan tiga pesanan awal berikut.", "kecil")
    table([
        ["Kode", "Pelanggan", "Telepon", "Kg", "Layanan"],
        ["LD00000001", "Ayu Lestari", "081200000001", "5", "Cuci Kering"],
        ["LD00000002", "Rafi Akbar", "081200000002", "8", "Cuci Setrika"],
        ["LD00000003", "Nina Putri", "081200000003", "3", "Cuci Kering"],
    ], [1.2, 1.25, 1.3, .35, 1.15])
    add("Fungsi statusBerat() menampilkan kategori <b>Besar</b> jika berat minimal 10 kg dan <b>Reguler</b> jika di bawah 10 kg. Kategori ditampilkan melalui percabangan dan perulangan pada Blade.")

    story.append(PageBreak())
    heading("2.1 Kode DDL (Data Definition Language)")
    add("DDL berikut membentuk database, dua tabel, dan constraint. USE memilih database yang dipakai. Kode diambil langsung dari database/sql/laundry_2401020158.sql.", "kecil")
    sql_block(ddl)
    heading("2.2 Kode DML (Data Manipulation Language)")
    add("INSERT berikut mengisi dua jenis layanan dan tiga pesanan awal sesuai soal. Impor hanya dilakukan pada pemasangan baru agar tidak berbenturan dengan data yang sudah ada.", "kecil")
    sql_block(dml)

    story.append(PageBreak())
    heading("3. Implementasi Aplikasi")
    table([
        ["Metode", "Route", "Fungsi"],
        ["GET", "/pesanan-laundry", "Menampilkan indeks"],
        ["POST", "/pesanan-laundry", "Menyimpan pesanan"],
        ["GET", "/pesanan-laundry/{id}/edit", "Menampilkan form edit"],
        ["PUT", "/pesanan-laundry/{id}", "Memperbarui pesanan"],
        ["DELETE", "/pesanan-laundry/{id}", "Menghapus pesanan"],
    ], [.7, 2.45, 1.4])
    add("Halaman indeks memuat form tambah, pencarian, filter, pesan hasil operasi, dan tabel pesanan. Pencarian memeriksa kode pesanan, nama pelanggan, serta nomor telepon. Filter menggunakan jenis layanan. Route / mengalihkan pengguna ke indeks.")
    heading("3.1 Validasi dan Pengamanan Input")
    add("Input teks dibersihkan menggunakan trim() dan strip_tags(). Kode wajib tepat 10 karakter alfanumerik; nama 3–100 karakter; telepon 10–20 karakter; berat bilangan bulat 1–20 kg; dan layanan harus tersedia. Kode serta telepon tidak boleh sama dengan pesanan lain.")
    add("Form memakai @csrf dan method spoofing untuk PUT serta DELETE. Pesan kesalahan ditampilkan per kolom, sedangkan old() mempertahankan input yang ditolak. Blade melakukan escape pada keluaran. File .env tidak disertakan dalam repository.")
    heading("3.2 Prepared Statement dan Penanganan Kesalahan")
    add("Koneksi PDO diambil dari koneksi Laravel. Semua kueri yang menerima input menggunakan prepare() dan execute(), sehingga input tidak digabungkan langsung ke struktur SQL. Contohnya:")
    story.append(Preformatted("$pdo = DB::connection()->getPdo();\n$stmt = $pdo->prepare(\n    'SELECT * FROM pesanan_laundry WHERE id = :id'\n);\n$stmt->execute(['id' => $id]);", styles["kode"]))
    add("Kesalahan koneksi atau kueri ditangani dengan try–catch. Detail dicatat melalui report(), sedangkan pengguna menerima pesan aman. Kegagalan membaca data menghasilkan HTTP 503; kegagalan menyimpan, mengubah, atau menghapus memberi pesan tanpa menampilkan SQL, kredensial, atau jejak kesalahan.")

    story.append(PageBreak())
    heading("4. Hasil Pengujian")
    add("Pengujian dilakukan pada 3 November 2026 menggunakan PHP 8.3.33, Laravel 12.70.2, dan MariaDB 10.4.32. Database pengujian dipisahkan dari database utama.")
    order = ["Read", "Create valid", "Create tidak valid", "Duplikat", "Update", "Konflik update", "Delete", "Pencarian", "Filter", "Error handling"]
    by_name = {item["uji"]: item for item in browser["hasil"]}
    rows = [["No.", "Pengujian", "Hasil aktual", "Status"]]
    for number, name in enumerate(order, 1):
        item = by_name[name]
        rows.append([str(number), name, item["hasil"], item["status"]])
    table(rows, [.35, 1.1, 2.75, .55])
    add(f"PHPUnit berhasil menjalankan <b>{suite.attrib['tests']} tes dengan {suite.attrib['assertions']} asersi</b>, tanpa kegagalan. Uji browser dan HTTP nyata lulus <b>12 dari 12 skenario</b>, termasuk sepuluh pengujian wajib.")
    add("Pengujian tambahan membuktikan bahwa permintaan tanpa token CSRF ditolak dengan HTTP 419. Gangguan koneksi menghasilkan HTTP 503 yang aman, lalu kembali HTTP 200 setelah koneksi normal. Data pada database utama tidak berubah.")
    add("Bukti lengkap: docs/bukti/phpunit.xml, hasil_phpunit.txt, hasil_browser.json, dan sebelas tangkapan layar asli.", "kecil")

    story.append(PageBreak())
    heading("5. Kendala dan Perbaikan")
    add("Kendala terjadi pada alat pengujian browser saat membandingkan URL pencarian. Browser dan Laravel menampilkan urutan parameter yang berbeda, walaupun hasil pencariannya benar. Akibatnya, pemeriksaan URL secara langsung sempat gagal.")
    add("Pemeriksaan dilakukan dengan membandingkan hasil pencarian dan isi parameter URL. Perbaikannya menggunakan urlsplit() serta parse_qs(..., keep_blank_values=True), sehingga yang dibandingkan adalah tujuan dan nilai parameter, bukan urutannya. Setelah perbaikan, seluruh 12 skenario browser diuji ulang dan lulus.")
    heading("6. Simpulan")
    add("Aplikasi Sistem Pesanan Laundry dapat menjalankan CRUD, pencarian tiga kolom, filter layanan, validasi input, dan penanganan kesalahan sesuai Tipe C. Pengujian menunjukkan seluruh skenario yang dicatat berjalan sesuai harapan. Prepared statement dan constraint basis data membantu menjaga keamanan serta integritas data.")
    heading("7. Status Pengumpulan")
    add("Source code, SQL, laporan, serta bukti pengujian sudah dipublikasikan menggunakan akun <b>christinesimbolon</b>. Minimal lima commit nyata sudah di-push ke branch main. Issue teknis sudah dibuat dan ditutup sebagai selesai.")
    add('<b>Repositori:</b> <link href="https://github.com/christinesimbolon/uts-ppw-2401020158">https://github.com/christinesimbolon/uts-ppw-2401020158</link>', "kecil")
    add('<b>Issue #1:</b> <link href="https://github.com/christinesimbolon/uts-ppw-2401020158/issues/1">https://github.com/christinesimbolon/uts-ppw-2401020158/issues/1</link>', "kecil")
    add("Perbaikan dan uji ulang dilakukan sebelum publikasi issue; issue merupakan catatan teknis retrospektif, bukan kronologi rekaan. Pengiriman tautan dan laporan melalui media dosen belum dilakukan.", "kecil")
    heading("Keterangan Lampiran")
    add("Laporan ini menyertakan enam contoh tangkapan layar. Seluruh sebelas gambar tersedia di docs/bukti/. Gambar diambil langsung dari aplikasi; URL aktual ditampilkan oleh Blade pada header halaman, bukan bilah browser buatan. Bukti asli tidak diubah.")
    add("Waktu hasil browser: " + escape(browser["waktu"]) + ". Angka pengujian dibaca dari berkas JUnit aktual.", "kecil")

    examples = [
        ("01_read.png", "1. Tampilan Data Awal"),
        ("02_create_valid.png", "2. Penambahan Data Berhasil"),
        ("03_create_tidak_valid.png", "3. Validasi Input dan Input Lama"),
        ("05b_update.png", "4. Pembaruan Data Berhasil"),
        ("09_filter.png", "5. Filter Jenis Layanan"),
        ("10_error_handling.png", "6. Penanganan Gangguan Koneksi"),
    ]
    for filename, caption in examples:
        story.append(PageBreak())
        heading("Lampiran " + caption)
        add("Sumber: docs/bukti/" + filename, "kecil")
        image = Image(str(BUKTI / filename))
        scale = min(WIDTH / image.imageWidth, (HEIGHT - 95) / image.imageHeight)
        image.drawWidth = image.imageWidth * scale
        image.drawHeight = image.imageHeight * scale
        image.hAlign = "CENTER"
        story.append(image)

    def footer(canvas, document):
        canvas.saveState()
        canvas.setFont("TimesLaporan", 10)
        # Nomor berada di dalam batas 3 cm; cover tidak memakai callback ini.
        canvas.drawCentredString(A4[0] / 2, MARGIN + 3, str(document.page - 1))
        canvas.restoreState()

    document = BaseDocTemplate(
        str(OUTPUT), pagesize=A4,
        leftMargin=MARGIN, rightMargin=MARGIN, topMargin=MARGIN, bottomMargin=MARGIN,
        title="Laporan Sistem Pesanan Laundry - Christine Simbolon 2401020158",
        author="Christine Simbolon", subject="Praktikum Pemrograman Web Paket 2 Tipe C",
    )
    cover_frame = Frame(MARGIN, MARGIN, WIDTH, HEIGHT,
                        leftPadding=0, rightPadding=0, topPadding=0, bottomPadding=0)
    # Cadangkan ruang nomor halaman tanpa keluar dari margin bawah.
    body_frame = Frame(MARGIN, MARGIN + 18, WIDTH, HEIGHT - 18,
                       leftPadding=0, rightPadding=0, topPadding=0, bottomPadding=0)
    document.addPageTemplates([
        PageTemplate(id="Cover", frames=cover_frame),
        PageTemplate(id="Isi", frames=body_frame, onPage=footer),
    ])
    document.build(story)
    print("PDF dibuat:", OUTPUT)
    print("Format: A4; margin 3 cm; Times New Roman; cover berlogo UMRAH.")


if __name__ == "__main__":
    main()
