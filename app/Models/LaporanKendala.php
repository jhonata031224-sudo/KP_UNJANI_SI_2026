<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Laporan kendala/rutin yang dikirim satuan Kasansi (21 Sansidam) ke DANPUS.
 * Lihat komentar migration create_laporan_kendalas_table untuk alasan kenapa
 * ini terpisah dari model Laporan.
 *
 * ALUR (disederhanakan):
 *   Menunggu Konfirmasi  ->  Dikonfirmasi
 *
 * Kasansi mengirim laporan (lampiran opsional) dan laporan LANGSUNG masuk ke
 * daftar Danpus dengan status "Menunggu Konfirmasi". Satu-satunya aksi Danpus
 * adalah Konfirmasi (LaporanKendalaController::konfirmasi()) -- setelah itu
 * status jadi "Dikonfirmasi", confirmed_at/confirmed_by terisi, dan proses
 * SELESAI. Tidak ada tembusan, balasan, dokumen siap kirim, tindak lanjut,
 * selesai, maupun penolakan.
 *
 * Kolom/tabel peninggalan alur lama (diteruskan_*, dokumen_kasansi_*, tabel
 * laporan_kendala_tembusans) SENGAJA tidak dihapus supaya data lama utuh,
 * tapi tidak lagi dipakai oleh alur baru.
 */
class LaporanKendala extends Model
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
        'status',
        'catatan',
        'confirmed_at',
        'confirmed_by',
        'diteruskan_at',
        'diteruskan_oleh',
        'dokumen_kasansi_path',
        'dokumen_kasansi_nama',
        'dokumen_kasansi_at',
        'dokumen_kasansi_oleh',
    ];

    protected $casts = [
        'confirmed_at'      => 'datetime',
        'diteruskan_at'     => 'datetime',
        'dokumen_kasansi_at' => 'datetime',
    ];

    public const STATUS_MENUNGGU_KONFIRMASI = 'Menunggu Konfirmasi';
    public const STATUS_DIKONFIRMASI = 'Dikonfirmasi';

    /**
     * Laporan yang masih menunggu aksi Danpus (belum dikonfirmasi). Dasarnya
     * confirmed_at (bukan label status) supaya baris peninggalan alur lama --
     * mis. berstatus Ditindaklanjuti/Selesai/Ditolak tapi belum pernah
     * dikonfirmasi -- tetap muncul dan masih bisa dikonfirmasi Danpus.
     */
    public function scopeMenungguKonfirmasi(Builder $query): Builder
    {
        return $query->whereNull('confirmed_at');
    }

    public function scopeSudahDikonfirmasi(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at');
    }

    public function sudahDikonfirmasi(): bool
    {
        return $this->confirmed_at !== null || $this->status === self::STATUS_DIKONFIRMASI;
    }

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

    public function diteruskanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diteruskan_oleh');
    }

    /**
     * Peninggalan alur lama (tembusan ke satuan lain). Tidak dipakai lagi oleh
     * alur kendala yang baru; relasi dipertahankan hanya supaya data lama di
     * tabel laporan_kendala_tembusans tetap bisa dibaca/dibersihkan.
     */
    public function tembusans(): HasMany
    {
        return $this->hasMany(LaporanKendalaTembusan::class);
    }

    public function lampirans(): HasMany
    {
        return $this->hasMany(LaporanKendalaLampiran::class);
    }

    /**
     * Daftar SEMUA lampiran kendala ini, apapun sumbernya -- lampiran_path
     * lama (1 file, sebelum fitur multi-lampiran ada) ATAU baris-baris baru
     * di tabel laporan_kendala_lampirans (banyak file, semua format). Dipakai
     * SEMUA view yang nampilin lampiran kendala biar gak perlu tau bedanya
     * kendala lama vs baru -- tinggal loop 1 daftar ini, tiap item punya
     * ->path dan ->nama_asli. SENGAJA prioritasin laporan_kendala_lampirans
     * (kalau ada isinya) daripada lampiran_path lama, sama seperti
     * Laporan::getSemuaLampiranAttribute().
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
                'nama_asli' => basename($this->lampiran_path),
            ]]);
        }

        return collect();
    }
}
