#!/usr/bin/env python3
"""
sinkron.py — tarik ulang kode helper dari aplikasi asal (PBJ-REVIT)
ke paket ci4-tabel-form-helper, lalu terapkan penyesuaian paket.

Sumber kebenaran kode adalah PBJ-REVIT; paket ini adalah salinan
terbitnya. Jalankan skrip ini setiap kali helper di PBJ berubah:

    python3 tools/sinkron.py [path-ke-folder-pbj-ci4]

Bawaan path: folder 'pbj-ci4' yang bersebelahan dengan repo ini,
atau dari variabel lingkungan PBJ_DIR.

Yang disinkronkan:
- src/Helpers/tabel_form_helper.php  <- app/Common.php (3 fungsi helper)
                                        + app/Helpers/pilih_helper.php (select_cari)
- assets/form-modal.js               <- public/assets/form-modal.js
- assets/paginasi.js                 <- public/assets/paginasi.js
- assets/select-cari.js              <- public/assets/select-cari.js
- assets/uang.js                     <- public/assets/uang.js
- assets/tabel-form.css              <- ekstraksi public/assets/app.css
- src/Views/modal_konfirmasi.php     <- app/Views/layout/_modal_konfirmasi.php

Penyesuaian paket yang selalu diterapkan ulang:
- Grid modal: kelas Tailwind PBJ -> kelas milik paket .fm-grid/.fm-penuh.
- form-modal.js: pencarian field CSRF diberi cadangan nama generik.
- Beberapa komentar khas PBJ digeneralisasi.

Yang TIDAK ditimpa: assets/tanggal.js (dirawat di paket; skrip hanya
memeriksa apakah blok tanggal di layout PBJ berubah dan memberi
peringatan bila perlu ditinjau manual).
"""
import hashlib
import os
import pathlib
import re
import sys

PAKET = pathlib.Path(__file__).resolve().parent.parent


def cari_pbj() -> pathlib.Path:
    if len(sys.argv) > 1:
        return pathlib.Path(sys.argv[1]).resolve()
    if os.environ.get('PBJ_DIR'):
        return pathlib.Path(os.environ['PBJ_DIR']).resolve()
    return (PAKET.parent / 'pbj-ci4').resolve()


def sha(teks: str) -> str:
    return hashlib.sha256(teks.encode()).hexdigest()


def tulis(rel: str, isi: str, hasil: list) -> None:
    tujuan = PAKET / rel
    lama = tujuan.read_text() if tujuan.exists() else None
    if lama == isi:
        hasil.append(f'  TETAP   {rel}')
    else:
        tujuan.parent.mkdir(parents=True, exist_ok=True)
        tujuan.write_text(isi)
        hasil.append(f'  BERUBAH {rel}')


HEADER_HELPER = """<?php

/**
 * Helper Tabel & Form Modal untuk CodeIgniter 4.
 *
 * Paket ci4-tabel-form-helper — fungsi global:
 * - sel_tabel_html(): render satu sel untuk tabel_bantu().
 * - tabel_bantu(array $konfig): tabel daftar standar (pencarian,
 *   paginasi, urut klik-header, baris grup & total) dari konfigurasi.
 * - form_modal(array $konfig): modal form CRUD (Tambah/Ubah/Hapus)
 *   dari konfigurasi; perilaku di assets/form-modal.js.
 * - select_cari(nama, opsi, terpilih, atribut): <select> searchable
 *   (atribut data-select-cari; widget di assets/select-cari.js).
 *
 * Membutuhkan fungsi bawaan CI4: esc() dan csrf_field().
 * Muat dengan helper('tabel_form') (penemuan paket Composer) atau
 * salin berkas ini ke app/Helpers/ proyek Anda.
 */

"""


