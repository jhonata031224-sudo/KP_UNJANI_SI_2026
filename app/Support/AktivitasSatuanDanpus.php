<?php

namespace App\Support;

use App\Models\LaporanKendala;
use App\Models\LaporanSurat;
use App\Models\LaporanSuratRiwayat;
use App\Models\Satuan;
use Illuminate\Support\Collection;

/**
 * Data tambahan menu "Riwayat Aktivitas" akun DANPUS: aktivitas SURAT dan
 * KENDALA tiap satuan, di samping aktivitas Laporan yang sudah ada.
 *
 * Murni BACA. Tidak mengubah alur/status apa pun. Hanya memakai kolom & relasi
 * yang sudah ada di LaporanSurat, LaporanSuratRiwayat, LaporanSuratTembusan,
 * dan LaporanKendala.
 *
 * ---- Batas hak akses (sengaja sempit) ----
 *  - Surat: hanya surat yang pengirim aslinya (satuan_id) adalah DANPUS atau
 *    salah satu satuan yang dipantau. Satuan non-pimpinan di sistem ini cuma
 *    bisa mengirim surat KE Danpus (tujuan dikunci di
 *    LaporanSuratController::store), jadi Danpus selalu menjadi pihak dalam
 *    alurnya. Surat yang dibuat Wadan (tujuan bebas, Danpus bukan pihak)
 *    SENGAJA tidak ikut.
 *  - Kendala: hanya kendala bertujuan DANPUS dari satuan yang dipantau
 *    (alur Kendala Kasansi -> Danpus).
 *
 * ---- Pengelompokan per satuan (tanpa duplikasi) ----
 * Satu surat muncul SEKALI per satuan. Satu surat boleh muncul di beberapa
 * satuan berbeda bila memang melibatkan keduanya (mis. surat Danpus yang
 * diteruskan Wadan ke Satlak Kal tampil di Satlak Kal). Satuan dianggap
 * terlibat bila: pengirim asli, tujuan saat ini, pengirim/penerima di
 * riwayat, atau tembusan -- definisi yang sama dengan pemantauan surat
 * Duktek di DashboardController::pelaporan().
 *
 * Arah dilihat dari sisi satuan:
 *   'keluar' = satuan adalah pengirim asli (satuan_id);
 *   'masuk'  = selain itu (diterima / ditujukan / ditangani / tembusan).
 * Balasan naik dari satuan (riwayat SURAT_KELUAR) TIDAK menggandakan kartu;
 * hanya ditandai lewat properti balasanDikirim.
 */
