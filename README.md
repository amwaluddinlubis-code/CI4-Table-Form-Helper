# ci4-tabel-form-helper

Helper **tabel daftar** dan **form modal CRUD** untuk **CodeIgniter 4** — dirender dari *konfigurasi*, bukan ditulis tangan per modul. Pola tampilan cukup diubah di satu tempat, semua halaman yang memakai helper ikut berubah.

Paket ini diekstrak dari aplikasi nyata [PBJ-REVIT](https://github.com/amwaluddinlubis-code/PBJ-REVIT) (sistem pengadaan sekolah), tempat kedua helper ini dipakai modul Biaya Non-Gedung, Analisa, Detail SPK, dan Register SPK.

## Isi paket

| Berkas | Fungsi |
| --- | --- |
| `src/Helpers/tabel_form_helper.php` | `tabel_bantu()`, `sel_tabel_html()`, `form_modal()`, `select_cari()` |
| `src/Helpers/tabel_form_plus_helper.php` | `flash_toast()`, `kolom_aksi()`, `sel_aksi()` |
| `src/Helpers/crud_bantu_helper.php` | `crud_bantu()` — blok CRUD lengkap (kepala + tabel aksi + modal) dari satu konfigurasi |
| `src/Controllers/CrudBantuTrait.php` | Pasangan sisi server `crud_bantu()`: ambil/konversi POST, simpan/hapus via model, balasan AJAX/redirect |
| `src/Views/modal_konfirmasi.php` | Modal konfirmasi bersama (Hapus dsb.) + toast notifikasi |
| `src/Commands/Publish.php` | Perintah `php spark tabel-form:publish` untuk menyalin aset ke proyek |
| `assets/tabel-form.css` | Gaya tabel, modal form, modal konfirmasi, toast, select-cari, paginasi, isian tanggal |
| `assets/form-modal.js` | Perilaku modal form: buka/isi/tutup, kirim AJAX `{ok, pesan}`, validasi tooltip, segarkan sebagian halaman |
| `assets/paginasi.js` | Tabel Standar: pencarian langsung, paginasi, urut klik-header, empty state, ekspor CSV |
| `assets/select-cari.js` | Select tunggal yang bisa dicari (atribut `data-select-cari`) |
| `assets/uang.js` | Field uang `data-uang`: pemisah ribuan Indonesia saat mengetik, angka polos saat terkirim |
| `assets/tanggal.js` | Isian tanggal tampil **dd-mm-yyyy** + tombol kalender; nilai terkirim tetap `yyyy-mm-dd` |
| `examples/` | Contoh controller (pola balasan AJAX) dan view daftar + modal |

## Syarat

- PHP 8.1+
- CodeIgniter 4.x
- Tanpa dependensi JavaScript/CSS eksternal (tidak perlu Tailwind/jQuery)

## Instalasi

### Cara 1 — Composer (repo GitHub sebagai sumber VCS)

Tambahkan ke `composer.json` proyek Anda:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/amwaluddinlubis-code/ci4-tabel-form-helper" }
  ]
}
```

Lalu:

```bash
composer require amwaluddinlubis/ci4-tabel-form-helper
php spark tabel-form:publish
```

Perintah publish menyalin keenam aset ke `public/assets/` dan partial modal konfirmasi ke `app/Views/layout/_modal_konfirmasi.php` (tambah `--force` untuk menimpa yang sudah ada).

### Cara 2 — Salin manual

1. Salin semua berkas `src/Helpers/` ke `app/Helpers/` proyek Anda.
2. Salin semua berkas `assets/` ke `public/assets/` proyek Anda.
3. Salin `src/Views/modal_konfirmasi.php` ke `app/Views/layout/_modal_konfirmasi.php`.

## Penyiapan layout

Muat helper di controller (atau BaseController) Anda:

```php
helper(['tabel_form', 'tabel_form_plus']);
```

Sertakan di layout, di bagian `<head>` dan sebelum `</body>`:

```php
<link rel="stylesheet" href="<?= base_url('assets/tabel-form.css') ?>">
<!-- ... konten halaman ... -->
<div id="wadah-toast"><?= flash_toast() ?></div>
<?= $this->include('layout/_modal_konfirmasi') ?>
<script src="<?= base_url('assets/paginasi.js') ?>"></script>
<script src="<?= base_url('assets/select-cari.js') ?>"></script>
<script src="<?= base_url('assets/uang.js') ?>"></script>
<script src="<?= base_url('assets/tanggal.js') ?>"></script>
<script src="<?= base_url('assets/form-modal.js') ?>"></script>
```

Mode gelap mengikuti kelas `gelap` pada elemen `<html>` (`html.gelap`), sama seperti aplikasi asalnya. Warna dapat disesuaikan lewat variabel CSS `--ta-*` di awal `tabel-form.css`.

## `tabel_bantu(array $konfig): string`

Merender tabel daftar lengkap dengan pencarian, paginasi, dan pengurutan dari `paginasi.js`:

```php
echo tabel_bantu([
    'kolom' => [
        ['judul' => 'Nama'],
        ['judul' => 'Harga', 'angka' => true],   // 'lebar' => '110px', 'judul_html' => mentah
    ],
    'baris' => [
        ['sel' => ['Semen', ['teks' => 'Rp 50.000', 'angka' => true]]],              // string diescape
        ['sel' => [['html' => '<b>Portland</b>'], ['teks' => 'Rp 52.000', 'angka' => true]]], // 'html' mentah
        ['grup' => 'Judul Grup'],                                                    // baris grup sepenuh lebar
        ['total' => [['teks' => 'Total'], ['teks' => 'Rp 102.000', 'angka' => true]]],        // baris total, selalu tampil
    ],
    'cari'     => 'Cari bahan…',  // string = kotak cari aktif; false = mati
    'paginasi' => 25,             // int = baris per halaman; false = mati
    'class'    => 'tabel-data',
]);
```

Sel juga menerima `'class'`, `'colspan'`, `'gaya'`, `'title'`; baris menerima `'class'` dan `'atribut'` mentah (mis. `data-cari-teks="..."` untuk indeks cari per baris).

## `form_modal(array $konfig): string`

Merender modal CRUD: judul rata kiri di pita kepala yang sedikit lebih gelap, label field duduk **di border isian**, validasi wajib berupa **tooltip merah**, jarak antar-input 15px tampak, dan tombol bawaan **Batal / Hapus / Simpan** (masing-masing bisa dinonaktifkan).

```php
<?= form_modal([
    'id'         => 'modal-bahan',
    'judul'      => 'Tambah Bahan',
    'judul_ubah' => 'Ubah Bahan',
    'aksi'       => base_url('bahan/simpan'),
    'aksi_hapus' => base_url('bahan/hapus/__ID__'),  // penanda WAJIB __ID__
    'konfirmasi_hapus' => 'Hapus bahan ini?',
    'kolom' => [
        ['nama' => 'kode',  'label' => 'Kode', 'wajib' => true],
        ['nama' => 'harga', 'label' => 'Harga (Rp)', 'jenis' => 'uang', 'wajib' => true],
        ['nama' => 'jenis', 'label' => 'Jenis', 'jenis' => 'pilihan',
         'opsi' => ['a' => 'A', 'b' => 'B']],
        // jenis: teks (bawaan) | angka | uang | tanggal | pilihan | area ('baris' => n)
        // opsi field: 'placeholder', 'petunjuk', 'penuh' => true, 'saran' => [...] (datalist)
    ],
    'catatan' => 'Teks kecil di bawah field.',
]) ?>
```

Membuka modal dari tombol di halaman:

```html
<!-- Tambah -->
<button type="button" onclick="bukaFormModal('modal-bahan')">+ Tambah</button>
<!-- Ubah: data = nilai field menurut nama + 'id' baris -->
<button type="button" onclick="bukaFormModal('modal-bahan', <?= esc(json_encode($baris), 'attr') ?>)">Ubah</button>
```

Hal yang wajib diketahui:

- **Penanda `__ID__`** pada `aksi_hapus` — jangan pakai `{id}`, karena kurung kurawal ter-percent-encode oleh pembangun URL CI4 sehingga penggantian di JS tidak pernah cocok.
- **Controller wajib bercabang AJAX**: method simpan/hapus membalas JSON `{ok: bool, pesan: string}` untuk permintaan AJAX dan redirect + flash untuk permintaan biasa. Contoh lengkap ada di `examples/Barang.php`.
- **Tombol Hapus** hanya muncul pada mode Ubah dan berjalan lewat modal konfirmasi bersama (form `data-konfirmasi`), bukan modal baru.
- Field `uang` diisi angka polos saat mode Ubah; format ribuan diterapkan otomatis. Field `tanggal` diberi data berformat ISO `yyyy-mm-dd`; tampilannya otomatis dd-mm-yyyy.
- Mode Ubah dengan endpoint berbeda dari Tambah: arahkan `action` form lewat skrip jembatan kecil di halaman Anda (pola yang dipakai aplikasi asal), atau buat satu endpoint yang memutuskan tambah vs ubah dari field `id`.
- Perluasan untuk modal khusus: `'tersembunyi' => [...]` (input hidden tambahan yang ikut terisi data buka), `'info_html'` (HTML mentah di puncak isi modal), `'catatan_html'` (HTML mentah di bawah field, boleh berisi input tambahan), opsi pilihan berbentuk `['label' => ..., 'data' => [...]]` untuk atribut `data-*` per opsi, dan `'segarkan' => ['#pemilih', ...]` agar sesudah simpan berhasil isi elemen target diganti dari muatan segar halaman **tanpa reload penuh**.

## Fungsi tambahan

### `select_cari(string $name, array $options, $selected = null, array $attrs = []): string`

Merender `<select>` searchable (widget `select-cari.js`) untuk form halaman biasa, di luar modal — di dalam `form_modal()` field `pilihan` sudah otomatis memakainya. Opsi boleh berupa peta `nilai => label` atau daftar baris `['value' => ..., 'label' => ..., 'data' => [...]]` (data menjadi atribut `data-*` per opsi, berguna untuk isian otomatis). Kunci `placeholder` pada `$attrs` menjadi label opsi kosong.

```php
<?= select_cari('gedung_id', ['' => '— pilih —', 1 => 'RKB', 2 => 'Toilet'], old('gedung_id'), ['required' => true]) ?>
```

### `flash_toast(?array $pesan = null): string`

Merender pesan flash session sebagai toast di dalam `<div id="wadah-toast">` (lihat Penyiapan layout): kunci flashdata `sukses` menjadi toast sukses; `galat`/`gagal`/`error` menjadi toast galat. Bisa juga diberi array langsung: `flash_toast(['sukses' => 'Tersimpan.'])`. Markupnya sama persis dengan toast yang dibuat `tampilToast()` di sisi klien, jadi skrip partial modal konfirmasi menganimasikannya tanpa kode tambahan.

### `kolom_aksi()` + `sel_aksi($id, array $data, array $opsi)`

Pasangan untuk kolom aksi otomatis di `tabel_bantu()` — tanpa menulis markup tombol sendiri:

```php
'kolom' => [['judul' => 'Nama'], kolom_aksi()],
'baris' => [
    ['sel' => ['Semen', sel_aksi($b['id'], $dataUbah, [
        'modal'      => 'modal-barang',                          // tombol Ubah membuka form_modal ini
        'hapus'      => base_url('barang/hapus/__ID__'),         // tombol Hapus (form data-konfirmasi)
        'konfirmasi' => 'Hapus barang ini?',
    ])]],
],
```

Teks tombol dapat diganti lewat `'teks_ubah'` / `'teks_hapus'`; kosongkan `'modal'` atau `'hapus'` untuk menyembunyikan tombolnya.

## `crud_bantu(array $konfig): string` (v1.2.0)

Satu panggilan merender **blok CRUD lengkap**: kepala berisi judul + tombol Tambah, tabel dengan kolom aksi otomatis, dan modal form untuk tambah/ubah — definisi kolomnya menyatu untuk tabel dan form. Pasangan sisi servernya `Ci4TabelForm\Controllers\CrudBantuTrait`. Contoh lengkap: `examples/CrudBantu.php`.

```php
<?= crud_bantu([
    'id' => 'barang', 'judul' => 'Data Barang',
    'kolom' => [
        ['nama' => 'nama', 'label' => 'Nama Barang', 'jenis' => 'teks', 'wajib' => true],
        ['nama' => 'harga', 'label' => 'Harga', 'jenis' => 'uang', 'format' => 'uang'],
        ['nama' => 'jenis', 'label' => 'Jenis', 'jenis' => 'pilihan',
         'opsi' => ['baru' => 'Baru', 'bekas' => 'Bekas']],
    ],
    'baris' => $barang,                       // daftar array/objek, wajib ada kunci 'id'
    'aksi' => 'barang/simpan',                // tambah & ubah dibedakan dari hidden 'id'
    'aksi_hapus' => 'barang/hapus/__ID__',
    'konfirmasi_hapus' => 'Hapus barang ini?',
    'cari' => 'Cari barang…', 'paginasi' => 25,
]) ?>
```

Kunci penting: `'grup_dari'` (nama kolom — pergantian nilainya menyisipkan baris grup), `'format'` per kolom (`uang` / `angka` / `tanggal` / callable untuk tampilan sel), `'tabel_saja'` / `'form_saja'` per kolom, `'kunci_id'` (bawaan `id`), `'tambah' => false` (tanpa tombol tambah), `'kosong'` (teks saat tidak ada baris). Controller memakai trait-nya:

```php
use Ci4TabelForm\Controllers\CrudBantuTrait;

