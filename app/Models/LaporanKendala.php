<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laporan kendala/rutin yang dikirim satuan Kasansi (21 Sansidam) LANGSUNG
 * ke DANPUS. Lihat komentar migration create_laporan_kendalas_table untuk
 * alasan kenapa ini terpisah dari model Laporan.
 */
class LaporanKendala extends Model
{
    use HasFactory;

    protected $fillable = [
        'satuan_id',
        'user_id',
        'tujuan_satuan_id',
        'perihal',
        'deskripsi',
        'prioritas',
        'lampiran_path',
        'status',
        'catatan',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public const STATUS_MENUNGGU = 'Menunggu';
    public const STATUS_DITINDAKLANJUTI = 'Ditindaklanjuti';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_DITOLAK = 'Ditolak';
    public const STATUS_DIKONFIRMASI = 'Dikonfirmasi';

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

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
