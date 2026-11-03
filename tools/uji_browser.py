"""Uji HTTP/browser nyata; hanya mengosongkan dua tabel database *_testing.

Tambahan untuk pembuat bukti (bukan dependensi aplikasi): Python, Playwright,
dan Chrome di Windows. Server sementara selalu dihentikan setelah uji.
Jalankan dari direktori proyek: py tools/uji_browser.py
"""


import json
import os
import re
import shutil
import socket
import subprocess
import time
from contextlib import ExitStack
from datetime import datetime
from pathlib import Path
from urllib.error import HTTPError
from urllib.parse import parse_qs, urlsplit
from urllib.request import urlopen

from playwright.sync_api import expect, sync_playwright

ROOT = Path(__file__).resolve().parents[1]
EVIDENCE = ROOT / "docs" / "bukti"
TEST_DB = "laundry_2401020158_testing"
BASE = "http://127.0.0.1:8765"
ERROR_BASE = "http://127.0.0.1:8766"
def executable(name: str) -> str:
    path = shutil.which(name)
    if path is None:
        raise RuntimeError(f"Program {name} tidak ditemukan pada PATH.")
    return path


MYSQL = executable("mysql")
PHP = executable("php")
CHROME = Path("C:/Program Files/Google/Chrome/Application/chrome.exe")
RESULTS = []


def record(name, detail):
    RESULTS.append({"uji": name, "status": "LULUS", "hasil": detail})
    print("LULUS:", name, "-", detail, flush=True)


def sql(database, statement):
    if database not in (TEST_DB, "laundry_2401020158"):
        raise RuntimeError("Database tidak diizinkan.")
    return subprocess.run(
        [MYSQL, "--protocol=tcp", "-h", "127.0.0.1", "-u", "root", "--batch", "--skip-column-names", database],
        input=statement, text=True, capture_output=True, check=True, cwd=ROOT,
    ).stdout.strip()


def reset_fixture():
    assert sql(TEST_DB, "SELECT DATABASE();") == TEST_DB
    source = (ROOT / "database/sql/laundry_2401020158.sql").read_text(encoding="utf-8")
    source = re.sub(r"^--[^\n]*", "", source, flags=re.MULTILINE)
    source = re.sub(r"CREATE\s+DATABASE\b.*?;", "", source, flags=re.DOTALL | re.IGNORECASE)
    source = re.sub(r"\bUSE\s+`?laundry_2401020158`?\s*;", "", source, flags=re.IGNORECASE)
    if re.search(r"\b(?:USE|DATABASE)\b", source, re.IGNORECASE):
        raise RuntimeError("Fixture masih memuat pemilihan database.")
    sql(TEST_DB, "DROP TABLE IF EXISTS pesanan_laundry; DROP TABLE IF EXISTS jenis_layanan;\n" + source)



def start_server(port, resources: ExitStack, error=False):
    with socket.socket() as sock:
        if sock.connect_ex(("127.0.0.1", port)) == 0:
            raise RuntimeError(f"Port {port} sedang dipakai; tidak mengganggu proses yang sudah ada.")
    env = os.environ.copy()
    env.update({"APP_ENV": "local", "APP_DEBUG": "false", "DB_DATABASE": TEST_DB,
                "DB_CONNECTION": "mysql", "DB_HOST": "127.0.0.1", "DB_PORT": "1" if error else "3306",
                "DB_USERNAME": "root", "DB_PASSWORD": "", "SESSION_DRIVER": "file", "CACHE_STORE": "file"})
    log_path = ROOT / ".tools" / f"server-{port}.log"
    log = resources.enter_context(log_path.open("w", encoding="utf-8"))
    process = subprocess.Popen(
        [PHP, "-S", f"127.0.0.1:{port}", "-t", str(ROOT / "public"),
         str(ROOT / "vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php")],
        cwd=ROOT / "public", env=env, stdout=log, stderr=log,
    )
    for _ in range(80):
        if process.poll() is not None:
            log.close()
            raise RuntimeError("Server gagal dimulai; periksa .tools/server-*.log.")
        try:
            with urlopen(f"http://127.0.0.1:{port}/up", timeout=1):
                return process, log
        except HTTPError:
            return process, log
        except OSError:
            time.sleep(0.15)
    process.terminate()
    log.close()
    raise RuntimeError("Server tidak merespons.")


def fill(page, code="LD00000004", name="Dina Sari", phone="081200000004", weight="10", service="2"):
    for key, value in {"kode_pesanan": code, "nama_pelanggan": name, "nomor_telepon": phone, "berat_kg": weight}.items():
        page.locator("#" + key).fill(value)
    page.locator("#jenis_layanan_id").select_option(service)


