<?php

/**
 * Fungsi tambahan paket ci4-tabel-form-helper (dirawat di paket ini,
 * TIDAK ditimpa tools/sinkron.py):
 *
 * - flash_toast(): render pesan flash session (atau array pesan)
 *   sebagai toast di dalam <div id="wadah-toast">.
 * - kolom_aksi(): definisi kolom "Aksi" untuk tabel_bantu().
 * - sel_aksi(): sel tombol Ubah/Hapus otomatis untuk tabel_bantu() —
 *   Ubah membuka form_modal() dengan data baris, Hapus memakai form
 *   data-konfirmasi (modal konfirmasi bersama).
 *
 * (select_cari() tinggal di tabel_form_helper.php karena disinkronkan
 * dari app/Helpers/pilih_helper.php milik PBJ-REVIT.)
 *
 * Muat bersama helper utama: helper(['tabel_form', 'tabel_form_plus']);
 */

if (! function_exists('flash_toast')) {
    /**
     * Render toast dari pesan flash session atau dari array pesan.
     *
     * Tanpa argumen: membaca flashdata session CI4 — kunci 'sukses'
     * menjadi toast sukses; 'galat', 'gagal', atau 'error' menjadi
     * toast galat. Dengan argumen: ['sukses' => teks|daftar teks,
     * 'galat' => teks|daftar teks].
     *
     * Letakkan di layout, di dalam wadah toast:
     *   <div id="wadah-toast"><?= flash_toast() ?></div>
     * Skrip pada partial modal_konfirmasi menganimasikan toast yang
     * sudah ada di wadah itu (muncul, hilang sendiri, tombol tutup) —
     * markup yang dirender fungsi ini sama persis dengan yang dibuat
     * window.tampilToast() di sisi klien.
     */
    function flash_toast(?array $pesan = null): string
    {
        if ($pesan === null) {
            $pesan = [];
            if (function_exists('session')) {
                $sesi = session();
                if ($sesi !== null) {
                    foreach (['sukses'] as $kunci) {
                        $nilai = $sesi->getFlashdata($kunci);
                        if ($nilai !== null && $nilai !== '') {
                            $pesan['sukses'] = $nilai;
                        }
                    }
                    foreach (['galat', 'gagal', 'error'] as $kunci) {
                        $nilai = $sesi->getFlashdata($kunci);
                        if ($nilai !== null && $nilai !== '') {
                            $pesan['galat'] = $nilai;
                            break;
                        }
                    }
                }
            }
        }
        $h = '';
        foreach (['sukses' => ['sukses', '✓', 'Berhasil'], 'galat' => ['galat', '!', 'Gagal']] as $kunci => [$kelas, $ikon, $judul]) {
            foreach ((array) ($pesan[$kunci] ?? []) as $teks) {
                if ((string) $teks === '') {
                    continue;
                }
                $h .= '<div class="toast ' . $kelas . '" role="status"><span class="toast-ikon">' . $ikon . '</span>'
                    . '<div class="toast-isi"><strong>' . $judul . '</strong><p>' . esc((string) $teks) . '</p></div>'
                    . '<button type="button" class="toast-tutup" aria-label="Tutup notifikasi">×</button></div>';
            }
        }
        return $h;
    }
}

if (! function_exists('kolom_aksi')) {
    /**
     * Definisi kolom Aksi untuk 'kolom' pada tabel_bantu(), pasangan
     * sel_aksi(). Contoh:
     *   'kolom' => [['judul' => 'Nama'], kolom_aksi()],
     */
    function kolom_aksi(string $judul = 'Aksi'): array
    {
        return ['judul' => $judul];
    }
}

if (! function_exists('sel_aksi')) {
    /**
     * Sel tombol aksi otomatis untuk 'sel' pada tabel_bantu():
     * - Tombol Ubah (bila 'modal' diisi): membuka form_modal() dengan
     *   $data (nilai field menurut nama + 'id'), sama seperti pola
     *   buka manual — tanpa menulis onclick sendiri.
     * - Tombol Hapus (bila 'hapus' diisi): form POST ke URL berpola
     *   __ID__ (penanda sama seperti aksi_hapus form_modal), dijaga
     *   modal konfirmasi bersama lewat atribut data-konfirmasi.
     *
     * Opsi: 'modal' => id modal, 'hapus' => URL pola __ID__,
     * 'konfirmasi' => teks konfirmasi hapus, 'teks_ubah',
     * 'teks_hapus'. Mengembalikan sel bentuk ['html' => ...].
     */
    function sel_aksi($id, array $data = [], array $opsi = []): array
    {
        $h = '';
        if (! empty($opsi['modal'])) {
            $json = esc(json_encode($data, JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}', 'attr');
            $h .= '<button type="button" onclick="bukaFormModal(\'' . esc((string) $opsi['modal'], 'attr') . '\', ' . $json . ')">'
                . esc((string) ($opsi['teks_ubah'] ?? 'Ubah')) . '</button>';
        }
        if (! empty($opsi['hapus'])) {
            $url = str_replace(['__ID__', '{id}'], [(string) $id, (string) $id], (string) $opsi['hapus']);
            if ($h !== '') {
                $h .= ' ';
            }
            $h .= '<form method="post" action="' . esc($url, 'attr') . '" data-konfirmasi="'
                . esc((string) ($opsi['konfirmasi'] ?? 'Hapus baris ini?'), 'attr') . '" style="display:inline">'
                . csrf_field()
                . '<button type="submit" class="tombol bahaya">' . esc((string) ($opsi['teks_hapus'] ?? 'Hapus')) . '</button></form>';
        }
        return ['html' => $h];
    }
}
