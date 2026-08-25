<?php

namespace App\Notifications;

use App\Models\LaporanKendala;
use Illuminate\Notifications\Notification;

class LaporanKendalaBaruDiterima extends Notification
{
    public function __construct(public LaporanKendala $kendala)
    {
    }

    /**
     * Hanya lewat channel database — belum ada integrasi email/broadcast.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'laporan_kendala_id' => $this->kendala->id,
            'satuan_asal' => $this->kendala->satuan->nama,
            'perihal' => $this->kendala->perihal,
            'prioritas' => $this->kendala->prioritas,
            'pesan' => "Laporan kendala baru dari {$this->kendala->satuan->nama}: {$this->kendala->perihal}",
        ];
    }
}
