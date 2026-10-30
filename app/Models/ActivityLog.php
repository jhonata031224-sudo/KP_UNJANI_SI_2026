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
        'context',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'context' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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

    /** Catat aktivitas manual maupun aktivitas otomatis dari middleware. */
    public static function catat(
        string $aksi,
        ?string $deskripsi = null,
        ?User $user = null,
        ?array $context = null,
    ): self {
        $user = $user ?? RequestFacade::user();

        return static::create([
            'user_id' => $user?->id,
            'nama_pengguna' => $user?->name,
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
            'ip_address' => RequestFacade::ip(),
            'context' => $context,
            'created_at' => now(),
        ]);
    }
}
