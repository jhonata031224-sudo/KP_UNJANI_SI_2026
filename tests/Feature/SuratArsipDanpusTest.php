<?php

namespace Tests\Feature;

use App\Models\LaporanSurat;
use App\Models\LaporanSuratRiwayat;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur 3 step surat Danpus: Danpus buat -> Wadan konfirmasi & teruskan ->
 * satuan tujuan akhir konfirmasi. Begitu step 3 terpenuhi, kartu di sisi
 * Danpus otomatis pindah dari Surat Keluar ke Arsip Surat.
 */
class SuratArsipDanpusTest extends TestCase
{
    use RefreshDatabase;

    private array $s = [];
    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['DANPUS' => 'Danpus', 'WADAN' => 'Wadan', 'URDAL' => 'Urdal', 'SATLAK_X' => 'Satlak Dukteksi'] as $kode => $nama) {
            $this->s[$kode] = Satuan::firstOrCreate(['kode' => $kode], ['nama' => $nama, 'kategori' => 'Test']);
            $this->u[$kode] = User::create([
                'name' => $nama, 'username' => 'uji_'.strtolower($kode), 'password' => bcrypt('x'),
                'satuan_id' => $this->s[$kode]->id,
            ]);
        }
    }

    private function buatSurat(): LaporanSurat
    {
        $surat = LaporanSurat::create([
            'satuan_id' => $this->s['DANPUS']->id, 'user_id' => $this->u['DANPUS']->id,
            'tujuan_satuan_id' => $this->s['WADAN']->id, 'perihal' => 'TEST',
            'kategori' => 'Umum', 'deskripsi' => 'isi', 'lampiran_path' => 'lampiran-surat/x.pdf', 'lampiran_nama_asli' => 'x.pdf', 'prioritas' => 'Biasa', 'status' => LaporanSurat::STATUS_MENUNGGU,
        ]);
        LaporanSuratRiwayat::create([
            'laporan_surat_id' => $surat->id, 'siklus' => 1, 'aksi' => LaporanSuratRiwayat::AKSI_BUAT_SURAT,
            'pengirim_satuan_id' => $this->s['DANPUS']->id, 'penerima_satuan_id' => $this->s['WADAN']->id,
            'user_id' => $this->u['DANPUS']->id, 'catatan' => 'x',
        ]);

        return $surat;
    }

    /** Ambil isi realtime Danpus: [ada di terkirim?, ada di arsip?] */
    private function posisiDiDanpus(): array
    {
        $json = $this->actingAs($this->u['DANPUS'])->getJson('/laporan-surat/realtime')->assertOk()->json();

        return [
            'terkirim' => str_contains($json['terkirim_items_html'] ?? '', 'TEST'),
            'arsip'    => str_contains($json['arsip_items_html'] ?? '', 'TEST'),
        ];
    }

    /** Sama, tapi lewat scope/query load awal (pola DashboardController). */
    private function posisiViaScope(): array
    {
        $id = $this->s['DANPUS']->id;

        return [
            'terkirim' => LaporanSurat::where('satuan_id', $id)->where('is_selesai', false)->alurBelumTuntasSisiPengirim()->exists(),
            'arsip'    => LaporanSurat::where('satuan_id', $id)
                ->where(fn ($f) => $f->where('is_selesai', true)->orWhere(fn ($t) => $t->alurTuntasSisiPengirim()))->exists(),
        ];
    }

    public function test_alur_tiga_step_memindahkan_surat_danpus_ke_arsip(): void
    {
        $surat = $this->buatSurat();

        // Step 1: baru dibuat, menunggu Wadan -> Surat Keluar
        $this->assertSame(['terkirim' => true, 'arsip' => false], $this->posisiDiDanpus());
        $this->assertSame(['terkirim' => true, 'arsip' => false], $this->posisiViaScope());

        // Step 2a: Wadan konfirmasi (belum diteruskan) -> TETAP Surat Keluar
        $this->actingAs($this->u['WADAN'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $this->assertSame(['terkirim' => true, 'arsip' => false], $this->posisiDiDanpus());
        $this->assertSame(['terkirim' => true, 'arsip' => false], $this->posisiViaScope());

        // Step 2b: Wadan teruskan ke satuan (+ Urdal view only) -> TETAP Surat Keluar
        $this->actingAs($this->u['WADAN'])->post("/laporan-surat/{$surat->id}/teruskan", [
            'tujuan_satuan_id' => $this->s['SATLAK_X']->id, 'disposisi' => 'Satlak Dukteksi', 'tindakan' => ['CATAT'],
        ])->assertSessionHasNoErrors();
        $this->assertSame(['terkirim' => true, 'arsip' => false], $this->posisiDiDanpus());
        $this->assertSame(['terkirim' => true, 'arsip' => false], $this->posisiViaScope());

        // Step 3: satuan tujuan akhir konfirmasi -> PINDAH ke Arsip Danpus
        $this->actingAs($this->u['SATLAK_X'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $this->assertSame(['terkirim' => false, 'arsip' => true], $this->posisiDiDanpus());
        $this->assertSame(['terkirim' => false, 'arsip' => true], $this->posisiViaScope());
    }

    public function test_badge_diteruskan_disembunyikan_di_kartu_surat_keluar_danpus(): void
    {
        $surat = $this->buatSurat();
        // Wadan sudah konfirmasi tapi belum meneruskan -> label sisi Danpus = "Diteruskan"
        $this->actingAs($this->u['WADAN'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();

        $html = $this->actingAs($this->u['DANPUS'])->getJson('/laporan-surat/realtime')->json('terkirim_items_html');
        $this->assertStringContainsString('TEST', $html);
        $this->assertStringNotContainsString('surat-file-card-badge', $html);   // badge hilang di kartu
        $this->assertStringContainsString('data-status="Diteruskan"', $html);   // logika/status tetap "Diteruskan"
    }

    public function test_kartu_di_arsip_danpus_tidak_berlabel_diteruskan(): void
    {
        $surat = $this->buatSurat();
        $this->actingAs($this->u['WADAN'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $this->actingAs($this->u['WADAN'])->post("/laporan-surat/{$surat->id}/teruskan", [
            'tujuan_satuan_id' => $this->s['SATLAK_X']->id, 'disposisi' => 'Satlak Dukteksi', 'tindakan' => ['CATAT'],
        ]);
        $this->actingAs($this->u['SATLAK_X'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();

        $arsip = $this->actingAs($this->u['DANPUS'])->getJson('/laporan-surat/realtime')->json('arsip_items_html');
        $this->assertStringContainsString('TEST', $arsip);
        $this->assertStringNotContainsString('>Diteruskan<', $arsip);
    }

    public function test_load_halaman_awal_danpus_konsisten_dengan_realtime(): void
    {
        $surat = $this->buatSurat();
        $this->actingAs($this->u['WADAN'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $this->actingAs($this->u['WADAN'])->post("/laporan-surat/{$surat->id}/teruskan", [
            'tujuan_satuan_id' => $this->s['SATLAK_X']->id, 'disposisi' => 'Satlak Dukteksi', 'tindakan' => ['CATAT'],
        ]);

        $bagian = function (string $html, string $id): string {
            preg_match('/id="'.$id.'"[^>]*>(.*?)(?=id="surat(?:Terkirim|Arsip|Masuk)Grid"|<\/section>)/s', $html, $m);
            return $m[1] ?? '';
        };

        // Sebelum step 3: hanya di Surat Keluar
        $res = $this->actingAs($this->u['DANPUS'])->get('/dashboard');
        $res->assertOk();
        $this->assertStringContainsString('TEST', $bagian($res->getContent(), 'suratTerkirimGrid'));
        $this->assertStringNotContainsString('TEST', $bagian($res->getContent(), 'suratArsipGrid'));

        // Step 3 selesai: pindah ke Arsip di load awal juga (tanpa menunggu polling)
        $this->actingAs($this->u['SATLAK_X'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $res = $this->actingAs($this->u['DANPUS'])->get('/dashboard');
        $this->assertStringNotContainsString('TEST', $bagian($res->getContent(), 'suratTerkirimGrid'));
        $this->assertStringContainsString('TEST', $bagian($res->getContent(), 'suratArsipGrid'));
    }

    public function test_surat_menunggu_konfirmasi_tetap_punya_badge(): void
    {
        $this->buatSurat();
        $html = $this->actingAs($this->u['DANPUS'])->getJson('/laporan-surat/realtime')->json('terkirim_items_html');
        $this->assertStringContainsString('surat-file-card-badge', $html);
        $this->assertStringContainsString('Menunggu Konfirmasi', $html);
    }
}
