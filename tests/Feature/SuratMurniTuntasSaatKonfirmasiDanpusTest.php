<?php

namespace Tests\Feature;

use App\Models\LaporanSurat;
use App\Models\LaporanSuratRiwayat;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Surat Keluar MURNI (bukan buatan Danpus/Wadan sendiri, bukan balasan):
 * begitu Danpus konfirmasi, alurnya TUNTAS di situ juga -- pindah ke Arsip
 * untuk KEDUA sisi (pengirim asli & Danpus) sekaligus, dan Danpus tidak lagi
 * bisa mendisposisi ulang record yang sama ke Wadan.
 *
 * Beda dengan SuratArsipDanpusTest yang menguji alur 3 step surat BUATAN
 * Danpus sendiri (Danpus -> Wadan -> satuan tujuan) -- flow itu tidak boleh
 * ikut berubah oleh perbaikan ini.
 */
class SuratMurniTuntasSaatKonfirmasiDanpusTest extends TestCase
{
    use RefreshDatabase;

    private array $s = [];
    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['DANPUS' => 'Danpus', 'WADAN' => 'Wadan', 'SATLAK_DUKTEK' => 'Satlak Dukteksi'] as $kode => $nama) {
            $this->s[$kode] = Satuan::firstOrCreate(['kode' => $kode], ['nama' => $nama, 'kategori' => 'Test']);
            $this->u[$kode] = User::create([
                'name' => $nama, 'username' => 'uji_'.strtolower($kode), 'password' => bcrypt('x'),
                'satuan_id' => $this->s[$kode]->id,
            ]);
        }
    }

    private function buatSuratMurniKeDanpus(): LaporanSurat
    {
        $surat = LaporanSurat::create([
            'satuan_id' => $this->s['SATLAK_DUKTEK']->id, 'user_id' => $this->u['SATLAK_DUKTEK']->id,
            'tujuan_satuan_id' => $this->s['DANPUS']->id, 'perihal' => 'ANGGARAN MEMBANGUN APLIKASI',
            'kategori' => 'Umum', 'deskripsi' => 'isi', 'lampiran_path' => 'lampiran-surat/x.pdf',
            'lampiran_nama_asli' => 'x.pdf', 'prioritas' => 'Biasa', 'status' => LaporanSurat::STATUS_MENUNGGU,
        ]);
        LaporanSuratRiwayat::create([
            'laporan_surat_id' => $surat->id, 'siklus' => $surat->siklus, 'aksi' => LaporanSuratRiwayat::AKSI_BUAT_SURAT,
            'pengirim_satuan_id' => $this->s['SATLAK_DUKTEK']->id, 'penerima_satuan_id' => $this->s['DANPUS']->id,
            'user_id' => $this->u['SATLAK_DUKTEK']->id, 'catatan' => 'x',
        ]);

        return $surat;
    }

    public function test_konfirmasi_danpus_langsung_menuntaskan_surat_murni(): void
    {
        $surat = $this->buatSuratMurniKeDanpus();

        // Sebelum dikonfirmasi: masih Surat Keluar (Satlak Dukteksi) & Surat Masuk (Danpus).
        $this->assertFalse($surat->fresh()->isSelesai());

        $this->actingAs($this->u['DANPUS'])
            ->patchJson("/laporan-surat/{$surat->id}/konfirmasi")
            ->assertOk();

        $surat->refresh();
        $this->assertTrue($surat->isSelesai());
        $this->assertSame(LaporanSurat::STATUS_DIKONFIRMASI, $surat->status);

        // Sisi pengirim asli (Satlak Dukteksi): keluar dari Surat Keluar, masuk Arsip.
        $satlakJson = $this->actingAs($this->u['SATLAK_DUKTEK'])->getJson('/laporan-surat/realtime')->assertOk()->json();
        $this->assertStringNotContainsString('ANGGARAN MEMBANGUN APLIKASI', $satlakJson['terkirim_items_html'] ?? '');
        $this->assertStringContainsString('ANGGARAN MEMBANGUN APLIKASI', $satlakJson['arsip_items_html'] ?? '');

        // Sisi Danpus: keluar dari Surat Masuk, masuk Arsip juga.
        $danpusJson = $this->actingAs($this->u['DANPUS'])->getJson('/laporan-surat/realtime')->assertOk()->json();
        $this->assertStringNotContainsString('ANGGARAN MEMBANGUN APLIKASI', $danpusJson['masuk_items_html'] ?? '');
        $this->assertStringContainsString('ANGGARAN MEMBANGUN APLIKASI', $danpusJson['arsip_items_html'] ?? '');
    }

    public function test_danpus_tidak_bisa_disposisi_ulang_surat_murni_yang_sudah_dikonfirmasi(): void
    {
        $surat = $this->buatSuratMurniKeDanpus();
        $this->actingAs($this->u['DANPUS'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();

        $this->actingAs($this->u['DANPUS'])->post("/laporan-surat/{$surat->id}/disposisi-ulang", [
            'tujuan_satuan_id' => $this->s['WADAN']->id, 'disposisi' => 'WADAN', 'tindakan' => ['CATAT'],
        ])->assertStatus(422);
    }

    public function test_alur_tiga_step_surat_buatan_danpus_sendiri_tidak_ikut_berubah(): void
    {
        // Sanity check: konfirmasi oleh Wadan atas surat BUATAN Danpus sendiri
        // (bukan surat murni dari satuan lain) TIDAK boleh langsung tuntas.
        $surat = LaporanSurat::create([
            'satuan_id' => $this->s['DANPUS']->id, 'user_id' => $this->u['DANPUS']->id,
            'tujuan_satuan_id' => $this->s['WADAN']->id, 'perihal' => 'SURAT DANPUS SENDIRI',
            'kategori' => 'Umum', 'deskripsi' => 'isi', 'lampiran_path' => 'lampiran-surat/x.pdf',
            'lampiran_nama_asli' => 'x.pdf', 'prioritas' => 'Biasa', 'status' => LaporanSurat::STATUS_MENUNGGU,
        ]);
        LaporanSuratRiwayat::create([
            'laporan_surat_id' => $surat->id, 'siklus' => $surat->siklus, 'aksi' => LaporanSuratRiwayat::AKSI_BUAT_SURAT,
            'pengirim_satuan_id' => $this->s['DANPUS']->id, 'penerima_satuan_id' => $this->s['WADAN']->id,
            'user_id' => $this->u['DANPUS']->id, 'catatan' => 'x',
        ]);

        $this->actingAs($this->u['WADAN'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();

        $this->assertFalse($surat->fresh()->isSelesai());
    }
}
