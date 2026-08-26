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
        'permintaan_laporan_id',
        'task_id',
        'proyek',
        'perihal',
        'deskripsi',
        'catatan',
        'kendala',
        'progres',
        'prioritas',
        'lampiran_path',
        'lampiran_nama_asli',
        'status',
    ];

    protected $casts = [
        'progres' => 'integer',
    ];

    public const STATUS_PROGRES = 'Progres';

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function tujuanSatuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'tujuan_satuan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function permintaanLaporan(): BelongsTo
    {
        return $this->belongsTo(PermintaanLaporan::class, 'permintaan_laporan_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(PermintaanLaporanTask::class, 'task_id');
    }
}
