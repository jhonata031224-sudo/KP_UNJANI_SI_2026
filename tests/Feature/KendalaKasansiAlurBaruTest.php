<?php

namespace Tests\Feature;

use App\Models\LaporanKendala;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanKendalaBaruDiterima;
use App\Notifications\LaporanKendalaDikonfirmasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Alur Kendala Kasansi yang disederhanakan:
 *   Kasansi kirim (lampiran opsional, tanpa tembusan) -> Menunggu Konfirmasi
 *   -> Danpus tekan Konfirmasi (satu-satunya aksi) -> Dikonfirmasi. Selesai.
 */
class KendalaKasansiAlurBaruTest extends TestCase
{
    use RefreshDatabase;

    private array $u = [];
    private array $s = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        foreach ([
            'DANPUS' => 'Danpus', 'WADAN' => 'Wadan',
            'SILIWANGI' => 'Kasansi Siliwangi', 'JAYA' => 'Kasansi Jaya',
            'SATLAKDUKTEK' => 'Satlak Duktek',
        ] as $kode => $nama) {
            $this->s[$kode] = Satuan::firstOrCreate(['kode' => $kode], ['nama' => $nama, 'kategori' => 'Test']);
            $this->u[$kode] = User::create([
                'name' => $nama, 'username' => 'uji_'.strtolower($kode), 'password' => bcrypt('x'),
                'satuan_id' => $this->s[$kode]->id,
            ]);
        }
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'perihal' => 'Gangguan jaringan',
            'kategori' => 'Sarana',
            'deskripsi' => 'Link utama putus sejak pagi.',
            'prioritas' => 'Tinggi',
        ], $extra);
    }

    private function kendala(string $status = LaporanKendala::STATUS_MENUNGGU_KONFIRMASI, array $extra = []): LaporanKendala
    {
        return LaporanKendala::create(array_merge([
            'satuan_id' => $this->s['SILIWANGI']->id, 'user_id' => $this->u['SILIWANGI']->id,
            'tujuan_satuan_id' => $this->s['DANPUS']->id, 'perihal' => 'Uji', 'deskripsi' => 'isi',
            'prioritas' => 'Sedang', 'status' => $status,
        ], $extra));
    }

    public function test_kasansi_bisa_kirim_tanpa_lampiran_dan_tanpa_tembusan(): void
    {
        Notification::fake();

        $this->actingAs($this->u['SILIWANGI'])
            ->post(route('laporan-kendala.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $k = LaporanKendala::firstOrFail();
        $this->assertSame(LaporanKendala::STATUS_MENUNGGU_KONFIRMASI, $k->status);
        $this->assertSame($this->s['DANPUS']->id, $k->tujuan_satuan_id);
        $this->assertCount(0, $k->lampirans);
        $this->assertDatabaseCount('laporan_kendala_tembusans', 0);
        Notification::assertSentTo($this->u['DANPUS'], LaporanKendalaBaruDiterima::class);
        Notification::assertNotSentTo($this->u['WADAN'], LaporanKendalaBaruDiterima::class);
    }

    public function test_kasansi_bisa_kirim_dengan_banyak_lampiran(): void
    {
        Notification::fake();

        $this->actingAs($this->u['SILIWANGI'])
            ->post(route('laporan-kendala.store'), $this->payload([
                'lampiran' => [
                    UploadedFile::fake()->create('a.pdf', 100),
                    UploadedFile::fake()->create('b.xlsx', 100),
                ],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertCount(2, LaporanKendala::firstOrFail()->lampirans);
    }

    public function test_lampiran_tetap_divalidasi_ukurannya_kalau_diberikan(): void
    {
        $this->actingAs($this->u['SILIWANGI'])
            ->post(route('laporan-kendala.store'), $this->payload([
                'lampiran' => [UploadedFile::fake()->create('besar.pdf', 11000)],
            ]))
            ->assertSessionHasErrors('lampiran.0');

        $this->assertDatabaseCount('laporan_kendalas', 0);
    }

    public function test_field_wajib_tetap_divalidasi(): void
    {
        $this->actingAs($this->u['SILIWANGI'])
            ->post(route('laporan-kendala.store'), ['kategori' => 'x'])
            ->assertSessionHasErrors(['perihal', 'deskripsi', 'prioritas']);
    }

    public function test_hanya_kasansi_yang_boleh_mengirim(): void
    {
        foreach (['DANPUS', 'WADAN', 'SATLAKDUKTEK'] as $kode) {
            $this->actingAs($this->u[$kode])
                ->post(route('laporan-kendala.store'), $this->payload())
                ->assertForbidden();
        }
        $this->assertDatabaseCount('laporan_kendalas', 0);
    }

    public function test_danpus_konfirmasi_mengubah_status_dan_memberi_tahu_kasansi(): void
    {
        Notification::fake();
        $k = $this->kendala();

        $this->actingAs($this->u['DANPUS'])
            ->patch(route('laporan-kendala.konfirmasi', $k))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $k->refresh();
        $this->assertSame(LaporanKendala::STATUS_DIKONFIRMASI, $k->status);
        $this->assertNotNull($k->confirmed_at);
        $this->assertSame($this->u['DANPUS']->id, $k->confirmed_by);
        Notification::assertSentTo($this->u['SILIWANGI'], LaporanKendalaDikonfirmasi::class);
        Notification::assertNotSentTo($this->u['JAYA'], LaporanKendalaDikonfirmasi::class);
    }

    public function test_konfirmasi_kedua_kali_ditolak(): void
    {
        Notification::fake();
        $k = $this->kendala();

        $this->actingAs($this->u['DANPUS'])->patch(route('laporan-kendala.konfirmasi', $k));
        $this->actingAs($this->u['DANPUS'])->patch(route('laporan-kendala.konfirmasi', $k))->assertStatus(422);

        Notification::assertSentToTimes($this->u['SILIWANGI'], LaporanKendalaDikonfirmasi::class, 1);
    }

    public function test_selain_danpus_tidak_bisa_konfirmasi_lewat_request_langsung(): void
    {
        $k = $this->kendala();

        foreach (['WADAN', 'SILIWANGI', 'JAYA', 'SATLAKDUKTEK'] as $kode) {
            $this->actingAs($this->u[$kode])
                ->patch(route('laporan-kendala.konfirmasi', $k))
                ->assertForbidden();
        }

        $this->assertSame(LaporanKendala::STATUS_MENUNGGU_KONFIRMASI, $k->fresh()->status);
        $this->assertNull($k->fresh()->confirmed_at);
    }

    public function test_aksi_lama_tidak_lagi_tersedia(): void
    {
        $k = $this->kendala();

        foreach (['laporan-kendala.status', 'laporan-kendala.teruskan', 'laporan-kendala.upload-dokumen'] as $nama) {
            $this->assertFalse(\Illuminate\Support\Facades\Route::has($nama), "Route {$nama} seharusnya sudah dihapus.");
        }

        foreach (['status', 'teruskan', 'upload-dokumen'] as $aksi) {
            foreach (['patch', 'post'] as $metode) {
                $this->actingAs($this->u['DANPUS'])
                    ->{$metode}("/laporan-kendala/{$k->id}/{$aksi}", ['status' => 'Ditolak', 'catatan' => 'x'])
                    ->assertNotFound();
            }
        }

        $this->assertSame(LaporanKendala::STATUS_MENUNGGU_KONFIRMASI, $k->fresh()->status);
    }

    public function test_dashboard_danpus_wadan_dan_kasansi_dirender(): void
    {
        $this->kendala();
        $this->kendala(LaporanKendala::STATUS_DIKONFIRMASI, ['perihal' => 'Sudah arsip', 'confirmed_at' => now(), 'confirmed_by' => $this->u['DANPUS']->id]);

        $danpus = $this->actingAs($this->u['DANPUS'])->get('/dashboard');
        $danpus->assertOk()->assertSee('Menunggu Konfirmasi')->assertSee(route('laporan-kendala.konfirmasi', LaporanKendala::first()), false);

        $wadan = $this->actingAs($this->u['WADAN'])->get('/dashboard');
        $wadan->assertOk()->assertSee('Menunggu Konfirmasi')
            ->assertDontSee('/konfirmasi', false);

        $kasansi = $this->actingAs($this->u['SILIWANGI'])->get('/dashboard');
        $kasansi->assertOk()->assertSee('Menunggu Konfirmasi')->assertSee('Dikonfirmasi Danpus')
            ->assertDontSee('tembusan_ke', false)->assertDontSee('Tembusan Kendala');

        $this->actingAs($this->u['SATLAKDUKTEK'])->get('/dashboard')->assertOk();
    }

    public function test_realtime_danpus_dan_kasansi(): void
    {
        $this->kendala();

        $this->actingAs($this->u['DANPUS'])->getJson(route('laporan-kendala.realtime'))
            ->assertOk()->assertJsonPath('latest_id', LaporanKendala::first()->id);

        $this->actingAs($this->u['SILIWANGI'])->getJson(route('laporan-kendala.realtime'))
            ->assertOk()->assertJsonStructure(['terkirim_items_html', 'arsip_items_html']);
    }

    public function test_migration_data_hanya_mengubah_label_menunggu_dan_tidak_merusak_data_lama(): void
    {
        $menunggu   = $this->kendala('Menunggu');
        $balasan    = $this->kendala('Menunggu Balasan');
        $tindak     = $this->kendala('Ditindaklanjuti', ['catatan' => 'catatan lama']);
        $ditolak    = $this->kendala('Ditolak', ['catatan' => 'alasan penolakan']);
        $konfirmasi = $this->kendala(LaporanKendala::STATUS_DIKONFIRMASI, ['confirmed_at' => now(), 'confirmed_by' => $this->u['DANPUS']->id]);

        (require base_path('database/migrations/2026_10_08_000001_simplify_laporan_kendala_status_flow.php'))->up();

        $this->assertSame('Menunggu Konfirmasi', $menunggu->fresh()->status);
        $this->assertSame('Menunggu Konfirmasi', $balasan->fresh()->status);
        // Data lama lain tidak disentuh (label & catatan utuh), tetap bisa dikonfirmasi.
        $this->assertSame('Ditindaklanjuti', $tindak->fresh()->status);
        $this->assertSame('catatan lama', $tindak->fresh()->catatan);
        $this->assertSame('Ditolak', $ditolak->fresh()->status);
        $this->assertSame('alasan penolakan', $ditolak->fresh()->catatan);
        $this->assertSame(LaporanKendala::STATUS_DIKONFIRMASI, $konfirmasi->fresh()->status);
        $this->assertSame(5, LaporanKendala::count());

        Notification::fake();
        $this->actingAs($this->u['DANPUS'])->patch(route('laporan-kendala.konfirmasi', $ditolak))->assertSessionHasNoErrors();
        $this->assertSame(LaporanKendala::STATUS_DIKONFIRMASI, $ditolak->fresh()->status);
    }
}