def bangun_helper(pbj: pathlib.Path) -> str:
    common = (pbj / 'app/Common.php').read_text()
    awal = common.index("if (! function_exists('sel_tabel_html'))")
    body = common[awal:]
    assert 'function tabel_bantu' in body and 'function form_modal' in body
    assert 'grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-4' in body
    body = body.replace('<div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-4">', '<div class="fm-grid">')
    assert 'md:col-span-2' in body
    body = body.replace("' md:col-span-2'", "' fm-penuh'")
    body = body.replace('Helper TABEL standar PBJ (keputusan AMWAL 25 Nov 2026)',
                        'Helper TABEL standar (berasal dari aplikasi PBJ-REVIT, keputusan AMWAL 25 Nov 2026)')
    body = body.replace('Helper FORM MODAL CRUD (keputusan AMWAL 25 Nov 2026)',
                        'Helper FORM MODAL CRUD (berasal dari aplikasi PBJ-REVIT, keputusan AMWAL 25 Nov 2026)')
    # select_cari() dari app/Helpers/pilih_helper.php PBJ: docblock +
    # fungsi dibungkus penjaga function_exists, diindentasi seperti
    # pola app/Common.php.
    pilih = (pbj / 'app/Helpers/pilih_helper.php').read_text()
    potongan = pilih[pilih.index('/**'):].rstrip()
    assert 'function select_cari' in potongan
    terindent = '\n'.join(('    ' + baris) if baris.strip() else baris for baris in potongan.split('\n'))
    body = body.rstrip() + "\n\nif (! function_exists('select_cari')) {\n" + terindent + "\n}\n"
    return HEADER_HELPER + body


def bangun_form_modal_js(pbj: pathlib.Path) -> str:
    fm = (pbj / 'public/assets/form-modal.js').read_text()
    lama = "var csrf = form2.querySelector('input[name=\"csrf_test_name\"]');"
    assert lama in fm
    fm = fm.replace(lama, "var csrf = form2.querySelector('input[name=\"csrf_test_name\"]') || form2.querySelector('input[type=\"hidden\"][name^=\"csrf\"]');")
    fm = fm.replace("// Helper FORM MODAL CRUD — perilaku untuk modal yang dirender\n// form_modal() (app/Common.php).",
                    "// Helper FORM MODAL CRUD — perilaku untuk modal yang dirender\n// form_modal() (paket ci4-tabel-form-helper, src/Helpers/tabel_form_helper.php).")
    return fm


def bangun_modal_konfirmasi(pbj: pathlib.Path) -> str:
    mk = (pbj / 'app/Views/layout/_modal_konfirmasi.php').read_text()
    potong = mk.index('<script>\n// Panel berlipat')
    mk = mk[:potong].rstrip() + "\n"
    mk = mk.replace(' * Partial layout: modal konfirmasi bersama (markup + skrip toast modal konfirmasi bersama (markup + skrip toast &\n * modal) + skrip panel berlipat. Semua form ber-atribut data-konfirmasi\n * dicegat skrip modal ini.',
                    ' * Modal konfirmasi bersama + toast (paket ci4-tabel-form-helper).\n * Semua form ber-atribut data-konfirmasi dicegat skrip modal ini:\n * form hanya terkirim sesudah pengguna menekan tombol setuju (#mk-ya).\n * Sertakan juga elemen <div id="wadah-toast"></div> di layout Anda\n * (tempat toast menempel) sebelum partial ini.')
    return mk


TOKENS_CSS = ['table', '.angka', '.baris-grup', '.baris-total', '.baris-kosong',
              '.kepala-tempel', '.bisa-urut', '.panah-urut', '.paginasi', '.cari-tabel',
              '.mk-', '.fm-', '.toast', 'wadah-toast', '.tanggal', '.tmbl-kalender',
              '.isian-tanggal', '.sc-', '.galat', '.kecil-teks', '.tombol', 'button',
              '.bahaya', '.ekspor', '.unit-terlipat']

