<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi pengumuman manual yang dikirim Admin ke SELURUH pengguna
 * lewat menu Setelan -> Notifikasi. Lewat channel database (lonceng
 * in-app, semua pengguna pasti kebagian) + webpush (notifikasi OS di
 * luar sistem, hanya utk pengguna yang sudah mengizinkan & fitur push
 * global sedang aktif -- lihat App\Notifications\Channels\WebPushChannel).
 */
class PengumumanBroadcastAdmin extends Notification
{
    public function __construct(public string $judul, public string $pesan, public string $kategori = 'keterangan')
    {
    }

    public function via($notifiable): array
    {
        return ['database', \App\Notifications\Channels\WebPushChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'tipe' => 'pengumuman_admin',
            // Kategori pengumuman -- dipakai frontend (notification-controls.
            // blade.php) buat nentuin dua hal: (1) apakah item dikasih titik
            // penanda "belum dibaca" atau tidak, dan (2) apakah item bisa
            // diklik utk buka modal penjelasan atau cuma teks info biasa.
            // 'maintenance'  = ada tindak lanjut/perlu perhatian -> tetap
            //                  ditandai belum dibaca & bisa diklik (modal).
            // 'keterangan'   = sekadar info umum, tidak perlu ditandai
            //                  belum dibaca & tidak bisa diklik sama sekali.
            'kategori' => $this->kategori,
        ];
    }
}
