<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LaporanSuratRiwayat extends Model
{
    use HasFactory;

    public const AKSI_BUAT_SURAT   = 'BUAT_SURAT';
    public const AKSI_KONFIRMASI   = 'KONFIRMASI';
    public const AKSI_TERUSKAN     = 'TERUSKAN';
    public const AKSI_SURAT_KELUAR = 'SURAT_KELUAR';
    public const AKSI_SELESAI      = 'SELESAI';

    protected $fillable = [
        'laporan_surat_id',
        'siklus',
        'aksi',
        'pengirim_satuan_id',
        'penerima_satuan_id',
        'user_id',
        'disposisi',
        'tindakan',
        'catatan',
        'lampiran_path',
        'lampiran_nama_asli',
    ];

    protected $casts = [
        'tindakan' => 'array',
        'siklus'   => 'integer',
    ];

    public function laporanSurat(): BelongsTo
    {
        return $this->belongsTo(LaporanSurat::class, 'laporan_surat_id');
    }

    public function pengirimSatuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'pengirim_satuan_id');
    }

    public function penerimaSatuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'penerima_satuan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getLampiranSizeAttribute(): ?string
    {
        if (! $this->lampiran_path || ! Storage::disk('public')->exists($this->lampiran_path)) {
            return null;
        }

        $bytes = Storage::disk('public')->size($this->lampiran_path);

        return $bytes < 1024 * 1024
            ? max(1, round($bytes / 1024)) . ' KB'
            : round($bytes / 1024 / 1024, 1) . ' MB';
    }

    public function labelAksi(): string
    {
        return match ($this->aksi) {
            self::AKSI_BUAT_SURAT   => 'Surat Dibuat',
            self::AKSI_KONFIRMASI   => 'Dikonfirmasi / ACC & Diterima',
            self::AKSI_TERUSKAN     => 'Diteruskan dengan Disposisi & Tindakan',
            self::AKSI_SURAT_KELUAR => 'Surat Keluar / Balasan Dikirim',
            self::AKSI_SELESAI      => 'Surat Diselesaikan (Final)',
            default                 => $this->aksi,
        };
    }
}
