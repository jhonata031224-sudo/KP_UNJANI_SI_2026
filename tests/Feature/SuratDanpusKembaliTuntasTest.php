<?php

namespace Tests\Feature;

use App\Models\LaporanSurat;
use App\Models\LaporanSuratRiwayat;
use App\Models\LaporanSuratTembusan;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Surat BUATAN Danpus yang sudah berputar penuh:
 *   Danpus -> Wadan -> satuan (ACC) -> balasan -> Wadan -> Danpus (konfirmasi)
 * Begitu Danpus konfirmasi, surat otomatis TUNTAS: keluar dari Surat Keluar,
 * masuk Arsip Surat, dan Hasil/RC masuk ke Urdal (tanpa klik "Selesai").
 */
class SuratDanpusKembaliTuntasTest extends TestCase
{
    use RefreshDatabase;

    private array $s = [];
    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        foreach (['DANPUS' => 'Danpus', 'WADAN' => 'Wadan', 'URDAL' => 'Urdal', 'SATLAKKAL' => 'Satlak Kal'] as $kode => $nama) {
            $this->s[$kode] = Satuan::firstOrCreate(['kode' => $kode], ['nama' => $nama, 'kategori' => 'Test']);
            $this->u[$kode] = User::create([
                'name' => $nama, 'username' => 'uji_'.strtolower($kode), 'password' => bcrypt('x'),
                'satuan_id' => $this->s[$kode]->id,
            ]);
        }
    }

    private function suratKembaliKeDanpus(): LaporanSurat
    {
        $surat = LaporanSurat::create([
            'satuan_id' => $this->s['DANPUS']->id, 'user_id' => $this->u['DANPUS']->id,
            'tujuan_satuan_id' => $this->s['WADAN']->id, 'perihal' => 'SURAT PUTARAN PENUH',
            'kategori' => 'Umum', 'deskripsi' => 'isi', 'lampiran_path' => 'lampiran-surat/x.pdf',
            'lampiran_nama_asli' => 'x.pdf', 'prioritas' => 'Biasa', 'siklus' => 1,
            'status' => LaporanSurat::STATUS_MENUNGGU,
        ]);
        LaporanSuratRiwayat::create([
            'laporan_surat_id' => $surat->id, 'siklus' => 1, 'aksi' => LaporanSuratRiwayat::AKSI_BUAT_SURAT,
            'pengirim_satuan_id' => $this->s['DANPUS']->id, 'penerima_satuan_id' => $this->s['WADAN']->id,
            'user_id' => $this->u['DANPUS']->id, 'catatan' => 'x',
        ]);

        $this->actingAs($this->u['WADAN'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $this->actingAs($this->u['WADAN'])->post("/laporan-surat/{$surat->id}/teruskan", [
            'tujuan_satuan_id' => $this->s['SATLAKKAL']->id, 'disposisi' => 'SATLAK KAL', 'tindakan' => ['CATAT'],
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->u['SATLAKKAL'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $this->actingAs($this->u['SATLAKKAL'])->post('/laporan-surat', [
            'induk_surat_id' => $surat->id,
            'lampiran'       => UploadedFile::fake()->create('hasil.pdf', 50, 'application/pdf'),
            'deskripsi'      => 'Sudah dikerjakan',
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->u['WADAN'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $this->actingAs($this->u['WADAN'])->post("/laporan-surat/{$surat->id}/ke-danpus")->assertSessionHasNoErrors();

        return $surat->fresh();
    }

    private function realtime(string $kode): array
    {
        return $this->actingAs($this->u[$kode])->getJson('/laporan-surat/realtime')->assertOk()->json();
    }

    public function test_sebelum_danpus_konfirmasi_surat_masih_di_surat_keluar_danpus(): void
    {
        $this->suratKembaliKeDanpus();

        $json = $this->realtime('DANPUS');
        $this->assertStringContainsString('SURAT PUTARAN PENUH', $json['terkirim_items_html']);
        $this->assertStringNotContainsString('SURAT PUTARAN PENUH', $json['arsip_items_html']);
    }

    public function test_danpus_konfirmasi_menuntaskan_surat_pindah_ke_arsip_dan_rc_ke_urdal(): void
    {
        $surat = $this->suratKembaliKeDanpus();

        $this->actingAs($this->u['DANPUS'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();

        $surat->refresh();
        $this->assertTrue($surat->isSelesai());
        $this->assertSame(LaporanSurat::STATUS_DIKONFIRMASI, $surat->status);

        $json = $this->realtime('DANPUS');
        $this->assertStringNotContainsString('SURAT PUTARAN PENUH', $json['terkirim_items_html']);
        $this->assertStringContainsString('SURAT PUTARAN PENUH', $json['arsip_items_html']);

        $this->assertSame(1, LaporanSuratTembusan::where('laporan_surat_id', $surat->id)
            ->where('satuan_id', $this->s['URDAL']->id)
            ->where('jenis', LaporanSuratTembusan::JENIS_HASIL_RC)->count());
    }

    public function test_klik_konfirmasi_ulang_tidak_menggandakan_rc_urdal(): void
    {
        $surat = $this->suratKembaliKeDanpus();

        $this->actingAs($this->u['DANPUS'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $this->actingAs($this->u['DANPUS'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();

        $this->assertSame(1, LaporanSuratTembusan::where('laporan_surat_id', $surat->id)
            ->where('jenis', LaporanSuratTembusan::JENIS_HASIL_RC)->count());
    }

    public function test_surat_lama_yang_sudah_dikonfirmasi_di_danpus_tapi_belum_selesai_tetap_keluar_dari_surat_keluar(): void
    {
        // Data lama (sebelum perbaikan): dikonfirmasi di Danpus, is_selesai masih false.
        $surat = $this->suratKembaliKeDanpus();
        $surat->update(['status' => LaporanSurat::STATUS_DIKONFIRMASI, 'dikonfirmasi_at' => now()]);

        $json = $this->realtime('DANPUS');
        $this->assertStringNotContainsString('SURAT PUTARAN PENUH', $json['terkirim_items_html']);
        $this->assertStringContainsString('SURAT PUTARAN PENUH', $json['arsip_items_html']);
    }
}
