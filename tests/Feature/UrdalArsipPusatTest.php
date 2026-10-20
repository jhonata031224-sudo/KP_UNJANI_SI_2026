<?php

namespace Tests\Feature;

use App\Models\LaporanSurat;
use App\Models\LaporanSuratRiwayat;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Urdal = arsip pusat surat-menyurat: semua surat yang SELESAI muncul di
 * Arsip Surat Urdal, dengan data-asal untuk filter:
 *   - 'danpus' = dikeluarkan dari sisi Danpus
 *   - 'satuan' = dikeluarkan langsung oleh satuan lain ke Danpus
 * Surat yang belum selesai TIDAK boleh masuk. Satuan lain tidak terpengaruh.
 */
class UrdalArsipPusatTest extends TestCase
{
    use RefreshDatabase;

    private array $s = [];
    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        foreach (['DANPUS' => 'Danpus', 'WADAN' => 'Wadan', 'URDAL' => 'Urdal', 'SATLAKKAL' => 'Satlak Kal', 'POKANALIS' => 'Pok Analis'] as $kode => $nama) {
            $this->s[$kode] = Satuan::firstOrCreate(['kode' => $kode], ['nama' => $nama, 'kategori' => 'Test']);
            $this->u[$kode] = User::create([
                'name' => $nama, 'username' => 'uji_'.strtolower($kode), 'password' => bcrypt('x'),
                'satuan_id' => $this->s[$kode]->id,
            ]);
        }
    }

    private function surat(string $dari, string $ke, string $perihal, array $extra = []): LaporanSurat
    {
        $surat = LaporanSurat::create(array_merge([
            'satuan_id' => $this->s[$dari]->id, 'user_id' => $this->u[$dari]->id,
            'tujuan_satuan_id' => $this->s[$ke]->id, 'perihal' => $perihal,
            'kategori' => 'Umum', 'deskripsi' => 'isi', 'lampiran_path' => 'lampiran-surat/x.pdf',
            'lampiran_nama_asli' => 'x.pdf', 'prioritas' => 'Biasa', 'siklus' => 1,
            'status' => LaporanSurat::STATUS_MENUNGGU,
        ], $extra));
        LaporanSuratRiwayat::create([
            'laporan_surat_id' => $surat->id, 'siklus' => 1, 'aksi' => LaporanSuratRiwayat::AKSI_BUAT_SURAT,
            'pengirim_satuan_id' => $this->s[$dari]->id, 'penerima_satuan_id' => $this->s[$ke]->id,
            'user_id' => $this->u[$dari]->id, 'catatan' => 'x',
        ]);

        return $surat;
    }

    private function arsipUrdal(): string
    {
        return $this->actingAs($this->u['URDAL'])->getJson('/laporan-surat/realtime')->assertOk()->json('arsip_items_html');
    }

    private function siapkanSurat(): void
    {
        // Selesai, dari sisi Danpus (putaran penuh -> is_selesai).
        $this->surat('DANPUS', 'DANPUS', 'DARI DANPUS SELESAI', ['status' => LaporanSurat::STATUS_DIKONFIRMASI, 'is_selesai' => true]);
        // Selesai, dari sisi Danpus (alur turun tuntas, is_selesai masih false).
        $this->surat('DANPUS', 'POKANALIS', 'DARI DANPUS TURUN TUNTAS', ['status' => LaporanSurat::STATUS_DIKONFIRMASI]);
        // Selesai, satuan lain langsung ke Danpus (dikonfirmasi Danpus -> is_selesai).
        $this->surat('SATLAKKAL', 'DANPUS', 'SATUAN KE DANPUS SELESAI', ['status' => LaporanSurat::STATUS_DIKONFIRMASI, 'is_selesai' => true]);
        // BELUM selesai: masih menunggu / masih di Wadan.
        $this->surat('SATLAKKAL', 'DANPUS', 'SATUAN KE DANPUS MENUNGGU');
        $this->surat('DANPUS', 'WADAN', 'DANPUS MASIH DI WADAN', ['status' => LaporanSurat::STATUS_DIKONFIRMASI]);
    }

    public function test_arsip_urdal_memuat_semua_surat_selesai_dan_bukan_yang_belum_selesai(): void
    {
        $this->siapkanSurat();
        $html = $this->arsipUrdal();

        $this->assertStringContainsString('DARI DANPUS SELESAI', $html);
        $this->assertStringContainsString('DARI DANPUS TURUN TUNTAS', $html);
        $this->assertStringContainsString('SATUAN KE DANPUS SELESAI', $html);
        $this->assertStringNotContainsString('SATUAN KE DANPUS MENUNGGU', $html);
        $this->assertStringNotContainsString('DANPUS MASIH DI WADAN', $html);
    }

    public function test_kartu_arsip_urdal_membawa_data_asal_untuk_filter(): void
    {
        $this->siapkanSurat();
        $html = $this->arsipUrdal();

        $this->assertSame(2, substr_count($html, 'data-asal="danpus"'));
        $this->assertSame(1, substr_count($html, 'data-asal="satuan"'));
    }

    public function test_halaman_awal_urdal_konsisten_dengan_realtime(): void
    {
        $this->siapkanSurat();
        $res = $this->actingAs($this->u['URDAL'])->get('/dashboard');
        $res->assertOk();
        $html = $res->getContent();

        $this->assertStringContainsString('DARI DANPUS SELESAI', $html);
        $this->assertStringContainsString('SATUAN KE DANPUS SELESAI', $html);
        $this->assertStringNotContainsString('SATUAN KE DANPUS MENUNGGU', $html);
        $this->assertStringContainsString('data-asal="satuan"', $html);
    }

    public function test_satuan_lain_tidak_terpengaruh_dan_tanpa_data_asal(): void
    {
        $this->siapkanSurat();
        $html = $this->actingAs($this->u['POKANALIS'])->getJson('/laporan-surat/realtime')->assertOk()->json('arsip_items_html');

        $this->assertStringNotContainsString('data-asal=', $html);
        $this->assertStringNotContainsString('SATUAN KE DANPUS SELESAI', $html);   // bukan miliknya
    }
}
