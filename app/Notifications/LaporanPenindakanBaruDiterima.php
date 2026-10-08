<?php

namespace App\Notifications;

use App\Models\LaporanPenindakan;
use Illuminate\Notifications\Notification;

class LaporanPenindakanBaruDiterima extends Notification
{
    public function __construct(public LaporanPenindakan $laporanPenindakan)
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
            'laporan_penindakan_id' => $this->laporanPenindakan->id,
            'satuan_asal' => $this->laporanPenindakan->satuan->nama,
            'perihal' => $this->laporanPenindakan->perihal,
            'prioritas' => $this->laporanPenindakan->prioritas,
            'pesan' => "Laporan penanganan insiden baru dari {$this->laporanPenindakan->satuan->nama}: {$this->laporanPenindakan->perihal}",
        ];
    }
}
