<?php

/**
 * Helper CRUD BANTU — pendamping tabel_form_helper.php (di luar
 * jangkauan sinkron dari PBJ-REVIT, sama seperti
 * tabel_form_plus_helper.php).
 *
 * Merangkai pola modul CRUD yang selama ini ditulis tangan per modul
 * (pola contoh examples/index.php) menjadi SATU panggilan: kepala
 * berisi judul + tombol Tambah, tabel_bantu() dengan kolom aksi
 * otomatis (sel_aksi), dan form_modal() untuk tambah/ubah. Pasangan
 * sisi servernya: Ci4TabelForm\Controllers\CrudBantuTrait.
 */

if (! function_exists('crud_bantu')) {
    /**
     * Render satu blok CRUD lengkap (kepala + tabel + modal form).
     *
     * Konfig:
     * - 'id' (wajib): awalan id tabel & modal (modal = "<id>-modal").
     * - 'judul': judul pada kepala blok (kosong = tanpa kepala).
     * - 'kolom' (wajib): definisi kolom yang MENYATU untuk tabel dan
     *   form modal. Tiap kolom: 'nama' (kunci data), 'label',
     *   'jenis' (teks|angka|uang|tanggal|pilihan|area — diteruskan
     *   ke form_modal), 'opsi' (pilihan), 'wajib', 'petunjuk',
     *   'placeholder', 'penuh', 'baris', 'saran' (diteruskan ke
     *   form_modal), 'angka' (rata kanan di tabel), 'lebar',
     *   'format' => 'uang'|'angka'|'tanggal'|callable($nilai, $baris)
     *   untuk tampilan sel tabel, 'tabel_saja' => true (tidak masuk
     *   form), 'form_saja' => true (tidak tampil di tabel).
     * - 'baris': daftar baris data (array asosiatif atau objek),
     *   wajib memuat kunci id pada 'kunci_id' (bawaan 'id').
     * - 'grup_dari': nama kolom — setiap pergantian nilainya menyisipkan
     *   baris grup pada tabel; 'grup_label' => callable($nilai): string
     *   untuk teks grupnya (bawaan: nilai apa adanya).
     * - 'aksi' (wajib): URL simpan form modal (tambah & ubah dibedakan
     *   dari isian hidden 'id' di sisi server).
     * - 'aksi_hapus': pola URL hapus berpenanda __ID__; mengaktifkan
     *   tombol Hapus pada modal (mode Ubah) DAN pada sel aksi.
     *   'konfirmasi_hapus': teks konfirmasi.
     * - 'tambah': label tombol tambah (bawaan 'Tambah'); false = tanpa
     *   tombol & tanpa modal (tabel baca-saja dengan aksi ubah saja
     *   tidak didukung — tanpa 'tambah', modal tetap dirender untuk
     *   mode Ubah bila ada baris).
     * - 'cari': petunjuk pencarian tabel_bantu (string) atau false;
     *   'paginasi': int atau false.
     * - 'kosong': teks bila tidak ada baris (bawaan 'Belum ada data.').
     * - 'catatan': catatan kecil di bawah field modal; 'lebar_modal':
     *   lebar modal form (bawaan 640).
     * - 'judul_ubah': judul modal mode Ubah (bawaan 'Ubah ' . judul
     *   tanpa 'Data ' di depan, atau 'Ubah').
     */
    function crud_bantu(array $konfig): string
    {
        $id = (string) ($konfig['id'] ?? 'crud');
        $modalId = $id . '-modal';
        $kunciId = (string) ($konfig['kunci_id'] ?? 'id');
        $kolomSemua = (array) ($konfig['kolom'] ?? []);

        $kolomTabel = array_values(array_filter($kolomSemua, static fn (array $k): bool => empty($k['form_saja'])));
        $kolomForm = array_values(array_filter($kolomSemua, static fn (array $k): bool => empty($k['tabel_saja'])));

        // --- Baris tabel ---
        $baris = [];
        $jumlahData = 0;
        $grupDari = (string) ($konfig['grup_dari'] ?? '');
        $grupTerakhir = null;
        foreach ((array) ($konfig['baris'] ?? []) as $dataMentah) {
            $data = (array) $dataMentah;
            $jumlahData++;
            if ($grupDari !== '') {
                $nilaiGrup = (string) ($data[$grupDari] ?? '');
                if ($nilaiGrup !== $grupTerakhir) {
                    $grupTerakhir = $nilaiGrup;
                    $teksGrup = isset($konfig['grup_label']) && is_callable($konfig['grup_label'])
                        ? (string) $konfig['grup_label']($data[$grupDari] ?? null, $data)
                        : $nilaiGrup;
                    $baris[] = ['grup' => $teksGrup];
                }
            }
            $sel = [];
            foreach ($kolomTabel as $k) {
                $nama = (string) ($k['nama'] ?? '');
                $nilai = $data[$nama] ?? null;
                $format = $k['format'] ?? null;
                if (is_callable($format)) {
                    $teks = (string) $format($nilai, $data);
                } elseif ($format === 'uang') {
                    $teks = 'Rp ' . number_format((float) $nilai, 0, ',', '.');
                } elseif ($format === 'angka') {
                    $teks = number_format((float) $nilai, 0, ',', '.');
                } elseif ($format === 'tanggal' && is_string($nilai) && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $nilai, $m)) {
                    $teks = $m[3] . '-' . $m[2] . '-' . $m[1];
                } else {
                    $teks = (string) ($nilai ?? '');
                }
                $sel[] = ['teks' => $teks, 'angka' => ! empty($k['angka']) || $format === 'uang' || $format === 'angka'];
            }
            // Data mode Ubah: nilai mentah field form + id. Kunci
            // 'id' selalu diisi (isian hidden form_modal bernama
            // 'id'); kunci asli ikut disertakan bila berbeda.
            $dataUbah = ['id' => $data[$kunciId] ?? null];
            if ($kunciId !== 'id') {
                $dataUbah[$kunciId] = $data[$kunciId] ?? null;
            }
            foreach ($kolomForm as $k) {
                $nama = (string) ($k['nama'] ?? '');
                $dataUbah[$nama] = $data[$nama] ?? null;
            }
            $sel[] = sel_aksi($data[$kunciId] ?? 0, $dataUbah, [
                'modal' => $modalId,
                'hapus' => (string) ($konfig['aksi_hapus'] ?? ''),
                'konfirmasi' => (string) ($konfig['konfirmasi_hapus'] ?? 'Hapus baris ini?'),
            ]);
            $baris[] = ['sel' => $sel];
        }

        $kolomRender = [];
        foreach ($kolomTabel as $k) {
            $kolomRender[] = [
                'judul' => (string) ($k['label'] ?? ($k['nama'] ?? '')),
                'angka' => ! empty($k['angka']),
                'lebar' => (string) ($k['lebar'] ?? ''),
            ];
        }
        $kolomRender[] = kolom_aksi();

        $h = '<div class="crud-bantu" id="' . esc($id, 'attr') . '">';
        $judul = (string) ($konfig['judul'] ?? '');
        $tambah = $konfig['tambah'] ?? 'Tambah';
        if ($judul !== '' || $tambah !== false) {
            $h .= '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:10px">';
            $h .= $judul !== '' ? '<h3 style="margin:0">' . esc($judul) . '</h3>' : '<span></span>';
            if ($tambah !== false) {
                $h .= '<button type="button" class="tombol" style="display:inline-flex;align-items:center;gap:6px;flex:none;white-space:nowrap" onclick="bukaFormModal(\'' . esc($modalId, 'attr') . '\')">' . esc((string) $tambah) . '</button>';
            }
            $h .= '</div>';
        }
        $h .= tabel_bantu([
            'id' => $id . '-tabel',
            'kolom' => $kolomRender,
            'baris' => $baris,
            'cari' => $konfig['cari'] ?? false,
            'paginasi' => $konfig['paginasi'] ?? false,
        ]);
        if ($jumlahData === 0) {
            $h .= '<p class="kecil-teks" style="margin-top:8px">' . esc((string) ($konfig['kosong'] ?? 'Belum ada data.')) . '</p>';
        }
        $h .= '</div>';

        // --- Modal form (tambah & ubah dalam satu modal) ---
        $judulTambah = $judul !== '' ? 'Tambah ' . preg_replace('/^Data\s+/i', '', $judul) : 'Tambah';
        $judulUbah = trim((string) ($konfig['judul_ubah'] ?? ('Ubah ' . preg_replace('/^Data\s+/i', '', $judul))));
        $h .= form_modal([
            'id' => $modalId,
            'aksi' => (string) ($konfig['aksi'] ?? ''),
            'judul' => $judulTambah,
            'judul_ubah' => $judulUbah !== '' ? $judulUbah : 'Ubah',
            'aksi_hapus' => (string) ($konfig['aksi_hapus'] ?? ''),
            'konfirmasi_hapus' => (string) ($konfig['konfirmasi_hapus'] ?? ''),
            'kolom' => $kolomForm,
            'catatan' => (string) ($konfig['catatan'] ?? ''),
            'lebar' => (int) ($konfig['lebar_modal'] ?? 640),
        ]);

        return $h;
    }
}
