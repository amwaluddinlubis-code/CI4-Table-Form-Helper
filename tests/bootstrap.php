<?php

/**
 * Bootstrap tes mandiri paket (tanpa aplikasi CI4 penuh).
 *
 * Helper membutuhkan dua fungsi bawaan CI4 — esc() dan csrf_field().
 * Di proyek nyata keduanya disediakan framework; untuk tes paket ini
 * disediakan stub yang setara perilakunya. Stub hanya didefinisikan
 * bila fungsi aslinya belum ada.
 */

if (! function_exists('esc')) {
    function esc($data, $context = 'html')
    {
        if (is_array($data)) {
            return array_map(static fn ($v) => esc($v, $context), $data);
        }
        return htmlspecialchars((string) $data, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('csrf_field')) {
    function csrf_field()
    {
        return '<input type="hidden" name="csrf_test_name" value="tokenuji">';
    }
}

require dirname(__DIR__) . '/src/Helpers/tabel_form_helper.php';
require dirname(__DIR__) . '/src/Helpers/tabel_form_plus_helper.php';
