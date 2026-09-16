<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanPenindakan extends Model
{
    use HasFactory;

    protected $fillable = [
        'satuan_id',
        'user_id',
        'tujuan_satuan_id',
        'aset',
        'jenis_ancaman',
        'perihal',
        'deskripsi',
        'tindakan',
        'prioritas',
        'status',
        'catatan_danpus',
        'tanggal_kirim',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kirim' => 'datetime',
        ];
    }

    /**
     * Satuan asal pembuat laporan (Satuan Pelaksanaan Penindakan).
     */
    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    /**
     * Satuan tujuan laporan (DANPUS).
     */
    public function tujuanSatuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'tujuan_satuan_id');
    }

    /**
     * Pengguna yang membuat laporan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bukti digital (foto/PDF/log/ZIP forensik) yang menyertai laporan ini.
     */
    public function bukti(): HasMany
    {
        return $this->hasMany(LaporanPenindakanBukti::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'Draft';
    }

    /**
     * Laporan yang statusnya Draft atau Direvisi masih boleh diedit &
     * dikirim/dikirim ulang oleh pemiliknya.
     */
    public function bisaDiedit(): bool
    {
        return in_array($this->status, ['Draft', 'Direvisi'], true);
    }
}
