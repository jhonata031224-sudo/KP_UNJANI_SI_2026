<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request as RequestFacade;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'nama_pengguna',
        'aksi',
        'deskripsi',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Format nama pengguna/unit yang ditampilkan pada log aktivitas.
     * Beberapa nama unit tersimpan dalam bentuk uppercase, tetapi pada
     * tampilan log ingin ditulis dengan kapitalisasi normal.
     */
    public function getNamaPenggunaAttribute($value): ?string
    {
        return match (strtoupper((string) $value)) {
            'BINMAT' => 'Binmat',
            'BINFUNG' => 'Binfung',
            'BINUM' => 'Binum',
            'DIKLAT' => 'Diklat',
            default => $value,
        };
    }

    /**
     * Catat satu baris log aktivitas. Dipanggil dari controller mana pun
     * (login/logout, CRUD pengguna, CRUD satuan, dll.) untuk mengisi menu
     * "Monitoring Aktivitas Sistem" & "Laporan Pengguna & Aktivitas".
     */
    public static function catat(string $aksi, ?string $deskripsi = null, ?User $user = null): self
    {
        $user = $user ?? RequestFacade::user();

        return static::create([
            'user_id' => $user?->id,
            'nama_pengguna' => $user?->name,
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
            'ip_address' => RequestFacade::ip(),
            'created_at' => now(),
        ]);
    }
}
