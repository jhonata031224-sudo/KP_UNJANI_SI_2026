<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PENINGGALAN ALUR LAMA -- tidak dipakai lagi.
 *
 * Dulu: baris tembusan (CC) satu laporan kendala Kasansi ke satu satuan
 * penerima (Satlak/Sdir) yang harus membalas dulu sebelum Kasansi bisa
 * meneruskan laporan ke Danpus. Alur kendala sekarang langsung
 * Kasansi -> Danpus (Menunggu Konfirmasi -> Dikonfirmasi) tanpa tembusan,
 * lihat LaporanKendalaController.
 *
 * Model & tabel laporan_kendala_tembusans SENGAJA dipertahankan supaya data
 * lama (feedback & dokumen balasan) tidak hilang.
 */
class LaporanKendalaTembusan extends Model
{
    use HasFactory;

    protected $fillable = [
        'laporan_kendala_id',
        'satuan_id',
        'dibaca_at',
        'dibaca_oleh',
        'feedback',
        'feedback_at',
        'feedback_oleh',
        'dokumen_balasan_path',
        'dokumen_balasan_nama',
        'dokumen_balasan_at',
        'dokumen_balasan_oleh',
    ];

    protected $casts = [
        'dibaca_at'          => 'datetime',
        'feedback_at'        => 'datetime',
        'dokumen_balasan_at' => 'datetime',
    ];

    public function laporanKendala(): BelongsTo
    {
        return $this->belongsTo(LaporanKendala::class);
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class);
    }

    public function dibacaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibaca_oleh');
    }

    public function feedbackOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'feedback_oleh');
    }

    public function dokumenBalasanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dokumen_balasan_oleh');
    }

    /**
     * True kalau tembusan ini sudah mengirim balasan (feedback teks ATAU
     * dokumen) ke Kasansi -- dipakai untuk menentukan apakah Kasansi
     * sudah bisa meneruskan ke Danpus.
     */
    public function sudahMembalas(): bool
    {
        return filled($this->feedback) || filled($this->dokumen_balasan_path);
    }
}
