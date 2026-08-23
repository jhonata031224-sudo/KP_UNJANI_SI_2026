<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Laporan keluhan yang dikirim satuan Kasansi (21 Kodam) ke salah satu
 * Satlak operasional. Lihat komentar migration create_laporan_keluhans_table
 * untuk alasan kenapa ini terpisah dari model Laporan.
 */
class LaporanKeluhan extends Model
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
    ];

    public const STATUS_MENUNGGU = 'Menunggu';
    public const STATUS_DITINDAKLANJUTI = 'Ditindaklanjuti';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_DITOLAK = 'Ditolak';

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
}
