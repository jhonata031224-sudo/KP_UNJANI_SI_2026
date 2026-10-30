<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\LaporanKendala;
use App\Models\LaporanSurat;
use App\Models\LaporanSuratRiwayat;
use App\Models\LaporanSuratTembusan;
use App\Models\Satuan;
use App\Models\User;
use App\Support\AktivitasSatuanDanpus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Riwayat Aktivitas Danpus: aktivitas Laporan + Surat + Kendala per satuan.
 * Fokus: sumber data benar, tanpa kebocoran antar-satuan, tanpa duplikasi,
 * hak akses, dan fitur laporan lama tidak berubah.
 */
class RiwayatAktivitasDanpusTest extends TestCase
{
    use RefreshDatabase;

    private array $s = [];
    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        $daftar = [
            'DANPUS' => 'Danpus', 'WADAN' => 'Wadan', 'URDAL' => 'Urdal',
            'SATLAKKAL' => 'Satlak Kal', 'SATLAKDAK' => 'Satlak Dak',
            'SILIWANGI' => 'Kasansi Siliwangi', 'JAYA' => 'Kasansi Jaya',
        ];
        foreach ($daftar as $kode => $nama) {
            $this->s[$kode] = Satuan::firstOrCreate(['kode' => $kode], ['nama' => $nama, 'kategori' => 'Test']);
            $this->u[$kode] = User::create([
                'name' => $nama, 'username' => 'uji_'.strtolower($kode), 'password' => bcrypt('x'),
                'satuan_id' => $this->s[$kode]->id,
            ]);
        }
    }

    private function surat(string $dari, string $tujuan, array $extra = []): LaporanSurat
    {
        $surat = LaporanSurat::create(array_merge([
            'satuan_id' => $this->s[$dari]->id, 'user_id' => $this->u[$dari]->id,
            'tujuan_satuan_id' => $this->s[$tujuan]->id, 'perihal' => 'Surat '.$dari.'-'.$tujuan,
            'kategori' => 'Umum', 'deskripsi' => 'isi surat', 'prioritas' => 'Sedang',
            'lampiran_path' => 'lampiran-surat/x.pdf', 'lampiran_nama_asli' => 'x.pdf',
            'status' => LaporanSurat::STATUS_MENUNGGU,
        ], $extra));
        $this->riwayat($surat, LaporanSuratRiwayat::AKSI_BUAT_SURAT, $dari, $tujuan);

        return $surat;
    }

    private function riwayat(LaporanSurat $surat, string $aksi, string $pengirim, string $penerima): LaporanSuratRiwayat
    {
        return LaporanSuratRiwayat::create([
            'laporan_surat_id' => $surat->id, 'siklus' => 1, 'aksi' => $aksi,
            'pengirim_satuan_id' => $this->s[$pengirim]->id, 'penerima_satuan_id' => $this->s[$penerima]->id,
            'user_id' => $this->u[$pengirim]->id, 'catatan' => 'catatan',
        ]);
    }

    private function kendala(string $dari, array $extra = []): LaporanKendala
    {
        return LaporanKendala::create(array_merge([
            'satuan_id' => $this->s[$dari]->id, 'user_id' => $this->u[$dari]->id,
            'tujuan_satuan_id' => $this->s['DANPUS']->id, 'perihal' => 'Kendala '.$dari,
            'deskripsi' => 'isi', 'prioritas' => 'Sedang', 'status' => LaporanKendala::STATUS_MENUNGGU_KONFIRMASI,
        ], $extra));
    }

    private function bangun(): array
    {
        $dipantau = Satuan::whereIn('kode', ['URDAL', 'SATLAKKAL', 'SATLAKDAK', 'SILIWANGI', 'JAYA'])->get();

        return AktivitasSatuanDanpus::bangun($dipantau, $this->s['DANPUS']);
    }

    // ---------------------------------------------------------------- SURAT

    public function test_surat_keluar_satuan_tampil_di_satuan_itu_saja(): void
    {
        $surat = $this->surat('SATLAKKAL', 'DANPUS');
        $a = $this->bangun();

        $kal = $a['surat'][$this->s['SATLAKKAL']->id];
        $this->assertCount(1, $kal);
        $this->assertSame($surat->id, $kal->first()->id);
        $this->assertSame('keluar', $kal->first()->arahUntukSatuan);

        // tidak bocor ke satuan lain
        $this->assertCount(0, $a['surat'][$this->s['SATLAKDAK']->id]);
        $this->assertCount(0, $a['surat'][$this->s['URDAL']->id]);
    }

    public function test_surat_danpus_yang_diteruskan_wadan_tampil_masuk_di_satuan_tujuan_akhir(): void
    {
        $surat = $this->surat('DANPUS', 'WADAN');
        $surat->update(['tujuan_satuan_id' => $this->s['SATLAKKAL']->id, 'status' => LaporanSurat::STATUS_MENUNGGU]);
        $this->riwayat($surat, LaporanSuratRiwayat::AKSI_TERUSKAN, 'WADAN', 'SATLAKKAL');
        $a = $this->bangun();

        $kal = $a['surat'][$this->s['SATLAKKAL']->id];
        $this->assertCount(1, $kal);
        $this->assertSame('masuk', $kal->first()->arahUntukSatuan);
        $this->assertCount(0, $a['surat'][$this->s['SATLAKDAK']->id]);
    }

    public function test_satu_surat_dengan_banyak_riwayat_tidak_menggandakan_kartu(): void
    {
        $surat = $this->surat('SATLAKKAL', 'DANPUS');
        $this->riwayat($surat, LaporanSuratRiwayat::AKSI_KONFIRMASI, 'SATLAKKAL', 'DANPUS');
        $this->riwayat($surat, LaporanSuratRiwayat::AKSI_SURAT_KELUAR, 'SATLAKKAL', 'WADAN');
        $a = $this->bangun();

        $kal = $a['surat'][$this->s['SATLAKKAL']->id];
        $this->assertCount(1, $kal);
        $this->assertTrue($kal->first()->balasanDikirim);
    }

    public function test_surat_buatan_wadan_tidak_ikut_karena_danpus_bukan_pihak(): void
    {
        $this->surat('WADAN', 'SATLAKKAL');
        $a = $this->bangun();

        $this->assertCount(0, $a['surat'][$this->s['SATLAKKAL']->id]);
    }

    public function test_tembusan_ditandai_dan_masuk_ke_satuan_tembusan(): void
    {
        $surat = $this->surat('SATLAKKAL', 'DANPUS');
        LaporanSuratTembusan::create([
            'laporan_surat_id' => $surat->id, 'satuan_id' => $this->s['URDAL']->id,
            'jenis' => LaporanSuratTembusan::JENIS_TEMBUSAN,
        ]);
        $a = $this->bangun();

        $urdal = $a['surat'][$this->s['URDAL']->id];
        $this->assertCount(1, $urdal);
        $this->assertSame('masuk', $urdal->first()->arahUntukSatuan);
        $this->assertTrue($urdal->first()->hanyaTembusan);
    }

    public function test_penanda_arsip_dan_menunggu_danpus_serta_ringkasan(): void
    {
        $this->surat('SATLAKKAL', 'DANPUS');                                   // menunggu Danpus
        $this->surat('SATLAKKAL', 'DANPUS', [                                   // sudah dikonfirmasi & selesai
            'status' => LaporanSurat::STATUS_DIKONFIRMASI, 'is_selesai' => true,
        ]);
        $a = $this->bangun();
        $rk = $a['ringkasan'][$this->s['SATLAKKAL']->id];

        $this->assertSame(2, $rk['surat_total']);
        $this->assertSame(2, $rk['surat_keluar']);
        $this->assertSame(0, $rk['surat_masuk']);
        $this->assertSame(1, $rk['surat_menunggu']);
        $this->assertSame(1, $rk['surat_menunggu_danpus']);
        $this->assertSame(1, $rk['surat_arsip']);
        $this->assertSame(1, $rk['perlu_tindak_lanjut']);
    }

    // -------------------------------------------------------------- KENDALA

    public function test_kendala_hanya_untuk_kasansi_dan_tidak_bocor_antar_satuan(): void
    {
        $this->kendala('SILIWANGI');
        $this->kendala('SILIWANGI', ['confirmed_at' => now(), 'status' => LaporanKendala::STATUS_DIKONFIRMASI, 'confirmed_by' => $this->u['DANPUS']->id]);
        $this->kendala('JAYA');
        $a = $this->bangun();

        $this->assertTrue($a['kendalaDidukung'][$this->s['SILIWANGI']->id]);
        $this->assertCount(2, $a['kendala'][$this->s['SILIWANGI']->id]);
        $this->assertCount(1, $a['kendala'][$this->s['JAYA']->id]);

        // satuan non-Kasansi: fitur kendala tidak ada di sistem
        $this->assertFalse($a['kendalaDidukung'][$this->s['SATLAKKAL']->id]);
        $this->assertCount(0, $a['kendala'][$this->s['SATLAKKAL']->id]);

        $rk = $a['ringkasan'][$this->s['SILIWANGI']->id];
        $this->assertSame(2, $rk['kendala_total']);
        $this->assertSame(1, $rk['kendala_menunggu']);
        $this->assertSame(1, $rk['kendala_dikonfirmasi']);
        $this->assertSame(1, $rk['perlu_tindak_lanjut']);
    }

    public function test_kendala_bukan_bertujuan_danpus_tidak_ditampilkan(): void
    {
        $this->kendala('JAYA', ['tujuan_satuan_id' => $this->s['WADAN']->id]);

        $this->assertCount(0, $this->bangun()['kendala'][$this->s['JAYA']->id]);
    }

    // ---------------------------------------------------------- HALAMAN

    public function test_halaman_danpus_menampilkan_tab_surat_dan_kendala_per_satuan(): void
    {
        $this->surat('SILIWANGI', 'DANPUS', ['perihal' => 'Permohonan Dukungan Siliwangi']);
        $this->kendala('SILIWANGI', ['perihal' => 'Link utama putus Siliwangi']);
        $this->kendala('JAYA', ['perihal' => 'Kendala khusus Jaya']);

        $html = $this->actingAs($this->u['DANPUS'])->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('data-akt-tabs="'.$this->s['SILIWANGI']->id.'"', $html);
        $this->assertStringContainsString('data-akt-view="surat"', $html);
        $this->assertStringContainsString('data-akt-view="kendala"', $html);
        $this->assertStringContainsString('Permohonan Dukungan Siliwangi', $html);
        $this->assertStringContainsString('Link utama putus Siliwangi', $html);
        $this->assertStringContainsString('data-akt-view="laporan"', $html);

        // Panel Siliwangi memuat kendala/surat Siliwangi saja.
        $panelJaya = $this->panel($html, $this->s['JAYA']->id);
        $this->assertStringContainsString('Kendala khusus Jaya', $panelJaya);
        $this->assertStringNotContainsString('Link utama putus Siliwangi', $panelJaya);
        $this->assertStringNotContainsString('Permohonan Dukungan Siliwangi', $panelJaya);

        // Satlak Kal tidak punya tab Kendala.
        $panelKal = $this->panel($html, $this->s['SATLAKKAL']->id);
        $this->assertStringNotContainsString('data-akt-view="kendala"', $panelKal);
    }

    public function test_surat_rahasia_dari_satuan_tidak_membocorkan_isi_ke_danpus(): void
    {
        $this->surat('SILIWANGI', 'DANPUS', ['prioritas' => 'Rahasia', 'deskripsi' => 'ISI-RAHASIA-XYZ']);

        $html = $this->actingAs($this->u['DANPUS'])->get('/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('ISI-RAHASIA-XYZ', $html);
    }

    public function test_wadan_dan_satuan_biasa_tidak_mendapat_tampilan_baru(): void
    {
        $this->surat('SILIWANGI', 'DANPUS');
        $this->kendala('SILIWANGI');

        $wadan = $this->actingAs($this->u['WADAN'])->get('/dashboard')->assertOk()->getContent();
        $this->assertStringNotContainsString('data-akt-tabs', $wadan);

        $kasansi = $this->actingAs($this->u['SILIWANGI'])->get('/dashboard')->assertOk()->getContent();
        $this->assertStringNotContainsString('data-akt-tabs', $kasansi);
    }

    public function test_modul_surat_dan_kendala_nonaktif_menyembunyikan_tab_terkait(): void
    {
        $this->kendala('SILIWANGI');
        $this->s['DANPUS']->update(['permissions' => ['laporan', 'monitoring', 'notifikasi']]);

        $html = $this->actingAs($this->u['DANPUS']->fresh())->get('/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-akt-view="surat"', $html);
        $this->assertStringNotContainsString('data-akt-view="kendala"', $html);
    }

    public function test_laporan_lama_tetap_utuh_progres_digabung_per_perihal(): void
    {
        // 2 checkpoint progres + 1 final satu perihal -> hitungan laporan tetap sama seperti sebelumnya.
        $a = ['satuan_id' => $this->s['SATLAKKAL']->id, 'user_id' => $this->u['SATLAKKAL']->id,
              'tujuan_satuan_id' => $this->s['DANPUS']->id, 'perihal' => 'Laporan Uji',
              'deskripsi' => 'x', 'prioritas' => 'Sedang'];
        Laporan::create($a + ['status' => Laporan::STATUS_PROGRES, 'progres' => 30]);
        Laporan::create($a + ['status' => Laporan::STATUS_PROGRES, 'progres' => 60]);
        Laporan::create($a + ['status' => 'Menunggu']);

        $html = $this->actingAs($this->u['DANPUS'])->get('/dashboard')->assertOk()->getContent();
        $panel = $this->panel($html, $this->s['SATLAKKAL']->id);

        $this->assertStringContainsString('Laporan Uji', $panel);
        $this->assertStringContainsString('openReportDetail', $panel);
        // Tab Laporan menghitung baris laporan efektif (2 progres + 1 final).
        $this->assertMatchesRegularExpression('/data-akt-tab="laporan">Laporan <span class="akt-tab-count">3<\/span>/', $panel);
    }

    public function test_alur_surat_tampil_berurutan_dengan_langkah_menunggu_dan_sorotan_satuan(): void
    {
        $surat = $this->surat('DANPUS', 'WADAN', ['perihal' => 'Perintah Alur Uji']);
        $surat->update(['tujuan_satuan_id' => $this->s['SATLAKKAL']->id]);
        $this->riwayat($surat, LaporanSuratRiwayat::AKSI_KONFIRMASI, 'WADAN', 'WADAN');
        $this->riwayat($surat, LaporanSuratRiwayat::AKSI_TERUSKAN, 'WADAN', 'SATLAKKAL');

        $html = $this->actingAs($this->u['DANPUS'])->get('/dashboard')->assertOk()->getContent();
        $panel = $this->panel($html, $this->s['SATLAKKAL']->id);

        $this->assertStringContainsString('class="danpus-report-dropdown akt-srow"', $panel);
        $this->assertStringNotContainsString('surat-file-card', $panel);

        $urutan = [
            'Surat Dibuat',
            'Dikonfirmasi / ACC &amp; Diterima',
            'Diteruskan dengan Disposisi &amp; Tindakan',
            'Menunggu konfirmasi Satlak Kal',
        ];
        $pos = -1;
        foreach ($urutan as $judul) {
            $p = strpos($panel, $judul, $pos + 1);
            $this->assertNotFalse($p, "Langkah '$judul' tidak ditemukan / tidak berurutan");
            $pos = $p;
        }
        // langkah yang melibatkan Satlak Kal disorot, dan langkah terakhir berstatus current
        $this->assertStringContainsString('melibatkan Satlak Kal', $panel);
        $this->assertStringContainsString('danpus-activity-item is-current is-menunggu', $panel);
    }

    public function test_catatan_alur_rahasia_tersamar_tetapi_surat_biasa_terlihat(): void
    {
        $r = $this->surat('SILIWANGI', 'DANPUS', ['prioritas' => 'Rahasia', 'perihal' => 'Surat Rahasia Uji']);
        $r->riwayats()->update(['catatan' => 'CATATAN-RAHASIA-QQ']);
        $b = $this->surat('SILIWANGI', 'DANPUS', ['prioritas' => 'Tinggi', 'perihal' => 'Surat Biasa Uji']);
        $b->riwayats()->update(['catatan' => 'CATATAN-BIASA-QQ']);

        $html = $this->actingAs($this->u['DANPUS'])->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Surat Rahasia Uji', $html);
        $this->assertStringNotContainsString('CATATAN-RAHASIA-QQ', $html);
        $this->assertStringContainsString('CATATAN-BIASA-QQ', $html);
    }

    public function test_surat_tuntas_tanpa_riwayat_selesai_diberi_langkah_penutup_arsip(): void
    {
        $this->surat('DANPUS', 'SATLAKKAL', ['perihal' => 'Surat Tuntas Uji', 'status' => LaporanSurat::STATUS_DIKONFIRMASI]);

        $html = $this->actingAs($this->u['DANPUS'])->get('/dashboard')->assertOk()->getContent();
        $panel = $this->panel($html, $this->s['SATLAKKAL']->id);

        $this->assertStringContainsString('Alur tuntas, masuk arsip', $panel);
        $this->assertStringContainsString('data-arsip="1"', $panel);
    }

    /** Ambil markup section#satlak-{id}. */
    private function panel(string $html, int $id): string
    {
        $mulai = strpos($html, '<section id="satlak-'.$id.'"');
        $this->assertNotFalse($mulai, 'Panel satuan '.$id.' tidak ditemukan');
        $akhir = strpos($html, '</section>', $mulai);

        return substr($html, $mulai, $akhir - $mulai);
    }
}
