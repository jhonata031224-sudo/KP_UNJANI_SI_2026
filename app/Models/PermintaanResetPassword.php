<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanResetPassword extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'password_baru',
        'catatan',
        'status',
        'diproses_oleh',
        'diproses_at',
    ];

    protected $casts = [
        'diproses_at' => 'datetime',
    ];

    public const STATUS_MENUNGGU = 'Menunggu';
    public const STATUS_DISETUJUI = 'Disetujui';
    public const STATUS_DITOLAK = 'Ditolak';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}
