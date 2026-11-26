<?php

namespace App\Controllers;

use App\Models\BarangModel;

/**
 * CONTOH pola controller untuk form_modal() — bukan berkas siap pakai,
 * sesuaikan nama model/field/route dengan proyek Anda.
 *
 * Kewajiban pola ini: method simpan/perbarui/hapus yang dipakai
 * form_modal() WAJIB bercabang AJAX — permintaan AJAX (fetch dari
 * form-modal.js) dibalas JSON {ok, pesan}; permintaan biasa dibalas
 * redirect + flash seperti form halaman pada umumnya.
 */
class Barang extends BaseController
{
    /** Balas sesuai jenis permintaan: JSON untuk AJAX, redirect untuk biasa. */
    private function balas(bool $ok, string $pesan, string $tujuan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }
        return redirect()->to($tujuan)->with($ok ? 'sukses' : 'galat', $pesan);
    }

    public function index()
    {
        helper(['tabel_form', 'tabel_form_plus']);

        return view('barang/index', [
            'judul'  => 'Data Barang',
            'barang' => (new BarangModel())->findAll(),
        ]);
    }

    public function simpan()
    {
        $data = [
            'nama'  => trim((string) $this->request->getPost('nama')),
            'harga' => (int) preg_replace('/\D/', '', (string) $this->request->getPost('harga')),
            'jenis' => (string) $this->request->getPost('jenis'),
        ];
        if ($data['nama'] === '') {
            return $this->balas(false, 'Nama wajib diisi.', 'barang');
        }
        (new BarangModel())->insert($data);

        return $this->balas(true, 'Barang tersimpan.', 'barang');
    }

    public function perbarui(int $id)
    {
        $data = [
            'nama'  => trim((string) $this->request->getPost('nama')),
            'harga' => (int) preg_replace('/\D/', '', (string) $this->request->getPost('harga')),
            'jenis' => (string) $this->request->getPost('jenis'),
        ];
        if ($data['nama'] === '') {
            return $this->balas(false, 'Nama wajib diisi.', 'barang');
        }
        (new BarangModel())->update($id, $data);

        return $this->balas(true, 'Barang diperbarui.', 'barang');
    }

    public function hapus(int $id)
    {
        (new BarangModel())->delete($id);

        return $this->balas(true, 'Barang terhapus.', 'barang');
    }
}
