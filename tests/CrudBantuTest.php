<?php

namespace Ci4TabelForm\Tests;

use Ci4TabelForm\Controllers\CrudBantuTrait;
use PHPUnit\Framework\TestCase;

/**
 * Uji v1.2.0: crud_bantu() (kepala + tabel aksi otomatis + modal)
 * dan CrudBantuTrait (konversi nilai + simpan/hapus duck-typed).
 */
final class CrudBantuTest extends TestCase
{
    private function renderContoh(array $ubah = []): string
    {
        return crud_bantu(array_merge([
            'id' => 'barang',
            'judul' => 'Data Barang',
            'kolom' => [
                ['nama' => 'nama', 'label' => 'Nama Barang', 'jenis' => 'teks', 'wajib' => true],
                ['nama' => 'harga', 'label' => 'Harga', 'jenis' => 'uang', 'format' => 'uang'],
                ['nama' => 'jenis', 'label' => 'Jenis', 'jenis' => 'pilihan', 'opsi' => ['baru' => 'Baru', 'bekas' => 'Bekas']],
                ['nama' => 'kode', 'label' => 'Kode', 'jenis' => 'teks', 'tabel_saja' => true],
            ],
            'baris' => [
                ['id' => 7, 'nama' => 'Semen 50kg', 'harga' => 68000, 'jenis' => 'baru', 'kode' => 'B001'],
                ['id' => 8, 'nama' => 'Pasir', 'harga' => 250000, 'jenis' => 'bekas', 'kode' => 'B002'],
            ],
            'aksi' => 'barang/simpan',
            'aksi_hapus' => 'barang/hapus/__ID__',
            'konfirmasi_hapus' => 'Hapus barang ini?',
            'cari' => 'Cari barang…',
            'paginasi' => 10,
        ], $ubah));
    }

    public function testKepalaDanTombolTambah(): void
    {
        $h = $this->renderContoh();
        $this->assertStringContainsString('Data Barang', $h);
        $this->assertStringContainsString("bukaFormModal('barang-modal')", $h);
        $this->assertStringContainsString('+ Tambah', $h);
    }

    public function testTabelKolomDanFormatUang(): void
    {
        $h = $this->renderContoh();
        $this->assertStringContainsString('<th>Nama Barang</th>', $h);
        $this->assertStringContainsString('<th>Kode</th>', $h);
        $this->assertStringContainsString('Rp 68.000', $h);
        $this->assertStringContainsString('Rp 250.000', $h);
        $this->assertStringContainsString('data-cari="Cari barang…"', $h);
        $this->assertStringContainsString('data-paginasi="10"', $h);
    }

    public function testSelAksiUbahMembawaDataMentah(): void
    {
        $h = $this->renderContoh();
        // Tombol Ubah membuka modal yang sama dengan data baris (JSON).
        $this->assertStringContainsString("bukaFormModal('barang-modal'", $h);
        $this->assertStringContainsString('&quot;id&quot;:7', $h);
        $this->assertStringContainsString('&quot;nama&quot;:&quot;Semen 50kg&quot;', $h);
        $this->assertStringContainsString('&quot;harga&quot;:68000', $h);
        // Tombol Hapus pada sel aksi: form POST ke URL __ID__ terisi.
        $this->assertStringContainsString('action="barang/hapus/7"', $h);
        $this->assertStringContainsString('data-konfirmasi="Hapus barang ini?"', $h);
    }

    public function testModalFormDariKolomYangSama(): void
    {
        $h = $this->renderContoh();
        $this->assertStringContainsString('id="barang-modal"', $h);
        $this->assertStringContainsString('action="barang/simpan"', $h);
        $this->assertStringContainsString('name="nama"', $h);
        $this->assertStringContainsString('name="harga"', $h);
        $this->assertStringContainsString('data-uang', $h);
        // Kolom tabel_saja tidak masuk form.
        $this->assertStringNotContainsString('name="kode"', $h);
        // Hapus di dalam modal aktif karena aksi_hapus terisi.
        $this->assertStringContainsString('data-aksi-hapus="barang/hapus/__ID__"', $h);
    }

    public function testBarisKosongMenampilkanTeksKosong(): void
    {
        $h = $this->renderContoh(['baris' => []]);
        $this->assertStringContainsString('Belum ada data.', $h);
    }

    public function testGrupDariKolom(): void
    {
        $h = crud_bantu([
            'id' => 'sub',
            'kolom' => [
                ['nama' => 'induk', 'label' => 'Induk', 'jenis' => 'teks', 'form_saja' => true],
                ['nama' => 'nama', 'label' => 'Nama Sub', 'jenis' => 'teks'],
            ],
            'baris' => [
                ['id' => 1, 'induk' => 'Belanja Barang', 'nama' => 'Habis Pakai'],
                ['id' => 2, 'induk' => 'Belanja Barang', 'nama' => 'Konsumsi'],
                ['id' => 3, 'induk' => 'Belanja Jasa', 'nama' => 'Honor'],
            ],
            'grup_dari' => 'induk',
            'aksi' => 'sub/simpan',
        ]);
        $this->assertSame(2, substr_count($h, 'baris-grup'));
        $this->assertStringContainsString('Belanja Barang', $h);
        $this->assertStringContainsString('Belanja Jasa', $h);
        // Kolom form_saja tidak tampil sebagai kolom tabel.
        $this->assertStringNotContainsString('<th>Induk</th>', $h);
    }

    // ---------- CrudBantuTrait ----------

    public function testTraitKonversiNilai(): void
    {
        $obj = new class {
            use CrudBantuTrait;

            public function konversi($nilai, string $jenis)
            {
                return self::crudKonversiNilai($nilai, $jenis);
            }
        };
        $this->assertSame(68000, $obj->konversi('Rp 68.000', 'uang'));
        $this->assertSame(1500.5, $obj->konversi('1.500,5', 'angka'));
        $this->assertSame('2026-11-28', $obj->konversi('28-11-2026', 'tanggal'));
        $this->assertSame('2026-11-28', $obj->konversi('2026-11-28', 'tanggal'));
        $this->assertSame('teks rapi', $obj->konversi('  teks rapi  ', 'teks'));
    }

    public function testTraitSimpanHapusDuckTyped(): void
    {
        $model = new class {
            public array $tersimpan = [];

            public function insert(array $data)
            {
                $this->tersimpan['insert'] = $data;

                return 1;
            }

            public function update($id, array $data)
            {
                $this->tersimpan['update'] = [$id, $data];

                return true;
            }

            public function delete($id)
            {
                $this->tersimpan['delete'] = $id;

                return true;
            }
        };
        $obj = new class {
            use CrudBantuTrait;

            public function simpan(object $m, array $d, $id = null): bool
            {
                return $this->crudSimpanData($m, $d, $id);
            }

            public function hapus(object $m, $id): bool
            {
                return $this->crudHapusData($m, $id);
            }
        };
        $this->assertTrue($obj->simpan($model, ['nama' => 'A'], null));
        $this->assertSame(['nama' => 'A'], $model->tersimpan['insert']);
        $this->assertTrue($obj->simpan($model, ['nama' => 'B'], '7'));
        $this->assertSame(['7', ['nama' => 'B']], $model->tersimpan['update']);
        $this->assertTrue($obj->hapus($model, 7));
        $this->assertSame(7, $model->tersimpan['delete']);
    }
}
