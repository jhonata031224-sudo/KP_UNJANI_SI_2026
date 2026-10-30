<?php

namespace App\Notifications;

use App\Models\LaporanMonitoring;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke pembuat laporan (Satuan Pelaksanaan Penangkalan) setiap kali DANPUS memutuskan
 * laporan monitoring & recovery-nya: Disetujui, Ditolak, atau Direvisi.
 */
class LaporanMonitoringStatusDiubah extends Notification
{
    public function __construct(public LaporanMonitoring $laporanMonitoring)
    {
    }

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\WebPushChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        $status = $this->laporanMonitoring->status;

        $pesan = match ($status) {
            'Disetujui' => "Laporan \"{$this->laporanMonitoring->jenis_kegiatan}\" telah disetujui DANPUS.",
            'Ditolak' => "Laporan \"{$this->laporanMonitoring->jenis_kegiatan}\" ditolak DANPUS.",
            'Direvisi' => "Laporan \"{$this->laporanMonitoring->jenis_kegiatan}\" perlu direvisi. Silakan periksa catatan DANPUS.",
            default => "Status laporan \"{$this->laporanMonitoring->jenis_kegiatan}\" diperbarui menjadi {$status}.",
        };

        return [
            'laporan_monitoring_id' => $this->laporanMonitoring->id,
            'status' => $status,
            'jenis_kegiatan' => $this->laporanMonitoring->jenis_kegiatan,
            'pesan' => $pesan,
        ];
    }
}
