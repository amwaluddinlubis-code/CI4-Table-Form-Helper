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


def bangun_css(pbj: pathlib.Path) -> str:
    css = (pbj / 'public/assets/app.css').read_text()
    hasil = render(parse_blocks(css))
    assert '.fm-isi .grid' in hasil
    hasil = hasil.replace('.fm-isi .grid', '.fm-grid')
    return HEADER_CSS + hasil + '\n'


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
