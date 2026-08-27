<?php

namespace App\Notifications;

use App\Models\LaporanPublikasi;
use Illuminate\Notifications\Notification;

class LaporanPublikasiBaruDiterima extends Notification
{
    public function __construct(public LaporanPublikasi $laporanPublikasi)
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
            'laporan_publikasi_id' => $this->laporanPublikasi->id,
            'satuan_asal' => $this->laporanPublikasi->satuan->nama,
            'judul' => $this->laporanPublikasi->judul,
            'pesan' => "Laporan publikasi baru dari {$this->laporanPublikasi->satuan->nama}: {$this->laporanPublikasi->judul}",
        ];
    }
}
