<?php

namespace App\Notifications;

use App\Models\Laporan;
use Illuminate\Notifications\Notification;

class LaporanBaruDiterima extends Notification
{
    public function __construct(public Laporan $laporan)
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
        $pesan = $this->laporan->status === Laporan::STATUS_PROGRES
            ? "Update progres ({$this->laporan->progres}%) dari {$this->laporan->satuan->nama}: {$this->laporan->perihal}"
            : "Laporan baru dari {$this->laporan->satuan->nama}: {$this->laporan->perihal}";

        return [
            'laporan_id' => $this->laporan->id,
            'satuan_asal' => $this->laporan->satuan->nama,
            'perihal' => $this->laporan->perihal,
            'prioritas' => $this->laporan->prioritas,
            'pesan' => $pesan,
        ];
    }
}
