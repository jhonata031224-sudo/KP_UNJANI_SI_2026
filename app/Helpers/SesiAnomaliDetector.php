<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

/**
 * Deteksi sinyal kejanggalan pada daftar sesi aktif -- sengaja dibuat
 * murni dari data yang SUDAH ada di setiap poll "Pengguna Aktif" (tidak
 * perlu tabel/migration baru, tidak perlu histori), supaya anomali
 * langsung kelihatan real-time begitu poll berikutnya jalan.
 *
 * Sinyal utama: SATU akun aktif login dari >1 titik lokasi yang jauh
 * secara bersamaan. Ini indikator kuat kredensial dipakai lebih dari
 * satu orang/perangkat sekaligus (akun dibagi, atau dicuri).
 *
 * Kenapa bukan sekadar "IP beda" atau "device beda": banyak pengguna sah
 * pindah dari HP ke laptop, atau IP-nya berubah karena provider seluler
 * -- itu normal. Yang benar-benar janggal adalah JARAK GEOGRAFIS yang
 * tidak masuk akal untuk dua sesi yang sama-sama aktif di waktu
 * bersamaan (mis. Jakarta & Surabaya menyala berbarengan).
 */
class SesiAnomaliDetector
{
    /**
     * Ambang jarak (km) dianggap "beda lokasi" -- dibedakan berdasarkan
     * sumber geo karena akurasinya jauh berbeda:
     *   - GPS: presisi (biasanya < 1 km meleset), jadi ambang bisa ketat.
     *   - IP:  geolocation IP di Indonesia sering meleset sampai puluhan/
     *          ratusan km (nembak ke kota hub ISP terdekat), jadi ambang
     *          harus longgar supaya tidak banjir alarm palsu.
     */
    private const AMBANG_KM_GPS_GPS = 20.0;
    private const AMBANG_KM_LAINNYA = 150.0;

    /**
     * Tandai setiap baris sesi dengan properti:
     *   - anomali_multi_lokasi (bool)
     *   - anomali_jarak_km     (float|null)  jarak terjauh ke sesi lain milik user yang sama
     *   - anomali_sesi_lain    (string|null) label singkat lokasi sesi pembanding, untuk tooltip
     *
     * @param  iterable<int, object>  $sesiAktif  baris stdClass dari DB::table('sessions')->get(),
     *                                            WAJIB menyertakan kolom: id, user_id, geo_lat, geo_lon, geo_sumber
     * @return Collection<int, object>
     */
    public static function tandai(iterable $sesiAktif): Collection
    {
        $items = collect($sesiAktif)->values();

        foreach ($items as $item) {
            $item->anomali_multi_lokasi = false;
            $item->anomali_jarak_km = null;
            $item->anomali_sesi_lain = null;
        }

        $items
            ->filter(fn ($s) => $s->user_id !== null)
            ->groupBy('user_id')
            ->each(function (Collection $grup) {
                if ($grup->count() < 2) {
                    return; // cuma satu sesi aktif -- tidak ada yang bisa dibandingkan
                }

                $grupIndexed = $grup->values();

                for ($i = 0; $i < $grupIndexed->count(); $i++) {
                    for ($j = $i + 1; $j < $grupIndexed->count(); $j++) {
                        self::bandingkanDuaSesi($grupIndexed[$i], $grupIndexed[$j]);
                    }
                }
            });

        return $items;
    }

    private static function bandingkanDuaSesi(object $a, object $b): void
    {
        $latA = self::toFloatOrNull($a->geo_lat ?? null);
        $lonA = self::toFloatOrNull($a->geo_lon ?? null);
        $latB = self::toFloatOrNull($b->geo_lat ?? null);
        $lonB = self::toFloatOrNull($b->geo_lon ?? null);

        if ($latA === null || $lonA === null || $latB === null || $lonB === null) {
            return; // salah satu/keduanya belum punya titik lokasi -- tidak bisa dibandingkan
        }

        $keduanyaGps = ($a->geo_sumber ?? null) === 'gps' && ($b->geo_sumber ?? null) === 'gps';
        $ambangKm = $keduanyaGps ? self::AMBANG_KM_GPS_GPS : self::AMBANG_KM_LAINNYA;

        $jarakKm = self::jarakKm($latA, $lonA, $latB, $lonB);

        if ($jarakKm <= $ambangKm) {
            return;
        }

        // Tandai kedua sisi, simpan jarak TERJAUH kalau user ini punya >2 sesi
        // (mis. 3 lokasi berbeda, ambil jarak paling ekstrem untuk ditampilkan).
        if ($a->anomali_jarak_km === null || $jarakKm > $a->anomali_jarak_km) {
            $a->anomali_jarak_km = $jarakKm;
            $a->anomali_sesi_lain = self::labelLokasi($b);
        }
        if ($b->anomali_jarak_km === null || $jarakKm > $b->anomali_jarak_km) {
            $b->anomali_jarak_km = $jarakKm;
            $b->anomali_sesi_lain = self::labelLokasi($a);
        }
        $a->anomali_multi_lokasi = true;
        $b->anomali_multi_lokasi = true;
    }

    private static function labelLokasi(object $s): string
    {
        $kota = $s->geo_kota ?? null;

        return $kota ?: 'lokasi lain';
    }

    private static function toFloatOrNull(mixed $v): ?float
    {
        return is_numeric($v) ? (float) $v : null;
    }

    /** Jarak antar 2 koordinat (rumus Haversine), hasil dalam kilometer. */
    private static function jarakKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0; // radius bumi rata-rata, km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $r * $c;
    }
}
