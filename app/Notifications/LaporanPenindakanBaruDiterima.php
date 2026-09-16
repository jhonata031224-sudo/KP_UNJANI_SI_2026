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
     * Hanya lewat channel database — belum ada integrasi email/broadcast.
     */
    public function via($notifiable): array
    {
        return ['database'];
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
