<?php

namespace App\Support;

/**
 * Pengecil gambar latar (hero) saat upload supaya ringan di HP.
 *
 * Foto dari kamera/HP umumnya 3000-6000px & beberapa MB. Di landing page foto
 * itu cuma jadi latar yang di-blur, jadi 1920px sudah lebih dari cukup. Tanpa
 * ini HP pengunjung harus mengunduh & men-decode file raksasa -> lag.
 *
 * Murni GD, tanpa dependency Laravel. Mengembalikan null (artinya "pakai file
 * asli apa adanya") bila: GD tidak tersedia, format tidak didukung, gambar
 * sudah cukup kecil, atau memori tidak cukup -- jadi upload tidak pernah gagal
 * gara-gara optimasi.
 */
class ImageOptimizer
{
    /**
     * @return array{data:string, ext:string}|null
     */
    public static function optimize(string $path, int $maxWidth = 1920, int $quality = 80): ?array
    {
        if (! extension_loaded('gd') || ! is_file($path)) {
            return null;
        }

        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        [$w, $h, $type] = $info;
        if ($w < 1 || $h < 1) {
            return null;
        }

        // Sudah cukup kecil (lebar & ukuran file) -> biarkan.
        if ($w <= $maxWidth && filesize($path) <= 700 * 1024) {
            return null;
        }

        $loader = match ($type) {
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG  => 'imagecreatefrompng',
            IMAGETYPE_WEBP => 'imagecreatefromwebp',
            default        => null,
        };
        if (! $loader || ! function_exists($loader)) {
            return null;
        }

        // GD butuh ~5 byte/piksel (sumber) + hasil resize. Jangan sampai
        // memicu fatal "memory exhausted" -> lebih baik pakai file asli.
        $limit = self::memoryLimitBytes();
        $newW = min($w, $maxWidth);
        $newH = (int) round($h * ($newW / $w));
        $need = ($w * $h * 5) + ($newW * $newH * 5) + (8 * 1024 * 1024);
        if ($limit > 0 && memory_get_usage(true) + $need > $limit) {
            return null;
        }

        try {
            $src = @$loader($path);
            if (! $src) {
                return null;
            }

            // Foto HP sering disimpan miring + tag EXIF orientasi.
            if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $exif = @exif_read_data($path);
                $deg = match ((int) ($exif['Orientation'] ?? 1)) {
                    3 => 180,
                    6 => -90,
                    8 => 90,
                    default => 0,
                };
                if ($deg !== 0 && ($rot = imagerotate($src, $deg, 0))) {
                    imagedestroy($src);
                    $src = $rot;
                    if ($deg !== 180) {
                        [$w, $h] = [$h, $w];
                        $newW = min($w, $maxWidth);
                        $newH = (int) round($h * ($newW / $w));
                    }
                }
            }

            $dst = imagecreatetruecolor($newW, $newH);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($src);

            ob_start();
            if (function_exists('imagewebp')) {
                imagewebp($dst, null, $quality);
                $ext = 'webp';
            } else {
                // JPEG tidak punya alpha -> ratakan ke latar putih.
                $flat = imagecreatetruecolor($newW, $newH);
                imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
                imagecopy($flat, $dst, 0, 0, 0, 0, $newW, $newH);
                imagejpeg($flat, null, $quality);
                imagedestroy($flat);
                $ext = 'jpg';
            }
            $data = (string) ob_get_clean();
            imagedestroy($dst);
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                @ob_end_clean();
            }

            return null;
        }

        // Kalau hasilnya malah lebih besar dari aslinya, pakai asli.
        if ($data === '' || strlen($data) >= filesize($path)) {
            return null;
        }

        return ['data' => $data, 'ext' => $ext];
    }

    private static function memoryLimitBytes(): int
    {
        $raw = trim((string) ini_get('memory_limit'));
        if ($raw === '' || $raw === '-1') {
            return 0; // tak terbatas
        }
        $n = (int) $raw;

        return match (strtolower(substr($raw, -1))) {
            'g' => $n * 1024 ** 3,
            'm' => $n * 1024 ** 2,
            'k' => $n * 1024,
            default => $n,
        };
    }
}