HEADER_CSS = """/* ==========================================================================
   tabel-form.css — paket ci4-tabel-form-helper
   Gaya untuk tabel_bantu(), form_modal(), modal konfirmasi, toast,
   select-cari, paginasi tabel, isian tanggal dd-mm-yyyy, dan field uang.
   Diekstrak dari aplikasi PBJ-REVIT (public/assets/app.css) dan
   disesuaikan agar berdiri sendiri. Timpa variabel di bawah untuk
   menyesuaikan tema; mode gelap mengikuti kelas `gelap` pada <html>
   (html.gelap), sama seperti aplikasi asalnya.
   ========================================================================== */
:root {
  --ta-biru: #0049b8;
  --ta-biru-500: #0968f6;
  --ta-biru-50: #eff5fd;
  --ta-teks: #1f1f1f;
  --ta-teks-2: #525252;
  --ta-teks-3: #737373;
  --ta-garis: #e5e5e5;
  --ta-garis-2: #a3a3a3;
  --ta-latar: #f9fafb;
}

.fm-grid { display: grid; grid-template-columns: 1fr; column-gap: 16px; row-gap: 23px; }
@media (min-width: 768px) {
  .fm-grid { grid-template-columns: 1fr 1fr; }
  .fm-penuh { grid-column: span 2; }
}

"""


def parse_blocks(s: str):
    blocks, i, n = [], 0, len(s)
    while i < n:
        start = i
        while i < n:
            if s[i].isspace():
                i += 1
            elif s.startswith('/*', i):
                j = s.find('*/', i)
                i = n if j < 0 else j + 2
            else:
                break
        if i >= n:
            break
        j = i
        while j < n and s[j] not in '{;':
            j += 1
        if j >= n:
            break
        prelude = s[i:j].strip()
        if s[j] == ';':
            blocks.append((prelude, None, s[start:j + 1]))
            i = j + 1
            continue
        depth, k = 1, j + 1
        while k < n and depth:
            if s[k] == '{':
                depth += 1
            elif s[k] == '}':
                depth -= 1
            k += 1
        blocks.append((prelude, s[j + 1:k - 1], s[start:k]))
        i = k
    return blocks


def simpan(prelude: str, body) -> bool:
    if prelude.startswith('@keyframes') or prelude.startswith('@-webkit-keyframes'):
        return True
    if prelude.startswith('@media') or prelude.startswith('@supports'):
        return any(simpan(p, b) for p, b, _ in parse_blocks(body or ''))
    if prelude.startswith('@'):
        return False
    return any(t in prelude for t in TOKENS_CSS)


def render(blocks) -> str:
    out = []
    for prelude, body, raw in blocks:
        if not simpan(prelude, body):
            continue
        if (prelude.startswith('@media') or prelude.startswith('@supports')) and body is not None:
            inner = render(parse_blocks(body))
            if inner.strip():
                out.append(prelude + ' {\n' + inner + '}')
        else:
            out.append(raw.strip())
    return '\n\n'.join(out)


