<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAPID Keys
    |--------------------------------------------------------------------------
    |
    | Sepasang kunci EC P-256 yang jadi "identitas" server SIBERAD di mata
    | push service browser (FCM buat Chrome, Mozilla Push Service buat
    | Firefox, dst). Dibuat SEKALI lalu disimpan permanen di environment
    | variable -- kalau diganti-ganti, semua subscription lama yang sudah
    | tersimpan otomatis tidak valid lagi dan user harus mengizinkan ulang.
    |
    | Generate pasangan baru (kalau perlu) dengan:
    |   php artisan tinker
    |   >>> Minishlink\WebPush\VAPID::createVapidKeys()
    |
    */

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', env('APP_URL', 'http://localhost')),
        'publicKey' => env('VAPID_PUBLIC_KEY'),
        'privateKey' => env('VAPID_PRIVATE_KEY'),
    ],

];
