<?php

namespace App\Notifications;

use App\Models\LaporanMonitoring;
use Illuminate\Notifications\Notification;

class LaporanMonitoringBaruDiterima extends Notification
{
    public function __construct(public LaporanMonitoring $laporanMonitoring)
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
            'laporan_monitoring_id' => $this->laporanMonitoring->id,
            'satuan_asal' => $this->laporanMonitoring->satuan->nama,
            'jenis_kegiatan' => $this->laporanMonitoring->jenis_kegiatan,
            'pesan' => "Laporan kegiatan baru dari {$this->laporanMonitoring->satuan->nama}: {$this->laporanMonitoring->jenis_kegiatan}",
        ];
    }
}
