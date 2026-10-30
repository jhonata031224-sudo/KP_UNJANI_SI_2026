<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    public function lampirans(): HasMany
    {
        return $this->hasMany(LaporanLampiran::class);
    }

    /**
     * Daftar SEMUA lampiran laporan ini, apapun sumbernya -- lampiran_path
     * lama (1 file, sebelum fitur multi-lampiran ada) ATAU baris-baris baru
     * di tabel laporan_lampirans (banyak file). Dipakai SEMUA view yang
     * nampilin lampiran (dashboard Satuan/Danpus/Pimpinan/Kendala) biar
     * gak perlu tau bedanya laporan lama vs baru -- tinggal loop 1 daftar
     * ini, tiap item punya ->path dan ->nama_asli.
     * SENGAJA prioritasin laporan_lampirans (kalau ada isinya) daripada
     * lampiran_path lama -- begitu 1 laporan sempat dapat lampiran baru
     * lewat fitur ini, kolom lama itu otomatis gak dipakai lagi buat
     * laporan itu (menghindari lampiran lama "nyangkut" dobel di daftar).
     */
    public function getSemuaLampiranAttribute(): Collection
    {
        $baru = $this->relationLoaded('lampirans') ? $this->lampirans : $this->lampirans()->get();
        if ($baru->isNotEmpty()) {
            return $baru;
        }

        if ($this->lampiran_path) {
            return collect([(object) [
                'id' => null,
                'path' => $this->lampiran_path,
                'nama_asli' => $this->lampiran_nama_asli ?: basename($this->lampiran_path),
            ]]);
        }

        return collect();
    }
}
