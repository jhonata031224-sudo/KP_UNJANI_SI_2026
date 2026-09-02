<?php

namespace App\Helpers;

/**
 * Parser user agent ringan tanpa library eksternal.
 * Mengembalikan info browser, OS, dan jenis perangkat
 * yang lebih mudah dibaca manusia dibanding raw UA string.
 */
class UserAgentParser
{
    private string $ua;

    public function __construct(string $ua)
    {
        $this->ua = $ua;
    }

    // ------------------------------------------------------------------ //
    //  Jenis perangkat
    // ------------------------------------------------------------------ //

    public function device(): string
    {
        $ua = $this->ua;

        if (preg_match('/iPad/i', $ua))                          return 'Tablet';
        if (preg_match('/Android.*(?:Tab|Tablet)/i', $ua))       return 'Tablet';
        if (preg_match('/iPhone/i', $ua))                        return 'HP iPhone';
        if (preg_match('/Android/i', $ua))                       return 'HP Android';
        if (preg_match('/Mobile/i', $ua))                        return 'HP';
        if (preg_match('/CrOS/i', $ua))                          return 'Chromebook';
        if (preg_match('/Macintosh|Mac OS X/i', $ua))            return 'Mac';
        if (preg_match('/Windows/i', $ua))                       return 'PC Windows';
        if (preg_match('/Linux/i', $ua))                         return 'PC Linux';

        return 'Perangkat Tidak Dikenal';
    }

    // ------------------------------------------------------------------ //
    //  Nama & versi browser
    // ------------------------------------------------------------------ //

    public function browser(): string
    {
        $ua = $this->ua;

        // Urutan deteksi penting — lebih spesifik dulu
        if (preg_match('/Edg\/([\d.]+)/i', $ua, $m))
            return 'Edge ' . $this->majorVersion($m[1]);

        if (preg_match('/OPR\/([\d.]+)|Opera\/([\d.]+)/i', $ua, $m))
            return 'Opera ' . $this->majorVersion($m[1] ?: $m[2]);

        if (preg_match('/SamsungBrowser\/([\d.]+)/i', $ua, $m))
            return 'Samsung Browser ' . $this->majorVersion($m[1]);

        if (preg_match('/UCBrowser\/([\d.]+)/i', $ua, $m))
            return 'UC Browser ' . $this->majorVersion($m[1]);

        if (preg_match('/YaBrowser\/([\d.]+)/i', $ua, $m))
            return 'Yandex Browser ' . $this->majorVersion($m[1]);

        if (preg_match('/Brave\/([\d.]+)/i', $ua, $m))
            return 'Brave ' . $this->majorVersion($m[1]);

        if (preg_match('/Firefox\/([\d.]+)/i', $ua, $m))
            return 'Firefox ' . $this->majorVersion($m[1]);

        if (preg_match('/CriOS\/([\d.]+)/i', $ua, $m))
            return 'Chrome (iOS) ' . $this->majorVersion($m[1]);

        if (preg_match('/FxiOS\/([\d.]+)/i', $ua, $m))
            return 'Firefox (iOS) ' . $this->majorVersion($m[1]);

        // Chrome harus setelah Edge & Opera karena UA mereka juga mengandung "Chrome"
        if (preg_match('/Chrome\/([\d.]+)/i', $ua, $m))
            return 'Chrome ' . $this->majorVersion($m[1]);

        if (preg_match('/Version\/([\d.]+).*Safari/i', $ua, $m))
            return 'Safari ' . $this->majorVersion($m[1]);

        if (preg_match('/Safari\/([\d.]+)/i', $ua, $m))
            return 'Safari';

        if (preg_match('/MSIE ([\d.]+)|Trident.*rv:([\d.]+)/i', $ua, $m))
            return 'Internet Explorer ' . $this->majorVersion($m[1] ?: $m[2]);

        return 'Browser Tidak Dikenal';
    }

    // ------------------------------------------------------------------ //
    //  Nama OS
    // ------------------------------------------------------------------ //

    public function os(): string
    {
        $ua = $this->ua;

        // iOS
        if (preg_match('/iPhone OS ([\d_]+)/i', $ua, $m))
            return 'iOS ' . str_replace('_', '.', $m[1]);

        if (preg_match('/iPad.*OS ([\d_]+)/i', $ua, $m))
            return 'iPadOS ' . str_replace('_', '.', $m[1]);

        // Android
        if (preg_match('/Android ([\d.]+)/i', $ua, $m))
            return 'Android ' . $m[1];

        // Windows — urutan penting (Windows 10/11 pakai NT 10.0)
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
        if (preg_match('/Mac OS X ([\d_.]+)/i', $ua, $m)) {
            $ver = str_replace('_', '.', $m[1]);
            return 'macOS ' . $ver;
        }

        // ChromeOS
        if (preg_match('/CrOS/i', $ua))
            return 'ChromeOS';

        // Linux
        if (preg_match('/Linux/i', $ua))
            return 'Linux';

        return 'OS Tidak Dikenal';
    }

    // ------------------------------------------------------------------ //
    //  Icon emoji berdasarkan perangkat
    // ------------------------------------------------------------------ //

    public function icon(): string
    {
        return match (true) {
            str_contains($this->device(), 'iPhone') => '📱',
            str_contains($this->device(), 'Android') => '📱',
            str_contains($this->device(), 'Tablet') => '📲',
            str_contains($this->device(), 'Mac') => '💻',
            str_contains($this->device(), 'Chromebook') => '💻',
            str_contains($this->device(), 'PC') => '🖥️',
            default => '❓',
        };
    }

    // ------------------------------------------------------------------ //
    //  Output ringkas siap tampil
    // ------------------------------------------------------------------ //

    /**
     * Contoh output: "🖥️ PC Windows · Windows 10/11 · Chrome 126"
     */
    public function ringkas(): string
    {
        return $this->icon() . ' ' . $this->device() . ' · ' . $this->os() . ' · ' . $this->browser();
    }

    // ------------------------------------------------------------------ //
    //  Helper
    // ------------------------------------------------------------------ //

    private function majorVersion(string $ver): string
    {
        return explode('.', $ver)[0];
    }
}
