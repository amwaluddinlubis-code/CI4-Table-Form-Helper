<?php

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

if (! function_exists('sel_tabel_html')) {
    /**
     * Render satu sel untuk tabel_bantu(): string -> diescape; array
     * berkunci 'html' -> mentah; 'teks' -> diescape, dengan opsi 'angka'
     * (rata kanan), 'class', 'colspan', 'gaya'.
     */
    function sel_tabel_html($sel): string
    {
        if (! is_array($sel)) {
            return '<td>' . esc((string) $sel) . '</td>';
        }
        $isi = array_key_exists('html', $sel) ? (string) $sel['html'] : esc((string) ($sel['teks'] ?? ''));
        $atribut = '';
        $kelas = trim((string) ($sel['class'] ?? '') . (! empty($sel['angka']) ? ' angka' : ''));
        if ($kelas !== '') {
            $atribut .= ' class="' . esc($kelas, 'attr') . '"';
        }
        if (! empty($sel['colspan'])) {
            $atribut .= ' colspan="' . (int) $sel['colspan'] . '"';
        }
        if (! empty($sel['gaya'])) {
            $atribut .= ' style="' . esc((string) $sel['gaya'], 'attr') . '"';
        }
        if (! empty($sel['title'])) {
            $atribut .= ' title="' . esc((string) $sel['title'], 'attr') . '"';
        }
        return '<td' . $atribut . '>' . $isi . '</td>';
    }
}

if (! function_exists('tabel_bantu')) {
    /**
     * Helper TABEL standar (berasal dari aplikasi PBJ-REVIT, keputusan AMWAL 25 Nov 2026): merender
     * tabel Tabel Standar v2 (atribut data-cari / data-paginasi untuk
     * paginasi.js, baris grup kelas baris-grup, baris total kelas
     * baris-total) dari konfigurasi — perubahan pola tabel cukup diubah
     * di sini, bukan di tiap view. Modul berpindah ke helper ini
     * bertahap; contoh pemakaian: biayalain/_kelompok.
     *
     * Konfig:
     * - 'kolom': daftar ['judul' => teks, 'judul_html' => mentah,
     *   'angka' => bool, 'lebar' => '110px', 'class' => ...]
     * - 'baris': daftar baris; tiap baris salah satu dari:
     *     ['sel' => [sel, ...], 'class' => ..., 'atribut' => 'data-...']
     *     ['grup' => 'Teks grup'] (atau 'grup_html') -> baris-grup
     *     ['total' => [sel, ...], 'class' => tambahan] -> baris-total
     *   Sel: lihat sel_tabel_html().
     * - 'cari': petunjuk pencarian (string) atau false (bawaan false)
     * - 'paginasi': baris per halaman (int) atau false (bawaan false)
     * - 'class': kelas tambahan tabel; 'id': id tabel
     */
    function tabel_bantu(array $konfig): string
    {
        $kolom = $konfig['kolom'] ?? [];
        $atribut = '';
        if (! empty($konfig['id'])) {
            $atribut .= ' id="' . esc((string) $konfig['id'], 'attr') . '"';
        }
        if (isset($konfig['cari']) && $konfig['cari'] !== false) {
            $atribut .= ' data-cari="' . esc((string) $konfig['cari'], 'attr') . '"';
        }
        if (isset($konfig['paginasi']) && $konfig['paginasi'] !== false) {
            $atribut .= ' data-paginasi="' . (int) $konfig['paginasi'] . '"';
        }
        if (! empty($konfig['class'])) {
            $atribut .= ' class="' . esc((string) $konfig['class'], 'attr') . '"';
        }
        $h = '<table' . $atribut . '><thead><tr>';
        foreach ($kolom as $k) {
            $ka = '';
            $kelas = trim((string) ($k['class'] ?? '') . (! empty($k['angka']) ? ' angka' : ''));
            if ($kelas !== '') {
                $ka .= ' class="' . esc($kelas, 'attr') . '"';
            }
            if (! empty($k['lebar'])) {
                $ka .= ' style="width:' . esc((string) $k['lebar'], 'attr') . '"';
            }
            if (! empty($k['atribut'])) {
                $ka .= ' ' . $k['atribut'];
            }
            $h .= '<th' . $ka . '>' . (isset($k['judul_html']) ? (string) $k['judul_html'] : esc((string) ($k['judul'] ?? ''))) . '</th>';
        }
        $h .= '</tr></thead><tbody>';
        $jmlKolom = max(1, count($kolom));
        foreach ($konfig['baris'] ?? [] as $b) {
            if (isset($b['grup']) || isset($b['grup_html'])) {
                $h .= '<tr class="baris-grup"><td colspan="' . $jmlKolom . '">'
                    . (isset($b['grup_html']) ? (string) $b['grup_html'] : esc((string) $b['grup'])) . '</td></tr>';
                continue;
            }
            $adalahTotal = isset($b['total']);
            $sel = $adalahTotal ? $b['total'] : ($b['sel'] ?? []);
            $kelas = trim(($adalahTotal ? 'baris-total ' : '') . (string) ($b['class'] ?? ''));
            $h .= '<tr' . ($kelas !== '' ? ' class="' . esc($kelas, 'attr') . '"' : '')
                . (! empty($b['atribut']) ? ' ' . $b['atribut'] : '') . '>';
            foreach ($sel as $s) {
                $h .= sel_tabel_html($s);
            }
            $h .= '</tr>';
        }
        return $h . '</tbody></table>';
    }
}

