<?php

namespace App\Notifications;

use App\Models\LaporanKendala;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke Kasansi (satuan pengirim) begitu Danpus menekan Konfirmasi
 * pada laporan kendalanya -- lihat LaporanKendalaController::konfirmasi().
 * Setelah ini proses selesai; laporan pindah ke Arsip Kendala Kasansi.
 */
class LaporanKendalaDikonfirmasi extends Notification
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
            'perihal' => $this->kendala->perihal,
            'pesan' => "Laporan kendala \"{$this->kendala->perihal}\" sudah dikonfirmasi oleh Danpus.",
            // Setelah dikonfirmasi laporan pindah ke submenu Arsip Kendala
            // milik Kasansi (lihat laporan-role.blade.php).
            'url' => route('dashboard').'#arsip-kendala-kasansi',
        ];
    }
}
