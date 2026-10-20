<?php

namespace Tests\Feature;

use App\Models\LaporanSurat;
use App\Models\LaporanSuratRiwayat;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Surat satuan -> Danpus yang dikonfirmasi Danpus memang is_selesai = true
 * (supaya pindah ke Arsip), tapi labelnya harus "Dikonfirmasi", BUKAN "Selesai".
 * "Selesai" hanya untuk status Selesai eksplisit.
 */
class LabelStatusSuratSatuanKeDanpusTest extends TestCase
{
    use RefreshDatabase;

    private array $s = [];
    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['DANPUS' => 'Danpus', 'WADAN' => 'Wadan', 'URDAL' => 'Urdal', 'SATLAKKAL' => 'Satlak Kal'] as $kode => $nama) {
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

    public function test_surat_satuan_ke_danpus_yang_dikonfirmasi_berlabel_dikonfirmasi_bukan_selesai(): void
    {
        $surat = $this->surat('SATLAKKAL', 'DANPUS', 'SURAT SATUAN KE DANPUS');
        $this->actingAs($this->u['DANPUS'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();

        $surat->refresh();
        $this->assertTrue($surat->isSelesai());                       // tetap pindah ke Arsip
        $this->assertSame('Dikonfirmasi', $surat->labelStatus());
        $this->assertSame('status-dikonfirmasi', $surat->badgeClass());
        $this->assertSame('Dikonfirmasi', $surat->labelStatus($this->s['SATLAKKAL']->id));   // sisi pengirim tidak "Diteruskan"
    }

    public function test_kartu_arsip_danpus_urdal_dan_pengirim_menampilkan_dikonfirmasi(): void
    {
        $surat = $this->surat('SATLAKKAL', 'DANPUS', 'SURAT SATUAN KE DANPUS');
        $this->actingAs($this->u['DANPUS'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();

        foreach (['DANPUS', 'URDAL', 'SATLAKKAL'] as $kode) {
            $html = $this->actingAs($this->u[$kode])->getJson('/laporan-surat/realtime')->assertOk()->json('arsip_items_html');
            $this->assertStringContainsString('SURAT SATUAN KE DANPUS', $html, $kode);
            $this->assertStringContainsString('data-status="Dikonfirmasi"', $html, $kode);
            $this->assertStringNotContainsString('Selesai</span>', $html, $kode);
        }
    }

    public function test_putaran_penuh_surat_danpus_juga_berlabel_dikonfirmasi(): void
    {
        $surat = $this->surat('DANPUS', 'DANPUS', 'PUTARAN PENUH', ['status' => LaporanSurat::STATUS_DIKONFIRMASI, 'is_selesai' => true]);

        $this->assertSame('Dikonfirmasi', $surat->labelStatus());
    }

    public function test_selesai_eksplisit_tetap_berlabel_selesai(): void
    {
        $surat = $this->surat('DANPUS', 'DANPUS', 'DISELESAIKAN MANUAL', ['status' => LaporanSurat::STATUS_SELESAI, 'is_selesai' => true]);

        $this->assertSame('Selesai', $surat->labelStatus());
        $this->assertSame('status-disetujui', $surat->badgeClass());
    }

    public function test_label_surat_belum_selesai_tidak_berubah(): void
    {
        $menunggu = $this->surat('SATLAKKAL', 'DANPUS', 'MENUNGGU');
        $this->assertSame('Menunggu Konfirmasi', $menunggu->labelStatus());

        $diteruskan = $this->surat('DANPUS', 'WADAN', 'DITERUSKAN', ['status' => LaporanSurat::STATUS_DIKONFIRMASI]);
        $this->assertSame('Diteruskan', $diteruskan->labelStatus($this->s['DANPUS']->id));   // sisi pengirim, belum selesai
        $this->assertSame('Dikonfirmasi', $diteruskan->labelStatus());
    }
}
