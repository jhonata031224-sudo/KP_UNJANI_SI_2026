<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Daftar pilihan "Disposisi" (siapa yang harus menangani surat) &
     * "Tindakan" (apa yang harus dilakukan penerima) khusus form Buat
     * Surat Baru milik Danpus -- lihat LaporanSuratController::store().
     * Daftar ini DIAMBIL PERSIS dari lembar fisik "Disposisi
     * Wadanpussiberad" (kolom "Kepada Yth" utk Disposisi, dan daftar
     * checklist tindakan di bawah judul formnya) -- SENGAJA tidak
     * ditambah/dikurang supaya konsisten dengan lembar fisik yang dipakai
     * satuan.
     */
    const DISPOSISI_DANPUS_OPTIONS = [
        'DIRBINFUNG',
        'DIRBINUM',
        'DIRBINMAT',
        'DIRBINDIKLAT',
        'DANSATLAK DUKTEKSI',
        'DANSATLAK KALSI',
        'DANSATLAK DAKSI',
        'DANSATLAK SIBERSOS',
        'KAPOK ANALIS',
        'KABAGURDAL',
        'POKMIN',
    ];

    const TINDAKAN_DANPUS_OPTIONS = [
        'SESUAI JUK KOMANDAN',
        'ACC',
        'PEDOMAN',
        'SEBAGAI BAHAN',
        'PELAJARI',
        'KOORDINASIKAN',
        'CATAT',
        'HADIR',
        'WAKILI',
        'INGATKAN',
        'SARAN',
        'MENGHADAP',
        'LAPORKAN HASILNYA',
        'IKUTI PERKEMBANGANNYA',
        'SIAPKAN',
        'SELESAIKAN',
        'INFOKAN',
        'ARSIP',
        'UDL',
        'UDK',
        'UMP',
    ];

    const STATUS_SELESAI      = 'selesai';
    const STATUS_DITERUSKAN   = 'diteruskan';

    const DISPOSISI_WADAN_OPTIONS = self::DISPOSISI_DANPUS_OPTIONS;
    const TINDAKAN_WADAN_OPTIONS   = self::TINDAKAN_DANPUS_OPTIONS;

    /**
     * Disposisi surat AWAL dari Danpus -- karena Tujuan surat Danpus sekarang
     * dikunci ke Wadan (lihat modal Buat Surat Baru di laporan-danpus.blade.php),
     * Disposisi awalnya pun otomatis SELALU "WADAN", nggak ada pilihan lain
     * (beda dengan DISPOSISI_DANPUS_OPTIONS di atas yang dipakai Wadan buat
     * meneruskan surat lebih lanjut ke jajarannya sendiri).
     */
    const DISPOSISI_AWAL_DANPUS = 'WADAN';

    protected $fillable = [
        'satuan_id',
        'user_id',
        'tujuan_satuan_id',
        'perihal',
        'kategori',
        'deskripsi',
        'prioritas',
        'deadline_at',
        'disposisi',
        'tindakan',
        'siklus',
        'disposisi_terakhir',
        'tindakan_terakhir',
        'lampiran_path',
        'lampiran_nama_asli',
        'status',
        'is_selesai',
        'selesai_at',
        'selesai_oleh',
        'dikonfirmasi_at',
        'dikonfirmasi_oleh',
    ];

    protected $casts = [
        'deadline_at'       => 'datetime',
        'dikonfirmasi_at'   => 'datetime',
        'selesai_at'        => 'datetime',
        'is_selesai'        => 'boolean',
        'siklus'            => 'integer',
        'tindakan'          => 'array',
        'tindakan_terakhir' => 'array',
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

    public function selesaiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selesai_oleh');
    }

    public function riwayats(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LaporanSuratRiwayat::class, 'laporan_surat_id')->oldest();
    }

    public function tembusans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LaporanSuratTembusan::class, 'laporan_surat_id');
    }

    /**
     * Satuan PERANTARA di alur surat Danpus dari sisi PENGIRIM (Danpus -> Wadan -> Satuan).
     * Hanya Wadan: selama surat masih dipegang Wadan, alurnya belum tuntas.
     * Danpus BUKAN perantara lagi -- kalau surat buatan Danpus sudah kembali
     * ke Danpus dan dikonfirmasi, berarti putaran penuh sudah selesai.
     */
    const KODE_PERANTARA_SISI_PENGIRIM = ['WADAN'];

    /**
     * Alur sudah terpenuhi dari sudut pandang PENGIRIM ASLI (Danpus):
     *   - alur turun : Danpus membuat surat -> Wadan konfirmasi & meneruskan ->
     *                  satuan tujuan akhir konfirmasi, ATAU
     *   - alur naik  : satuan membalas -> Wadan meneruskan -> Danpus konfirmasi.
     * Artinya surat sudah berstatus dikonfirmasi dan pemegang saat ini
     * BUKAN Wadan. Surat seperti ini otomatis pindah dari Surat Keluar ke
     * Arsip Surat Danpus (tanpa menunggu aksi "Selesai" manual).
     */
    public function scopeAlurTuntasSisiPengirim(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DIKONFIRMASI)
            ->whereHas('tujuanSatuan', function ($t) {
                $t->whereNotIn('kode', self::KODE_PERANTARA_SISI_PENGIRIM);
            });
    }

    /**
     * ARSIP PUSAT (Urdal): semua surat yang sudah selesai, dari sisi manapun.
     *   - is_selesai = true (surat satuan lain -> Danpus yang dikonfirmasi
     *     Danpus, surat Danpus yang sudah berputar penuh, atau "Selesai" manual), ATAU
     *   - surat buatan Danpus yang alur turunnya sudah tuntas (Wadan sudah
     *     meneruskan & satuan tujuan akhir sudah konfirmasi).
     * Dipakai oleh Arsip Surat Urdal (render awal & polling realtime).
     */
    public function scopeArsipPusat(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('is_selesai', true)
              ->orWhere(function ($d) {
                  $d->whereHas('satuan', fn ($s) => $s->where('kode', 'DANPUS'))
                    ->alurTuntasSisiPengirim();
              });
        });
    }

    /**
     * Asal surat untuk filter Arsip Urdal:
     *   'danpus' = dikeluarkan dari sisi Danpus (pembuat surat = Danpus),
     *   'satuan' = dikeluarkan langsung oleh satuan lain ke Danpus.
     */
    public function asalArsip(): string
    {
        return strtoupper((string) ($this->satuan->kode ?? '')) === 'DANPUS' ? 'danpus' : 'satuan';
    }

    /** Kebalikan persis scopeAlurTuntasSisiPengirim() -- dipakai query Surat Keluar. */
    public function scopeAlurBelumTuntasSisiPengirim(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', '!=', self::STATUS_DIKONFIRMASI)
              ->orWhereNull('status')
              ->orWhereDoesntHave('tujuanSatuan', function ($t) {
                  $t->whereNotIn('kode', self::KODE_PERANTARA_SISI_PENGIRIM);
              });
        });
    }

    /**
     * ALUR NAIK (balasan): surat turun dari Danpus -> Wadan -> satuan
     * penerima (mis. Duktek). Setelah satuan itu konfirmasi & mengerjakan,
     * dia mengirim hasilnya balik LANGSUNG ke Wadan lewat tombol "Kirim
     * Surat" di kartu Surat Masuk (Urdal tidak jadi gerbang), lalu Wadan
     * meneruskan ke Danpus (LaporanSuratController::kembalikanKeDanpus()).
     *
     * Kode satuan yang TIDAK memakai tombol "Kirim Surat" itu:
     *   - DANPUS / WADAN : pelaku alur (pengirim & perantara), bukan pelaksana.
     *   - URDAL          : sudah punya jalur sendiri (teruskanKeWadan()).
     */
    const KODE_TANPA_BALASAN_NAIK = ['DANPUS', 'WADAN', 'URDAL'];

    public static function satuanBolehKirimBalasanNaik(?string $kodeSatuan): bool
    {
        $kode = strtoupper((string) $kodeSatuan);

        return $kode !== '' && ! in_array($kode, self::KODE_TANPA_BALASAN_NAIK, true);
    }

    /**
     * Surat turun dari Danpus yang SUDAH di-ACC satuan pemegangnya
     * ($satuanId) tapi belum dikirim balik. Selama begini, kartunya tetap di
     * Surat Masuk (dengan tombol "Kirim Surat"), BUKAN di Arsip Surat.
     * Begitu dikirim balik, tujuan_satuan_id pindah ke Wadan dan surat
     * otomatis keluar dari scope ini (lalu masuk Arsip lewat riwayat).
     */
    public function scopeMenungguBalasanSatuan(Builder $query, int $satuanId): Builder
    {
        return $query->where('tujuan_satuan_id', $satuanId)
            ->where('status', self::STATUS_DIKONFIRMASI)
            ->where('is_selesai', false)
            ->whereHas('satuan', fn ($s) => $s->where('kode', 'DANPUS'))
            ->whereHas('tujuanSatuan', fn ($t) => $t->whereNotIn('kode', self::KODE_TANPA_BALASAN_NAIK));
    }

    /**
     * Versi in-memory scopeMenungguBalasanSatuan() buat view (relasi satuan &
     * tujuanSatuan sudah di-eager-load) -- dipakai menentukan tombol
     * "Kirim Surat" di kartu Surat Masuk.
     */
    public function isMenungguBalasanSatuan(?int $satuanId): bool
    {
        if (! $satuanId || (int) $this->tujuan_satuan_id !== (int) $satuanId) {
            return false;
        }

        if ($this->status !== self::STATUS_DIKONFIRMASI || $this->isSelesai()) {
            return false;
        }

        return strtoupper((string) ($this->satuan->kode ?? '')) === 'DANPUS'
            && self::satuanBolehKirimBalasanNaik($this->tujuanSatuan->kode ?? null);
    }

    /**
     * Riwayat balasan naik pada SIKLUS SAAT INI (aksi SURAT_KELUAR). Dibatasi
     * per siklus supaya balasan dari siklus lama tidak "menempel" setelah
     * Danpus membuat disposisi ulang (siklus baru).
     */
    public function balasanNaikSiklusIni(): \Illuminate\Support\Collection
    {
        return $this->riwayats
            ->where('aksi', LaporanSuratRiwayat::AKSI_SURAT_KELUAR)
            ->where('siklus', (int) $this->siklus)
            ->values();
    }

    public function adaBalasanNaikSiklusIni(): bool
    {
        return $this->balasanNaikSiklusIni()->isNotEmpty();
    }

    public function isDikonfirmasi(): bool
    {
        return $this->status === self::STATUS_DIKONFIRMASI || $this->isSelesai();
    }

    public function isSelesai(): bool
    {
        return (bool) $this->is_selesai || $this->status === self::STATUS_SELESAI;
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

    // ------------------------------------------------------------------
    // Surat RAHASIA: siapa yang boleh melihat isinya (perihal & lampiran)
    // ------------------------------------------------------------------

    /** Label pengganti perihal bagi pihak yang tidak berhak melihat surat rahasia. */
    public const LABEL_PERIHAL_RAHASIA = 'Surat Rahasia';

    /** Disk PRIVAT untuk lampiran surat Rahasia (config/filesystems.php; tidak bisa dibuka lewat URL publik). */
    public const DISK_RAHASIA = 'rahasia';

    /**
     * Satuan yang TIDAK pernah berhak membaca isi surat rahasia walau terlibat di
     * alur: Wadan (hanya meneruskan), Urdal (view-only / arsip) & Admin (pengawas).
     */
    public const KODE_TANPA_AKSES_RAHASIA = ['WADAN', 'URDAL', 'ADMIN'];

    /** @var array<int,string> memo kode satuan per request. */
    private static array $kodeSatuanMemo = [];

    private static function kodeSatuan(int $satuanId): string
    {
        return self::$kodeSatuanMemo[$satuanId]
            ??= strtoupper((string) Satuan::whereKey($satuanId)->value('kode'));
    }

    /**
     * Boleh melihat ISI surat (perihal asli & lampiran)?
     *   - Bukan rahasia -> semua pihak.
     *   - Rahasia       -> pengirim asli (Danpus), dan satuan yang terlibat langsung
     *                      sebagai tujuan/penerima/pengirim langkah (mis. satuan tujuan
     *                      akhir & satuan pengirim balasan) KECUALI Wadan, Urdal, Admin
     *                      (lihat KODE_TANPA_AKSES_RAHASIA). Pihak lain (transit,
     *                      tembusan, view-only) hanya tahu "ada surat rahasia".
     * Alur surat TIDAK berubah: pihak yang tidak berhak tetap bisa meneruskan/
     * mengonfirmasi, hanya isinya tersembunyi.
     */
    public function bolehLihatIsi(?int $viewerSatuanId): bool
    {
        if (! $this->isRahasia()) {
            return true;
        }
        if (! $viewerSatuanId) {
            return false;
        }

        $v = (int) $viewerSatuanId;
        if ($v === (int) $this->satuan_id) {
            return true;
        }
        if (in_array(self::kodeSatuan($v), self::KODE_TANPA_AKSES_RAHASIA, true)) {
            return false;
        }
        if ($v === (int) $this->tujuan_satuan_id) {
            return true;
        }

        return $this->riwayats->contains(
            fn ($r) => in_array($v, [(int) $r->pengirim_satuan_id, (int) $r->penerima_satuan_id], true)
        );
    }

    /** Perihal yang boleh ditampilkan ke satuan $viewerSatuanId. */
    public function perihalUntuk(?int $viewerSatuanId): string
    {
        return $this->bolehLihatIsi($viewerSatuanId)
            ? (string) $this->perihal
            : self::LABEL_PERIHAL_RAHASIA;
    }

    /** Perihal untuk catatan netral (log aktivitas dsb.) -- tak pernah membocorkan perihal rahasia. */
    public function perihalLog(): string
    {
        return $this->isRahasia()
            ? self::LABEL_PERIHAL_RAHASIA . ' #' . $this->id
            : (string) $this->perihal;
    }

    /**
     * URL berkas lampiran untuk $viewerSatuanId. Surat biasa: URL publik seperti sebelumnya.
     * Surat rahasia: lewat route ber-otorisasi (berkas ada di disk privat); null bila tak berhak.
     */
    public function lampiranUrlUntuk(?string $path, ?int $viewerSatuanId, ?int $riwayatId = null): ?string
    {
        if (! $path) {
            return null;
        }
        if (! $this->isRahasia()) {
            return asset('storage/' . $path);
        }
        if (! $this->bolehLihatIsi($viewerSatuanId)) {
            return null;
        }

        return $riwayatId
            ? route('laporan-surat.lampiran-riwayat', [$this->id, $riwayatId])
            : route('laporan-surat.lampiran', $this->id);
    }

    /** Disk untuk menyimpan lampiran BARU milik surat ini. */
    public function diskLampiranBaru(): string
    {
        return $this->isRahasia() ? self::DISK_RAHASIA : 'public';
    }

    /** Hapus berkas lampiran dari disk publik DAN privat (berkas hanya ada di salah satunya). */
    public static function hapusBerkasLampiran(?string $path): void
    {
        if (! $path) {
            return;
        }
        Storage::disk('public')->delete($path);
        Storage::disk(self::DISK_RAHASIA)->delete($path);
    }

    /** Disk tempat berkas $path sebenarnya berada (privat lebih dulu, lalu publik); null bila tidak ada. */
    public static function diskLampiranAda(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        foreach ([self::DISK_RAHASIA, 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    /**
     * Status surat sudah "pindah tangan" (dikonfirmasi diterima oleh
     * pemegang saat ini) TAPI dilihat dari sudut pandang $viewerSatuanId
     * yang BUKAN pemegang saat ini -- artinya cuma satu langkah di alur
     * yang kelar, bukan keseluruhan surat. Dipakai supaya badge status
     * "Dikonfirmasi" nggak menyesatkan pengirim asli, seolah suratnya
     * sudah tuntas cuma karena penerima berikutnya (mis. Wadan) baru
     * konfirmasi terima -- padahal masih lanjut ke satuan lain / Urdal.
     */
    protected function isDikonfirmasiSepihakDariSudutPandang(?int $viewerSatuanId): bool
    {
        return $viewerSatuanId !== null
            && ! $this->isSelesai()
            && (int) $viewerSatuanId === (int) $this->satuan_id
            && (int) $this->tujuan_satuan_id !== (int) $viewerSatuanId
            && $this->status === self::STATUS_DIKONFIRMASI;
    }

    /**
     * @param  int|null  $viewerSatuanId  Satuan yang sedang melihat kartu ini
     *                                    (mis. dari Surat Keluar sisi pengirim).
     *                                    Opsional supaya pemanggilan lama tanpa
     *                                    argumen tetap jalan seperti biasa.
     */
    public function labelStatus(?int $viewerSatuanId = null): string
    {
        // Label "Selesai" HANYA untuk surat yang diselesaikan lewat aksi Selesai
        // eksplisit (status = Selesai). Surat yang tuntas otomatis -- mis. surat
        // satuan -> Danpus yang dikonfirmasi Danpus, atau putaran penuh surat
        // Danpus -- memang is_selesai = true (supaya pindah ke Arsip) tapi
        // statusnya tetap Dikonfirmasi, jadi labelnya juga "Dikonfirmasi".
        if ($this->isSelesai()) {
            return $this->status === self::STATUS_SELESAI ? 'Selesai' : 'Dikonfirmasi';
        }

        if ($this->isDikonfirmasiSepihakDariSudutPandang($viewerSatuanId)) {
            return 'Diteruskan';
        }

        return match ($this->status) {
            self::STATUS_DIKONFIRMASI => 'Dikonfirmasi',
            self::STATUS_DITERUSKAN   => 'Diteruskan',
            default                   => 'Menunggu Konfirmasi',
        };
    }

    public function badgeClass(?int $viewerSatuanId = null): string
    {
        if ($this->isSelesai()) {
            return $this->status === self::STATUS_SELESAI ? 'status-disetujui' : 'status-dikonfirmasi';
        }

        if ($this->isDikonfirmasiSepihakDariSudutPandang($viewerSatuanId)) {
            return 'status-sedang';
        }

        return match ($this->status) {
            self::STATUS_DIKONFIRMASI => 'status-dikonfirmasi',
            self::STATUS_DITERUSKAN   => 'status-sedang',
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
        $disk = self::diskLampiranAda($this->lampiran_path);
        if (! $disk) {
            return null;
        }

        $bytes = Storage::disk($disk)->size($this->lampiran_path);

        return $bytes < 1024 * 1024
            ? max(1, round($bytes / 1024)) . ' KB'
            : round($bytes / 1024 / 1024, 1) . ' MB';
    }
}
