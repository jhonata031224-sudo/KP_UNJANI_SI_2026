<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class CaptchaController extends Controller
{
    /**
     * Buat gambar captcha (angka + huruf kecil + huruf kapital, dengan noise
     * garis/titik) pakai GD bawaan PHP — tanpa font TTF eksternal, supaya
     * tidak butuh file tambahan yang bisa hilang saat deploy.
     */
    public function image(): Response
    {
        $width = 260;
        $height = 90;

        // Karakter membingungkan (0/O, 1/l/I) dibuang supaya tetap terbaca.
        $karakter = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $kode = '';
        for ($i = 0; $i < 5; $i++) {
            $kode .= $karakter[random_int(0, strlen($karakter) - 1)];
        }
        session(['captcha_code' => $kode]);

        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 10, 26, 18);
        imagefill($image, 0, 0, $bg);

        // Garis noise di belakang teks.
        for ($i = 0; $i < 9; $i++) {
            $warnaGaris = imagecolorallocate($image, random_int(40, 90), random_int(90, 140), random_int(60, 100));
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $warnaGaris);
        }

        // Tiap karakter digambar di kanvas kecil lalu diperbesar (scale 3x) ke
        // kanvas utama, supaya ukurannya jauh lebih besar & tebal — tanpa font
        // TTF eksternal — sehingga lebih mudah dibaca (termasuk untuk lansia).
        $scale = 3;
        $fontW = imagefontwidth(5);
        $fontH = imagefontheight(5);
        $x = 18;
        for ($i = 0; $i < strlen($kode); $i++) {
            $warnaTeks = imagecolorallocate($image, random_int(200, 255), random_int(190, 230), random_int(90, 140));

            $charCanvas = imagecreatetruecolor($fontW, $fontH);
            $charBg = imagecolorallocate($charCanvas, 10, 26, 18);
            imagefill($charCanvas, 0, 0, $charBg);
            imagestring($charCanvas, 5, 0, 0, $kode[$i], $warnaTeks);

            $y = random_int(14, 28);
            imagecopyresampled(
                $image, $charCanvas,
                $x, $y, 0, 0,
                $fontW * $scale, $fontH * $scale, $fontW, $fontH
            );
            imagedestroy($charCanvas);

            $x += random_int(38, 46);
        }

        // Titik noise di depan teks.
        for ($i = 0; $i < 260; $i++) {
            $warnaTitik = imagecolorallocate($image, random_int(30, 200), random_int(30, 200), random_int(30, 200));
            imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $warnaTitik);
        }

        ob_start();
        imagepng($image);
        $data = ob_get_clean();
        imagedestroy($image);

        return response($data, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
