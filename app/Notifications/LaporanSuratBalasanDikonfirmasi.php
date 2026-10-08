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
     */
    public function __construct(public LaporanSurat $surat, public bool $suratAsli = false)
    {
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
            return [
                'laporan_surat_id' => $this->surat->id,
                'perihal'          => $this->surat->perihal,
                'pesan'            => "Surat \"{$this->surat->perihal}\" sudah dikonfirmasi (ACC) oleh Danpus.",
                'url'              => route('dashboard').'#arsip-surat',
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
