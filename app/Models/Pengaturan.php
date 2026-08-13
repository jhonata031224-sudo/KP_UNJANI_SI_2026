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
            'hero_judul_awal' => 'SIBER', 'hero_judul_aksen' => 'AD',
            'hero_subjudul' => 'Sistem Informasi Berbasis Elektronik Angkatan Darat',
            'hero_deskripsi' => 'Mendigitalisasi alur pelaporan kegiatan seluruh Satuan Pelaksana Pusat Siber Angkatan Darat — dari input laporan di lapangan, verifikasi berjenjang, hingga visualisasi real-time bagi pengambil keputusan.',
            'fitur' => [
                ['judul'=>'Real-time','deskripsi'=>'Laporan dan status pekerjaan dapat dipantau secara langsung tanpa menunggu rekap manual.'],
                ['judul'=>'Terpusat','deskripsi'=>'Seluruh data laporan dan dokumen pendukung tersimpan dalam satu sistem yang mudah diakses.'],
                ['judul'=>'Efisien','deskripsi'=>'Alur persetujuan berjenjang menjadi lebih cepat dan mengurangi proses administrasi berulang.'],
                ['judul'=>'Aman & Terkontrol','deskripsi'=>'Data terjaga dengan sistem cadangan dan hak akses yang diatur sesuai kebutuhan pengguna.'],
            ],
            'tentang_deskripsi' => "Pussiberad bukan sebuah perusahaan komersial, melainkan satuan resmi di bawah TNI Angkatan Darat yang dibentuk untuk menyelenggarakan pembinaan personel serta fungsi sandi dan siber dalam rangka membantu tugas TNI-AD. Satuan ini bernama Pusat Siber Angkatan Darat (Pussiberad), sebelumnya bernama Pusat Sandi dan Siber TNI Angkatan Darat (Pussansiad).\n\nPembentukan satuan ini merupakan hasil pengembangan Organisasi dan Tugas (Orgas) baru di lingkungan TNI-AD, sesuai Peraturan KASAD Nomor 26 Tahun 2019 tanggal 26 Desember 2019 tentang Organisasi dan Tugas Markas Besar TNI Angkatan Darat, Bab IV Tugas dan Tanggung Jawab, Pasal 35 Pussansiad.",
            'tentang_moto_judul'=>'Satria Yudha Waskita',
            'tentang_moto_deskripsi'=>'Semboyan resmi Pussansiad/Pussiberad ini diambil dari bahasa Sanskerta/Jawa Kuno, yang secara harfiah berarti "prajurit perang yang ahli, bijaksana, dan waspada" — menggambarkan identitas serta tugas utama prajurit siber TNI AD sebagai garda terdepan pertahanan digital bangsa.',
            'website'=>'https://tni-ad.mil.id/', 'sosial_media'=>[], 'landing_content'=>self::defaultLandingContent(),
        ]);
    }

    public static function defaultLandingContent(): array
    {
        return [
            'brand'=>['name'=>'SIBER','accent'=>'AD','tagline'=>'Pussiberad · TNI AD'],
            'meta'=>['title'=>'SIBERAD — Sistem Informasi Berbasis Elektronik Angkatan Darat | PUSSIBERAD','description'=>'SIBERAD — Sistem Informasi Berbasis Elektronik Angkatan Darat. Platform pelaporan dan monitoring resmi Pusat Siber Angkatan Darat (PUSSIBERAD).'],
            'nav'=>[['label'=>'Beranda','url'=>'#tentang'],['label'=>'Fitur','url'=>'#fitur'],['label'=>'Tentang','url'=>'#tentang-pussiberad'],['label'=>'Kontak','url'=>'#tim']],
            'hero'=>['button_label'=>'Selengkapnya','button_url'=>'#fitur','crest_caption'=>'Pusat Siber Angkatan Darat','crest_motto'=>'Satria Yudha Waskita'],
            'stats'=>[['number'=>'04','label'=>'Satuan Pelaksana Terpantau'],['number'=>'24/7','label'=>'Monitoring & Pelaporan'],['number'=>'100%','label'=>'Data Terpusat & Terverifikasi'],['number'=>'01','label'=>'Sistem Untuk Seluruh Satuan']],
            'features_section'=>['eyebrow'=>'Keunggulan','title'=>'Kenapa Memakai Sistem Ini','description'=>'Dirancang untuk menyederhanakan alur pelaporan harian, dari input di lapangan hingga pengambilan keputusan.'],
            'about_section'=>['eyebrow'=>'Tentang','title'=>'Pussiberad'],
            'footer'=>['description'=>'Sistem Informasi Berbasis Elektronik Angkatan Darat — mendigitalisasi alur pelaporan seluruh Satuan Pelaksana Pussiberad.','copyright'=>'SIBERAD · Pussiberad · TNI AD'],
        ];
    }

    public function landingConfig(): array { return array_replace_recursive(self::defaultLandingContent(), $this->landing_content ?? []); }
}
