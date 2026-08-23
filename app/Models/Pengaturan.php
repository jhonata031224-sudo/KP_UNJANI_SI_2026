<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $fillable = [
        'nama_instansi','singkatan','logo_path','alamat','email_kontak','telepon_kontak',
        'hero_eyebrow','hero_judul_awal','hero_judul_aksen','hero_subjudul','hero_deskripsi','hero_image_path',
        'fitur','tentang_deskripsi','tentang_moto_judul','tentang_moto_deskripsi','website','sosial_media','landing_content',
    ];

    protected $casts = ['fitur' => 'array','sosial_media' => 'array','landing_content' => 'array'];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'nama_instansi' => 'Pusat Siber dan Sandi Angkatan Darat',
            'singkatan' => 'Pussiberad',
            'alamat' => 'Jl. Veteran No. 5, Gambir, Jakarta Pusat, DKI Jakarta 10110',
            'telepon_kontak' => '(021) 3849192',
            'hero_eyebrow' => 'PUSSIBERAD // SISTEM PENDUKUNG OPERASIONAL',
            'hero_judul_awal' => 'BRAHMASTRA ', 'hero_judul_aksen' => 'WIRA',
            'hero_subjudul' => 'Sistem Informasi Berbasis Elektronik Angkatan Darat',
            'hero_deskripsi' => 'Sistem pendukung operasional Satuan Siber AD dan jajaran Pusat Siber TNI Angkatan Darat — mulai dari input laporan, hingga visualisasi real-time bagi pengambil keputusan.',
            'fitur' => [
                ['judul'=>'Real-time','deskripsi'=>'Status dan progres laporan diperbarui otomatis, sehingga tidak perlu menunggu rekap manual untuk memantaunya.'],
                ['judul'=>'Terpusat','deskripsi'=>'Data laporan dan dokumen pendukung tersimpan dalam satu sistem, memudahkan pencarian dibanding menyimpannya secara terpisah.'],
                ['judul'=>'Efisien','deskripsi'=>'Alur persetujuan bertingkat membantu mempersingkat proses administrasi dibanding pengumpulan laporan secara manual.'],
                ['judul'=>'Aman & Terkontrol','deskripsi'=>'Akses data diatur berdasarkan peran pengguna, sehingga laporan hanya dapat dilihat atau diubah oleh pihak yang berwenang.'],
            ],
            'tentang_deskripsi' => "Pussiberad bukan sebuah perusahaan komersial, melainkan satuan resmi di bawah TNI Angkatan Darat yang dibentuk untuk menyelenggarakan pembinaan personel serta fungsi sandi dan siber dalam rangka membantu tugas TNI-AD. Satuan ini bernama Pusat Siber Angkatan Darat (Pussiberad), sebelumnya bernama Pusat Sandi dan Siber TNI Angkatan Darat (Pussansiad).\n\nPembentukan satuan ini merupakan hasil pengembangan Organisasi dan Tugas (Orgas) baru di lingkungan TNI-AD, sesuai Peraturan KASAD Nomor 26 Tahun 2019 tanggal 26 Desember 2019 tentang Organisasi dan Tugas Markas Besar TNI Angkatan Darat, Bab IV Tugas dan Tanggung Jawab, Pasal 35 Pussansiad.",
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
        ]);
    }

    public static function defaultLandingContent(): array
    {
        return [
            'brand'=>['name'=>'BRAHMASTRA ','accent'=>'WIRA','tagline'=>'Pussiberad · TNI AD'],
            'meta'=>['title'=>'BRAHMASTRA WIRA — Sistem Informasi Berbasis Elektronik Angkatan Darat | PUSSIBERAD','description'=>'BRAHMASTRA WIRA — Sistem Informasi Berbasis Elektronik Angkatan Darat. Platform pelaporan dan monitoring resmi Pusat Siber Angkatan Darat (PUSSIBERAD).'],
            'nav'=>[['label'=>'Beranda','url'=>'#tentang'],['label'=>'Fitur','url'=>'#fitur'],['label'=>'Tentang','url'=>'#tentang-pussiberad'],['label'=>'Kontak','url'=>'#tim']],
            'hero'=>['button_label'=>'Selengkapnya','button_url'=>'#fitur','crest_caption'=>'Pusat Siber Angkatan Darat','crest_motto'=>''],
            'stats'=>[['number'=>'12','label'=>'Akun Terdaftar'],['number'=>'24/7','label'=>'Layanan Aktif'],['number'=>'100%','label'=>'Transparan & Real-Time'],['number'=>'1','label'=>'Sistem Pelaporan Digital']],
            'features_section'=>['eyebrow'=>'Keunggulan','title'=>'Kenapa Memakai Sistem Ini','description'=>'Membantu proses pelaporan dan persetujuan agar lebih tertata dan mudah dipantau.'],
            'about_section'=>['eyebrow'=>'Tentang','title'=>'Pussiberad'],
            'footer'=>['description'=>'Sistem Informasi Berbasis Elektronik Angkatan Darat, mendigitalisasi alur pelaporan seluruh Satuan Pelaksana Pussiberad.','copyright'=>'BRAHMASTRA WIRA · Pussiberad · TNI AD'],
        ];
    }

    public function landingConfig(): array { return array_replace_recursive(self::defaultLandingContent(), $this->landing_content ?? []); }
}
