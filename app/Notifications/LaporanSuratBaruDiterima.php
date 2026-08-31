<?php

namespace App\Notifications;

use App\Models\LaporanSurat;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke satuan tujuan begitu ada surat baru masuk dari Kasansi.
 * Lewat channel database (lonceng in-app) + webpush, sama seperti
 * LaporanKendalaBaruDiterima.
 */
class LaporanSuratBaruDiterima extends Notification
{
    public function __construct(public LaporanSurat $surat)
    {
    }

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\WebPushChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'laporan_surat_id' => $this->surat->id,
            'satuan_asal' => $this->surat->satuan->nama,
            'perihal' => $this->surat->perihal,
            'prioritas' => $this->surat->prioritas,
            'pesan' => "Surat baru dari {$this->surat->satuan->nama}: {$this->surat->perihal}",
        ];
    }
}
