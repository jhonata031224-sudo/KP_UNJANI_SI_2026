<?php

namespace App\Notifications;

use App\Models\Laporan;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke satuan pengirim (mis. Satlakal) begitu Danpus/Wadan
 * memutuskan hasil akhir laporannya (Disetujui / Ditolak / Revisi) lewat
 * LaporanController::updateStatus().
 *
 * Lewat channel database -> badge lonceng + popup melayang + suara
 * notifikasi di navbar dashboard (lihat notification-controls.blade.php,
 * semuanya otomatis jalan untuk setiap notifikasi database baru) + webpush.
 */
class LaporanStatusDiputuskan extends Notification
{
    public function __construct(public Laporan $laporan)
    {
    }

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\WebPushChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        $status = (string) $this->laporan->status;
        $lower = strtolower($status);
        $perihal = $this->laporan->perihal;

        $pimpinan = str_contains($lower, 'wadan') ? 'Wadan' : (str_contains($lower, 'danpus') ? 'Danpus' : 'penerima');

        if (str_contains($lower, 'revisi')) {
            $pesan = "Laporan \"{$perihal}\" perlu direvisi oleh {$pimpinan}. Silakan periksa catatan dan kirim ulang.";
            $url = route('dashboard').'#permintaan-laporan';
        } elseif (str_contains($lower, 'tolak')) {
            $pesan = "Laporan \"{$perihal}\" ditolak oleh {$pimpinan}.";
            $url = route('dashboard').'#riwayat';
        } else {
            $pesan = "Laporan \"{$perihal}\" telah disetujui oleh {$pimpinan}.";
            $url = route('dashboard').'#riwayat';
        }

        return [
            'laporan_id' => $this->laporan->id,
            'perihal' => $perihal,
            'status' => $status,
            'pesan' => $pesan,
            // Disetujui/Ditolak -> pindah ke Arsip Laporan (#riwayat);
            // Revisi -> balik ke Permintaan Laporan aktif.
            'url' => $url,
        ];
    }
}
