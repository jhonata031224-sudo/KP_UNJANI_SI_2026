<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'satuan_id',
        'jabatan',
        'foto_path',
        'notif_push_enabled',
    ];



    /**
     * Satuan tempat user ini terdaftar (dipakai untuk memvalidasi pilihan
     * "Login sebagai" pada form login).
     */
    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class);
    }

    /**
     * Semua device/browser yang mengizinkan push notification untuk akun
     * ini (bisa lebih dari satu -- HP & laptop sekaligus). Dipakai oleh
     * App\Notifications\Channels\WebPushChannel setiap kali notifikasi
     * baru dikirim.
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Daftar SEMUA pengguna, terurut sesuai jenjang organisasi resmi
     * (Danpus -> Wadan -> Urdal -> Pok Analis -> 4 Sdir -> 4 Satlak, lihat
     * Satuan::kunciUrutSatuan()) -- BUKAN alfabet nama. Dipakai di semua
     * tabel/daftar/export pengguna Admin supaya urutannya konsisten di
     * seluruh sistem dan otomatis ikut urut yang benar walau ada satuan
     * atau pengguna baru ditambahkan nanti.
     */
    public static function terurutOrganisasi()
    {
        return self::with('satuan')->get()
            ->sortBy(fn ($u) => Satuan::kunciUrutSatuan($u->satuan->kategori ?? null, $u->satuan->kode ?? null))
            ->values();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notif_push_enabled' => 'boolean',
        ];
    }
}
