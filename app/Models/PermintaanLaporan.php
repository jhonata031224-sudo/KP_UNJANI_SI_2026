<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanLaporan extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('hideArchivedOnPimpinanDashboard', function ($query): void {
            $user = request()->user();
            $kode = $user?->satuan?->kode ? strtoupper(trim($user->satuan->kode)) : null;

            // Hanya berlaku pada initial Dashboard Pimpinan/Danpus.
            // Endpoint realtime/riwayat tetap bisa mengambil data arsip
            // karena tidak melewati kondisi request /dashboard ini.
            if (request()->is('dashboard') && in_array($kode, ['DANPUS', 'WADAN'], true)) {
                $query->whereNull($query->getModel()->getTable() . '.archived_at');
            }
        });
    }

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
        'archived_at',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
        'dikerjakan_at' => 'datetime',
        'selesai_at' => 'datetime',
        'dibatalkan_at' => 'datetime',
        'archived_at' => 'datetime',
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

    public function tasks(): HasMany
    {
        return $this->hasMany(PermintaanLaporanTask::class)->orderBy('urutan');
    }

    /**
     * Progres kini dihitung dari checklist task, bukan diketik manual.
     * Fallback ke nilai `progres` yang sudah tersimpan kalau permintaan ini
     * belum punya task sama sekali (row lama dari sebelum fitur checklist).
     */
    public function hitungProgresDariTask(): int
    {
        if ($this->tasks->isEmpty()) {
            return (int) $this->progres;
        }

        return (int) round($this->tasks->where('selesai', true)->count() / $this->tasks->count() * 100);
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

    /**
     * Laporan TERAKHIR yang pernah dikirim untuk permintaan ini (progress-log
     * maupun final) -- dipakai buat deteksi "sedang direvisi" di bawah,
     * karena status Revisi cuma nempel di baris Laporan-nya, bukan di
     * PermintaanLaporan (yang balik ke STATUS_DIKERJAKAN biasa).
     */
    public function laporanTerakhir(): ?Laporan
    {
        return $this->laporans->sortByDesc('id')->first();
    }

    /**
     * Sedang dikerjakan KARENA baru dibalikin Pimpinan buat direvisi
     * (beda dari "Sedang dikerjakan" biasa yang belum pernah dikirim sama
     * sekali) -- dipakai buat nyeragamin tampilan status/aksi di semua
     * role, bukan cuma di dashboard Pimpinan.
     */
    public function isSedangRevisi(): bool
    {
        if ($this->status !== self::STATUS_DIKERJAKAN) {
            return false;
        }

        $terakhir = $this->laporanTerakhir();
        if (! $terakhir) {
            return false;
        }

        $statusTerakhir = strtolower((string) $terakhir->status);

        // Dua jalur yang sama-sama berarti "laporan lagi diulang satuan":
        // (a) keputusan "Revisi" eksplisit dari Pimpinan (LaporanController::
        //     updateStatus) -- status laporan terakhir mengandung "revisi".
        // (b) laporan final DITOLAK, lalu permintaan dibuka lagi dari Riwayat
        //     (revisiDariRiwayat -> status balik Sedang dikerjakan + laporan_id
        //     direset null). Status laporannya bisa jadi masih "Ditolak" kalau
        //     revisi-nya dilakukan sebelum flip status ditambahkan -- makanya
        //     tetap dihitung revisi di sini asal laporan_id sudah null.
        // Checkpoint progres di tengah jalan (STATUS_PROGRES) TIDAK dihitung --
        // itu alur "lanjut selesaikan task", bukan "kirim ulang laporan".
        return str_contains($statusTerakhir, 'revisi')
            || ($this->laporan_id === null
                && $terakhir->status !== Laporan::STATUS_PROGRES
                && str_contains($statusTerakhir, 'tolak'));
    }

    public function statusTampilan(): string
    {
        if ($this->isTerlambat()) {
            return 'Terlambat';
        }

        if ($this->isSedangRevisi()) {
            return 'Revisi';
        }

        return $this->status;
    }
}