class AktivitasSatuanDanpus
{
    /**
     * @param  Collection<int, Satuan>  $satuanList  satuan yang dipantau
     * @return array{
     *   surat: array<int, Collection>,
     *   kendala: array<int, Collection>,
     *   kendalaDidukung: array<int, bool>,
     *   ringkasan: array<int, array<string,int>>
     * }
     */
    public static function bangun(Collection $satuanList, ?Satuan $danpus): array
    {
        $hasil = ['surat' => [], 'kendala' => [], 'kendalaDidukung' => [], 'ringkasan' => []];
        $danpusId = $danpus?->id;
        $idDipantau = $satuanList->pluck('id')->map(fn ($i) => (int) $i)->all();

        $suratSemua = collect();
        if ($danpusId && $idDipantau) {
            $suratSemua = LaporanSurat::with([
                    'satuan', 'tujuanSatuan', 'dikonfirmasiOleh',
                    'riwayats.pengirimSatuan', 'riwayats.penerimaSatuan', 'tembusans.satuan',
                ])
                ->whereIn('satuan_id', array_merge($idDipantau, [$danpusId]))
                ->latest()
                ->get();
        }

        $kendalaSemua = collect();
        if ($danpusId && $idDipantau) {
            $kendalaSemua = LaporanKendala::with(['satuan', 'lampirans', 'confirmedBy'])
                ->where('tujuan_satuan_id', $danpusId)
                ->whereIn('satuan_id', $idDipantau)
                ->latest()
                ->get();
        }

        foreach ($satuanList as $satuan) {
            $sid = (int) $satuan->id;

            $surat = self::suratUntukSatuan($suratSemua, $sid)
                ->map(function (LaporanSurat $s) use ($sid, $danpusId) {
                    $s->arahUntukSatuan = (int) $s->satuan_id === $sid ? 'keluar' : 'masuk';
                    $s->balasanDikirim = $s->riwayats->contains(
                        fn ($r) => $r->aksi === LaporanSuratRiwayat::AKSI_SURAT_KELUAR && (int) $r->pengirim_satuan_id === $sid
                    );
                    $s->hanyaTembusan = $s->arahUntukSatuan === 'masuk'
                        && (int) $s->tujuan_satuan_id !== $sid
                        && ! $s->riwayats->contains(fn ($r) => (int) $r->penerima_satuan_id === $sid || (int) $r->pengirim_satuan_id === $sid);
                    $s->diarsipkan = self::suratDiarsipkan($s);
                    $s->menungguDanpus = $danpusId !== null
                        && ! $s->isSelesai()
                        && $s->status === LaporanSurat::STATUS_MENUNGGU
                        && (int) $s->tujuan_satuan_id === (int) $danpusId;

                    return $s;
                })
                ->values();

            // Kendala hanya ada untuk Kasansi (21 Kotama).
            $didukung = in_array(strtoupper((string) $satuan->kode), Satuan::KODE_KOTAMA, true);
            $kendala = $didukung
                ? $kendalaSemua->where('satuan_id', $sid)->values()
                : collect();

            $hasil['surat'][$sid] = $surat;
            $hasil['kendala'][$sid] = $kendala;
            $hasil['kendalaDidukung'][$sid] = $didukung;

            $kendalaMenunggu = $kendala->reject(fn ($k) => $k->sudahDikonfirmasi())->count();
            $suratMenungguDanpus = $surat->where('menungguDanpus', true)->count();

            $hasil['ringkasan'][$sid] = [
                'surat_total'            => $surat->count(),
                'surat_masuk'            => $surat->where('arahUntukSatuan', 'masuk')->count(),
                'surat_keluar'           => $surat->where('arahUntukSatuan', 'keluar')->count(),
                'surat_menunggu'         => $surat->filter(fn ($s) => ! $s->isSelesai() && $s->status === LaporanSurat::STATUS_MENUNGGU)->count(),
                'surat_menunggu_danpus'  => $suratMenungguDanpus,
                'surat_arsip'            => $surat->where('diarsipkan', true)->count(),
                'kendala_total'          => $kendala->count(),
                'kendala_menunggu'       => $kendalaMenunggu,
                'kendala_dikonfirmasi'   => $kendala->count() - $kendalaMenunggu,
                // Yang butuh aksi Danpus langsung: surat bertujuan Danpus yang
                // belum dikonfirmasi + kendala yang belum dikonfirmasi.
                'perlu_tindak_lanjut'    => $suratMenungguDanpus + $kendalaMenunggu,
            ];
        }

        return $hasil;
    }

    /** Surat yang melibatkan satuan $sid (lihat penjelasan di docblock kelas). */
    public static function suratUntukSatuan(Collection $suratSemua, int $sid): Collection
    {
        return $suratSemua->filter(function (LaporanSurat $s) use ($sid) {
            return (int) $s->satuan_id === $sid
                || (int) $s->tujuan_satuan_id === $sid
                || $s->riwayats->contains(fn ($r) => (int) $r->penerima_satuan_id === $sid || (int) $r->pengirim_satuan_id === $sid)
                || $s->tembusans->contains(fn ($t) => (int) $t->satuan_id === $sid);
        })->sortByDesc('created_at')->values();
    }

    /**
     * Surat sudah masuk Arsip -- versi in-memory LaporanSurat::scopeArsipPusat():
     * selesai, atau surat buatan Danpus yang alur turunnya tuntas (dikonfirmasi
     * dan pemegang saat ini bukan Wadan).
     */
    public static function suratDiarsipkan(LaporanSurat $s): bool
    {
        if ($s->isSelesai()) {
            return true;
        }

        return strtoupper((string) ($s->satuan->kode ?? '')) === 'DANPUS'
            && $s->status === LaporanSurat::STATUS_DIKONFIRMASI
            && ! in_array(strtoupper((string) ($s->tujuanSatuan->kode ?? '')), LaporanSurat::KODE_PERANTARA_SISI_PENGIRIM, true);
    }
}
