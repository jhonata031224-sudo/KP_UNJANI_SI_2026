<?php

namespace Tests\Feature;

use App\Models\LaporanSurat;
use App\Models\LaporanSuratRiwayat;
use App\Models\LaporanSuratTembusan;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanSuratBalasanDikonfirmasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ALUR NAIK (balasan): Danpus -> Wadan -> satuan pelaksana (mis. Duktek) ACC
 * -> satuan klik "Kirim Surat" (langsung ke Wadan) -> Wadan konfirmasi &
 * teruskan ke Danpus -> Danpus konfirmasi/ACC -> satuan dapat notifikasi
 * informasi (tidak bisa diklik).
 */
class SuratBalasanNaikTest extends TestCase
{
    use RefreshDatabase;

    private array $s = [];
    private array $u = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $daftar = [
            'DANPUS'       => 'Danpus',
            'WADAN'        => 'Wadan',
            'URDAL'        => 'Urdal',
            'SATLAKDUKTEK' => 'Satlak Dukteksi',
            'POKANALIS'    => 'Pok Analis',
        ];
        foreach ($daftar as $kode => $nama) {
            $this->s[$kode] = Satuan::firstOrCreate(['kode' => $kode], ['nama' => $nama, 'kategori' => 'Test']);
            $this->u[$kode] = User::create([
                'name' => $nama, 'username' => 'uji_'.strtolower($kode), 'password' => bcrypt('x'),
                'satuan_id' => $this->s[$kode]->id,
            ]);
        }
    }

    private function buatSuratTurun(): LaporanSurat
    {
        $surat = LaporanSurat::create([
            'satuan_id' => $this->s['DANPUS']->id, 'user_id' => $this->u['DANPUS']->id,
            'tujuan_satuan_id' => $this->s['WADAN']->id, 'perihal' => 'TEST BALASAN',
            'kategori' => 'Umum', 'deskripsi' => 'isi', 'lampiran_path' => 'lampiran-surat/x.pdf',
            'lampiran_nama_asli' => 'x.pdf', 'prioritas' => 'Biasa', 'siklus' => 1,
            'status' => LaporanSurat::STATUS_MENUNGGU,
        ]);
        LaporanSuratRiwayat::create([
            'laporan_surat_id' => $surat->id, 'siklus' => 1, 'aksi' => LaporanSuratRiwayat::AKSI_BUAT_SURAT,
            'pengirim_satuan_id' => $this->s['DANPUS']->id, 'penerima_satuan_id' => $this->s['WADAN']->id,
            'user_id' => $this->u['DANPUS']->id, 'catatan' => 'x',
        ]);

        return $surat;
    }

    /** Danpus buat -> Wadan konfirmasi+teruskan -> Duktek ACC (siap dibalas). */
    private function suratTurunSampaiDuktekAcc(): LaporanSurat
    {
        $surat = $this->buatSuratTurun();
        $this->actingAs($this->u['WADAN'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $this->actingAs($this->u['WADAN'])->post("/laporan-surat/{$surat->id}/teruskan", [
            'tujuan_satuan_id' => $this->s['SATLAKDUKTEK']->id, 'disposisi' => 'DANSATLAK DUKTEKSI', 'tindakan' => ['CATAT'],
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->u['SATLAKDUKTEK'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();

        return $surat->fresh();
    }

    private function kirimBalasan(LaporanSurat $surat, array $extra = [])
    {
        return $this->actingAs($this->u['SATLAKDUKTEK'])->post('/laporan-surat', array_merge([
            'induk_surat_id' => $surat->id,
            'lampiran'       => UploadedFile::fake()->create('hasil.pdf', 50, 'application/pdf'),
            'deskripsi'      => 'Sudah dikerjakan',
        ], $extra));
    }

    private function realtime(string $kode): array
    {
        return $this->actingAs($this->u[$kode])->getJson('/laporan-surat/realtime')->assertOk()->json();
    }

    public function test_surat_yang_sudah_acc_tetap_di_surat_masuk_satuan_dan_belum_arsip(): void
    {
        $this->suratTurunSampaiDuktekAcc();

        $json = $this->realtime('SATLAKDUKTEK');
        $this->assertStringContainsString('TEST BALASAN', $json['masuk_items_html']);
        $this->assertStringContainsString('Kirim Surat', $json['masuk_items_html']);   // tombol di bawah Lihat Detail
        $this->assertStringNotContainsString('TEST BALASAN', $json['arsip_items_html']);
    }

    public function test_hanya_satuan_pelaksana_yang_boleh_kirim_balasan_naik(): void
    {
        $surat = $this->suratTurunSampaiDuktekAcc();

        foreach (['WADAN', 'DANPUS', 'URDAL'] as $kode) {
            $this->assertFalse($surat->isMenungguBalasanSatuan($this->s[$kode]->id), $kode);
            $this->assertFalse(LaporanSurat::satuanBolehKirimBalasanNaik($kode), $kode);
        }
        $this->assertTrue($surat->isMenungguBalasanSatuan($this->s['SATLAKDUKTEK']->id));
    }

    public function test_kirim_surat_mengalir_ke_wadan_dan_pindah_ke_arsip_satuan(): void
    {
        $surat = $this->suratTurunSampaiDuktekAcc();

        // Request TIDAK membawa perihal/kategori/prioritas -- field surat tidak diinput ulang.
        $this->kirimBalasan($surat)->assertSessionHasNoErrors();

        $surat->refresh();
        $this->assertSame($this->s['WADAN']->id, (int) $surat->tujuan_satuan_id);
        $this->assertSame(LaporanSurat::STATUS_MENUNGGU, $surat->status);
        // Row yang sama (bukan surat baru) & field aslinya utuh.
        $this->assertSame(1, LaporanSurat::count());
        $this->assertSame(['TEST BALASAN', 'Umum', 'Biasa'], [$surat->perihal, $surat->kategori, $surat->prioritas]);
        $this->assertSame('hasil.pdf', $surat->lampiran_nama_asli);

        $riwayat = LaporanSuratRiwayat::where('laporan_surat_id', $surat->id)
            ->where('aksi', LaporanSuratRiwayat::AKSI_SURAT_KELUAR)->first();
        $this->assertNotNull($riwayat);
        $this->assertSame($this->s['SATLAKDUKTEK']->id, (int) $riwayat->pengirim_satuan_id);
        $this->assertSame($this->s['WADAN']->id, (int) $riwayat->penerima_satuan_id);

        // Sisi satuan: keluar dari Surat Masuk, masuk Arsip.
        $duktek = $this->realtime('SATLAKDUKTEK');
        $this->assertStringNotContainsString('TEST BALASAN', $duktek['masuk_items_html']);
        $this->assertStringContainsString('TEST BALASAN', $duktek['arsip_items_html']);

        // Sisi Wadan: masuk Surat Masuk.
        $this->assertStringContainsString('TEST BALASAN', $this->realtime('WADAN')['masuk_items_html']);
    }

    public function test_tembusan_opsional_dan_tidak_boleh_ke_wadan_danpus_urdal(): void
    {
        $surat = $this->suratTurunSampaiDuktekAcc();

        foreach (['WADAN', 'DANPUS', 'URDAL'] as $kode) {
            $this->kirimBalasan($surat, ['tembusan' => [$this->s[$kode]->id]])
                ->assertSessionHasErrors('tembusan.0');
        }
        // Semua ditolak -> surat belum berpindah.
        $this->assertSame($this->s['SATLAKDUKTEK']->id, (int) $surat->fresh()->tujuan_satuan_id);

        $this->kirimBalasan($surat, ['tembusan' => [$this->s['POKANALIS']->id]])->assertSessionHasNoErrors();
        $this->assertTrue(
            LaporanSuratTembusan::where('laporan_surat_id', $surat->id)
                ->where('satuan_id', $this->s['POKANALIS']->id)
                ->where('jenis', LaporanSuratTembusan::JENIS_TEMBUSAN)->exists()
        );
    }

    public function test_lampiran_wajib_diisi(): void
    {
        $surat = $this->suratTurunSampaiDuktekAcc();

        $this->actingAs($this->u['SATLAKDUKTEK'])
            ->post('/laporan-surat', ['induk_surat_id' => $surat->id])
            ->assertSessionHasErrors('lampiran');
    }

    public function test_kirim_surat_ditolak_jika_belum_acc_bukan_pemegang_atau_sudah_selesai(): void
    {
        // Belum di-ACC Duktek (status menunggu) -> 422.
        $surat = $this->buatSuratTurun();
        $this->actingAs($this->u['WADAN'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $this->actingAs($this->u['WADAN'])->post("/laporan-surat/{$surat->id}/teruskan", [
            'tujuan_satuan_id' => $this->s['SATLAKDUKTEK']->id, 'disposisi' => 'DANSATLAK DUKTEKSI', 'tindakan' => ['CATAT'],
        ]);
        $this->kirimBalasan($surat)->assertStatus(422);

        // Sudah ACC tapi yang mengirim bukan pemegang surat -> 403.
        $this->actingAs($this->u['SATLAKDUKTEK'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $this->actingAs($this->u['POKANALIS'])->post('/laporan-surat', [
            'induk_surat_id' => $surat->id,
            'lampiran'       => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])->assertStatus(403);

        // Surat sudah selesai -> 422.
        $surat->update(['is_selesai' => true, 'status' => LaporanSurat::STATUS_SELESAI]);
        $this->kirimBalasan($surat)->assertStatus(422);
    }

    public function test_surat_baru_tanpa_induk_tetap_lewat_jalur_lama(): void
    {
        // Satlak membuat surat baru ke Urdal (tanpa induk_surat_id) -> baris baru, bukan balasan.
        $this->actingAs($this->u['SATLAKDUKTEK'])->post('/laporan-surat', [
            'tujuan_satuan_id' => $this->s['URDAL']->id,
            'perihal'          => 'SURAT BARU',
            'kategori'         => 'Koordinasi',
            'prioritas'        => 'Rendah',
            'deskripsi'        => 'isi ringkasan',
            'lampiran'         => UploadedFile::fake()->create('baru.pdf', 20, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $baru = LaporanSurat::where('perihal', 'SURAT BARU')->first();
        $this->assertNotNull($baru);
        $this->assertSame($this->s['SATLAKDUKTEK']->id, (int) $baru->satuan_id);
        $this->assertSame(
            0,
            LaporanSuratRiwayat::where('laporan_surat_id', $baru->id)
                ->where('aksi', LaporanSuratRiwayat::AKSI_SURAT_KELUAR)->count()
        );
    }

    public function test_wadan_di_fase_naik_meneruskan_ke_danpus_dan_danpus_acc_memberi_notifikasi_ke_satuan(): void
    {
        $surat = $this->suratTurunSampaiDuktekAcc();
        $this->kirimBalasan($surat)->assertSessionHasNoErrors();

        // Kartu Wadan menandai mode naik (Konfirmasi -> Teruskan ke Danpus, tanpa form disposisi).
        $htmlWadan = $this->realtime('WADAN')['masuk_items_html'];
        $this->assertStringContainsString('data-wadan-naik="1"', $htmlWadan);
        $this->assertStringContainsString('data-ke-danpus-action', $htmlWadan);

        $this->actingAs($this->u['WADAN'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        $this->actingAs($this->u['WADAN'])->post("/laporan-surat/{$surat->id}/ke-danpus")->assertSessionHasNoErrors();
        $this->assertSame($this->s['DANPUS']->id, (int) $surat->fresh()->tujuan_satuan_id);

        // Danpus ACC -> notifikasi informasi ke satuan yang tadi membalas (Duktek), sekali saja.
        Notification::fake();
        $this->actingAs($this->u['DANPUS'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        Notification::assertSentTo($this->u['SATLAKDUKTEK'], LaporanSuratBalasanDikonfirmasi::class);
        Notification::assertNotSentTo($this->u['POKANALIS'], LaporanSuratBalasanDikonfirmasi::class);
        Notification::assertSentToTimes($this->u['SATLAKDUKTEK'], LaporanSuratBalasanDikonfirmasi::class, 1);

        // Klik ulang tidak menggandakan notifikasi.
        $this->actingAs($this->u['DANPUS'])->patchJson("/laporan-surat/{$surat->id}/konfirmasi")->assertOk();
        Notification::assertSentToTimes($this->u['SATLAKDUKTEK'], LaporanSuratBalasanDikonfirmasi::class, 1);
    }

    public function test_notifikasi_balasan_dikonfirmasi_hanya_keterangan_tanpa_url(): void
    {
        $surat = $this->suratTurunSampaiDuktekAcc();
        $data  = (new LaporanSuratBalasanDikonfirmasi($surat))->toDatabase($this->u['SATLAKDUKTEK']);

        $this->assertSame('surat_info', $data['tipe']);
        $this->assertArrayNotHasKey('url', $data);   // tanpa url = tidak bisa diklik di lonceng
        $this->assertStringContainsString('Danpus', $data['pesan']);
    }

    public function test_balasan_lama_tidak_menempel_setelah_disposisi_ulang_siklus_baru(): void
    {
        $surat = $this->suratTurunSampaiDuktekAcc();
        $this->kirimBalasan($surat)->assertSessionHasNoErrors();

        $surat->refresh();
        $this->assertTrue($surat->adaBalasanNaikSiklusIni());

        // Danpus disposisi ulang -> siklus 2: balasan siklus 1 tidak dihitung lagi.
        $surat->update(['siklus' => 2]);
        $surat->unsetRelation('riwayats');
        $this->assertFalse($surat->adaBalasanNaikSiklusIni());
    }
}
