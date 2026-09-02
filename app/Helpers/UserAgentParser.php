<?php

namespace App\Helpers;

/**
 * Parser user agent ringan tanpa library eksternal.
 * Mengembalikan info browser, OS, versi, dan nama perangkat spesifik.
 */
class UserAgentParser
{
    private string $ua;

    public function __construct(string $ua)
    {
        $this->ua = $ua;
    }

    // ------------------------------------------------------------------ //
    //  Jenis perangkat (generik)
    // ------------------------------------------------------------------ //

    public function device(): string
    {
        $ua = $this->ua;

        if (preg_match('/iPad/i', $ua))                        return 'Tablet (iPad)';
        if (preg_match('/Android.*(Tab|Tablet)/i', $ua))       return 'Tablet Android';
        if (preg_match('/iPhone/i', $ua))                      return $this->iphoneModel();
        if (preg_match('/Android/i', $ua))                     return $this->androidModel();
        if (preg_match('/Mobile/i', $ua))                      return 'HP';
        if (preg_match('/CrOS/i', $ua))                        return 'Chromebook';
        if (preg_match('/Macintosh|Mac OS X/i', $ua))          return 'Mac';
        if (preg_match('/Windows/i', $ua))                     return 'PC Windows';
        if (preg_match('/Linux/i', $ua))                       return 'PC Linux';

        return 'Perangkat Tidak Dikenal';
    }

    // ------------------------------------------------------------------ //
    //  Model HP Android spesifik
    // ------------------------------------------------------------------ //

    private function androidModel(): string
    {
        $ua = $this->ua;

        // Ambil kode model dari UA: "Android X.X; <model>)"
        if (!preg_match('/Android[\s\/][\d.]+;\s*([^;)]+)/i', $ua, $m)) {
            return 'HP Android';
        }

        $raw = trim($m[1]);

        // Jika sudah nama yang bisa dibaca (mengandung spasi & huruf)
        if (preg_match('/^[A-Za-z].*\s/u', $raw)) {
            return $raw;
        }

        // Samsung SM-xxxx
        if (preg_match('/^SM-/i', $raw)) {
            return $this->resolveSamsungModel($raw);
        }

        // Kode numerik Xiaomi/Redmi (contoh: 2312DRA50G, 22111317G, 23049RAD8G)
        if (preg_match('/^\d{4}[A-Z0-9]+$/i', $raw)) {
            return "Xiaomi/Redmi ({$raw})";
        }

        // Realme (RMX...)
        if (preg_match('/^RMX/i', $raw)) {
            return "Realme ({$raw})";
        }

        // OPPO (CPH...)
        if (preg_match('/^CPH/i', $raw)) {
            return "OPPO ({$raw})";
        }

        // vivo (Vxxxx)
        if (preg_match('/^V\d{4}/i', $raw)) {
            return "vivo ({$raw})";
        }

        // Huawei (ELS, VOG, ANE, dll)
        if (preg_match('/^(ELS|VOG|ANE|CLT|HMA|JNY|STK|NEN|LYA|BRQ)-/i', $raw)) {
            return "Huawei ({$raw})";
        }

        // Fallback: tampilkan kode apa adanya
        return "Android ({$raw})";
    }

    // ------------------------------------------------------------------ //
    //  Map kode Samsung ke nama marketing
    // ------------------------------------------------------------------ //

