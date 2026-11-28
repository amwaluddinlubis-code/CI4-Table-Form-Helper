# Changelog

Semua perubahan penting paket ini dicatat di berkas ini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/),
versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [1.2.2] - 2026-11-28

### Diperbaiki

- **Label `select_cari()` tidak lagi basi sesudah form di-reset.**
  `form.reset()` mengembalikan nilai select ke bawaan HTML tanpa
  memicu peristiwa `change`, sehingga widget menampilkan pilihan
  sebelumnya (mis. modal Tambah sesudah modal Ubah menampilkan induk
  baris yang diubah, sementara nilai select sudah kembali ke bawaan —
  temuan QA browser SPJ BOSP). `select-cari.js` kini menyegarkan
  labelnya sesudah setiap reset form pemilik.

## [1.2.1] - 2026-11-28

### Diperbaiki

- **CSS paket tidak lagi menimpa aplikasi pemakai.** Hasil ekstraksi
  dari app.css aplikasi sumber sebelumnya membawa aturan elemen
  global (`button { ... }`, `table { ... }`) dan lapisan tema
  aplikasi sumber (aturan `body:not(.login) ...` bertoken `--ui-*`)
  yang mengubah seluruh shell aplikasi pemakai di halaman yang
  memuat CSS paket (insiden SPJ BOSP). Kini: aturan tombol hanya
  berlaku untuk kelas `.tombol`, aturan tabel hanya untuk
  `table.tf-tabel`, lapisan tema aplikasi sumber dibuang saat
  sinkron, dan token `--ui-*` dipetakan ke token paket `--ta-*`.
  `tools/sinkron.py` menegakkan pelingkupan ini pada setiap sinkron
  berikutnya (gagal keras bila pola global muncul lagi).
- `tabel_bantu()` selalu memasang kelas `tf-tabel` pada tabelnya;
  tombol Ekspor CSV pada `paginasi.js` memakai kelas
  `tombol kecil ekspor`.
- Kotak pencarian tabel kini berbingkai dari CSS paket (sebelumnya
  mengikuti gaya input global aplikasi sumber yang tidak ikut
  terekstrak, sehingga tampil tanpa bingkai di aplikasi pemakai).
- Select `form_modal()` dan `select_cari()` membawa atribut
  `data-native-select` — opt-out bagi pengaya select global milik
  aplikasi pemakai (mis. daisy-select SPJ BOSP) agar select paket
  tidak diperkaya dua kali dan tampil sebagai dropdown ganda.
  Select Per halaman pada `paginasi.js` juga membawanya.
- `crud_bantu()`: tombol Tambah tidak lagi memakai awalan teks
  "+ " (bertabrakan dengan ikon plus otomatis milik aplikasi
  pemakai) dan dirender sebaris flex yang tidak membungkus.
- `sel_aksi()`: tombol Ubah/Hapus memakai kelas khusus `.tf-aksi`
  (kecil, berbingkai, varian `.bahaya` untuk Hapus) menggantikan
  pil `.tombol`/tanpa kelas, agar selaras dengan penata tombol
  aplikasi pemakai dan baris tabel tidak menjadi tinggi.

## [1.2.0] - 2026-11-28

### Ditambahkan

- `crud_bantu()` — satu panggilan merender blok CRUD lengkap:
  kepala (judul + tombol Tambah), `tabel_bantu()` dengan kolom
  aksi otomatis (Ubah membuka modal berisi data baris, Hapus lewat
  konfirmasi), dan `form_modal()` untuk tambah/ubah. Definisi
  kolom menyatu untuk tabel dan form (`tabel_saja` / `form_saja`
  memisahkan bila perlu), format sel `uang` / `angka` / `tanggal` /
  callable, dan baris grup otomatis lewat `grup_dari`.
- `Ci4TabelForm\Controllers\CrudBantuTrait` — pasangan sisi
  server: `crudAmbilData()` (konversi POST menurut jenis field),
  `crudSimpanData()` / `crudHapusData()` (insert/update/delete
  duck-typed lewat model), dan `crudBalas()` (JSON untuk AJAX,
  redirect + flash untuk permintaan biasa).
- Contoh satu berkas: `examples/CrudBantu.php`.
- Tes bertambah menjadi 33 tes / 116 asersi.

Kedua berkas baru dirawat di paket (di luar jangkauan sinkron):
`src/Helpers/crud_bantu_helper.php` dan
`src/Controllers/CrudBantuTrait.php`; muat helper dengan
`helper(['tabel_form', 'tabel_form_plus', 'crud_bantu'])`.

## [1.1.0] - 2026-11-27

### Ditambahkan

- `select_cari()` — render `<select>` searchable untuk form halaman
  biasa (disinkronkan dari `app/Helpers/pilih_helper.php` PBJ-REVIT
  lewat `tools/sinkron.py`).
- `flash_toast()` — render pesan flash session (`sukses` / `galat`)
  sebagai toast di dalam `#wadah-toast`, dengan markup yang sama
  seperti toast sisi klien.
- `kolom_aksi()` + `sel_aksi()` — kolom dan sel tombol Ubah/Hapus
  otomatis untuk `tabel_bantu()`: Ubah membuka `form_modal()` dengan
  data baris, Hapus memakai form `data-konfirmasi`.
- Tes bertambah menjadi 25 tes / 78 asersi.

Fungsi tambahan tinggal di `src/Helpers/tabel_form_plus_helper.php`
(dirawat di paket, tidak ditimpa skrip sinkron); muat dengan
`helper(['tabel_form', 'tabel_form_plus'])`.

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
