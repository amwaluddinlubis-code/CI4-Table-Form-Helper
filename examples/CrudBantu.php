<?php

namespace App\Controllers;

use App\Models\BarangModel;
use Ci4TabelForm\Controllers\CrudBantuTrait;

/**
 * CONTOH modul CRUD satu berkas memakai crud_bantu() + CrudBantuTrait
 * (v1.2.0) — pasangan view-nya cukup SATU baris:
 *
 *   <?= crud_bantu([
 *       'id' => 'barang', 'judul' => 'Data Barang',
 *       'kolom' => [
 *           ['nama' => 'nama', 'label' => 'Nama Barang', 'jenis' => 'teks', 'wajib' => true],
 *           ['nama' => 'harga', 'label' => 'Harga', 'jenis' => 'uang', 'format' => 'uang'],
 *           ['nama' => 'jenis', 'label' => 'Jenis', 'jenis' => 'pilihan',
 *            'opsi' => ['baru' => 'Baru', 'bekas' => 'Bekas']],
 *       ],
 *       'baris' => $barang,
 *       'aksi' => 'barang/simpan',
 *       'aksi_hapus' => 'barang/hapus/__ID__',
 *       'konfirmasi_hapus' => 'Hapus barang ini?',
 *       'cari' => 'Cari barang…', 'paginasi' => 25,
 *   ]) ?>
 *
 * Muat helper di BaseController: helper(['tabel_form', 'tabel_form_plus', 'crud_bantu']);
 */
class BarangCrud extends BaseController
{
    use CrudBantuTrait;

    public function index()
    {
        return view('barang/crud', [
            'judul'  => 'Data Barang',
            'barang' => (new BarangModel())->findAll(),
        ]);
    }

    public function simpan()
    {
        $data = $this->crudAmbilData([
            'nama'  => 'teks',
            'harga' => 'uang',
            'jenis' => 'pilihan',
        ]);
        if ($data['nama'] === '') {
            return $this->crudBalas(false, 'Nama wajib diisi.', 'barang');
        }
        $ok = $this->crudSimpanData(new BarangModel(), $data, $this->request->getPost('id'));

        return $this->crudBalas($ok, $ok ? 'Barang tersimpan.' : 'Gagal menyimpan barang.', 'barang');
    }

    public function hapus(int $id)
    {
        $ok = $this->crudHapusData(new BarangModel(), $id);

        return $this->crudBalas($ok, $ok ? 'Barang terhapus.' : 'Gagal menghapus barang.', 'barang');
    }
}
