<?php

namespace App\Notifications\Channels;

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

        $subscriptions = $notifiable->pushSubscriptions()->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $vapid = config('webpush.vapid');
        if (blank($vapid['public_key']) || blank($vapid['private_key'])) {
            return;
        }

        $payload = $this->buildPayload($notification, $notifiable);

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
    }

    /** @return array<string, mixed> */
    private function buildPayload(Notification $notification, object $notifiable): array
    {
        $data = method_exists($notification, 'toDatabase')
            ? $notification->toDatabase($notifiable)
            : (method_exists($notification, 'toArray') ? $notification->toArray($notifiable) : []);

        return [
            'title' => config('app.name', 'SIBERAD'),
            'body' => $data['pesan'] ?? 'Ada pembaruan baru di SIBERAD.',
            'notification_id' => $notification->id,
            'url' => url('/dashboard'),
        ];
    }
}