def lingkupkan_css(hasil: str) -> str:
    """Lingkupkan aturan elemen global warisan app.css PBJ ke kelas milik
    paket. Di PBJ aturan `button { ... }` dan `table { ... }` memang gaya
    global aplikasinya, tetapi di aplikasi pemakai paket aturan itu
    menimpa seluruh shell (insiden SPJ BOSP 28 Nov 2026: semua tombol
    aplikasi menjadi pil biru). Sesudah transformasi ini gaya tombol
    hanya berlaku untuk .tombol dan gaya tabel hanya untuk
    table.tf-tabel (kelas yang selalu dipasang tabel_bantu())."""
    def ganti(teks: str, dari: str, ke: str, wajib: bool = False) -> str:
        if wajib:
            assert dari in teks, f'lingkupkan_css: pola wajib tidak ditemukan: {dari[:50]!r}'
        return teks.replace(dari, ke)

    hasil = re.sub(r'(?m)^table \{', 'table.tf-tabel {', hasil)
    assert 'table.tf-tabel {' in hasil, 'lingkupkan_css: tidak ada blok table untuk dilingkupkan'
    hasil = ganti(hasil, "td.angka,\nth.angka {", "table.tf-tabel td.angka,\ntable.tf-tabel th.angka {", wajib=True)
    hasil = ganti(hasil, "button,\n.tombol {", ".tombol {", wajib=True)
    hasil = ganti(hasil, "button:hover,\n.tombol:hover {", ".tombol:hover {")
    hasil = ganti(hasil, "button:active,\n.tombol:active {", ".tombol:active {")
    hasil = ganti(hasil, """button:focus-visible,
.tombol:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible {""", """.tombol:focus-visible,
.mk-lapik input:focus-visible,
.mk-lapik select:focus-visible,
.mk-lapik textarea:focus-visible {""")
    hasil = ganti(hasil, "button.bahaya {", ".tombol.bahaya {")
    hasil = ganti(hasil, "button.bahaya:hover {", ".tombol.bahaya:hover {")
    hasil = ganti(hasil, "button.kecil,\n.tombol.kecil {", ".tombol.kecil {")
    hasil = ganti(hasil, "button.abu,\n.tombol.abu {", ".tombol.abu {")
    hasil = ganti(hasil, "button.ekspor,\n.tombol.ekspor {", ".tombol.ekspor {")
    hasil = ganti(hasil, "button.ekspor:hover,\n.tombol.ekspor:hover {", ".tombol.ekspor:hover {")
    hasil = ganti(hasil, "button.abu:hover,\n.tombol.abu:hover {", ".tombol.abu:hover {")
    hasil = ganti(hasil, "button:disabled {", ".tombol:disabled {")
    hasil = ganti(hasil, "\ntable {\n", "\ntable.tf-tabel {\n")
    hasil = ganti(hasil, "\ntable tr:hover td {", "\ntable.tf-tabel tr:hover td {")
    hasil = ganti(hasil, "html.gelap table tr:hover td {", "html.gelap table.tf-tabel tr:hover td {")
    hasil = ganti(hasil, "html.gelap body:not(.login) table tr:hover td {", "html.gelap body:not(.login) table.tf-tabel tr:hover td {")
    hasil = ganti(hasil, "body:not(.login) table tr:hover td {", "body:not(.login) table.tf-tabel tr:hover td {")
    hasil = ganti(hasil, "table.satu-baris ", "table.tf-tabel.satu-baris ")
    hasil = ganti(hasil, "table.kepala-tempel thead th {", "table.tf-tabel.kepala-tempel thead th {")
    hasil = ganti(hasil, """tbody tr:hover td,
table.tf-tabel tr:hover td {""", """table.tf-tabel tr:hover td {""")
    hasil = ganti(hasil, """html.gelap tbody tr:hover td,
html.gelap table.tf-tabel tr:hover td {""", """html.gelap table.tf-tabel tr:hover td {""")
    hasil = ganti(hasil, """.tombol,
button {
  transition:""", """.tombol {
  transition:""")
    hasil = ganti(hasil, """.tombol:hover,
button:hover {
  transform: translateY(-1px);
}""", """.tombol:hover {
  transform: translateY(-1px);
}""")
    for baris in hasil.split('\n'):
        b = baris.strip()
        assert not re.match(r'button[\s,{:.#]', b) and b != 'table {', \
            f'lingkupkan_css: sisa aturan global: {baris!r}'
    return hasil


PRELUDE_BUANG = ('body:not(.login)', '.topbar', '.samping', '.tombol-user',
                 '.grup-font', '.dropdown-user', '.tombol-ikon-top',
                 '.tombol-samping', '.dashboard-aksi', '.tautan-pekerjaan',
                 '.biaya-aksi', '.biaya-data', '.login-v2')


