<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Response;

class LandingAccessCaptchaController
{
    public function image(): Response
    {
        $width = 260;
        $height = 90;
        $alphabet = 'ABCDEFGHJKLMNPQRSTWXYZabcdefghijkmnpqrstuwxyz23456789';
        $code = '';

        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        session(['captcha_code' => $code]);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">';
        $svg .= '<rect width="100%" height="100%" rx="10" fill="#071b12"/>';

        for ($i = 0; $i < 12; $i++) {
            $x1 = random_int(0, $width); $y1 = random_int(0, $height);
            $x2 = random_int(0, $width); $y2 = random_int(0, $height);
            $r = random_int(35, 90); $g = random_int(85, 150); $b = random_int(55, 105);
            $svg .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="rgb('.$r.','.$g.','.$b.')" stroke-width="1"/>';
        }

        $x = 18;
        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            $upper = ctype_upper($char);
            $size = $upper ? 38 : 31;
            $weight = $upper ? 800 : 600;
            $y = $upper ? 55 : 62;
            $r = random_int(205, 255); $g = random_int(190, 232); $b = random_int(90, 145);
            $rotate = random_int(-7, 7);
            $svg .= '<text x="'.$x.'" y="'.$y.'" fill="rgb('.$r.','.$g.','.$b.')" font-family="Arial, sans-serif" font-size="'.$size.'" font-weight="'.$weight.'" transform="rotate('.$rotate.' '.$x.' '.$y.')">'.htmlspecialchars($char, ENT_QUOTES, 'UTF-8').'</text>';
            $x += random_int(40, 47);
        }

        for ($i = 0; $i < 260; $i++) {
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
