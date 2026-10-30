<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Response;

class LandingAccessCaptchaController
{
    public function image(): Response
    {
        // Mode compact (?c=1) dipakai di HP. Kanvas dibuat mengikuti rasio kotak gambar
        // di layar (?r=lebar/tinggi, dikirim JS) supaya kode captcha memenuhi kotak:
        // tidak ada sisi kosong dan karakter tidak terpotong. Tanpa ?r= dipakai rasio bawaan.
        $compact = request()->query('c') === '1';
        $height = $compact ? 72 : 90;
        if ($compact) {
            $ratio = (float) request()->query('r', 0);
            if ($ratio < 1.4 || $ratio > 3.4) {
                $ratio = 2.1;
            }
            $width = (int) round($height * $ratio);
        } else {
            $width = 260;
        }
        $margin = 8;
        $advance = ($width - 2 * $margin) / 5;
        $startX = 18;
        $stepMin = 40;
        $stepMax = 47;
        $noiseLines = $compact ? 6 : 12;
        $noiseDots = $compact ? (int) round($width * $height / 90) : 260;
        $alphabet = 'ABCDEFGHJKLMNPQRSTWXYZabcdefghijkmnpqrstuwxyz23456789';
        $code = '';

        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        session(['captcha_code' => $code]);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">';
        $svg .= '<rect width="100%" height="100%" rx="10" fill="#071b12"/>';

        for ($i = 0; $i < $noiseLines; $i++) {
            $x1 = random_int(0, $width); $y1 = random_int(0, $height);
            $x2 = random_int(0, $width); $y2 = random_int(0, $height);
            $r = random_int(35, 90); $g = random_int(85, 150); $b = random_int(55, 105);
            $svg .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="rgb('.$r.','.$g.','.$b.')" stroke-width="1"/>';
        }

        $x = $startX;
        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            $upper = ctype_upper($char);
            if ($compact) {
                // Tiap karakter dipusatkan di slotnya masing-masing (text-anchor middle),
                // ukuran font mengikuti lebar slot supaya kelima karakter muat penuh.
                $big = min(36, $advance * 1.1);
                $size = $upper ? $big : $big * 0.86;
                if (in_array($char, ['W', 'M', 'w', 'm'], true)) {
                    $size *= 0.88; // huruf lebar dikecilkan sedikit supaya tidak menimpa tetangganya
                }
                $y = $upper ? 50 : 52;
                $cx = $margin + ($i + 0.5) * $advance + random_int(-1, 1);
                $anchor = ' text-anchor="middle"';
                $posX = round($cx, 1);
            } else {
                $size = $upper ? 38 : 31;
                $y = $upper ? 55 : 62;
                $anchor = '';
                $posX = $x;
            }
            $weight = $upper ? 800 : 600;
            $r = random_int(205, 255); $g = random_int(190, 232); $b = random_int(90, 145);
            $rotate = random_int(-7, 7);
            $svg .= '<text x="'.$posX.'" y="'.$y.'"'.$anchor.' fill="rgb('.$r.','.$g.','.$b.')" font-family="Arial, sans-serif" font-size="'.round($size, 1).'" font-weight="'.$weight.'" transform="rotate('.$rotate.' '.$posX.' '.$y.')">'.htmlspecialchars($char, ENT_QUOTES, 'UTF-8').'</text>';
            $x += random_int($stepMin, $stepMax);
        }

        for ($i = 0; $i < $noiseDots; $i++) {
            $x = random_int(0, $width - 1); $y = random_int(0, $height - 1);
            $r = random_int(30, 205); $g = random_int(30, 205); $b = random_int(30, 205);
            $svg .= '<circle cx="'.$x.'" cy="'.$y.'" r="0.8" fill="rgb('.$r.','.$g.','.$b.')"/>';
        }

        $svg .= '</svg>';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