def saring_blok(blocks) -> str:
    """Buang blok lapisan tema khusus aplikasi PBJ (prelude menyebut
    body:not(.login) atau kelas shell PBJ; isinya memakai variabel --ui-*
    milik PBJ yang tidak ada di paket — di aplikasi pemakai aturan itu
    menimpa shell, insiden SPJ BOSP 28 Nov 2026). Prelude tabel
    beratribut data-cari/data-paginasi dilingkupkan ke table.tf-tabel."""
    out = []
    for prelude, body, raw in blocks:
        if any(t in prelude for t in PRELUDE_BUANG):
            continue
        if (prelude.startswith('@media') or prelude.startswith('@supports')) and body is not None:
            inner = saring_blok(parse_blocks(body))
            if inner.strip():
                out.append(prelude + ' {\n' + inner + '}')
            continue
        teks = raw.strip()
        if prelude.startswith('table['):
            kepala, _, ekor = teks.partition('{')
            teks = kepala.replace('table[', 'table.tf-tabel[') + '{' + ekor
        out.append(teks)
    return '\n\n'.join(out)


TAMBAHAN_CSS_PAKET = """
/* ===== Penyesuaian paket (bukan dari app.css PBJ) =====
   Kotak cari tabel dibuat paginasi.js tanpa kelas; di PBJ tampilannya
   mengikuti gaya input global aplikasi yang tidak ikut terekstrak,
   jadi paket memberinya bingkai sendiri. */
.paginasi.cari-tabel input[type="search"] {
  border: 1px solid var(--ta-garis-2);
  border-radius: 999px;
  padding: 7px 14px;
  font-size: 11.2px;
  font-family: inherit;
  background: #fff;
  color: var(--ta-teks);
}

.paginasi.cari-tabel input[type="search"]:focus {
  outline: none;
  border-color: var(--ta-biru-500);
  box-shadow: 0 0 0 3px var(--ta-biru-50);
}

/* Tombol aksi baris tabel (sel_aksi): kecil, berbingkai, sebaris —
   sengaja BUKAN .tombol pil agar tidak bertabrakan dengan penata
   tombol global aplikasi pemakai (ikon otomatis, target sentuh). */
.tf-aksi {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  font-size: 11.5px;
  font-weight: 600;
  font-family: inherit;
  border: 1px solid var(--ta-garis-2);
  border-radius: 8px;
  background: #fff;
  color: var(--ta-teks);
  cursor: pointer;
  white-space: nowrap;
}

.tf-aksi:hover {
  background: var(--ta-biru-50);
  border-color: var(--ta-biru-500);
  color: var(--ta-biru);
}

.tf-aksi.bahaya {
  color: #800000;
  border-color: #d8a7a7;
}

.tf-aksi.bahaya:hover {
  background: #fdeded;
  border-color: #800000;
  color: #800000;
}
"""


def bangun_css(pbj: pathlib.Path) -> str:
    css = (pbj / 'public/assets/app.css').read_text()
    hasil = render(parse_blocks(css))
    assert '.fm-isi .grid' in hasil
    hasil = hasil.replace('.fm-isi .grid', '.fm-grid')
    hasil = saring_blok(parse_blocks(hasil))
    hasil = lingkupkan_css(hasil)
    assert 'body:not(.login)' not in hasil, 'masih ada sisa lapisan tema PBJ'
    hasil = petakan_var_ui(hasil)
    assert '--ui-' not in hasil, 'masih ada variabel --ui-* yang belum dipetakan'
    return (HEADER_CSS + hasil + TAMBAHAN_CSS_PAKET).rstrip() + '\n'


