<?php

namespace App\Models;

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
 * Tujuan resmi (tujuan_satuan_id) TETAP selalu DANPUS. Tapi kalau Kasansi
 * memilih tembusan saat mengirim, laporan tidak langsung sampai ke Danpus --
 * statusnya Menunggu Tembusan dulu sampai minimal satu satuan tembusan
 * memberi feedback, baru Kasansi bisa menekan "Kirim ke Danpus"
 * (LaporanKendalaController::teruskan()) yang mengubah status jadi Menunggu
 * seperti biasa dan BARU DI SITU Danpus diberi tahu. Laporan tanpa tembusan
 * tetap langsung Menunggu seperti alur lama.
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
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'diteruskan_at' => 'datetime',
    ];

    public const STATUS_MENUNGGU_TEMBUSAN = 'Menunggu Balasan';
    public const STATUS_MENUNGGU = 'Menunggu';
    public const STATUS_DITINDAKLANJUTI = 'Ditindaklanjuti';
    public const STATUS_SELESAI = 'Selesai';
    public const STATUS_DITOLAK = 'Ditolak';
    public const STATUS_DIKONFIRMASI = 'Dikonfirmasi';

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
     * Tembusan (CC) laporan kendala ini ke satuan lain (4 Satlak/4 Sdir).
     * Kalau ada, laporan ini mampir dulu ke sini (status Menunggu Tembusan)
     * sebelum Kasansi meneruskannya ke DANPUS -- tapi tembusan sendiri
     * TETAP TIDAK PERNAH mengubah status Menunggu/Ditindaklanjuti/Selesai/
     * Ditolak/Dikonfirmasi di atas, itu murni wewenang DANPUS. Satu-satunya
     * pengaruh tembusan adalah lewat feedback-nya yang membuka tombol
     * "Kirim ke Danpus" milik Kasansi, lihat siapDiteruskan().
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

    /**
     * True kalau laporan ini sedang mampir di tembusan (Menunggu Tembusan)
     * DAN minimal satu satuan tembusan sudah memberi feedback -- artinya
     * Kasansi sudah boleh menekan "Kirim ke Danpus".
     */
    public function siapDiteruskan(): bool
    {
        return $this->status === self::STATUS_MENUNGGU_TEMBUSAN
            && $this->tembusans->contains(fn (LaporanKendalaTembusan $t) => filled($t->feedback));
    }
}
