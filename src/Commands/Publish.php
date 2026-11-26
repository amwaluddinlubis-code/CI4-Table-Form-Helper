<?php

namespace Ci4TabelForm\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Menerbitkan aset paket ke proyek pemakai:
 * - assets/*.css & *.js  -> public/assets/
 * - src/Views/modal_konfirmasi.php -> app/Views/layout/_modal_konfirmasi.php
 *
 * Jalankan: php spark tabel-form:publish [--force]
 */
class Publish extends BaseCommand
{
    protected $group       = 'Ci4TabelForm';
    protected $name        = 'tabel-form:publish';
    protected $description = 'Salin aset CSS/JS dan partial modal konfirmasi paket ci4-tabel-form-helper ke proyek.';
    protected $usage       = 'tabel-form:publish [options]';
    protected $options     = [
        '--force' => 'Timpa berkas tujuan yang sudah ada.',
    ];

    public function run(array $params)
    {
        $paket  = dirname(__DIR__, 2);
        $paksa  = array_key_exists('force', $params) || CLI::getOption('force') !== null;
        $target = [
            [$paket . '/assets/tabel-form.css', FCPATH . 'assets/tabel-form.css'],
            [$paket . '/assets/form-modal.js', FCPATH . 'assets/form-modal.js'],
            [$paket . '/assets/paginasi.js', FCPATH . 'assets/paginasi.js'],
            [$paket . '/assets/select-cari.js', FCPATH . 'assets/select-cari.js'],
            [$paket . '/assets/uang.js', FCPATH . 'assets/uang.js'],
            [$paket . '/assets/tanggal.js', FCPATH . 'assets/tanggal.js'],
            [$paket . '/src/Views/modal_konfirmasi.php', APPPATH . 'Views/layout/_modal_konfirmasi.php'],
        ];

        foreach ($target as [$asal, $tujuan]) {
            $dir = dirname($tujuan);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            if (is_file($tujuan) && ! $paksa) {
                CLI::write('LEWATI (sudah ada, pakai --force untuk menimpa): ' . $tujuan, 'yellow');
                continue;
            }
            if (! copy($asal, $tujuan)) {
                CLI::error('GAGAL menyalin: ' . $asal . ' -> ' . $tujuan);
                continue;
            }
            CLI::write('Terbit: ' . $tujuan, 'green');
        }

        CLI::write('');
        CLI::write('Selesai. Jangan lupa:');
        CLI::write('1. Muat helper: helper(\'tabel_form\') di controller/base controller.');
        CLI::write('2. Sertakan tabel-form.css dan keenam berkas JS di layout Anda.');
        CLI::write('3. Sertakan <div id="wadah-toast"></div> dan partial layout/_modal_konfirmasi di layout.');
    }
}