    private function resolveSamsungModel(string $raw): string
    {
        $code = strtoupper($raw);

        $map = [
            // Galaxy S24 series
            'SM-S928' => 'Samsung Galaxy S24 Ultra',
            'SM-S926' => 'Samsung Galaxy S24+',
            'SM-S921' => 'Samsung Galaxy S24',
            // Galaxy S23 series
            'SM-S918' => 'Samsung Galaxy S23 Ultra',
            'SM-S916' => 'Samsung Galaxy S23+',
            'SM-S911' => 'Samsung Galaxy S23',
            // Galaxy S22 series
            'SM-S908' => 'Samsung Galaxy S22 Ultra',
            'SM-S906' => 'Samsung Galaxy S22+',
            'SM-S901' => 'Samsung Galaxy S22',
            // Galaxy S21 series
            'SM-G998' => 'Samsung Galaxy S21 Ultra',
            'SM-G996' => 'Samsung Galaxy S21+',
            'SM-G991' => 'Samsung Galaxy S21',
            // Galaxy A series
            'SM-A736' => 'Samsung Galaxy A73',
            'SM-A725' => 'Samsung Galaxy A72',
            'SM-A715' => 'Samsung Galaxy A71',
            'SM-A556' => 'Samsung Galaxy A55',
            'SM-A546' => 'Samsung Galaxy A54',
            'SM-A536' => 'Samsung Galaxy A53',
            'SM-A525' => 'Samsung Galaxy A52',
            'SM-A515' => 'Samsung Galaxy A51',
            'SM-A346' => 'Samsung Galaxy A34',
            'SM-A336' => 'Samsung Galaxy A33',
            'SM-A325' => 'Samsung Galaxy A32',
            'SM-A315' => 'Samsung Galaxy A31',
            'SM-A256' => 'Samsung Galaxy A25',
            'SM-A246' => 'Samsung Galaxy A24',
            'SM-A236' => 'Samsung Galaxy A23',
            'SM-A225' => 'Samsung Galaxy A22',
            'SM-A156' => 'Samsung Galaxy A15',
            'SM-A146' => 'Samsung Galaxy A14',
            'SM-A136' => 'Samsung Galaxy A13',
            'SM-A057' => 'Samsung Galaxy A05s',
            'SM-A045' => 'Samsung Galaxy A04',
            'SM-A037' => 'Samsung Galaxy A03s',
            // Galaxy Note
            'SM-N986' => 'Samsung Galaxy Note 20 Ultra',
            'SM-N981' => 'Samsung Galaxy Note 20',
            'SM-N976' => 'Samsung Galaxy Note 10+',
            'SM-N970' => 'Samsung Galaxy Note 10',
            // Galaxy Z series
            'SM-F946' => 'Samsung Galaxy Z Fold5',
            'SM-F936' => 'Samsung Galaxy Z Fold4',
            'SM-F926' => 'Samsung Galaxy Z Fold3',
            'SM-F731' => 'Samsung Galaxy Z Flip5',
            'SM-F721' => 'Samsung Galaxy Z Flip4',
            'SM-F711' => 'Samsung Galaxy Z Flip3',
            // Galaxy M series
            'SM-M546' => 'Samsung Galaxy M54',
            'SM-M536' => 'Samsung Galaxy M53',
            'SM-M346' => 'Samsung Galaxy M34',
            'SM-M336' => 'Samsung Galaxy M33',
        ];

        foreach ($map as $prefix => $name) {
            if (str_starts_with($code, $prefix)) {
                return $name;
            }
        }

        // Tidak ada di map — tampilkan kode apa adanya
        return "Samsung ({$raw})";
    }

    // ------------------------------------------------------------------ //
    //  Model iPhone dari versi iOS
    // ------------------------------------------------------------------ //

    private function iphoneModel(): string
    {
        if (!preg_match('/iPhone OS ([\d_]+)/i', $this->ua, $m)) {
            return 'iPhone';
        }

        $ver = str_replace('_', '.', $m[1]);
        $major = (int) explode('.', $ver)[0];

        // Perkiraan iPhone dari versi iOS (tidak 100% akurat karena user bisa update)
        $guess = match (true) {
            $major >= 18 => 'iPhone 16 / 17',
            $major === 17 => 'iPhone 15 / 16',
            $major === 16 => 'iPhone 14 / 15',
            $major === 15 => 'iPhone 13 / 14',
            $major === 14 => 'iPhone 12 / 13',
            $major === 13 => 'iPhone 11 / 12',
            default       => 'iPhone',
        };

        return $guess;
    }

    // ------------------------------------------------------------------ //
    //  Nama & versi browser
    // ------------------------------------------------------------------ //

