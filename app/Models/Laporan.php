<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Laporan extends Model
{
    use HasFactory;

    protected $fillable = [
        'satuan_id',
        'user_id',
        'tujuan_satuan_id',
        'proyek',
        'perihal',
        'deskripsi',
        'catatan',
        'prioritas',
        'lampiran_path',
        'status',
    ];

    /**
     * Satuan asal pengirim laporan (mis. Satuan Pelaksanaan Dukungan Teknologi).
     */
    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    /**
     * Satuan tujuan laporan (mis. DANPUS).
     */
    public function tujuanSatuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'tujuan_satuan_id');
    }

    /**
     * Pengguna yang mengirim laporan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
