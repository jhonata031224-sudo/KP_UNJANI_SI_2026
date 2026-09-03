<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Surat yang dikirim satuan Kasansi (21 Sansidam) ke SATU satuan tujuan
 * bebas (dipilih sendiri, bisa satuan mana saja di sistem), TANPA tembusan.
 *
 * Alur status:
 *   - 'menunggu_konfirmasi' : baru terkirim, belum dikonfirmasi penerima.
 *                             Tampil di tabel Kirim Surat sisi pengirim.
 *   - 'dikonfirmasi'        : penerima sudah konfirmasi. Baru masuk ke
 *                             Arsip Surat sisi pengirim (Kasansi).
 *
 * Sisi penerima selalu melihat surat di Surat Masuk (dalam grup menu Surat),
 * apapun statusnya, dengan tombol Konfirmasi jika masih menunggu.
 */
class LaporanSurat extends Model
{
    use HasFactory;

    const STATUS_MENUNGGU    = 'menunggu_konfirmasi';
    const STATUS_DIKONFIRMASI = 'dikonfirmasi';

    protected $fillable = [
        'satuan_id',
        'user_id',
        'tujuan_satuan_id',
        'perihal',
        'kategori',
        'deskripsi',
        'prioritas',
        'lampiran_path',
        'lampiran_nama_asli',
        'status',
        'dikonfirmasi_at',
        'dikonfirmasi_oleh',
    ];

    protected $casts = [
        'dikonfirmasi_at' => 'datetime',
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

    public function dikonfirmasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikonfirmasi_oleh');
    }

    public function isDikonfirmasi(): bool
    {
        return $this->status === self::STATUS_DIKONFIRMASI;
    }

    public function labelStatus(): string
    {
        return match ($this->status) {
            self::STATUS_DIKONFIRMASI => 'Dikonfirmasi',
            default                   => 'Menunggu Konfirmasi',
        };
    }

    public function badgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DIKONFIRMASI => 'status-dikonfirmasi',
            default                   => 'status-menunggu',
        };
    }

    /**
     * Ukuran file lampiran, sudah diformat (mis. "256 KB") -- dipakai kartu
     * & modal detail Surat buat nampilin ukuran dokumen tanpa nyimpen kolom
     * baru, tinggal hitung dari file aslinya di storage tiap dibutuhkan.
     */
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
}
