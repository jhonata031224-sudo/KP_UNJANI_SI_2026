<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Surat yang dikirim satuan Kasansi (21 Sansidam) ke SATU satuan tujuan
 * bebas (dipilih sendiri, bisa satuan mana saja di sistem), TANPA tembusan
 * dan TANPA status/progres apa pun -- lihat komentar migration
 * create_laporan_surats_table. Berbeda dari LaporanKendala yang tujuannya
 * tetap DANPUS dan masih punya alur status.
 */
class LaporanSurat extends Model
{
    use HasFactory;

    protected $fillable = [
        'satuan_id',
        'user_id',
        'tujuan_satuan_id',
        'perihal',
        'kategori',
        'deskripsi',
        'prioritas',
        'lampiran_path',
    ];

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
