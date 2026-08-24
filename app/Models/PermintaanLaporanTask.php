<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanLaporanTask extends Model
{
    protected $fillable = [
        'permintaan_laporan_id',
        'deskripsi',
        'selesai',
        'selesai_at',
        'urutan',
    ];

    protected $casts = [
        'selesai' => 'boolean',
        'selesai_at' => 'datetime',
    ];

    public function permintaanLaporan(): BelongsTo
    {
        return $this->belongsTo(PermintaanLaporan::class);
    }
}
