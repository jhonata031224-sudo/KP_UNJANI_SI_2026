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
     * Lewat channel database (lonceng in-app) + webpush (notifikasi OS
     * di luar sistem, lihat App\Notifications\Channels\WebPushChannel).
     */
    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\WebPushChannel::class];
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
