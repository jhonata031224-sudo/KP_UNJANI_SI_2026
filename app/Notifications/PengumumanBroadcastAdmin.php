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
    public function __construct(public string $judul, public string $pesan)
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
        ];
    }
}
