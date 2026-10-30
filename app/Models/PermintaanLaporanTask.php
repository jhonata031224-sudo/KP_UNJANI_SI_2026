<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanLaporanTask extends Model
{
    protected $fillable = [
        'permintaan_laporan_id',
        'deskripsi',
        'detail',
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

    /**
     * Checkpoint Laporan yang disubmit lewat form "Update Progres" saat task
     * ini diklik (lihat LaporanController::store()) -- dipakai Pimpinan buat
     * lihat detail/lampiran laporan tanpa perlu pindah tab.
     */
    public function laporans(): HasMany
    {
        return $this->hasMany(Laporan::class, 'task_id');
    }
}
