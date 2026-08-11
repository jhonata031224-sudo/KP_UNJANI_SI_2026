<?php

namespace App\Notifications;

use App\Models\PermintaanLaporan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PermintaanLaporanBaru extends Notification
{
    use Queueable;

    public function __construct(public PermintaanLaporan $permintaan)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'pesan' => 'Ada permintaan laporan baru: '.$this->permintaan->perihal.'. Deadline '.$this->permintaan->deadline_at->translatedFormat('d M Y H:i').'.',
            'jenis' => 'permintaan_laporan',
            'permintaan_laporan_id' => $this->permintaan->id,
            'perihal' => $this->permintaan->perihal,
            'deadline_at' => $this->permintaan->deadline_at->toIso8601String(),
        ];
    }
}
