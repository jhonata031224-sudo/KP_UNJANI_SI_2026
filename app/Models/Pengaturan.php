<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $fillable = [
        'nama_instansi','singkatan','logo_path','alamat','email_kontak','telepon_kontak',
        'hero_eyebrow','hero_judul_awal','hero_judul_aksen','hero_subjudul','hero_deskripsi','hero_image_path',
        'fitur','tentang_deskripsi','tentang_nama_resmi','tentang_nama_lama','tentang_fungsi_utama',
        'tentang_moto_judul','tentang_moto_deskripsi','website','sosial_media','landing_content',
        'notifikasi_push_aktif','struktur_organisasi_path',
    ];

    protected $casts = ['fitur' => 'array','sosial_media' => 'array','landing_content' => 'array','notifikasi_push_aktif' => 'boolean'];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'nama_instansi' => 'Pusat Siber dan Sandi Angkatan Darat',
            'singkatan' => 'Pussiberad',
            'alamat' => 'Jl. Veteran No. 5, Gambir, Jakarta Pusat, DKI Jakarta 10110',
            'telepon_kontak' => '(021) 3849192',
            'hero_eyebrow' => 'PUSSIBERAD SISTEM PENDUKUNG OPERASIONAL',
            'hero_judul_awal' => 'DT-PHATRAM-', 'hero_judul_aksen' => '2639',
            'hero_subjudul' => 'Sistem Informasi Berbasis Elektronik Angkatan Darat',
            'hero_deskripsi' => 'Sistem pendukung operasional Satuan Siber AD dan jajaran Pusat Siber TNI Angkatan Darat — mulai dari input laporan, hingga visualisasi real-time bagi pengambil keputusan.',
            'fitur' => [
                ['judul'=>'Real-time','deskripsi'=>'Status dan perkembangan laporan diperbarui otomatis, sehingga tidak perlu direkap manual setiap saat.'],
                ['judul'=>'Terpusat','deskripsi'=>'Laporan beserta dokumen pendukungnya tersimpan rapi dalam satu sistem yang sama, sehingga lebih mudah ditemukan.'],
                ['judul'=>'Efisien','deskripsi'=>'Alur persetujuan bertingkat membantu mempercepat proses dibanding pengumpulan laporan secara manual.'],
                ['judul'=>'Aman & Terkontrol','deskripsi'=>'Akses terhadap laporan diatur sesuai peran pengguna, sehingga hanya pihak berwenang yang dapat melihat atau mengubah.'],
            ],
            'tentang_deskripsi' => "Pussiberad bukan sebuah perusahaan komersial, melainkan satuan resmi di bawah TNI Angkatan Darat yang dibentuk untuk menyelenggarakan pembinaan personel serta fungsi sandi dan siber dalam rangka membantu tugas TNI-AD. Satuan ini bernama Pusat Siber Angkatan Darat (Pussiberad), sebelumnya bernama Pusat Sandi dan Siber TNI Angkatan Darat (Pussansiad).\n\nPembentukan satuan ini merupakan hasil pengembangan Organisasi dan Tugas (Orgas) baru di lingkungan TNI-AD, sesuai Peraturan KASAD Nomor 26 Tahun 2019 tanggal 26 Desember 2019 tentang Organisasi dan Tugas Markas Besar TNI Angkatan Darat, Bab IV Tugas dan Tanggung Jawab, Pasal 35 Pussansiad.",
            'tentang_nama_resmi' => 'Pusat Siber Angkatan Darat (Pussiberad)',
            'tentang_nama_lama' => 'Pusat Sandi dan Siber TNI Angkatan Darat (Pussansiad)',
            'tentang_fungsi_utama' => 'Pertahanan siber, sandi, serta penanganan insiden keamanan informasi di lingkungan TNI AD.',
            'tentang_moto_judul'=>'Satria Yudha Waskita',
            'tentang_moto_deskripsi'=>'Semboyan resmi Pussansiad/Pussiberad ini diambil dari bahasa Sanskerta/Jawa Kuno, yang secara harfiah berarti "prajurit perang yang ahli, bijaksana, dan waspada" — menggambarkan identitas serta tugas utama prajurit siber TNI AD sebagai garda terdepan pertahanan digital bangsa.',
            'website'=>'https://tni-ad.mil.id/',
            'sosial_media'=>[
                ['platform' => 'instagram', 'label' => 'Instagram @pussiberad', 'url' => 'https://www.instagram.com/pussiberad?igsh=MTA1N2tuMHRobzE5OQ=='],
                ['platform' => 'tiktok', 'label' => 'TikTok @pusat.siber_ad', 'url' => 'https://www.tiktok.com/@pusat.siber_ad?_r=1&_t=ZS-98XYV7h9dfs'],
                ['platform' => 'youtube', 'label' => 'YouTube TNI Angkatan Darat', 'url' => 'https://www.youtube.com/@tniangkatandarat'],
                ['platform' => 'x', 'label' => 'X (Twitter) @tni_ad', 'url' => 'https://x.com/tni_ad'],
                ['platform' => 'facebook', 'label' => 'Facebook TNI Angkatan Darat', 'url' => 'https://web.facebook.com/TNIAngkatanDarat'],
                ['platform' => 'wikipedia', 'label' => 'Profil Resmi', 'url' => 'https://id.wikipedia.org/wiki/Pusat_Sandi_dan_Siber_Angkatan_Darat'],
            ],
            'landing_content'=>self::defaultLandingContent(),
            'notifikasi_push_aktif'=>true,
        ]);
    }

    public static function defaultLandingContent(): array
    {
        return [
            'brand'=>['name'=>'DT-PHATRAM-','accent'=>'2639','tagline'=>'Pussiberad · TNI AD'],
            'meta'=>['title'=>'DT-PHATRAM-2639 — Sistem Informasi Berbasis Elektronik Angkatan Darat | PUSSIBERAD','description'=>'DT-PHATRAM-2639 — Sistem Informasi Berbasis Elektronik Angkatan Darat. Platform pelaporan dan monitoring resmi Pusat Siber Angkatan Darat (PUSSIBERAD).'],
            'nav'=>[['label'=>'Beranda','url'=>'#tentang'],['label'=>'Fitur','url'=>'#fitur'],['label'=>'Tentang','url'=>'#tentang-pussiberad'],['label'=>'Kontak','url'=>'#tim']],
            'hero'=>['button_label'=>'Selengkapnya','button_url'=>'#fitur','crest_caption'=>'Pusat Siber Angkatan Darat','crest_motto'=>''],
            'stats'=>[['number'=>'12','label'=>'Akun Terdaftar'],['number'=>'24/7','label'=>'Layanan Aktif'],['number'=>'100%','label'=>'Transparan & Real-Time'],['number'=>'1','label'=>'Sistem Pelaporan Digital']],
            'features_section'=>['eyebrow'=>'Keunggulan','title'=>'Kenapa Memakai Sistem Ini','description'=>'Membantu proses pelaporan dan persetujuan agar lebih tertata dan mudah dipantau.'],
            'about_section'=>[
                'eyebrow'=>'Tentang','title'=>'Pussiberad',
                'items'=>[
                    ['label'=>'Satria','description'=>'Kesatria atau pejuang yang gagah berani, jujur, dan membela kebenaran.'],
                    ['label'=>'Yudha','description'=>'Perang atau perjuangan — kini diwujudkan sebagai pertempuran di ruang siber (cyber warfare) melawan peretasan, spionase, dan ancaman digital.'],
                    ['label'=>'Waskita','description'=>'Tajam penglihatan, waspada, dan bijaksana — dasar kemampuan mendeteksi dan menangkal ancaman siber sejak dini sebelum merusak sistem pertahanan negara.'],
                ],
            ],
            'login'=>[
                'nav_label'=>'Masuk','title'=>'Masuk','subtitle'=>'Masuk menggunakan akun personel yang terdaftar.',
                'submit_label'=>'Masuk','footer_note'=>'Akses hanya untuk personel resmi PUSSIBERAD',
            ],
            'loader'=>['caption'=>'Memverifikasi Sistem…'],
            'footer'=>[
                'description'=>'Sistem Informasi Berbasis Elektronik Angkatan Darat, mendigitalisasi alur pelaporan seluruh Satuan Pelaksana Pussiberad.',
                'copyright'=>'DT-PHATRAM-2639 · Pussiberad · TNI AD',
                'brand_subtitle'=>'Pusat Siber Angkatan Darat',
                'nav_title'=>'Navigasi',
                'social_title'=>'Terhubung',
                'mabesad_title'=>'Mabesad',
                'mabesad_description'=>'Markas Besar TNI Angkatan Darat — pusat kendali dan pimpinan utama TNI AD, dipimpin oleh Kepala Staf Angkatan Darat (Kasad).',
                'bottom_tagline'=>'Satria · Yudha · Waskita',
            ],
        ];
    }

    public function landingConfig(): array
    {
        $config = array_replace_recursive(self::defaultLandingContent(), $this->landing_content ?? []);

        // "Akun Terdaftar" harus selalu nunjukin jumlah akun sungguhan di
        // sistem (bukan angka yang diketik manual lewat editor landing page)
        // -- stat lain (label, angka statis lain) tetap boleh diedit admin.
        $config['stats'] = collect($config['stats'] ?? [])
            ->map(function ($stat) {
                if (str_contains(strtolower(trim((string) ($stat['label'] ?? ''))), 'akun terdaftar')) {
                    $stat['number'] = (string) User::count();
                }

                return $stat;
            })
            ->all();

        return $config;
    }
}
