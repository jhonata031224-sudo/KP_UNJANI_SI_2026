<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanLaporan extends Model
{
    use HasFactory;

    protected $fillable = [
        'pembuat_id',
        'tujuan_satuan_id',
        'perihal',
        'kategori',
        'instruksi',
        'deadline_at',
        'prioritas',
        'status',
        'laporan_id',
        'progres',
        'dikerjakan_at',
        'selesai_at',
        'dibatalkan_at',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
        'dikerjakan_at' => 'datetime',
        'selesai_at' => 'datetime',
        'dibatalkan_at' => 'datetime',
        'progres' => 'integer',
    ];

    public const STATUS_BELUM = 'Belum dikerjakan';
    public const STATUS_DIKERJAKAN = 'Sedang dikerjakan';
    public const STATUS_PEMERIKSAAN = 'Menunggu pemeriksaan';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_DIBATALKAN = 'Dibatalkan';

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembuat_id');
    }

    public function tujuanSatuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'tujuan_satuan_id');
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }

    /**
     * SEMUA laporan yang pernah dikirim untuk permintaan ini (progress-log
     * maupun final) -- beda dari laporan() yang cuma nunjuk ke laporan FINAL
     * (laporan_id null selama masih progress-log, walau sudah ada kiriman).
     */
    public function laporans(): HasMany
    {
        return $this->hasMany(Laporan::class, 'permintaan_laporan_id');
    }

    public function isTerlambat(): bool
    {
        return !$this->laporan_id
            && !in_array($this->status, [self::STATUS_SELESAI, self::STATUS_PEMERIKSAAN, self::STATUS_DIBATALKAN], true)
            && $this->deadline_at?->isPast();
    }

    public function isDapatDibatalkan(): bool
    {
        return in_array($this->status, [self::STATUS_BELUM, self::STATUS_DIKERJAKAN], true);
    }

    /**
     * Deadline cuma boleh diedit (dibuka lagi kesempatannya buat satuan)
     * kalau kondisinya "Terlambat", "Dibatalkan", atau laporan finalnya
     * "Ditolak" -- di luar itu (Disetujui/Menunggu pemeriksaan) dianggap
     * sudah final, jadi tombol Edit di UI cuma nampilin keterangan.
     */
    public function isDapatEditDeadline(): bool
    {
        if ($this->isTerlambat() || $this->status === self::STATUS_DIBATALKAN) {
            return true;
        }

        return $this->status === self::STATUS_SELESAI
            && str_contains(strtolower($this->laporan?->status ?? ''), 'tolak');
    }

    public function alasanTidakBisaEditDeadline(): string
    {
        return match (true) {
            $this->status === self::STATUS_PEMERIKSAAN => 'Laporan untuk permintaan ini sedang menunggu pemeriksaan Anda, deadline baru bisa diubah setelah ada keputusan (disetujui/ditolak).',
            $this->status === self::STATUS_SELESAI => 'Laporan untuk permintaan ini sudah disetujui, deadline tidak dapat diubah lagi.',
            default => 'Deadline permintaan ini tidak dapat diubah saat ini.',
        };
    }

    public function statusTampilan(): string
    {
        return $this->isTerlambat() ? 'Terlambat' : $this->status;
    }
}
