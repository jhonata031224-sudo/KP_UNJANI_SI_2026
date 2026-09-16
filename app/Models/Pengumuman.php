<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengumuman extends Model
{
    // Eloquent salah menerka bentuk jamak "Pengumuman" jadi "pengumumen"
    // (mengikuti pola woman->women), makanya nama tabel di-set eksplisit.
    protected $table = 'pengumumans';

    protected $fillable = [
        'judul',
        'isi',
        'dibuat_oleh',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
