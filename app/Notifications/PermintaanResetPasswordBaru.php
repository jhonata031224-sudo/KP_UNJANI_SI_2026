<?php

namespace App\Notifications;

use App\Models\PermintaanResetPassword;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke seluruh Admin setiap kali ada permintaan ganti password baru
 * dari Pimpinan/Satuan yang perlu ditinjau.
 */
class PermintaanResetPasswordBaru extends Notification
{
    public function __construct(public PermintaanResetPassword $permintaan)
    {
    }

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\WebPushChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        $nama = $this->permintaan->user->name ?? 'Pengguna';
        $satuan = $this->permintaan->user->satuan->nama ?? '-';

        return [
            'permintaan_reset_password_id' => $this->permintaan->id,
            'pesan' => "Permintaan ganti password dari {$nama} ({$satuan}) perlu ditinjau.",
        ];
    }
}
