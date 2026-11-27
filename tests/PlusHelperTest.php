<?php

namespace Ci4TabelForm\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Uji fungsi tambahan v1.1.0: select_cari(), flash_toast(),
 * kolom_aksi() + sel_aksi().
 */
final class PlusHelperTest extends TestCase
{
    // ---------- select_cari() ----------

    public function testSelectCariPetaNilaiLabel(): void
    {
        $h = select_cari('kota', ['mdn' => 'Medan', 'pdg' => 'Padang'], 'pdg');
        $this->assertStringContainsString('name="kota"', $h);
        $this->assertStringContainsString('data-select-cari', $h);
        $this->assertStringContainsString('<option value="">— pilih —</option>', $h);
        $this->assertStringContainsString('<option value="pdg" selected>Padang</option>', $h);
        $this->assertStringContainsString('<option value="mdn">Medan</option>', $h);
    }

    public function testSelectCariBarisDenganDataDanAtribut(): void
    {
        $h = select_cari('bahan', [
            ['value' => '1', 'label' => 'Semen', 'data' => ['harga' => 50000]],
        ], null, ['id' => 'pil-bahan', 'required' => true, 'placeholder' => '— pilih bahan —']);
        $this->assertStringContainsString('id="pil-bahan"', $h);
        $this->assertStringContainsString('required', $h);
        $this->assertStringContainsString('data-placeholder="— pilih bahan —"', $h);
        $this->assertStringContainsString('data-harga="50000"', $h);
    }

    public function testSelectCariEscapeLabel(): void
    {
        $h = select_cari('x', ['a' => '<b>Berani</b>']);
        $this->assertStringContainsString('&lt;b&gt;Berani&lt;/b&gt;', $h);
        $this->assertStringNotContainsString('<b>Berani</b>', $h);
    }

    // ---------- flash_toast() ----------

    public function testFlashToastArrayEksplisit(): void
    {
        $h = flash_toast(['sukses' => 'Barang tersimpan.', 'galat' => ['Gagal satu', 'Gagal dua']]);
        $this->assertStringContainsString('class="toast sukses"', $h);
        $this->assertStringContainsString('<strong>Berhasil</strong><p>Barang tersimpan.</p>', $h);
        $this->assertSame(2, substr_count($h, 'class="toast galat"'));
        $this->assertStringContainsString('toast-tutup', $h);
    }

    public function testFlashToastEscapeDanKosong(): void
    {
        $this->assertSame('', flash_toast([]));
        $this->assertSame('', flash_toast());
        $h = flash_toast(['sukses' => '<script>jahat</script>']);
        $this->assertStringContainsString('&lt;script&gt;', $h);
    }

    // ---------- kolom_aksi() + sel_aksi() ----------

    public function testKolomAksi(): void
    {
        $this->assertSame(['judul' => 'Aksi'], kolom_aksi());
        $this->assertSame(['judul' => 'Tindakan'], kolom_aksi('Tindakan'));
    }

    public function testSelAksiUbahMembukaModalDenganData(): void
    {
        $sel = sel_aksi(7, ['id' => 7, 'nama' => 'Semen'], ['modal' => 'modal-barang']);
        $this->assertStringContainsString('bukaFormModal(\'modal-barang\',', $sel['html']);
        $this->assertStringContainsString('&quot;nama&quot;', $sel['html']);
        $this->assertStringContainsString('>Ubah</button>', $sel['html']);
    }

    public function testSelAksiHapusFormKonfirmasi(): void
    {
        $sel = sel_aksi(9, [], [
            'hapus' => 'http://contoh.test/barang/hapus/__ID__',
            'konfirmasi' => 'Hapus barang ini?',
        ]);
        $this->assertStringContainsString('action="http://contoh.test/barang/hapus/9"', $sel['html']);
        $this->assertStringContainsString('data-konfirmasi="Hapus barang ini?"', $sel['html']);
        $this->assertStringContainsString('name="csrf_test_name"', $sel['html']);
        $this->assertStringContainsString('class="tf-aksi bahaya"', $sel['html']);
    }

    public function testSelAksiDiDalamTabelBantu(): void
    {
        $h = tabel_bantu([
            'kolom' => [['judul' => 'Nama'], kolom_aksi()],
            'baris' => [['sel' => ['Semen', sel_aksi(3, ['id' => 3], ['modal' => 'm', 'hapus' => '/hapus/__ID__'])]]],
        ]);
        $this->assertStringContainsString('<th>Aksi</th>', $h);
        $this->assertStringContainsString('bukaFormModal', $h);
        $this->assertStringContainsString('action="/hapus/3"', $h);
    }
}