class Barang extends BaseController
{
    use CrudBantuTrait;

    public function simpan()
    {
        $data = $this->crudAmbilData(['nama' => 'teks', 'harga' => 'uang', 'jenis' => 'pilihan']);
        if ($data['nama'] === '') {
            return $this->crudBalas(false, 'Nama wajib diisi.', 'barang');
        }
        $ok = $this->crudSimpanData(new BarangModel(), $data, $this->request->getPost('id'));

        return $this->crudBalas($ok, $ok ? 'Barang tersimpan.' : 'Gagal menyimpan.', 'barang');
    }
}
```

`crudAmbilData()` mengonversi menurut jenis (`uang` → int polos, `angka` → float, `tanggal` dd-mm-yyyy → yyyy-mm-dd); `crudBalas()` menjawab JSON `{ok, pesan}` untuk AJAX dan redirect + flash untuk permintaan biasa.

## Perbedaan kecil dari versi aplikasi asal

Di PBJ-REVIT grid dua kolom modal memakai kelas Tailwind. Di paket ini grid memakai kelas milik paket (`.fm-grid` / `.fm-penuh` di `tabel-form.css`) agar paket tidak bergantung pada Tailwind — tampilan dan jaraknya sama.

## Pengujian

Paket ini memiliki tes PHPUnit untuk keluaran render kedua helper:

```bash
composer install
composer test
```

Di GitHub, workflow **tests** menjalankan tes yang sama pada PHP 8.1–8.4 setiap push dan pull request.

## Sinkronisasi dari PBJ-REVIT

Sumber kebenaran kode helper adalah aplikasi PBJ-REVIT; repo ini adalah salinan terbitnya. Sesudah helper di PBJ berubah, perbarui paket ini dengan:

```bash
python3 tools/sinkron.py /path/ke/pbj-ci4
```

Skrip menarik ulang helper PHP, keempat berkas JS bersama, CSS (diekstrak dari `app.css`), dan partial modal konfirmasi — lalu menerapkan kembali penyesuaian paket (kelas `.fm-grid`, cadangan nama field CSRF, komentar) secara idempoten: bila PBJ tidak berubah, tidak ada berkas yang berubah. `assets/tanggal.js` tidak ditimpa; skrip hanya memeriksa penanda blok tanggal PBJ dan memberi peringatan bila perlu ditinjau manual.

## Lisensi

MIT — lihat berkas [LICENSE](LICENSE). Silakan dipakai dan diubah; cantumkan pemberitahuan lisensinya.
