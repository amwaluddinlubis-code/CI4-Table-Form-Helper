# Changelog

Semua perubahan penting paket ini dicatat di berkas ini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/),
versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [1.0.0] - 2026-11-27

Rilis perdana — ekstraksi helper tampilan dari aplikasi
[PBJ-REVIT](https://github.com/amwaluddinlubis-code/PBJ-REVIT).

### Ditambahkan

- `tabel_bantu()` dan `sel_tabel_html()` — tabel daftar standar dari
  konfigurasi: pencarian langsung, paginasi, urut klik-header, baris
  grup, dan baris total (mesin `assets/paginasi.js`).
- `form_modal()` — modal form CRUD dari konfigurasi: tombol bawaan
  Batal/Hapus/Simpan, label di border isian, validasi wajib via
  tooltip, simpan AJAX berbalasan JSON `{ok, pesan}`, mode segarkan
  sebagian halaman tanpa reload (perilaku di `assets/form-modal.js`).
- Partial modal konfirmasi bersama + toast (`src/Views/modal_konfirmasi.php`).
- Aset pendukung: `select-cari.js` (select searchable), `uang.js`
  (field uang `data-uang`), `tanggal.js` (isian tanggal dd-mm-yyyy
  dengan tombol kalender), dan `tabel-form.css`.
- Perintah `php spark tabel-form:publish` untuk menyalin aset dan
  partial ke proyek pemakai.
- `tools/sinkron.py` — menarik ulang kode dari PBJ-REVIT dan
  menerapkan penyesuaian paket secara idempoten.
- Tes PHPUnit (16 tes, 48 asersi) dan workflow GitHub Actions
  (PHP 8.1–8.4).
- Contoh controller + view di `examples/`.

### Catatan

- Grid dua kolom modal memakai kelas milik paket (`.fm-grid` /
  `.fm-penuh`), bukan kelas Tailwind seperti versi aplikasi asal —
  tampilan dan jarak antar-input (15px tampak) sama.
