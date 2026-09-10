<?php

namespace App\Notifications\Channels;

use App\Models\Pengaturan;
use App\Models\PushSubscription;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Channel notifikasi custom: mengirim push notification browser (muncul
 * di notification tray OS walau tab/browser SIBERAD sedang tertutup) buat
 * SEMUA notifikasi yang sudah ada di sistem, tanpa perlu nulis ulang
 * pesannya masing-masing.
 *
 * Catatan: channel ini membutuhkan package minishlink/web-push.
 * Jika belum terinstall, channel ini akan diam-diam di-skip sehingga
 * channel database (lonceng in-app) tetap berjalan normal.
 */
class WebPushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        // Guard: jika library belum terinstall, skip tanpa error
        if (! class_exists(\Minishlink\WebPush\WebPush::class)) {
            return;
        }

        // Saklar global Admin (menu Setelan -> Notifikasi) -- kalau
        // dimatikan, tidak ada push apapun yang dikirim ke siapapun,
        // meski subscription-nya masih tersimpan di database.
        if (! Pengaturan::current()->notifikasi_push_aktif) {
            return;
        }

        // Saklar per-user: kalau user sendiri yang matiin notif push-nya
        // lewat panel Notifikasi di sidebar, skip juga.
        if (isset($notifiable->notif_push_enabled) && ! $notifiable->notif_push_enabled) {
            return;
        }

        $subscriptions = $notifiable->pushSubscriptions()->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $vapid = config('webpush.vapid');
        if (blank($vapid['publicKey']) || blank($vapid['privateKey'])) {
            return;
        }

        $payload = $this->buildPayload($notification, $notifiable);

        // Notifikasi ini dipanggil synchronous langsung dari controller (bukan
        // queue). Channel push HANYA pelengkap (browser tray notification) --
        // apapun yang gagal di sini (mis. WebPush::__construct memicu notice
        // "install GMP/BCMath" yang oleh Laravel diubah jadi ErrorException,
        // atau endpoint push error lain yang tak terduga) TIDAK BOLEH sampai
        // menggagalkan request utama (mis. kirim kendala) atau channel lain
        // (database/lonceng in-app) yang sudah lebih dulu berhasil dikirim.
        try {
            $webPush = new \Minishlink\WebPush\WebPush(['VAPID' => $vapid]);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(
                    \Minishlink\WebPush\Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
                    ]),
                    json_encode($payload)
                );
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    continue;
                }

                $statusCode = $report->getResponse()?->getStatusCode();

                if (in_array($statusCode, [404, 410], true)) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                    continue;
                }

                Log::warning('Gagal mengirim web push notification.', [
                    'endpoint' => $report->getEndpoint(),
                    'status' => $statusCode,
                    'reason' => $report->getReason(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal memproses web push notification, channel lain tetap lanjut.', [
                'notifiable_id' => $notifiable->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function buildPayload(Notification $notification, object $notifiable): array
    {
        $data = method_exists($notification, 'toDatabase')
            ? $notification->toDatabase($notifiable)
            : (method_exists($notification, 'toArray') ? $notification->toArray($notifiable) : []);

        $namaSistem = Pengaturan::current()->namaSistem();

        return [
            'title' => $data['judul'] ?? $namaSistem,
            'body' => $data['pesan'] ?? "Ada pembaruan baru di {$namaSistem}.",
            'notification_id' => $notification->id,
            'url' => url('/dashboard'),
        ];
    }
}