# app.css PBJ kini menamai token desainnya --ui-*. Nama itu TIDAK boleh
# ikut ke paket: aplikasi pemakai (mis. SPJ BOSP) memakai nama --ui-*
# untuk sistem desainnya sendiri, jadi mendefinisikan ulang token itu
# di :root paket akan menimpa token aplikasi. Sebagai gantinya setiap
# var(--ui-*) pada blok terekstrak dipetakan ke token paket --ta-*
# atau nilai harfiah yang sama dengan :root PBJ.
PETA_VAR_UI = {
    '--ui-accent': 'var(--ta-biru)',
    '--ui-accent-500': 'var(--ta-biru-500)',
    '--ui-accent-hover': '#003a94',
    '--ui-danger': '#800000',
    '--ui-danger-hover': '#660000',
    '--ui-durasi': '.18s',
    '--ui-ease': 'ease',
    '--ui-radius-pill': '999px',
    '--ui-stroke-strong': 'var(--ta-garis-2)',
    '--ui-surface': '#fff',
    '--ui-surface-raised': '#fff',
    '--ui-text': 'var(--ta-teks)',
    '--ui-text-2xs': '10px',
    '--ui-text-xs': '11.5px',
    '--ui-text-sm': '13px',
    '--ui-text-base': '14.5px',
    '--ui-text-lg': '17px',
}


def petakan_var_ui(hasil: str) -> str:
    def ganti(m: re.Match) -> str:
        nama = m.group(1)
        assert nama in PETA_VAR_UI, f'variabel {nama} belum ada di PETA_VAR_UI sinkron.py'
        return PETA_VAR_UI[nama]

    return re.sub(r'var\((--ui-[a-z0-9-]+)(?:,[^)]*)?\)', ganti, hasil)


def cek_tanggal(pbj: pathlib.Path, hasil: list) -> None:
    """tanggal.js dirawat di paket — beri peringatan bila blok tanggal PBJ berubah."""
    skrip = (pbj / 'app/Views/layout/_skrip.php').read_text()
    milik = (PAKET / 'assets/tanggal.js').read_text()
    penanda = ['isian-tanggal', 'tanggal-bungkus', 'tmbl-kalender', 'showPicker',
               'dd-mm-yyyy', 'dmyKeIso', 'isoKeDmy']
    hilang_pbj = [p for p in penanda if p not in skrip]
    hilang_paket = [p for p in penanda if p not in milik]
    if hilang_pbj or hilang_paket:
        hasil.append(f'  PERINGATAN tanggal.js: penanda hilang — PBJ: {hilang_pbj}, paket: {hilang_paket}. Tinjau manual.')
    else:
        hasil.append('  CEK     assets/tanggal.js (penanda blok tanggal PBJ utuh; berkas paket tidak ditimpa)')


def main() -> int:
    pbj = cari_pbj()
    wajib = [pbj / 'app/Common.php', pbj / 'app/Helpers/pilih_helper.php', pbj / 'public/assets/app.css',
             pbj / 'public/assets/form-modal.js', pbj / 'app/Views/layout/_modal_konfirmasi.php',
             pbj / 'app/Views/layout/_skrip.php']
    for b in wajib:
        if not b.exists():
            print(f'GAGAL: berkas sumber tidak ditemukan: {b}')
            print('Sebutkan path PBJ-REVIT: python3 tools/sinkron.py <path>')
            return 1
    print(f'Sumber PBJ : {pbj}')
    print(f'Tujuan     : {PAKET}')
    hasil: list = []
    tulis('src/Helpers/tabel_form_helper.php', bangun_helper(pbj), hasil)
    tulis('assets/form-modal.js', bangun_form_modal_js(pbj), hasil)
    for nama in ('paginasi.js', 'select-cari.js', 'uang.js'):
        tulis(f'assets/{nama}', (pbj / f'public/assets/{nama}').read_text(), hasil)
    tulis('assets/tabel-form.css', bangun_css(pbj), hasil)
    tulis('src/Views/modal_konfirmasi.php', bangun_modal_konfirmasi(pbj), hasil)
    cek_tanggal(pbj, hasil)
    print('\n'.join(hasil))
    berubah = [h for h in hasil if h.startswith('  BERUBAH')]
    print(f"\nSelesai: {len(berubah)} berkas berubah." if berubah else '\nSelesai: paket sudah sinkron, tidak ada perubahan.')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
