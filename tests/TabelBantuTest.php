<?php

namespace Ci4TabelForm\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Uji render tabel_bantu() & sel_tabel_html().
 */
final class TabelBantuTest extends TestCase
{
    private function tabelDasar(): string
    {
        return tabel_bantu([
            'kolom' => [
                ['judul' => 'Nama'],
                ['judul' => 'Harga', 'angka' => true],
            ],
            'baris' => [
                ['grup' => 'Kelompok A'],
                ['sel' => ['Semen <b>50</b>', ['teks' => 'Rp 50.000', 'angka' => true]]],
                ['sel' => [['html' => '<b>Portland</b>'], ['teks' => 'Rp 52.000', 'angka' => true]]],
                ['total' => [['teks' => 'Total'], ['teks' => 'Rp 102.000', 'angka' => true]]],
            ],
            'cari'     => 'Cari bahan…',
            'paginasi' => 25,
            'id'       => 'tabel-uji',
        ]);
    }

    public function testAtributCariPaginasiDanId(): void
    {
        $h = $this->tabelDasar();
        $this->assertStringContainsString('id="tabel-uji"', $h);
        $this->assertStringContainsString('data-cari="Cari bahan…"', $h);
        $this->assertStringContainsString('data-paginasi="25"', $h);
    }

    public function testBarisGrupDanTotal(): void
    {
        $h = $this->tabelDasar();
        $this->assertStringContainsString('<tr class="baris-grup"><td colspan="2">Kelompok A</td></tr>', $h);
        $this->assertStringContainsString('baris-total', $h);
    }

    public function testSelStringDiescapeDanHtmlMentah(): void
    {
        $h = $this->tabelDasar();
        $this->assertStringContainsString('Semen &lt;b&gt;50&lt;/b&gt;', $h);
        $this->assertStringContainsString('<b>Portland</b>', $h);
    }

    public function testSelAngkaBerkelasAngka(): void
    {
        $h = $this->tabelDasar();
        $this->assertStringContainsString('<td class="angka">Rp 50.000</td>', $h);
        $this->assertStringContainsString('<th class="angka">Harga</th>', $h);
    }

    public function testCariDanPaginasiBisaDimatikan(): void
    {
        $h = tabel_bantu([
            'kolom' => [['judul' => 'A']],
            'baris' => [['sel' => ['x']]],
            'cari' => false, 'paginasi' => false,
        ]);
        $this->assertStringNotContainsString('data-cari', $h);
        $this->assertStringNotContainsString('data-paginasi', $h);
    }

    public function testSelColspanClassDanTitle(): void
    {
        $h = tabel_bantu([
            'kolom' => [['judul' => 'A'], ['judul' => 'B']],
            'baris' => [['sel' => [['teks' => 'gabung', 'colspan' => 2, 'class' => 'tebal', 'title' => 'petunjuk']]]],
        ]);
        $this->assertStringContainsString('colspan="2"', $h);
        $this->assertStringContainsString('class="tebal"', $h);
        $this->assertStringContainsString('title="petunjuk"', $h);
    }

    public function testKolomLebarDanJudulHtml(): void
    {
        $h = tabel_bantu([
            'kolom' => [['judul_html' => '<em>Nama</em>', 'lebar' => '110px']],
            'baris' => [],
        ]);
        $this->assertStringContainsString('<em>Nama</em>', $h);
        $this->assertStringContainsString('style="width:110px"', $h);
    }
}
