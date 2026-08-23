<?php

namespace App\Notifications;

use App\Models\LaporanKeluhan;
use Illuminate\Notifications\Notification;

class LaporanKeluhanBaruDiterima extends Notification
{
    public function __construct(public LaporanKeluhan $keluhan)
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
            'laporan_keluhan_id' => $this->keluhan->id,
            'satuan_asal' => $this->keluhan->satuan->nama,
            'perihal' => $this->keluhan->perihal,
            'prioritas' => $this->keluhan->prioritas,
            'pesan' => "Laporan keluhan baru dari {$this->keluhan->satuan->nama}: {$this->keluhan->perihal}",
        ];
    }
}
