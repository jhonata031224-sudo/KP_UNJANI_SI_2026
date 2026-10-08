<?php

namespace App\Notifications;

use App\Models\LaporanSurat;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi INFORMASI ke satuan yang tadi mengirim balasan (alur naik):
 * Danpus sudah konfirmasi / ACC surat balasannya di ujung alur.
 *
 * Untuk pengirim balasan naik: sengaja TANPA 'url' dan bertipe 'surat_info' -- di lonceng
 * (notification-controls.blade.php) notifikasi ini tampil sebagai teks
 * keterangan biasa: tidak ditandai belum-dibaca dan TIDAK bisa diklik,
 * karena suratnya sudah ada di Arsip Surat satuan ini dan tidak ada tindak
 * lanjut yang perlu dibuka.
 */
class LaporanSuratBalasanDikonfirmasi extends Notification
{
    /**
     * @param bool $suratAsli true = penerima adalah PENGIRIM ASLI surat
     *                        (teks: "Surat ... sudah dikonfirmasi"); false =
     *                        penerima pengirim balasan naik (teks: "Balasan surat ...").
     * @param string $oleh    Nama satuan yang mengonfirmasi (default "Danpus").
     *                        Diisi satuan lain (mis. Wadan) saat Danpus adalah
     *                        PENGIRIM ASLI surat dan penerimanya yang mengonfirmasi.
     */
    public function __construct(
        public LaporanSurat $surat,
        public bool $suratAsli = false,
        public string $oleh = 'Danpus',
    ) {
    }

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\WebPushChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        // PENGIRIM ASLI (satuan bawahan, mis. Kasansi): notifikasi bisa diklik
        // dan mengarah ke Arsip Surat, tempat suratnya sekarang berada. Tanpa
        // 'tipe' => 'surat_info' supaya lonceng memperlakukannya sebagai
        // notifikasi biasa (belum-dibaca + bisa diklik).
        if ($this->suratAsli) {
            // Konfirmasi oleh Danpus (ujung alur) -> surat sudah di Arsip Surat.
            // Konfirmasi oleh satuan lain (mis. Wadan menerima surat dari
            // Danpus) -> surat MASIH di Surat Keluar pengirim sampai selesai
            // (mis. diteruskan Wadan), jadi arahkan ke sana kecuali sudah selesai.
            $tab = ($this->oleh === 'Danpus' || $this->surat->is_selesai) ? '#arsip-surat' : '#kirim-surat';

            return [
                'laporan_surat_id' => $this->surat->id,
                'perihal'          => $this->surat->perihal,
                'pesan'            => "Surat \"{$this->surat->perihal}\" sudah dikonfirmasi (ACC) oleh {$this->oleh}.",
                'url'              => route('dashboard').$tab,
            ];
        }

        // Pengirim balasan naik: tetap informasi saja (tidak bisa diklik).
        return [
            'laporan_surat_id' => $this->surat->id,
            'perihal'          => $this->surat->perihal,
            'pesan'            => "Balasan surat \"{$this->surat->perihal}\" sudah dikonfirmasi (ACC) oleh Danpus.",
            'tipe'             => 'surat_info',
        ];
    }
}
