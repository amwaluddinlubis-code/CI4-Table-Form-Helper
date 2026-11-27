<?php

namespace Ci4TabelForm\Controllers;

/**
 * Trait pasangan crud_bantu() untuk controller CI4.
 *
 * Menyediakan tiga hal yang selalu berulang di modul CRUD pola
 * form_modal():
 * - crudAmbilData(): mengambil field POST dengan konversi menurut
 *   jenis field (uang -> int polos, angka -> float, tanggal
 *   dd-mm-yyyy -> yyyy-mm-dd, sisanya string ter-trim).
 * - crudSimpanData()/crudHapusData(): insert/update/delete lewat
 *   model CI4 (duck-typed: objek apa pun ber-method insert/update/
 *   delete dapat dipakai, termasuk model aplikasi).
 * - crudBalas(): balasan bercabang — permintaan AJAX (fetch dari
 *   form-modal.js) dibalas JSON {ok, pesan}; permintaan biasa
 *   dibalas redirect + flash 'sukses'/'galat' (tampil sebagai toast
 *   lewat flash_toast()).
 *
 * Contoh:
 *   class Barang extends BaseController {
 *       use CrudBantuTrait;
 *       public function simpan() {
 *           $data = $this->crudAmbilData(['nama' => 'teks', 'harga' => 'uang', 'jenis' => 'pilihan']);
 *           if ($data['nama'] === '') {
 *               return $this->crudBalas(false, 'Nama wajib diisi.', 'barang');
 *           }
 *           $ok = $this->crudSimpanData(new BarangModel(), $data, $this->request->getPost('id'));
 *           return $this->crudBalas($ok, $ok ? 'Barang tersimpan.' : 'Gagal menyimpan.', 'barang');
 *       }
 *       public function hapus(int $id) {
 *           $ok = $this->crudHapusData(new BarangModel(), $id);
 *           return $this->crudBalas($ok, $ok ? 'Barang terhapus.' : 'Gagal menghapus.', 'barang');
 *       }
 *   }
 */
trait CrudBantuTrait
{
    /**
     * Ambil field dari POST dengan konversi jenis.
     *
     * @param array<string, string> $jenisField peta nama field => jenis
     *                                          (teks|pilihan|area|uang|angka|tanggal)
     */
    protected function crudAmbilData(array $jenisField): array
    {
        $data = [];
        foreach ($jenisField as $nama => $jenis) {
            $mentah = $this->request->getPost($nama);
            $data[$nama] = self::crudKonversiNilai($mentah, (string) $jenis);
        }

        return $data;
    }

    /** Konversi satu nilai menurut jenis field. */
    protected static function crudKonversiNilai($mentah, string $jenis)
    {
        if ($jenis === 'uang') {
            return (int) preg_replace('/\D/', '', (string) ($mentah ?? ''));
        }
        if ($jenis === 'angka') {
            $bersih = str_replace(['.', ' '], '', (string) ($mentah ?? ''));
            $bersih = str_replace(',', '.', $bersih);

            return is_numeric($bersih) ? (float) $bersih : 0.0;
        }
        if ($jenis === 'tanggal') {
            $teks = trim((string) ($mentah ?? ''));
            if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $teks, $m)) {
                return $m[3] . '-' . $m[2] . '-' . $m[1];
            }

            return $teks; // sudah yyyy-mm-dd (isian date) atau kosong
        }

        return trim((string) ($mentah ?? ''));
    }

    /**
     * Simpan data: insert bila $id kosong, update bila terisi.
     * Mengembalikan true bila model melaporkan berhasil.
     */
    protected function crudSimpanData(object $model, array $data, $id = null): bool
    {
        if ($id === null || $id === '' || $id === 0 || $id === '0') {
            $hasil = $model->insert($data);
        } else {
            $hasil = $model->update($id, $data);
        }

        return $hasil !== false;
    }

    /** Hapus satu baris menurut id. */
    protected function crudHapusData(object $model, $id): bool
    {
        return $model->delete($id) !== false;
    }

    /**
     * Balasan bercabang AJAX/biasa untuk form_modal().
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\ResponseInterface
     */
    protected function crudBalas(bool $ok, string $pesan, string $tujuan)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => $ok, 'pesan' => $pesan]);
        }

        return redirect()->to($tujuan)->with($ok ? 'sukses' : 'galat', $pesan);
    }
}
