<?php

namespace Ci4TabelForm\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Uji render form_modal().
 */
final class FormModalTest extends TestCase
{
    private function modalDasar(array $timpa = []): string
    {
        return form_modal(array_merge([
            'id'               => 'modal-uji',
            'judul'            => 'Tambah Bahan',
            'judul_ubah'       => 'Ubah Bahan',
            'aksi'             => 'http://contoh.test/bahan/simpan',
            'aksi_hapus'       => 'http://contoh.test/bahan/hapus/__ID__',
            'konfirmasi_hapus' => 'Hapus bahan ini?',
            'kolom'            => [
                ['nama' => 'kode', 'label' => 'Kode', 'wajib' => true],
                ['nama' => 'harga', 'label' => 'Harga (Rp)', 'jenis' => 'uang', 'wajib' => true],
                ['nama' => 'tgl', 'label' => 'Tanggal', 'jenis' => 'tanggal'],
                ['nama' => 'jenis', 'label' => 'Jenis', 'jenis' => 'pilihan',
                 'opsi' => ['' => '— Pilih —', 'a' => ['label' => 'A', 'data' => ['x' => '1']]]],
                ['nama' => 'ket', 'label' => 'Keterangan', 'jenis' => 'area', 'penuh' => true],
            ],
        ], $timpa));
    }

    public function testRangkaModalDanJudul(): void
    {
        $h = $this->modalDasar();
        $this->assertStringContainsString('data-form-modal', $h);
        $this->assertStringContainsString('data-judul-tambah="Tambah Bahan"', $h);
        $this->assertStringContainsString('data-judul-ubah="Ubah Bahan"', $h);
        $this->assertStringContainsString('class="fm-grid"', $h);
    }

    public function testAksiHapusMemakaiPenandaId(): void
    {
        $h = $this->modalDasar();
        $this->assertStringContainsString('data-aksi-hapus="http://contoh.test/bahan/hapus/__ID__"', $h);
        $this->assertStringContainsString('data-konfirmasi-hapus="Hapus bahan ini?"', $h);
        $this->assertStringContainsString('data-fm-hapus', $h);
    }

    public function testCsrfDanFieldTersembunyi(): void
    {
        $h = $this->modalDasar(['tersembunyi' => ['kunci_ganda']]);
        $this->assertStringContainsString('name="csrf_test_name"', $h);
        $this->assertStringContainsString('name="id"', $h);
        $this->assertStringContainsString('name="kunci_ganda"', $h);
    }

    public function testJenisField(): void
    {
        $h = $this->modalDasar();
        $this->assertStringContainsString('data-uang', $h);                    // uang
        $this->assertStringContainsString('type="date"', $h);                  // tanggal
        $this->assertStringContainsString('data-select-cari', $h);             // pilihan
        $this->assertStringContainsString('data-x="1"', $h);                   // data-* per opsi
        $this->assertStringContainsString('<textarea', $h);                    // area
        $this->assertStringContainsString('fm-penuh', $h);                     // rentang 2 kolom
    }

    public function testLabelWajibDanTombol(): void
    {
        $h = $this->modalDasar();
        $this->assertStringContainsString('fm-label', $h);
        $this->assertStringContainsString('fm-wajib', $h);
        $this->assertStringContainsString('required', $h);
        $this->assertStringContainsString('data-teks-ubah="Simpan Perubahan"', $h);
        $this->assertStringContainsString('data-fm-tutup', $h);
    }

    public function testTombolBisaDinonaktifkan(): void
    {
        $h = $this->modalDasar(['tombol' => ['hapus' => false, 'batal' => false]]);
        $this->assertStringNotContainsString('data-fm-hapus', $h);
        $this->assertStringNotContainsString('data-fm-tutup', $h);
    }

    public function testTanpaAksiHapusTidakAdaTombolHapus(): void
    {
        $konfig = ['id' => 'm2', 'aksi' => '/simpan', 'kolom' => [['nama' => 'a', 'label' => 'A']]];
        $h = form_modal($konfig);
        $this->assertStringNotContainsString('data-fm-hapus', $h);
        $this->assertStringNotContainsString('data-aksi-hapus', $h);
    }

    public function testSegarkanDanCatatanHtml(): void
    {
        $h = $this->modalDasar([
            'segarkan'     => ['#panel-x'],
            'info_html'    => '<p>info</p>',
            'catatan_html' => '<div>catatan mentah</div>',
            'catatan'      => 'Catatan kecil.',
        ]);
        $this->assertStringContainsString('data-fm-segar="#panel-x"', $h);
        $this->assertStringContainsString('<p>info</p>', $h);
        $this->assertStringContainsString('<div>catatan mentah</div>', $h);
        $this->assertStringContainsString('Catatan kecil.', $h);
    }

    public function testSaranDatalistDanLebar(): void
    {
        $h = form_modal([
            'id' => 'm3', 'aksi' => '/simpan', 'lebar' => 720,
            'kolom' => [['nama' => 'kota', 'label' => 'Kota', 'saran' => ['Medan', 'Padang']]],
        ]);
        $this->assertStringContainsString('max-width:720px', $h);
        $this->assertStringContainsString('<datalist id="m3-kota-saran">', $h);
        $this->assertStringContainsString('<option value="Medan">', $h);
    }
}
