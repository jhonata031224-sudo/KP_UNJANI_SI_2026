<?php

namespace App\Notifications;

use App\Models\LaporanSurat;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi INFORMASI ke satuan yang tadi mengirim balasan (alur naik):
 * Danpus sudah konfirmasi / ACC surat balasannya di ujung alur.
 *
 * Sengaja TANPA 'url' dan bertipe 'surat_info' -- di lonceng
 * (notification-controls.blade.php) notifikasi ini tampil sebagai teks
 * keterangan biasa: tidak ditandai belum-dibaca dan TIDAK bisa diklik,
 * karena suratnya sudah ada di Arsip Surat satuan ini dan tidak ada tindak
 * lanjut yang perlu dibuka.
 */
class LaporanSuratBalasanDikonfirmasi extends Notification
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
            'perihal'          => $this->surat->perihal,
            'pesan'            => "Balasan surat \"{$this->surat->perihal}\" sudah dikonfirmasi (ACC) oleh Danpus.",
            'tipe'             => 'surat_info',
        ];
    }
}
