<?php
/**
 * CONTOH view daftar + modal CRUD memakai tabel_bantu() & form_modal().
 * Pasangan contoh controller: examples/Barang.php.
 * Layout Anda wajib sudah memuat tabel-form.css, keenam berkas JS paket,
 * <div id="wadah-toast"></div>, dan partial modal konfirmasi.
 */
?>
<h1><?= esc($judul) ?></h1>

<p>
  <button type="button" class="tombol" onclick="bukaFormModal('modal-barang')">+ Tambah Barang</button>
</p>

<?php
$baris = [];
foreach ($barang as $b) {
    // Data baris untuk mode Ubah: nilai field menurut NAMA field + 'id'.
    $dataUbah = esc(json_encode([
        'id'    => $b['id'],
        'nama'  => $b['nama'],
        'harga' => $b['harga'],   // field uang diisi angka polos; format ribuan otomatis
        'jenis' => $b['jenis'],
    ], JSON_HEX_APOS | JSON_HEX_QUOT), 'attr');
    $baris[] = ['sel' => [
        esc($b['nama']),
        ['teks' => 'Rp ' . number_format((float) $b['harga'], 0, ',', '.'), 'angka' => true],
        esc($b['jenis']),
        ['html' => '<button type="button" onclick="bukaFormModal(\'modal-barang\', ' . $dataUbah . ')">Ubah</button>'],
    ]];
}
echo tabel_bantu([
    'kolom' => [
        ['judul' => 'Nama'],
        ['judul' => 'Harga', 'angka' => true],
        ['judul' => 'Jenis'],
        ['judul' => 'Aksi'],
    ],
    'baris'    => $baris,
    'cari'     => 'Cari barang…',  // string = kotak cari aktif; false = mati
    'paginasi' => 25,              // int = baris per halaman; false = mati
]);
?>

<?= form_modal([
    'id'          => 'modal-barang',
    'judul'       => 'Tambah Barang',
    'judul_ubah'  => 'Ubah Barang',
    'aksi'        => base_url('barang/simpan'),
    // Penanda id WAJIB __ID__ (bukan {id} — kurung kurawal ter-encode URL).
    // Catatan: untuk mode Ubah, arahkan aksi form ke endpoint perbarui lewat
    // jembatan kecil di halaman Anda, atau buat endpoint simpan menerima
    // field 'id' dan memutuskan insert vs update di controller.
    'aksi_hapus'       => base_url('barang/hapus/__ID__'),
    'konfirmasi_hapus' => 'Hapus barang ini?',
    'kolom' => [
        ['nama' => 'nama', 'label' => 'Nama Barang', 'wajib' => true],
        ['nama' => 'harga', 'label' => 'Harga (Rp)', 'jenis' => 'uang', 'wajib' => true],
        ['nama' => 'jenis', 'label' => 'Jenis', 'jenis' => 'pilihan', 'opsi' => [
            ''        => '— Pilih —',
            'umum'    => 'Umum',
            'khusus'  => 'Khusus',
        ]],
        // jenis lain: teks (bawaan) | angka | tanggal | area ('baris' => n)
        // opsi field: 'placeholder', 'petunjuk', 'penuh' => true (rentang 2 kolom),
        // 'saran' => [...] (datalist untuk field teks)
    ],
    'catatan' => 'Harga boleh diketik dengan atau tanpa pemisah ribuan.',
    // Tombol bawaan: Batal, Hapus, Simpan — nonaktifkan per sakelar:
    // 'tombol' => ['hapus' => false],
]) ?>
