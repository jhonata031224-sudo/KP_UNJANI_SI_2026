<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class CaptchaController extends Controller
{
    /**
     * Buat gambar captcha (angka + huruf kecil + huruf kapital, dengan noise
     * garis/titik) pakai GD bawaan PHP — tanpa font TTF eksternal, supaya
     * tidak butuh file tambahan yang bisa hilang saat deploy.
     *
     * Huruf kapital dan huruf kecil sengaja dibuat berbeda secara visual:
     * kapital lebih besar/tebal dan berada sedikit lebih tinggi, sedangkan
     * huruf kecil lebih kecil/ringan dan berada sedikit lebih rendah. Ini
     * membantu pengguna membedakan case tanpa mengubah kode captcha maupun
     * mekanisme validasinya.
     *
     * Jika GD tidak tersedia pada environment deployment, gunakan SVG sebagai
     * fallback agar endpoint captcha tetap dapat dirender tanpa mengubah alur
     * validasi captcha.
     */
    public function image(): Response
    {
        $width = 260;
        $height = 90;

        // Karakter membingungkan (0/O, 1/l/I, V/v yang mirip U/u di font ini) dibuang supaya tetap terbaca.
        $karakter = 'ABCDEFGHJKLMNPQRSTWXYZabcdefghijkmnpqrstuwxyz23456789';
        $kode = '';
        for ($i = 0; $i < 5; $i++) {
            $kode .= $karakter[random_int(0, strlen($karakter) - 1)];
        }
        session(['captcha_code' => $kode]);

        // Railway/container tertentu tidak menyediakan ekstensi GD. Jangan
        // biarkan endpoint captcha menjadi 500; fallback ini hanya dipakai
        // bila GD memang tidak tersedia, sehingga hasil GD yang lama tetap
        // digunakan pada environment yang memilikinya.
        if (! function_exists('imagecreatetruecolor')) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="260" height="90" viewBox="0 0 260 90">';
            $svg .= '<rect width="260" height="90" rx="8" fill="#0a1a12"/>';

            // Noise garis di belakang teks.
            for ($i = 0; $i < 9; $i++) {
                $x1 = random_int(0, $width);
                $y1 = random_int(0, $height);
                $x2 = random_int(0, $width);
                $y2 = random_int(0, $height);
                $r = random_int(40, 90);
                $g = random_int(90, 140);
                $b = random_int(60, 100);
                $svg .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="rgb('.$r.','.$g.','.$b.')" stroke-width="1"/>';
            }

            // Kapital dibuat lebih besar dan tebal; lowercase sedikit lebih
            // kecil/ringan serta lebih rendah. Case tetap merupakan bagian dari
            // kode yang divalidasi server, hanya presentasinya yang diperjelas.
            $x = 18;
            for ($i = 0; $i < strlen($kode); $i++) {
                $char = $kode[$i];
                $isUpper = ctype_upper($char);
                $fontSize = $isUpper ? 38 : 31;
                $fontWeight = $isUpper ? 800 : 500;
                $y = $isUpper ? 55 : 61;
                $r = random_int(200, 255);
                $g = random_int(190, 230);
                $b = random_int(90, 140);
                $rotate = random_int(-7, 7);

                $svg .= '<text x="'.$x.'" y="'.$y.'" fill="rgb('.$r.','.$g.','.$b.')" font-family="DejaVu Sans, Arial, sans-serif" font-size="'.$fontSize.'" font-weight="'.$fontWeight.'" transform="rotate('.$rotate.' '.$x.' '.$y.')">'.$char.'</text>';
                $x += random_int(38, 46);
            }

            // Noise titik di depan teks.
            for ($i = 0; $i < 260; $i++) {
                $x = random_int(0, $width - 1);
                $y = random_int(0, $height - 1);
                $r = random_int(30, 200);
                $g = random_int(30, 200);
                $b = random_int(30, 200);
                $svg .= '<circle cx="'.$x.'" cy="'.$y.'" r="0.8" fill="rgb('.$r.','.$g.','.$b.')"/>';
            }

            $svg .= '</svg>';

            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        }

        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 10, 26, 18);
        imagefill($image, 0, 0, $bg);

        // Garis noise di belakang teks.
        for ($i = 0; $i < 9; $i++) {
            $warnaGaris = imagecolorallocate($image, random_int(40, 90), random_int(90, 140), random_int(60, 100));
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $warnaGaris);
        }

        // Tiap karakter digambar di kanvas kecil lalu diperbesar ke kanvas
        // utama. Kapital dan lowercase memakai ukuran/posisi berbeda agar
        // perbedaan case terlihat jelas walaupun tanpa font TTF eksternal.
        $fontW = imagefontwidth(5);
        $fontH = imagefontheight(5);
        $x = 18;
        for ($i = 0; $i < strlen($kode); $i++) {
            $char = $kode[$i];
            $isUpper = ctype_upper($char);
            $warnaTeks = imagecolorallocate($image, random_int(200, 255), random_int(190, 230), random_int(90, 140));

            $charCanvas = imagecreatetruecolor($fontW, $fontH);
            $charBg = imagecolorallocate($charCanvas, 10, 26, 18);
            imagefill($charCanvas, 0, 0, $charBg);

            // Kapital dibuat tebal dengan double-draw yang sangat kecil,
            // lowercase cukup satu draw agar bentuknya tidak menyatu.
            imagestring($charCanvas, 5, 0, 0, $char, $warnaTeks);
            if ($isUpper) {
                imagestring($charCanvas, 5, 1, 0, $char, $warnaTeks);
            }

            $scale = $isUpper ? 3.45 : 2.75;
            $scaledW = (int) round($fontW * $scale);
            $scaledH = (int) round($fontH * $scale);
            $y = $isUpper ? random_int(10, 16) : random_int(23, 30);

            imagecopyresampled(
                $image, $charCanvas,
                $x, $y, 0, 0,
                $scaledW, $scaledH, $fontW, $fontH
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
