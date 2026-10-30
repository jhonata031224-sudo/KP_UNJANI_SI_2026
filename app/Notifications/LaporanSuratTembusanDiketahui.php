<?php

namespace App\Notifications;

use App\Models\LaporanSurat;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi INFORMASI: penerima Tembusan / View Only sudah menekan "tanda mengetahui"
 * pada sebuah surat. Dikirim ke semua pihak terkait (Danpus, Wadan, Urdal, satuan
 * pengirim/penerima surat, dan satuan pengirim balasan) supaya tahu surat itu sudah
 * diketahui. Lihat LaporanSuratController::konfirmasiTembusan().
 *
 * Bertipe 'surat_info' & tanpa 'url': di lonceng tampil sebagai keterangan biasa
 * (tidak menambah hitungan belum-dibaca, tidak bisa diklik) -- sama seperti
 * LaporanSuratBalasanDikonfirmasi untuk pengirim balasan. Tetap muncul sebagai
 * popup melayang dan berbunyi (poll notifikasi + suara di notification-controls).
 */
class LaporanSuratTembusanDiketahui extends Notification
{
    /**
     * @param string $oleh Nama satuan yang menekan "mengetahui".
     */
    public function __construct(public LaporanSurat $surat, public string $oleh)
    {
    }

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\WebPushChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        // Surat Rahasia: samarkan perihal bagi pihak yang tidak berhak melihatnya.
        $this->surat->loadMissing('riwayats');
        $perihal = $this->surat->perihalUntuk($notifiable->satuan_id ?? null);

        return [
            'laporan_surat_id' => $this->surat->id,
            'perihal'          => $perihal,
            'pesan'            => "{$this->oleh} sudah mengetahui surat \"{$perihal}\".",
            'tipe'             => 'surat_info',
        ];
    }
}