    public function browser(): string
    {
        $ua = $this->ua;

        if (preg_match('/Edg\/([\d.]+)/i', $ua, $m))
            return 'Edge ' . $this->major($m[1]);

        if (preg_match('/OPR\/([\d.]+)|Opera\/([\d.]+)/i', $ua, $m))
            return 'Opera ' . $this->major($m[1] ?: $m[2]);

        if (preg_match('/SamsungBrowser\/([\d.]+)/i', $ua, $m))
            return 'Samsung Browser ' . $this->major($m[1]);

        if (preg_match('/UCBrowser\/([\d.]+)/i', $ua, $m))
            return 'UC Browser ' . $this->major($m[1]);

        if (preg_match('/YaBrowser\/([\d.]+)/i', $ua, $m))
            return 'Yandex Browser ' . $this->major($m[1]);

        if (preg_match('/Brave\/([\d.]+)/i', $ua, $m))
            return 'Brave ' . $this->major($m[1]);

        if (preg_match('/Firefox\/([\d.]+)/i', $ua, $m))
            return 'Firefox ' . $this->major($m[1]);

        if (preg_match('/CriOS\/([\d.]+)/i', $ua, $m))
            return 'Chrome (iOS) ' . $this->major($m[1]);

        if (preg_match('/FxiOS\/([\d.]+)/i', $ua, $m))
            return 'Firefox (iOS) ' . $this->major($m[1]);

        if (preg_match('/Chrome\/([\d.]+)/i', $ua, $m))
            return 'Chrome ' . $this->major($m[1]);

        if (preg_match('/Version\/([\d.]+).*Safari/i', $ua, $m))
            return 'Safari ' . $this->major($m[1]);

        if (preg_match('/Safari/i', $ua))
            return 'Safari';

        if (preg_match('/MSIE ([\d.]+)|Trident.*rv:([\d.]+)/i', $ua, $m))
            return 'Internet Explorer ' . $this->major($m[1] ?: $m[2]);

        return 'Browser Tidak Dikenal';
    }

    // ------------------------------------------------------------------ //
    //  Nama & versi OS
    // ------------------------------------------------------------------ //

    public function os(): string
    {
        $ua = $this->ua;

        // iOS
        if (preg_match('/iPhone OS ([\d_]+)/i', $ua, $m))
            return 'iOS ' . str_replace('_', '.', $m[1]);

        // iPadOS
        if (preg_match('/iPad.*OS ([\d_]+)/i', $ua, $m))
            return 'iPadOS ' . str_replace('_', '.', $m[1]);

        // Android — ambil versi penuh
        if (preg_match('/Android ([\d.]+)/i', $ua, $m))
            return 'Android ' . $m[1];

        // Windows
        if (preg_match('/Windows NT ([\d.]+)/i', $ua, $m)) {
            return match ($m[1]) {
                '10.0' => 'Windows 10/11',
                '6.3'  => 'Windows 8.1',
                '6.2'  => 'Windows 8',
                '6.1'  => 'Windows 7',
                '6.0'  => 'Windows Vista',
                '5.1', '5.2' => 'Windows XP',
                default => 'Windows NT ' . $m[1],
            };
        }

        // macOS
        if (preg_match('/Mac OS X ([\d_.]+)/i', $ua, $m))
            return 'macOS ' . str_replace('_', '.', $m[1]);

        // ChromeOS
        if (preg_match('/CrOS/i', $ua))
            return 'ChromeOS';

        // Linux
        if (preg_match('/Linux/i', $ua))
            return 'Linux';

        return 'OS Tidak Dikenal';
    }

    // ------------------------------------------------------------------ //
    //  Emoji icon
    // ------------------------------------------------------------------ //

    public function icon(): string
    {
        $ua = $this->ua;

        if (preg_match('/iPad/i', $ua))                        return '📲';
        if (preg_match('/Android.*(Tab|Tablet)/i', $ua))       return '📲';
        if (preg_match('/iPhone/i', $ua))                      return '📱';
        if (preg_match('/Android/i', $ua))                     return '📱';
        if (preg_match('/Mobile/i', $ua))                      return '📱';
        if (preg_match('/Macintosh|Mac OS X/i', $ua))          return '💻';
        if (preg_match('/CrOS/i', $ua))                        return '💻';
        if (preg_match('/Windows/i', $ua))                     return '🖥️';
        if (preg_match('/Linux/i', $ua))                       return '🖥️';

        return '❓';
    }

    // ------------------------------------------------------------------ //
    //  Helper
    // ------------------------------------------------------------------ //

    private function major(string $ver): string
    {
        return explode('.', $ver)[0];
    }
}
