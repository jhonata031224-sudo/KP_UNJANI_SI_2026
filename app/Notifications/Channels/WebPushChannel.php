<?php

namespace App\Notifications\Channels;

use App\Models\PushSubscription;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Channel notifikasi custom: mengirim push notification browser (muncul
 * di notification tray OS walau tab/browser SIBERAD sedang tertutup) buat
 * SEMUA notifikasi yang sudah ada di sistem, tanpa perlu nulis ulang
 * pesannya masing-masing.
 *
 * Cukup dipakai dengan menambahkan 'webpush' ke array via() pada class
 * notifikasi mana pun -- data pesannya diambil otomatis dari toDatabase()
 * (atau toArray() kalau toDatabase() tidak ada), persis data yang sama
 * yang sudah dipakai lonceng notifikasi in-app. Tidak perlu bikin method
 * toWebPush() terpisah di tiap class notifikasi.
 */
class WebPushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $subscriptions = $notifiable->pushSubscriptions()->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $vapid = config('webpush.vapid');
        if (blank($vapid['public_key']) || blank($vapid['private_key'])) {
            // Belum dikonfigurasi (VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY kosong)
            // -- jangan sampai bikin proses pengiriman notifikasi lain
            // (channel database) ikut gagal cuma karena ini.
            return;
        }

        $payload = $this->buildPayload($notification, $notifiable);

        $webPush = new WebPush(['VAPID' => $vapid]);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                Subscription::create([
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

            // 404/410 = subscription ini sudah tidak valid lagi (browser
            // di-uninstall, izin notifikasi dicabut, dsb) -- push service
            // sendiri yang bilang begitu, jadi aman langsung dihapus dari
            // DB supaya tidak terus dicoba kirim tiap ada notifikasi baru.
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
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Notification $notification, object $notifiable): array
    {
        $data = method_exists($notification, 'toDatabase')
            ? $notification->toDatabase($notifiable)
            : (method_exists($notification, 'toArray') ? $notification->toArray($notifiable) : []);

        return [
            'title' => config('app.name', 'SIBERAD'),
            'body' => $data['pesan'] ?? 'Ada pembaruan baru di SIBERAD.',
            'notification_id' => $notification->id,
            // Dipakai di sw.js buat nentuin halaman apa yang dibuka/difokuskan
            // saat notifikasi di-klik. Sengaja diarahkan ke dashboard umum
            // (bukan halaman detail spesifik) karena tiap role beda-beda
            // halaman detailnya -- lonceng notifikasi in-app di dashboard
            // yang jadi sumber kebenaran buat detail & tautannya.
            'url' => url('/dashboard'),
        ];
    }
}
