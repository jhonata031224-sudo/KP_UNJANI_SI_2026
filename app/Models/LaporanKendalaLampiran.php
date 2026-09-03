<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanKendalaLampiran extends Model
{
    protected $fillable = [
        'laporan_kendala_id',
        'path',
        'nama_asli',
    ];

    public function laporanKendala(): BelongsTo
    {
        return $this->belongsTo(LaporanKendala::class);
    }
}
