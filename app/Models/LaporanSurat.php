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
 *                             Tampil di tabel Surat Keluar sisi pengirim.
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

    /**
     * Fondasi alur "Surat dari Danpus": prioritas KHUSUS dipakai saat
     * pengirim surat adalah Danpus (lihat LaporanSuratController::store()).
     * Satuan lain (Kasansi, Satlak, Sdir, Urdal, Pok Analis, Wadan, dst)
     * TETAP pakai skema lama Tinggi/Sedang/Rendah -- TIDAK diubah.
     *
     * Pemetaan istilah lama -> baru (khusus sisi Danpus):
     *   Rendah -> Biasa   | Sedang -> Kilat  | Tinggi -> Rahasia
     *
     * Prioritas cuma ditentukan Danpus sekali, pas surat pertama dibuat
     * (lewat store()) -- SENGAJA tidak ada route/aksi buat mengubah
     * prioritas surat yang sudah ada (lihat komentar di
     * LaporanSuratController), jadi begitu surat terkirim ke satuan/
     * pejabat lain, penerima otomatis tidak bisa mengubahnya.
     */
    const PRIORITAS_DANPUS_BIASA   = 'Biasa';
    const PRIORITAS_DANPUS_KILAT   = 'Kilat';
    const PRIORITAS_DANPUS_RAHASIA = 'Rahasia';

    const PRIORITAS_DANPUS = [
        self::PRIORITAS_DANPUS_BIASA,
        self::PRIORITAS_DANPUS_KILAT,
        self::PRIORITAS_DANPUS_RAHASIA,
    ];

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

    /**
     * Surat prioritas "Rahasia" (khusus surat dari Danpus, lihat
     * PRIORITAS_DANPUS_RAHASIA di atas).
     */
    public function isRahasia(): bool
    {
        return $this->prioritas === self::PRIORITAS_DANPUS_RAHASIA;
    }

    /**
     * Isi ringkasan surat yang boleh ditampilkan ke satuan $viewerSatuanId.
     * Aturan ringkasan (fondasi alur surat Danpus):
     *   - Biasa & Kilat -> ringkasan tetap ditampilkan ke siapapun.
     *   - Rahasia       -> ringkasan dikosongkan buat siapapun SELAIN
     *                      satuan pengirim asli (satuan_id) -- penerima
     *                      cuma lihat surat sudah masuk (perihal, kategori,
     *                      lampiran, dll) tanpa isi ringkasannya.
     */
    public function ringkasanUntuk(?int $viewerSatuanId): string
    {
        if ($this->isRahasia() && (int) $viewerSatuanId !== (int) $this->satuan_id) {
            return '';
        }

        return (string) $this->deskripsi;
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
