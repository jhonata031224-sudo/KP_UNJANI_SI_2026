<?php

namespace App\Notifications;

use App\Models\LaporanKendala;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke satuan penerima TEMBUSAN (bukan penerima utama) laporan
 * kendala Kasansi. Terpisah dari LaporanKendalaBaruDiterima (yang dikirim ke
 * DANPUS sebagai penerima utama/approval) supaya pesannya jelas ini cuma
 * info koordinasi, bukan sesuatu yang perlu diputuskan.
 */
class LaporanKendalaTembusanBaru extends Notification
{
    public function __construct(public LaporanKendala $kendala)
    {
    }

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
            'pesan' => "Tembusan laporan dari {$this->kendala->satuan->nama}: {$this->kendala->perihal}",
        ];
    }
}
