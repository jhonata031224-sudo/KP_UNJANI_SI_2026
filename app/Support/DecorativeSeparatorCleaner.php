<?php

namespace App\Support;

/**
 * Logika pembersihan teks dekoratif ("//", " - ", " — ", spasi ganda) yang
 * dipakai RemoveDecorativeSeparators (untuk response text/html biasa) DAN
 * endpoint-endpoint polling realtime yang mengembalikan potongan HTML lewat
 * JSON (mis. LaporanKendalaController::realtime()).
 *
 * KENAPA INI PERLU JADI SATU HELPER BERSAMA (bukan cuma di middleware):
 * Middleware RemoveDecorativeSeparators CUMA jalan untuk response dengan
 * Content-Type text/html -- endpoint realtime() sengaja balikin JSON supaya
 * gampang di-fetch() dari JS, jadi middleware itu TIDAK PERNAH menyentuh
 * potongan HTML kartu yang dikirim lewat items_html/terkirim_items_html/
 * arsip_items_html. Akibatnya render pertama halaman (lewat Blade penuh,
 * kena middleware) dan render polling (lewat JSON, TIDAK kena middleware)
 * bisa menghasilkan teks yang beda utk kartu yang SAMA & datanya SAMA
 * PERSIS -- kalau field-nya (perihal/catatan/deskripsi dsb) kebetulan
 * mengandung "-", "/", atau spasi ganda. Perbandingan signature() di
 * kendala-terkirim-realtime.blade.php (dan skrip polling sejenis lainnya)
 * jadi PERMANEN dianggap "berubah" utk kartu itu -> kartu itu di-replace +
 * animasi masuk diputar ULANG SETIAP POLLING (3 detik) TANPA HENTI, biarpun
 * datanya sebenarnya tidak pernah berubah -- itulah bug "kartu kedip terus
 * nonstop kayak aktif" yang cuma kena kartu tertentu (yang teksnya kena
 * pola ini). Fix-nya: pastikan HTML yang dikirim lewat JSON polling
 * dinormalisasi PERSIS SAMA seperti HTML yang sudah lewat middleware, pakai
 * fungsi clean() yang sama ini di kedua sisi.
 */
class DecorativeSeparatorCleaner
{
    public static function clean(string $html): string
    {
        $protected = [];

        $html = preg_replace_callback(
            '~<(script|style)\b[^>]*>.*?</\1>~is',
            function (array $match) use (&$protected): string {
                $key = '___DECORATIVE_SEPARATOR_BLOCK_'.count($protected).'___';
                $protected[$key] = $match[0];

                return $key;
            },
            $html
        );

        $html = preg_replace_callback(
            '~>([^<]+)<~',
            static function (array $match): string {
                $text = $match[1];
                $text = preg_replace('/\s*\/\/\s*/u', ' ', $text);
                $text = preg_replace('/(^|\s)[-—](?=\s|$)/u', '$1', $text);
                $text = preg_replace('/ {2,}/', ' ', $text);

                return '>'.$text.'<';
            },
            $html
        );

        foreach ($protected as $key => $block) {
            $html = str_replace($key, $block, $html);
        }

        return $html;
    }
}
