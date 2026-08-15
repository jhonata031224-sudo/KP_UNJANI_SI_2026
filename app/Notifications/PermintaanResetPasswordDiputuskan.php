<?php

namespace App\Notifications;

use App\Models\PermintaanResetPassword;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke pengaju setiap kali Admin memutuskan permintaan ganti
 * password-nya: Disetujui atau Ditolak.
 */
class PermintaanResetPasswordDiputuskan extends Notification
{
    public function __construct(public PermintaanResetPassword $permintaan)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $pesan = $this->permintaan->status === PermintaanResetPassword::STATUS_DISETUJUI
            ? 'Permintaan ganti password Anda telah disetujui Admin. Password baru Anda sudah aktif.'
            : 'Permintaan ganti password Anda ditolak Admin.';

        return [
            'permintaan_reset_password_id' => $this->permintaan->id,
            'status' => $this->permintaan->status,
            'pesan' => $pesan,
        ];
    }
}
