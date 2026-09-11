<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris tembusan (CC) satu laporan kendala Kasansi ke satu satuan penerima
 * (Satlak/Sdir). Lihat komentar migration create_laporan_kendala_tembusans_table
 * untuk kenapa ini terpisah dari alur approval laporan_kendalas ke DANPUS.
 *
 * feedback/feedback_at/feedback_oleh adalah catatan yang dikirim satuan
 * penerima ini BALIK ke Kasansi -- begitu terisi, Kasansi sudah boleh
 * meneruskan laporan induknya ke Danpus (lihat LaporanKendala::siapDiteruskan()
 * dan LaporanKendalaController::teruskan()).
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
