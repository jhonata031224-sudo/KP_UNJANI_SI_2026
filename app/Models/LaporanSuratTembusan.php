<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanSuratTembusan extends Model
{
    use HasFactory;

    public const JENIS_VIEW_ONLY = 'view_only';
    public const JENIS_TEMBUSAN  = 'tembusan';
    public const JENIS_HASIL_RC  = 'hasil_rc';

    protected $fillable = [
        'laporan_surat_id',
        'laporan_surat_riwayat_id',
        'satuan_id',
        'jenis',
        'dikonfirmasi_at',
        'dikonfirmasi_oleh',
    ];

    protected $casts = [
        'dikonfirmasi_at' => 'datetime',
    ];

    public function laporanSurat(): BelongsTo
    {
        return $this->belongsTo(LaporanSurat::class, 'laporan_surat_id');
    }

    public function riwayat(): BelongsTo
    {
        return $this->belongsTo(LaporanSuratRiwayat::class, 'laporan_surat_riwayat_id');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function dikonfirmasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikonfirmasi_oleh');
    }

    public function isDikonfirmasi(): bool
    {
        return $this->dikonfirmasi_at !== null;
    }

    public function labelJenis(): string
    {
        return match ($this->jenis) {
            self::JENIS_VIEW_ONLY => 'View Only',
            self::JENIS_TEMBUSAN  => 'Tembusan',
            self::JENIS_HASIL_RC  => 'Hasil / RC',
            default               => ucfirst($this->jenis),
        };
    }
}
