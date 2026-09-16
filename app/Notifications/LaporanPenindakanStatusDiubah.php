<?php

namespace App\Notifications;

use App\Models\LaporanPenindakan;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke pembuat laporan (Satuan Pelaksanaan Penindakan) setiap kali
 * DANPUS memutuskan laporan penanganan insidennya: Disetujui, Ditolak, atau
 * Direvisi.
 */
class LaporanPenindakanStatusDiubah extends Notification
{
    public function __construct(public LaporanPenindakan $laporanPenindakan)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $status = $this->laporanPenindakan->status;

        $pesan = match ($status) {
            'Disetujui' => "Laporan \"{$this->laporanPenindakan->perihal}\" telah disetujui DANPUS.",
            'Ditolak' => "Laporan \"{$this->laporanPenindakan->perihal}\" ditolak DANPUS.",
            'Direvisi' => "Laporan \"{$this->laporanPenindakan->perihal}\" perlu direvisi. Silakan periksa catatan DANPUS.",
            default => "Status laporan \"{$this->laporanPenindakan->perihal}\" diperbarui menjadi {$status}.",
        };

        return [
            'laporan_penindakan_id' => $this->laporanPenindakan->id,
            'status' => $status,
            'perihal' => $this->laporanPenindakan->perihal,
            'pesan' => $pesan,
        ];
    }
}