if (! function_exists('form_modal')) {
    /**
     * Helper FORM MODAL CRUD (berasal dari aplikasi PBJ-REVIT, keputusan AMWAL 25 Nov 2026): merender
     * modal form pola .mk-lapik/.mk-kartu dari konfigurasi saja — modul
     * tidak lagi menulis markup + skrip modalnya sendiri. Perilaku
     * (buka/isi/tutup, kirim AJAX berbalasan JSON {ok, pesan}, hapus
     * lewat modal konfirmasi standar, validasi wajib via tooltip) ada
     * di public/assets/form-modal.js: bukaFormModal(id) untuk Tambah,
     * bukaFormModal(id, data) untuk Ubah (data = nilai field menurut
     * nama, termasuk 'id').
     *
     * Tampilan (koreksi AMWAL 25 Nov 2026): judul RATA KIRI pada pita
     * kepala yang sedikit lebih gelap, kaki tombol juga sedikit lebih
     * gelap, dan LABEL FIELD DUDUK DI BORDER isian (gaya label-on-border)
     * dengan tanda * merah untuk field wajib.
     *
     * Konfig:
     * - 'id' (wajib), 'aksi' (URL simpan, wajib)
     * - 'judul' (bawaan 'Tambah'), 'judul_ubah' (bawaan = judul)
     * - 'aksi_hapus': URL hapus berpenanda __ID__ (BUKAN {id} — kurung
     *   kurawal ter-encode oleh pembangun URL); 'konfirmasi_hapus': teks
     * - 'kolom': daftar field: ['nama', 'label', 'jenis' => teks|angka|
     *   uang|tanggal|pilihan|area, 'opsi' => [nilai => label] (pilihan),
     *   'saran' => [nilai, ...] (datalist untuk teks), 'wajib' => bool,
     *   'petunjuk', 'placeholder', 'penuh' => bool (rentang 2 kolom),
     *   'baris' => jumlah baris (area)]
     * - 'tombol': sakelar bawaan — ['batal' => true, 'hapus' => true,
     *   'simpan' => true, 'simpan_teks', 'simpan_teks_ubah', 'hapus_teks'];
     *   nonaktifkan dengan false. Hapus hanya tampil pada mode Ubah dan
     *   bila 'aksi_hapus' terisi.
     * - 'tersembunyi': daftar nama input hidden tambahan (selain 'id')
     *   yang ikut terisi oleh data bukaFormModal — mis. kunci ganda.
     * - 'info_html': HTML mentah di puncak isi modal (mis. keterangan
     *   baris yang sedang diubah); 'catatan_html': HTML mentah di bawah
     *   field (mis. pratinjau hitungan langsung yang diperbarui skrip
     *   modul). 'opsi' pilihan boleh berbentuk ['label' => ..., 'data'
     *   => [kunci => nilai]] untuk atribut data-* pada <option>.
     * - 'segarkan': daftar pemilih elemen (mis. ['#panel-x']) yang isi
     *   HTML-nya diganti dari muatan segar halaman sesudah simpan
     *   berhasil — TANPA memuat ulang seluruh halaman.
     * - 'catatan': teks kecil di bawah field; 'lebar': max-width (px, 640)
     */
    function form_modal(array $konfig): string
    {
        $id = (string) ($konfig['id'] ?? '');
        $tombol = array_merge(['batal' => true, 'hapus' => true, 'simpan' => true], $konfig['tombol'] ?? []);
        $judul = (string) ($konfig['judul'] ?? 'Tambah');
        $judulUbah = (string) ($konfig['judul_ubah'] ?? $judul);
        $lebar = (int) ($konfig['lebar'] ?? 640);
        $h = '<div id="' . esc($id, 'attr') . '" class="mk-lapik" hidden data-form-modal'
            . ' data-judul-tambah="' . esc($judul, 'attr') . '" data-judul-ubah="' . esc($judulUbah, 'attr') . '"'
            . (! empty($konfig['aksi_hapus']) ? ' data-aksi-hapus="' . esc((string) $konfig['aksi_hapus'], 'attr') . '"' : '')
            . (! empty($konfig['konfirmasi_hapus']) ? ' data-konfirmasi-hapus="' . esc((string) $konfig['konfirmasi_hapus'], 'attr') . '"' : '')
            . (! empty($konfig['segarkan']) ? ' data-fm-segar="' . esc(implode(',', (array) $konfig['segarkan']), 'attr') . '"' : '')
            . '><div class="mk-kartu fm-kartu" role="dialog" aria-modal="true" style="max-width:' . $lebar . 'px;width:94vw">'
            . '<div class="fm-kepala"><h3 data-fm-judul>' . esc($judul) . '</h3></div>'
            . '<form data-fm-form method="post" novalidate action="' . esc((string) ($konfig['aksi'] ?? ''), 'attr') . '">'
            . csrf_field()
            . '<input type="hidden" name="id" value="">'
            . implode('', array_map(static fn ($n) => '<input type="hidden" name="' . esc((string) $n, 'attr') . '" value="">', (array) ($konfig['tersembunyi'] ?? [])))
            . '<div class="fm-isi">'
            . '<div class="galat" data-fm-galat hidden style="margin-bottom:12px"></div>'
            . (! empty($konfig['info_html']) ? (string) $konfig['info_html'] : '')
            . '<div class="fm-grid">';
        foreach ($konfig['kolom'] ?? [] as $f) {
            $nama = (string) ($f['nama'] ?? '');
            $jenis = (string) ($f['jenis'] ?? 'teks');
            $label = (string) ($f['label'] ?? $nama);
            $idField = $id . '-' . $nama;
            $wajib = ! empty($f['wajib']) ? ' required' : '';
            $ph = isset($f['placeholder']) ? ' placeholder="' . esc((string) $f['placeholder'], 'attr') . '"' : '';
            $h .= '<div class="fm-field' . (! empty($f['penuh']) ? ' fm-penuh' : '') . '"><div class="fm-kotak-field">';
            if ($jenis === 'pilihan') {
                $h .= '<select data-select-cari id="' . esc($idField, 'attr') . '" name="' . esc($nama, 'attr') . '" data-label="' . esc($label, 'attr') . '"' . $wajib . '>';
                foreach ($f['opsi'] ?? [] as $nilai => $labelOpsi) {
                    $dataOpsi = '';
                    if (is_array($labelOpsi)) {
                        foreach ($labelOpsi['data'] ?? [] as $kd => $vd) {
                            $dataOpsi .= ' data-' . esc((string) $kd, 'attr') . '="' . esc((string) $vd, 'attr') . '"';
                        }
                        $labelOpsi = (string) ($labelOpsi['label'] ?? '');
                    }
                    $h .= '<option value="' . esc((string) $nilai, 'attr') . '"' . $dataOpsi . '>' . esc((string) $labelOpsi) . '</option>';
                }
                $h .= '</select>';
            } elseif ($jenis === 'area') {
                $h .= '<textarea id="' . esc($idField, 'attr') . '" name="' . esc($nama, 'attr') . '" data-label="' . esc($label, 'attr') . '" rows="' . (int) ($f['baris'] ?? 3) . '"' . $wajib . $ph . '></textarea>';
            } else {
                $h .= '<input type="' . ($jenis === 'tanggal' ? 'date' : 'text') . '" id="' . esc($idField, 'attr') . '" name="' . esc($nama, 'attr') . '" data-label="' . esc($label, 'attr') . '"' . $wajib . $ph;
                if ($jenis === 'uang') {
                    $h .= ' data-uang inputmode="numeric"';
                } elseif ($jenis === 'angka') {
                    $h .= ' inputmode="decimal"';
                }
                if (! empty($f['saran'])) {
                    $h .= ' list="' . esc($idField . '-saran', 'attr') . '"';
                }
                $h .= '>';
                if (! empty($f['saran'])) {
                    $h .= '<datalist id="' . esc($idField . '-saran', 'attr') . '">';
                    foreach ($f['saran'] as $s) {
                        $h .= '<option value="' . esc((string) $s, 'attr') . '">';
                    }
                    $h .= '</datalist>';
                }
            }
            $h .= '<label class="fm-label" for="' . esc($idField, 'attr') . '">' . esc($label)
                . (! empty($f['wajib']) ? ' <span class="fm-wajib" aria-hidden="true">*</span>' : '') . '</label></div>';
            if (! empty($f['petunjuk'])) {
                $h .= '<span class="kecil-teks" style="display:block;margin-top:3px">' . esc((string) $f['petunjuk']) . '</span>';
            }
            $h .= '</div>';
        }
        $h .= '</div>';
        if (! empty($konfig['catatan'])) {
            $h .= '<p class="kecil-teks" style="margin:12px 0 2px">' . esc((string) $konfig['catatan']) . '</p>';
        }
        if (! empty($konfig['catatan_html'])) {
            $h .= (string) $konfig['catatan_html'];
        }
        $h .= '</div><div class="fm-kaki">';
        if ($tombol['hapus'] && ! empty($konfig['aksi_hapus'])) {
            $h .= '<button type="button" class="tombol bahaya" data-fm-hapus hidden style="margin-right:auto">' . esc((string) ($tombol['hapus_teks'] ?? 'Hapus')) . '</button>';
        }
        if ($tombol['batal']) {
            $h .= '<button type="button" data-fm-tutup>Batal</button>';
        }
        if ($tombol['simpan']) {
            $h .= '<button type="submit" class="tombol" data-fm-simpan data-teks-tambah="' . esc((string) ($tombol['simpan_teks'] ?? 'Simpan'), 'attr')
                . '" data-teks-ubah="' . esc((string) ($tombol['simpan_teks_ubah'] ?? 'Simpan Perubahan'), 'attr') . '">'
                . esc((string) ($tombol['simpan_teks'] ?? 'Simpan')) . '</button>';
        }
        return $h . '</div></form></div></div>';
    }
}

