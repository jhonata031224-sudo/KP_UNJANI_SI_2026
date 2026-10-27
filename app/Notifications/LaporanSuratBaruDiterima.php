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
    /**
     * @param  string|null  $pesan  Override teks notifikasi (mis. untuk balasan
     *                              naik dari satuan). Null = teks default
     *                              "Surat baru dari ..." seperti biasa.
     */
    public function __construct(public LaporanSurat $surat, public ?string $pesan = null)
    {
    }

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\WebPushChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        // Surat Rahasia: perihal asli hanya untuk pihak yang berhak (lihat
        // LaporanSurat::bolehLihatIsi); pihak lain (Wadan, Urdal, dst) melihat "Surat Rahasia".
        $this->surat->loadMissing('riwayats');
        $perihal = $this->surat->perihalUntuk($notifiable->satuan_id ?? null);
        $pesan = $this->pesan !== null
            ? str_replace((string) $this->surat->perihal, $perihal, $this->pesan)
            : "Surat baru dari {$this->surat->satuan->nama}: {$perihal}";

        return [
            'laporan_surat_id' => $this->surat->id,
            'satuan_asal' => $this->surat->satuan->nama,
            'perihal' => $perihal,
            'prioritas' => $this->surat->prioritas,
            'pesan' => $pesan,
            // Section #surat-masuk ada persis sama di dashboard Satuan
            // maupun Pimpinan, jadi aman dipakai apa adanya utk siapapun
            // penerimanya.
            'url' => route('dashboard').'#surat-masuk',
        ];
    }
}