def main():
    if not MYSQL or not PHP or not CHROME.exists():
        raise RuntimeError("Memerlukan mysql, php dan Chrome lokal.")
    if not (ROOT / ".env").exists():
        raise RuntimeError("Siapkan .env dan APP_KEY terlebih dahulu.")
    (ROOT / ".tools").mkdir(exist_ok=True)
    EVIDENCE.mkdir(parents=True, exist_ok=True)
    main_before = sql("laundry_2401020158", "SELECT * FROM jenis_layanan; SELECT * FROM pesanan_laundry;")
    reset_fixture()
    processes = []
    resources = ExitStack()

    try:
        processes.append(start_server(8765, resources))
        with sync_playwright() as playwright:
            context = playwright.chromium.launch_persistent_context(
                user_data_dir=str(ROOT / ".browser-profile"), executable_path=str(CHROME), headless=True,
                viewport={"width": 1280, "height": 960}, accept_downloads=False,
                args=["--no-first-run", "--disable-background-networking"],
            )
            try:
                page = context.pages[0]
                page.goto(BASE + "/pesanan-laundry")
                page.wait_for_load_state("networkidle")

                def screenshot(stem):
                    page.evaluate("window.scrollTo(0,0)")
                    href = page.locator("header a").last.get_attribute("href")
                    assert isinstance(href, str)
                    shown = urlsplit(href)
                    actual = urlsplit(page.url)
                    assert (shown.scheme, shown.netloc, shown.path) == (actual.scheme, actual.netloc, actual.path)
                    assert parse_qs(shown.query, keep_blank_values=True) == parse_qs(actual.query, keep_blank_values=True)
                    page.screenshot(path=str(EVIDENCE / (stem + ".png")), full_page=True)

                expect(page.locator("tbody tr")).to_have_count(3)
                expect(page.get_by_text("Christine Simbolon", exact=True)).to_be_visible()
                expect(page.locator("tbody")).to_contain_text("Cuci Setrika")
                screenshot("01_read")
                record("Read", "Tiga pesanan wajib dan nama layanan tampil.")

                fill(page)
                page.get_by_role("button", name="Simpan pesanan", exact=True).click()
                expect(page.get_by_role("status")).to_contain_text("berhasil ditambahkan")
                expect(page.locator("tbody tr")).to_have_count(4)
                expect(page.locator("tbody")).to_contain_text("Besar")
                screenshot("02_create_valid")
                record("Create valid", "LD00000004 tersimpan, feedback sukses dan kategori Besar tampil.")

                fill(page, code="LD00000005", name="", phone="081200000005", weight="0", service="1")
                page.locator("form").first.evaluate("form => form.noValidate = true")
                page.get_by_role("button", name="Simpan pesanan", exact=True).click()
                expect(page.locator("#ringkasan-kesalahan")).to_be_visible()
                expect(page.locator("#kode_pesanan")).to_have_value("LD00000005")
                expect(page.locator("#berat_kg")).to_have_value("0")
                expect(page.locator("tbody tr")).to_have_count(4)
                screenshot("03_create_tidak_valid")
                record("Create tidak valid", "Server menolak nama kosong/berat0; old() mempertahankan input.")

                fill(page, code="LD00000001", phone="081200000001")
                page.get_by_role("button", name="Simpan pesanan", exact=True).click()
                expect(page.locator("main")).to_contain_text("Kode pesanan sudah digunakan.")
                expect(page.locator("main")).to_contain_text("Nomor telepon sudah digunakan.")
                expect(page.locator("tbody tr")).to_have_count(4)
                screenshot("04_duplikat")
                record("Duplikat", "Kode dan nomor telepon yang sama ditolak; data tetap empat.")

                page.goto(BASE + "/pesanan-laundry/4/edit")
                expect(page.locator("#nama_pelanggan")).to_have_value("Dina Sari")
                screenshot("05a_form_edit")
                fill(page, name="Dina Sari Diperbarui", weight="12", service="1")
                page.get_by_role("button", name="Simpan perubahan", exact=True).click()
                expect(page.get_by_role("status")).to_contain_text("berhasil diperbarui")
                expect(page.locator("tbody")).to_contain_text("Dina Sari Diperbarui")
                assert sql(TEST_DB, "SELECT berat_kg FROM pesanan_laundry WHERE id=4;") == "12"
                screenshot("05b_update")
                record("Update", "Form menampilkan data lama; berat12 dan nama baru tersimpan.")

                page.goto(BASE + "/pesanan-laundry/4/edit")
                fill(page, code="LD00000002", phone="081200000002")
                page.get_by_role("button", name="Simpan perubahan", exact=True).click()
                expect(page.locator("main")).to_contain_text("Kode pesanan sudah digunakan.")
                expect(page.locator("main")).to_contain_text("Nomor telepon sudah digunakan.")
                assert sql(TEST_DB, "SELECT kode_pesanan FROM pesanan_laundry WHERE id=4;") == "LD00000004"
                screenshot("06_konflik_update")
                record("Konflik update", "Kode/telepon Rafi ditolak; LD00000004 tidak berubah.")

                page.goto(BASE + "/pesanan-laundry")
                page.get_by_role("button", name="Hapus pesanan LD00000004", exact=True).click()
                expect(page.get_by_role("status")).to_contain_text("berhasil dihapus")
                expect(page.locator("tbody tr")).to_have_count(3)
                screenshot("07_delete")
                record("Delete", "Form POST dengan CSRF dan spoofing DELETE menghapus hanya pesanan4.")

                for query, expected_code in [("LD00000001", "LD00000001"), ("Rafi", "LD00000002"), ("081200000003", "LD00000003")]:
                    page.goto(BASE + "/pesanan-laundry")
                    page.locator("#q").fill(query)
                    page.get_by_role("button", name="Cari dan terapkan filter", exact=True).click()
                    expect(page.locator("tbody tr")).to_have_count(1)
                    expect(page.locator("tbody")).to_contain_text(expected_code)
                screenshot("08_pencarian")
                record("Pencarian", "Ketiga kolom diuji bergantian: kode, nama, nomor telepon.")

                page.goto(BASE + "/pesanan-laundry")
                page.locator("#filter_jenis_layanan_id").select_option("1")
                page.get_by_role("button", name="Cari dan terapkan filter", exact=True).click()
                expect(page.locator("tbody tr")).to_have_count(2)
                expect(page.locator("tbody")).not_to_contain_text("Rafi Akbar")
                screenshot("09_filter")
                page.locator("#q").fill("Nina")
                page.get_by_role("button", name="Cari dan terapkan filter", exact=True).click()
                expect(page.locator("tbody tr")).to_have_count(1)
                expect(page.locator("tbody")).to_contain_text("Nina Putri")
                record("Filter", "Cuci Kering menampilkan Ayu/Nina; gabungan kata Nina menyisakan Nina.")

                # APP_ENV=local memastikan middleware CSRF tidak dilewati seperti di PHPUnit.
                page.goto(BASE + "/pesanan-laundry")
                token = page.locator('input[name="_token"]').first.input_value()
                for method in (None, "PUT", "DELETE"):
                    payload: dict[str, str | float | bool] = {} if method is None else {"_method": method}
                    path = "/pesanan-laundry" if method is None else "/pesanan-laundry/1"
                    response = page.request.post(BASE + path, form=payload)
                    assert response.status == 419, (method, response.status)
                valid_response = page.request.post(BASE + "/pesanan-laundry", form={"_token": token, "berat_kg": "0"}, max_redirects=0)
                assert valid_response.status == 302
                expect(page.locator("tbody tr")).to_have_count(3)
                record("CSRF HTTP nyata", "POST, spoofed PUT, DELETE tanpa token ditolak419; token sah mencapai validasi302.")
                page.goto(BASE + "/pesanan-laundry")

                logs = ROOT / "storage/logs/laravel.log"
                old_size = logs.stat().st_size if logs.exists() else 0
                processes.append(start_server(8766, resources, error=True))
                response = page.goto(ERROR_BASE + "/pesanan-laundry")
                assert response is not None and response.status == 503
                expect(page.get_by_role("heading", name="Data sementara tidak tersedia")).to_be_visible()
                content = page.locator("body").inner_text()
                assert "SQLSTATE" not in content and "Stack trace" not in content and TEST_DB not in content
                screenshot("10_error_handling")
                assert logs.exists() and logs.stat().st_size > old_size
                with logs.open("rb") as handle:
                    handle.seek(old_size)
                    assert b"SQLSTATE" in handle.read()
                record("Error handling", "Koneksi sengaja diarahkan ke port1: HTTP503 aman; rincian tercatat melalui report().")

                response = page.goto(BASE + "/pesanan-laundry")
                assert response is not None and response.status == 200
                expect(page.locator("tbody tr")).to_have_count(3)
                main_after = sql("laundry_2401020158", "SELECT * FROM jenis_layanan; SELECT * FROM pesanan_laundry;")
                assert main_before == main_after
                record("Isolasi dan uji ulang", "Koneksi normal kembali200; tiga data awal utuh dan database utama tidak berubah.")
            finally:
                context.close()
    finally:
        for process, log in reversed(processes):
            process.terminate()
            try:
                process.wait(timeout=10)
            except subprocess.TimeoutExpired:
                process.kill()
                process.wait(timeout=5)
            log.close()
        resources.close()
        (EVIDENCE / "hasil_browser.json").write_text(
            json.dumps({"waktu": datetime.now().astimezone().isoformat(), "basis_data_uji": TEST_DB,
                        "url_uji": BASE, "url_simulasi_error": ERROR_BASE,
                        "jenis_bukti": "PNG seluruh halaman asli dari Chrome. URL aktual ditampilkan oleh Blade di header, tanpa bilah browser buatan atau modifikasi gambar.",
                        "hasil": RESULTS}, ensure_ascii=False, indent=2), encoding="utf-8")


if __name__ == "__main__":
    main()