if (! function_exists('select_cari')) {
    /**
     * Select yang dapat dicari (searchable dropdown ala Preline Advanced Select).
     *
     * Merender <select> asli bertanda data-select-cari; berkas select-cari.js
     * mengubahnya di peramban menjadi dropdown dengan kotak pencarian, navigasi
     * papan tik, dan tanda centang pada pilihan aktif. Select asli tetap tinggal
     * di formulir (tersembunyi) sehingga nama/nilai yang terkirim dan peristiwa
     * change tidak berubah — kode lama yang membaca select ini tetap bekerja.
     *
     * Select yang ditulis tangan pun bisa: cukup tambahkan atribut
     * data-select-cari (dan data-placeholder bila perlu) pada <select> biasa.
     *
     * @param array $options Daftar opsi: baris ['value' => .., 'label' => ..,
     *                       'data' => ['harga' => 123]] atau peta nilai => label.
     * @param mixed $selected Nilai terpilih awal.
     * @param array $attrs    Atribut select tambahan (id, class, required, …);
     *                        kunci 'placeholder' menjadi label opsi kosong.
     */
    function select_cari(string $name, array $options, $selected = null, array $attrs = []): string
    {
        $placeholder = (string) ($attrs['placeholder'] ?? '— pilih —');
        unset($attrs['placeholder']);

        $html = '<select name="' . esc($name, 'attr') . '" data-select-cari data-placeholder="' . esc($placeholder, 'attr') . '"';
        foreach ($attrs as $k => $v) {
            if ($v === true) {
                $html .= ' ' . esc($k, 'attr');
            } elseif ($v !== false && $v !== null) {
                $html .= ' ' . esc($k, 'attr') . '="' . esc((string) $v, 'attr') . '"';
            }
        }
        $html .= '><option value="">' . esc($placeholder) . '</option>';

        foreach ($options as $k => $o) {
            if (is_array($o)) {
                $value = (string) ($o['value'] ?? '');
                $label = (string) ($o['label'] ?? $value);
                $data  = (array) ($o['data'] ?? []);
            } else {
                $value = (string) $k;
                $label = (string) $o;
                $data  = [];
            }
            $html .= '<option value="' . esc($value, 'attr') . '"'
                . ($selected !== null && (string) $selected === $value ? ' selected' : '');
            foreach ($data as $dk => $dv) {
                $html .= ' data-' . esc((string) $dk, 'attr') . '="' . esc((string) $dv, 'attr') . '"';
            }
            $html .= '>' . esc($label) . '</option>';
        }

        return $html . '</select>';
    }
}
