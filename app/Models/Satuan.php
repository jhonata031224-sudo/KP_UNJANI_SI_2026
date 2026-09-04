<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Satuan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'kategori',
        'deskripsi',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Bagian nama sebelum tanda kurung, mis. "Sdir Binfung" dari
     * "Sdir Binfung (Pembinaan Fungsi)". Jika tidak ada kurung,
     * kembalikan nama lengkap.
     */
    public function getNamaSingkatAttribute(): string
    {
        $pos = strpos($this->nama, '(');
        return $pos !== false ? rtrim(substr($this->nama, 0, $pos)) : $this->nama;
    }

    /**
     * Bagian nama di dalam tanda kurung, mis. "Pembinaan Fungsi" dari
     * "Sdir Binfung (Pembinaan Fungsi)". Jika tidak ada kurung,
     * kembalikan nama lengkap.
     */
    public function getNamaKeteranganAttribute(): string
    {
        preg_match('/\(([^)]+)\)/', $this->nama, $m);
        return $m[1] ?? $this->nama;
    }

    /**
     * Modul dashboard yang bisa diatur hak aksesnya lewat "Manajemen Role &
     * Hak Akses" (Admin). Kunci dipakai sebagai nilai checkbox, label untuk
     * ditampilkan di UI.
     */
    public const MODUL_HAK_AKSES = [
        'laporan' => 'Kirim & Kelola Laporan',
        'monitoring' => 'Monitoring Laporan & Aktivitas',
        'notifikasi' => 'Notifikasi',
    ];

    /**
     * Penjelasan singkat per modul, dipakai di halaman Admin > Hak Akses
     * Pengguna supaya jelas menu apa yang muncul/hilang saat modul itu
     * dicentang, tanpa perlu buka-tutup dashboard satuan yang bersangkutan
     * untuk mengecek sendiri.
     */
    public const MODUL_HAK_AKSES_DESKRIPSI = [
        'laporan' => 'Bisa membuat & mengirim laporan kegiatan ke satuan lain, lalu memantau statusnya (menunggu, disetujui, revisi, atau ditolak).',
        'monitoring' => 'Bisa memantau (lihat saja, tanpa mengelola) laporan dan aktivitas dari satuan-satuan lain.',
        'notifikasi' => 'Menerima notifikasi lonceng otomatis di dashboard saat ada laporan baru, permintaan laporan, atau aktivitas penting lainnya.',
    ];

    /**
     * Matriks modul yang relevan dengan tanggung jawab tiap role/satuan.
     * Ini dipakai Admin saat mengatur Role & Hak Akses agar pilihan modul
     * tidak lagi sama untuk seluruh role.
     */
    public const MODUL_HAK_AKSES_PER_ROLE = [
        'ADMIN' => ['laporan', 'monitoring', 'notifikasi'],
        'DANPUS' => ['laporan', 'monitoring', 'notifikasi'],
        'WADAN' => ['laporan', 'monitoring', 'notifikasi'],
        'SATLAKKAL' => ['laporan', 'monitoring', 'notifikasi'],
        'SATLAKSISOS' => ['laporan', 'notifikasi'],
        'SATLAKDAK' => ['laporan', 'monitoring', 'notifikasi'],
        'SATLAKDUKTEK' => ['laporan', 'monitoring', 'notifikasi'],
        'BINFUNG' => ['laporan', 'monitoring', 'notifikasi'],
        'BINUM' => ['laporan', 'monitoring', 'notifikasi'],
        'DIKLAT' => ['laporan', 'monitoring', 'notifikasi'],
        'BINMAT' => ['laporan', 'monitoring', 'notifikasi'],
        'POKANALIS' => ['laporan', 'monitoring', 'notifikasi'],
        'URDAL' => ['laporan', 'monitoring', 'notifikasi'],
    ];

    public static function modulHakAksesUntukRole(?string $kode): array
    {
        $kode = strtoupper(trim((string) $kode));
        $keys = self::MODUL_HAK_AKSES_PER_ROLE[$kode] ?? array_keys(self::MODUL_HAK_AKSES);

        return array_intersect_key(self::MODUL_HAK_AKSES, array_flip($keys));
    }

    public static function modulHakAksesKeysUntukRole(?string $kode): array
    {
        return array_keys(self::modulHakAksesUntukRole($kode));
    }

    /**
     * Modul yang BENAR-BENAR aktif untuk satuan ini saat ini -- dipakai baik
     * untuk render menu (sembunyikan modul yang dimatikan) maupun untuk
     * enforcement di EnsureModulAktif (blokir route kalau modul mati).
     *
     * - Kalau Admin BELUM PERNAH menyimpan Hak Akses untuk satuan ini
     *   (kolom `permissions` masih null), semua modul yang relevan untuk
     *   role tsb tetap aktif -- supaya satuan lama/baru tidak mendadak
     *   ke-lockout hanya karena belum pernah disentuh dari halaman Hak
     *   Akses.
     * - Begitu Admin sudah pernah simpan (permissions berupa array, bisa
     *   saja kosong), itulah yang jadi acuan -- tetap dibatasi supaya tidak
     *   melampaui matriks role (MODUL_HAK_AKSES_PER_ROLE).
     */
    public function modulAktifKeys(): array
    {
        $allowedUntukRole = self::modulHakAksesKeysUntukRole($this->kode);

        if ($this->permissions === null) {
            return $allowedUntukRole;
        }

        return array_values(array_intersect($this->permissions, $allowedUntukRole));
    }

    /**
     * Cek apakah satu modul ('laporan', 'monitoring', 'notifikasi') aktif
     * untuk satuan ini. Role ADMIN SELALU dianggap aktif untuk semua modul
     * -- supaya Admin tidak bisa mengunci dirinya sendiri lewat halaman Hak
     * Akses miliknya sendiri.
     */
    public function modulAktif(string $key): bool
    {
        if (strtoupper(trim((string) $this->kode)) === 'ADMIN') {
            return true;
        }

        return in_array($key, $this->modulAktifKeys(), true);
    }

    /**
     * Kategori satuan yang tersedia, dipakai untuk pengelompokan di dropdown login.
     */
    public const KATEGORI_SATLAK = 'satlak';
    public const KATEGORI_DIREKTORAT = 'direktorat';
    public const KATEGORI_PIMPINAN = 'pimpinan';
    public const KATEGORI_ADMIN = 'admin';
    /**
     * Unsur Pelayanan -- satuan yang langsung di bawah/melayani Danpus untuk
     * urusan dalam (Urdal), tidak masuk kelompok Direktorat
     * (Sdir/Binfung/Binum/dst), Satlak, maupun Pimpinan. Lapor ke
     * DANPUS/WADAN (lihat kodeTujuanUntuk()).
     */
    public const KATEGORI_UNSUR_PELAYANAN = 'unsur_pelayanan';
    /**
     * Unsur Pembantu Pimpinan -- satuan yang langsung di bawah/melayani
     * Danpus untuk analisis dan kajian (Pok Analis), tidak masuk kelompok
     * Direktorat (Sdir/Binfung/Binum/dst), Satlak, maupun Pimpinan. Lapor ke
     * DANPUS/WADAN (lihat kodeTujuanUntuk()).
     */
    public const KATEGORI_UNSUR_PEMBANTU_PIMPINAN = 'unsur_pembantu_pimpinan';
    /**
     * Kategori Kotama (Komando Utama) -- 21 Sansidam aktif di lingkungan TNI AD,
     * kelompok satuan di luar Satlak/Direktorat/Pimpinan/Unsur
     * Pelayanan/Unsur Pembantu Pimpinan/Admin.
     */
    public const KATEGORI_KOTAMA = 'kotama';

    /**
     * Urutan tampil kategori secara umum (Admin -> Pimpinan -> Unsur
     * Pelayanan -> Unsur Pembantu Pimpinan -> Direktorat -> Satlak ->
     * Kotama), dipakai di seluruh tempat yang menampilkan daftar satuan
     * gabungan lintas kategori -- menggantikan field "urutan" manual yang
     * sudah dihapus.
     *
     * Unsur Pelayanan (Urdal) dan Unsur Pembantu Pimpinan (Pok Analis)
     * sengaja ditaruh setelah Pimpinan (Wadan) dan sebelum Direktorat (4
     * Sdir) sesuai urutan organisasi yang diminta. Kotama ditaruh paling
     * akhir karena kategori paling baru.
     */
    public static function prioritasKategori(): array
    {
        return [
            self::KATEGORI_ADMIN => 1,
            self::KATEGORI_PIMPINAN => 2,
            self::KATEGORI_UNSUR_PELAYANAN => 3,
            self::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 4,
            self::KATEGORI_DIREKTORAT => 5,
            self::KATEGORI_SATLAK => 6,
            self::KATEGORI_KOTAMA => 7,
        ];
    }

    /**
     * Urutan tampil satuan di DALAM satu kategori yang sama (dipakai sebagai
     * tie-breaker setelah prioritasKategori()), berdasarkan kode -- BUKAN
     * alfabet nama. Dulu tie-breaker-nya alfabet nama, tapi begitu nama
     * satuan Direktorat/Satlak diubah pakai prefix "Sdir"/"Satlak" hasil
     * alfabetnya jadi acak dan tidak sesuai urutan organisasi yang diminta.
     * Kode dipilih sebagai kunci karena kode tidak pernah berubah walau
     * nama tampilnya diubah-ubah.
     */
    public static function urutanDalamKategori(): array
    {
        return [
            'ADMIN' => 1,
            'DANPUS' => 1,
            'WADAN' => 2,
            'BINFUNG' => 1,
            'BINUM' => 2,
            'DIKLAT' => 3,
            'BINMAT' => 4,
            'URDAL' => 1,
            'POKANALIS' => 1,
            'SATLAKKAL' => 1,
            'SATLAKDAK' => 2,
            'SATLAKSISOS' => 3,
            'SATLAKDUKTEK' => 4,
        ] + self::urutanKotama();
    }

    /**
     * Urutan 21 Sansidam aktif (kategori Kotama) supaya tampil berurutan
     * GEOGRAFIS dari Barat ke Timur (Aceh -> Papua) sesuai KODE_KOTAMA,
     * bukan alfabet kode/nama maupun nomor Sansidam.
     */
    private static function urutanKotama(): array
    {
        $urutan = 1;

        return collect(self::KODE_KOTAMA)
            ->mapWithKeys(fn ($kode) => [$kode => $urutan++])
            ->all();
    }

    /**
     * Kunci urut gabungan (kategori lalu urutan-dalam-kategori) yang dipakai
     * di semua tempat yang menampilkan daftar satuan/pengguna lintas
     * kategori, supaya urutannya konsisten di seluruh sistem.
     */
    public static function kunciUrutSatuan(?string $kategori, ?string $kode): string
    {
        $prioritasKategori = self::prioritasKategori()[$kategori ?? ''] ?? 9;
        $urutanDalamKategori = self::urutanDalamKategori()[strtoupper($kode ?? '')] ?? 99;

        return sprintf('%d-%03d', $prioritasKategori, $urutanDalamKategori);
    }

    /**
     * Semua satuan (dengan users_count) terurut persis seperti yang tampil di
     * daftar: kategori (prioritasKategori) -> urutan-dalam-kategori (kode) ->
     * created_at -> id. Satuan baru yang kodenya belum terdaftar di
     * urutanDalamKategori() jatuh ke akhir grup kategorinya.
     *
     * Dipakai bareng oleh DashboardController (render awal tabel Data Satuan)
     * dan SatuanController (respons AJAX Tambah/Ubah) supaya urutannya tetap
     * sama setelah submit tanpa reload.
     */
    public static function terurut(): \Illuminate\Support\Collection
    {
        return static::withCount('users')->get()
            ->sort(function ($a, $b) {
                $kunciA = self::kunciUrutSatuan($a->kategori, $a->kode);
                $kunciB = self::kunciUrutSatuan($b->kategori, $b->kode);
                if ($kunciA !== $kunciB) return $kunciA <=> $kunciB;
                if ($a->created_at != $b->created_at) return $a->created_at <=> $b->created_at;
                return $a->id <=> $b->id;
            })
            ->values();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Laporan yang dikirim oleh satuan ini (mis. laporan dari Satuan Pelaksanaan Dukungan Teknologi).
     */
    public function laporanTerkirim(): HasMany
    {
        return $this->hasMany(Laporan::class, 'satuan_id');
    }

    /**
     * Permintaan laporan yang ditujukan ke satuan ini (dari Danpus/Wadan).
     */
    public function permintaanLaporanMasuk(): HasMany
    {
        return $this->hasMany(PermintaanLaporan::class, 'tujuan_satuan_id');
    }

    /**
     * Laporan yang ditujukan ke satuan ini (mis. laporan masuk ke DANPUS).
     */
    public function laporanDiterima(): HasMany
    {
        return $this->hasMany(Laporan::class, 'tujuan_satuan_id');
    }

    /**
     * Tembusan laporan kendala Kasansi yang masuk ke satuan ini (khusus 4
     * Satlak/4 Sdir) -- lihat LaporanKendalaTembusan.
     */
    public function tembusanKendalaMasuk(): HasMany
    {
        return $this->hasMany(LaporanKendalaTembusan::class, 'satuan_id');
    }

    /**
     * Akun media sosial resmi yang dikelola satuan ini (mis. akun Instagram
     * resmi Satuan Pelaksanaan Siber Sosial) — dipakai fitur manajemen & posting konten.
     */
    public function akunMedsos(): HasMany
    {
        return $this->hasMany(AkunMedsos::class);
    }

    /**
     * Seluruh postingan media sosial yang dibuat oleh satuan ini.
     */
    public function postingan(): HasMany
    {
        return $this->hasMany(Postingan::class);
    }

    /**
     * Personel yang saat ini ditempatkan di satuan ini — dipakai fitur
     * Administrasi Personel (Binfung).
     */
    public function personels(): HasMany
    {
        return $this->hasMany(Personel::class);
    }

    /**
     * Kode 4 Satlak operasional (Penangkalan, Siber Sosial, Penindakan, Duktek).
     */
    public const KODE_SATLAK = ['SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK', 'SATLAKDUKTEK'];

    /**
     * Kode satuan pembinaan/direktorat (Binfung, Binum, Diklat, Binmat).
     */
    public const KODE_PEMBINAAN = ['BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT'];

    /**
     * Kode 8 satuan (4 Satlak operasional + 4 Sdir/pembinaan) yang boleh
     * jadi tujuan TEMBUSAN (CC) laporan kendala Kasansi -- sekadar info
     * koordinasi, bukan tujuan approval. Tujuan approval laporan kendala
     * tetap satu-satunya DANPUS, lihat LaporanKendalaController.
     */
    public static function kodeTembusanKasansi(): array
    {
        return array_merge(self::KODE_SATLAK, self::KODE_PEMBINAAN);
    }

    /**
     * Kode satuan Unsur Pelayanan -- langsung di bawah/melayani Danpus untuk
     * urusan dalam, bukan bagian Direktorat/Satlak/Pimpinan (Urdal).
     */
    public const KODE_UNSUR_PELAYANAN = ['URDAL'];
    /**
     * Kode satuan Unsur Pembantu Pimpinan -- langsung di bawah/melayani
     * Danpus untuk analisis dan kajian, bukan bagian Direktorat/Satlak/
     * Pimpinan (Pok Analis).
     */
    public const KODE_UNSUR_PEMBANTU_PIMPINAN = ['POKANALIS'];

    /**
     * Kode 21 Sansidam aktif (kategori Kotama), diurutkan GEOGRAFIS dari Barat
     * ke Timur (Aceh -> Papua): Iskandar Muda, Bukit Barisan, Tuanku
     * Tambusai, Tuanku Imam Bonjol, Sriwijaya, Radin Inten, Siliwangi,
     * Jayakarta, Diponegoro, Brawijaya, Udayana, Tanjungpura, Tambun
     * Bungai, Mulawarman, Palaka Wira, Hasanuddin, Merdeka, Pattimura,
     * Kasuari, Cenderawasih, Mandala Trikora. Kode dibuat dari nama/julukan
     * wilayah (bukan penomoran generik dam1, dam2, dst).
     */
    public const KODE_KOTAMA = [
        'ISKANDARMUDA', 'BUKITBARISAN', 'TUANKUTAMBUSAI', 'IMAMBONJOL',
        'SRIWIJAYA', 'RADININTEN', 'SILIWANGI', 'JAYA', 'DIPONEGORO',
        'BRAWIJAYA', 'UDAYANA', 'TANJUNGPURA', 'TAMBUNBUNGAI', 'MULAWARMAN',
        'PALAKAWIRA', 'HASANUDDIN', 'MERDEKA', 'PATTIMURA', 'KASUARI',
        'CENDERAWASIH', 'MANDALATRIKORA',
    ];

    /**
     * Alur tujuan laporan resmi (hierarki komando):
     * - Satlak hanya boleh lapor ke DANPUS/WADAN (tujuan utama).
     *   Satlak tidak boleh saling kirim ke sesama Satlak maupun ke satuan pembinaan.
     * - Satuan pembinaan (Binmat, Binfung, Binum, Diklat), Unsur Pelayanan
     *   (Urdal), Unsur Pembantu Pimpinan (Pok Analis), dan 21 Sansidam
     *   (Kotama) lapor ke DANPUS/WADAN -- sama seperti Satlak. WADAN ikut
     *   diizinkan karena WADAN juga boleh MEMBUAT Permintaan Laporan untuk
     *   semua kategori satuan itu (lihat PermintaanLaporanController::
     *   pengirimKode()); tanpa WADAN di sini, unit yang dapat permintaan dari
     *   WADAN tidak punya tujuan yang valid untuk mengirim balik progresnya.
     * - Satuan lain (mis. WADAN) tidak dibatasi di sini (kembalikan null).
     *
     * @return string[]|null Daftar kode satuan tujuan yang diizinkan, atau null jika tidak dibatasi.
     */
    public static function kodeTujuanUntuk(?string $kodeAsal): ?array
    {
        $kodeAsal = strtoupper((string) $kodeAsal);

        if (in_array($kodeAsal, self::KODE_SATLAK, true)) {
            return ['DANPUS', 'WADAN'];
        }

        if (in_array($kodeAsal, self::KODE_PEMBINAAN, true)
            || in_array($kodeAsal, self::KODE_UNSUR_PELAYANAN, true)
            || in_array($kodeAsal, self::KODE_UNSUR_PEMBANTU_PIMPINAN, true)) {
            return ['DANPUS', 'WADAN'];
        }

        // 21 Sansidam (kategori Kotama) lapor ke DANPUS/WADAN, sama seperti
        // satuan pembinaan, Unsur Pelayanan, dan Unsur Pembantu Pimpinan.
        if (in_array($kodeAsal, self::KODE_KOTAMA, true)) {
            return ['DANPUS', 'WADAN'];
        }

        return null;
    }
}
