<?php

namespace App\Notifications;

use App\Models\LaporanKendalaTembusan;
use App\Models\Satuan;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Kasansi (satuan pengirim laporan kendala) begitu salah satu
 * satuan tembusan (Satlak/Sdir) mengirim feedback. Ini penanda laporan
 * kendala terkait sudah bisa diteruskan Kasansi ke Danpus lewat tombol
 * "Kirim ke Danpus" -- LaporanKendalaController::teruskan().
 */
class LaporanKendalaTembusanFeedbackDiterima extends Notification
{
    public function __construct(
        public LaporanKendalaTembusan $tembusan,
        public Satuan $satuanPemberiFeedback,
    ) {
    }

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\WebPushChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        $kendala = $this->tembusan->laporanKendala;

        return [
            'laporan_kendala_id' => $this->tembusan->laporan_kendala_id,
            'satuan_pemberi_feedback' => $this->satuanPemberiFeedback->nama,
            'perihal' => $kendala->perihal ?? '-',
            'pesan' => "{$this->satuanPemberiFeedback->nama} memberi feedback pada laporan kendala \"{$kendala->perihal}\" -- sudah bisa diteruskan ke Danpus.",
        ];
    }
}
