<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Simpan/perbarui subscription push milik device/browser yang sedang
     * dipakai user login. Dipanggil JS setelah user klik "Aktifkan
     * Notifikasi" dan browser berhasil bikin push subscription baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'endpoint' => $validated['endpoint'],
            ],
            [
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                // WAJIB "aes128gcm" (RFC 8291), BUKAN "aesgcm" (skema draft lama).
                // Browser modern (Chrome/Firefox/Edge/Safari) HANYA mendekripsi
                // payload push yang dienkripsi dengan aes128gcm -- lihat MDN
                // PushManager.supportedContentEncodings: "User agents must
                // support the aes128gcm content coding defined in RFC 8291".
                // Sebelumnya field ini di-hardcode "aesgcm" (skema lama), jadi
                // WebPushChannel berhasil ngirim ke push service (respons tetap
                // sukses, tidak ada error di log), tapi browser penerima gagal
                // mendekripsi payload-nya secara DIAM-DIAM -- notifikasi push
                // tidak pernah muncul di tray OS walau semuanya "terlihat"
                // berhasil di sisi server. Ini root cause laporan "notif tidak
                // muncul di penerima saat di luar sistem".
                'content_encoding' => 'aes128gcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Hapus subscription (dipanggil saat user klik "Matikan Notifikasi",
     * atau otomatis oleh browser saat permission dicabut).
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $request->user()->pushSubscriptions()
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Halaman diagnosa push untuk user yang sedang login. Buka
     * /push/diagnosa di HP yang bermasalah: hasilnya JSON berisi status tiap
     * syarat push (VAPID, saklar, jumlah device terdaftar). Tambahkan
     * ?kirim=1 untuk mengirim notif tes langsung ke device user ini dan
     * melihat balasan asli dari push service (FCM/Mozilla/Apple).
     * Tidak membocorkan kunci apapun -- hanya true/false dan potongan host.
     */
    public function diagnosa(Request $request): JsonResponse
    {
        $user = $request->user();
        $vapid = config('webpush.vapid');
        $subs = $user->pushSubscriptions()->get();

        $hasil = [
            'library_webpush_terpasang' => class_exists(\Minishlink\WebPush\WebPush::class),
            'ext_bcmath' => extension_loaded('bcmath'),
            'ext_gmp' => extension_loaded('gmp'),
            'vapid_public_terisi' => filled($vapid['publicKey'] ?? null),
            'vapid_private_terisi' => filled($vapid['privateKey'] ?? null),
            'saklar_global_push_aktif' => (bool) Pengaturan::current()->notifikasi_push_aktif,
            'saklar_user_push_aktif' => (bool) $user->notif_push_enabled,
            'jumlah_device_terdaftar' => $subs->count(),
            'device' => $subs->map(fn ($s) => [
                'push_service' => parse_url($s->endpoint, PHP_URL_HOST),
                'encoding' => $s->content_encoding,
                'terdaftar' => optional($s->updated_at)->toDateTimeString(),
                'user_agent' => substr((string) $s->user_agent, 0, 80),
            ])->values(),
        ];

        if ($request->boolean('kirim')) {
            $hasil['tes_kirim'] = [];
            try {
                if (blank($vapid['publicKey'] ?? null) || blank($vapid['privateKey'] ?? null)) {
                    throw new \RuntimeException('VAPID key belum diisi di environment server.');
                }
                if ($subs->isEmpty()) {
                    throw new \RuntimeException('Belum ada device terdaftar. Buka app di HP, izinkan notifikasi, lalu coba lagi.');
                }

                $webPush = new \Minishlink\WebPush\WebPush(['VAPID' => $vapid], ['TTL' => 600, 'urgency' => 'high']);
                $payload = json_encode([
                    'title' => 'Tes Notifikasi',
                    'body' => 'Kalau ini muncul di HP (termasuk saat terkunci), push berjalan normal.',
                    'notification_id' => 'tes-'.now()->timestamp,
                    'badge_count' => 1,
                    'url' => url('/dashboard'),
                ]);
                foreach ($subs as $s) {
                    $webPush->queueNotification(\Minishlink\WebPush\Subscription::create([
                        'endpoint' => $s->endpoint,
                        'publicKey' => $s->public_key,
                        'authToken' => $s->auth_token,
                        'contentEncoding' => $s->content_encoding ?: 'aes128gcm',
                    ]), $payload);
                }
                foreach ($webPush->flush() as $report) {
                    $hasil['tes_kirim'][] = [
                        'push_service' => parse_url($report->getEndpoint(), PHP_URL_HOST),
                        'berhasil' => $report->isSuccess(),
                        'status_http' => $report->getResponse()?->getStatusCode(),
                        'alasan' => $report->isSuccess() ? null : $report->getReason(),
                    ];
                }
            } catch (\Throwable $e) {
                $hasil['tes_kirim'][] = ['berhasil' => false, 'error' => $e->getMessage()];
            }
        }

        return response()->json($hasil, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
