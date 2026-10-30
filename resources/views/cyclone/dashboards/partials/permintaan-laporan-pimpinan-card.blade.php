@php
    // Status & tombol aksi di kartu ini sengaja lebih spesifik dari checkpoint
    // polos "Sedang diproses" di Log Aktivitas -- pimpinan perlu sinyal kapan
    // harus bertindak (Menunggu), dan kapan laporan lagi diulang satuan
    // (Revisi) setelah pimpinan klik tombol Revisi dari kartu Selesai·Ditolak.
    // Logika ini sama persis dengan permintaan-laporan-pimpinan-row.blade.php
    // (versi tabel lama) -- cuma dipindah ke markup kartu.
    // $riwayatMode = kartu ini dirender di section #riwayat (Riwayat Laporan),
    // bukan #permintaan-laporan aktif -> menu titik-3 jadi "Lihat Aktivitas" +
    // "Revisi" (bukan "Arsipkan"). Default false (dipakai di list aktif).
    $riwayatMode = $riwayatMode ?? false;
    $laporanTerakhir = $item->laporans->sortByDesc('id')->first();
    // Satu sumber kebenaran -- lihat PermintaanLaporan::isSedangRevisi()
    // (ikut nangkep laporan final ditolak yang laporan_id-nya sudah direset
    // null lewat revisiDariRiwayat, bukan cuma status literal "Revisi").
    $sedangRevisi = $item->isSedangRevisi();
    // Catatan/keterangan penolakan yang ditulis Pimpinan sendiri waktu klik
    // "Tolak" (LaporanController::updateStatus -> laporans.catatan). Ditarik
    // dari laporan DITOLAK paling baru yang ada catatannya, jadi tetap kebaca
    // pas status udah balik "Revisi" (laporan_id direset null). Sama persis
    // dengan $catatanPenolakan di kartu satuan (permintaan-laporan-item.blade.php).
    // Ikut cocokin "revisi" -- revisiDariRiwayat() nge-flip status laporan
    // terakhir "Ditolak" -> "Revisi" tapi teks catatannya tetap ada.
    $laporanDitolakTerakhir = $item->laporans
        ->filter(fn ($l) => (str_contains(strtolower((string) $l->status), 'tolak') || str_contains(strtolower((string) $l->status), 'revisi')) && trim((string) $l->catatan) !== '')
        ->sortByDesc('id')
        ->first();
    $catatanPenolakan = trim((string) ($laporanDitolakTerakhir?->catatan ?? ''));
    if ($item->status === \App\Models\PermintaanLaporan::STATUS_DIBATALKAN) {
        $statusPimpinan = 'Dibatalkan'; $statusPimpinanClass = 'bad';
    } elseif ($item->status === \App\Models\PermintaanLaporan::STATUS_PEMERIKSAAN) {
        $statusPimpinan = 'Menunggu'; $statusPimpinanClass = 'blue';
    } elseif ($item->status === \App\Models\PermintaanLaporan::STATUS_SELESAI) {
        $hasilAkhir = strtolower($item->laporan?->status ?? '');
        if (str_contains($hasilAkhir, 'tolak')) { $statusPimpinan = 'Ditolak'; $statusPimpinanClass = 'bad'; }
        else { $statusPimpinan = 'Disetujui'; $statusPimpinanClass = 'ok'; }
    } elseif ($sedangRevisi) { $statusPimpinan = 'Revisi'; $statusPimpinanClass = 'revisi'; }
    elseif ($item->isTerlambat()) { $statusPimpinan = 'Terlambat'; $statusPimpinanClass = 'bad'; }
    // "Terbaru" = permintaan belum dikonfirmasi/dikerjakan satuan -- sama kayak
    // badge "Terbaru" di kartu satuan (permintaan-laporan-item.blade.php).
    // Ditaruh SETELAH cek isTerlambat() supaya permintaan baru yang sudah
    // kepalang lewat deadline tetap kebaca "Terlambat" (merah), bukan "Terbaru".
    elseif ($item->status === \App\Models\PermintaanLaporan::STATUS_BELUM) { $statusPimpinan = 'Terbaru'; $statusPimpinanClass = 'new'; }
    else { $statusPimpinan = 'Sedang diproses'; $statusPimpinanClass = 'wait'; }
    $bisaEditDeadline = $item->isDapatEditDeadline();
    $alasanTidakBisaEdit = $bisaEditDeadline ? '' : $item->alasanTidakBisaEditDeadline();
    // Sama kayak eligibleStatus() di danpus-permintaan-arsip-mode.blade.php --
    // cuma 4 status akhir ini yang boleh dipindah ke Riwayat Laporan.
    $archiveEligible = in_array($statusPimpinan, ['Terlambat', 'Dibatalkan', 'Disetujui', 'Ditolak'], true);
    $dcardPrioClass = 'prio-' . strtolower($item->prioritas);
    $dcardTasksTotal = $item->tasks->count();
    $dcardTasksDone = $item->tasks->where('selesai', true)->count();
    // Pill deadline di footer DISEMBUNYIKAN (bukan dicoret) buat status yang
    // bikin hitungan mundur udah gak relevan -- semua KECUALI yang deadline-nya
    // masih jadi patokan hidup ("Terbaru" / "Sedang diproses" / "Revisi") atau
    // alarm aktif ("Terlambat"). "Revisi" IKUT nampilin pill: Pimpinan ngasih
    // deadline baru pas klik Revisi (revisiDariRiwayat), jadi deadline-nya masih
    // patokan hidup. Jadi Menunggu, Dibatalkan, Selesai (disetujui/ditolak) ->
    // pill deadline hilang total.
    // Logika ini disamakan dengan kartu satuan (permintaan-laporan-item.blade.php).
    $deadlineHidden = !in_array($statusPimpinan, ['Terbaru', 'Sedang diproses', 'Terlambat', 'Revisi'], true);
    // Warna urgensi pill deadline -- sama kayak $deadlineClass di kartu satuan
    // (permintaan-laporan-item.blade.php), biar kartu Pimpinan ikut kebaca
    // merah/kuning/normal, bukan abu-abu polos semua.
    $deadlineClass = $item->isTerlambat() ? 'bad' : ($item->deadline_at && $item->deadline_at->diffInHours(now()) <= 24 ? 'near' : 'normal');
    // Data checklist task buat modal "Lihat Progres" (read-only) -- bentuknya
    // SAMA PERSIS dengan arsipItemData() di PermintaanLaporanController (yang
    // dipakai buildArchiveTaskTrack() di danpus-permintaan-arsip-mode.blade.php
    // buat Riwayat/Status), jadi fungsi render-nya bisa dipakai ulang tanpa
    // perubahan buat kartu yang masih aktif ini.
    $tasksJson = $item->tasks->sortBy('urutan')->values()->map(function ($task) {
        $taskLaporan = $task->laporans->sortByDesc('id')->first();
        return [
            'deskripsi' => $task->deskripsi,
            'detail' => $task->detail,
            'selesai' => (bool) $task->selesai,
            'selesai_at' => $task->selesai_at?->translatedFormat('d M Y H:i'),
            'laporan' => $taskLaporan ? [
                'perihal' => $taskLaporan->perihal,
                'prioritas' => $taskLaporan->prioritas,
                'progres' => $taskLaporan->progres,
                'kendala' => $taskLaporan->kendala,
                'proyek' => $taskLaporan->proyek,
                'tanggal' => $taskLaporan->created_at?->translatedFormat('d M Y H:i'),
                'deskripsi' => $taskLaporan->deskripsi,
                'lampiran' => $taskLaporan->semuaLampiran->map(fn ($x) => ['url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values(),
            ] : null,
        ];
    })->values()->toJson();
@endphp
<article class="deadline-sender-item" data-realtime-permintaan-id="{{ $item->id }}" data-search="{{ strtolower($item->perihal.' '.($item->instruksi ?? '').' '.($item->tujuanSatuan->nama ?? '').' '.($item->tujuanSatuan->kode ?? '')) }}" data-status="{{ $statusPimpinan }}" data-prioritas="{{ e($item->prioritas) }}" data-deadline-at="{{ $item->deadline_at?->timestamp ?? 0 }}" data-archived-at="{{ $item->archived_at?->timestamp ?? 0 }}" data-ditandai="0">
    <button type="button" class="dcard-pin-btn" aria-pressed="false" aria-label="Tandai permintaan ini">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"></path></svg>
    </button>
    @php
        // Menu titik-3 Riwayat cuma berisi "Revisi", dan cuma buat status yang
        // masih bisa dibuka ulang: Ditolak, Terlambat, Dibatalkan (semua status
        // arsip KECUALI Disetujui). Klik -> modal Edit Deadline mode "revisi"
        // (deadline baru) -> PermintaanLaporanController::revisiDariRiwayat()
        // -> permintaan keluar Riwayat & aktif lagi buat satuan.
        $riwayatRevisiEligible = $riwayatMode
            && in_array($statusPimpinan, ['Ditolak', 'Terlambat', 'Dibatalkan'], true);
    @endphp
    @if($riwayatRevisiEligible)
    <div class="dcard-menu-wrap">
        <button type="button" class="dcard-menu-btn dcard-menu-toggle" aria-haspopup="true" aria-expanded="false" aria-label="Menu kartu">
            <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="12" cy="19" r="1.8"></circle></svg>
        </button>
        <div class="dcard-menu">
            <button type="button" class="dcard-menu-item dcard-riwayat-revisi-btn"
                data-mode="revisi"
                data-permintaan-id="{{ $item->id }}"
                data-perihal="{{ e($item->perihal) }}"
                data-deadline="{{ $item->deadline_at?->format('Y-m-d\TH:i') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 9h6"></path><path d="M9 13h6"></path><path d="M9 17h3"></path></svg>
                Revisi
            </button>
        </div>
    </div>
    @elseif(!$riwayatMode && $archiveEligible)
    <div class="dcard-menu-wrap">
        <button type="button" class="dcard-menu-btn dcard-menu-toggle" aria-haspopup="true" aria-expanded="false" aria-label="Menu kartu">
            <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="12" cy="19" r="1.8"></circle></svg>
        </button>
        <div class="dcard-menu">
            <button type="button" class="dcard-menu-item dcard-archive-btn" data-permintaan-id="{{ $item->id }}" data-perihal="{{ e($item->perihal) }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="4" rx="1"></rect><path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"></path><path d="M10 12h4"></path></svg>
                Arsipkan
            </button>
        </div>
    </div>
    @endif
    <div class="dcard-head">
        <div class="dcard-icon {{ $dcardPrioClass }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="1"></rect><path d="m9 14 2 2 4-4"></path></svg>
        </div>
    </div>
    <div class="dcard-body">
        <div class="deadline-sender-title">{{ $item->perihal }}</div>
        <span class="deadline-pill dcard-status-pill {{ $statusPimpinanClass }}">{{ $statusPimpinan }}</span>
        <span class="satuan-pill">{{ $item->tujuanSatuan->kode ?? $item->tujuanSatuan->nama ?? '-' }}</span>
    </div>
    <div class="dcard-progress">
        <div class="dcard-progress-head"><span class="dcard-progress-label">Progres</span><span class="dcard-progress-value">{{ $item->progres }}%</span></div>
        <div class="dcard-progress-track"><div class="dcard-progress-fill" style="width:{{ min(100, max(0, (int) $item->progres)) }}%"></div></div>
    </div>
    <div class="dcard-footer">
        <span class="dcard-tasks-summary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            @if($dcardTasksTotal > 0)
                {{ $dcardTasksDone }}/{{ $dcardTasksTotal }} tugas selesai
            @else
                Prioritas {{ $item->prioritas }}
            @endif
        </span>
        @unless($deadlineHidden)
        <span class="dcard-deadline-pill {{ $deadlineClass }}" title="{{ $item->deadline_at?->translatedFormat('d M Y H:i') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            {{ $item->deadline_at?->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE) ?? '-' }}
        </span>
        @endunless
    </div>
    <div class="dcard-status-area">
        <div class="deadline-actions">
            {{-- "Lihat Detail" -> modal #permintaanDetailModal ("Detail Permintaan
                 Laporan" persis kayak satuan: Tujuan/Deadline/Perihal/Kategori/
                 Prioritas/Status/Instruksi). Modal ini SENGAJA read-only --
                 footernya cuma "Tutup" + "Lihat Aktivitas". SEMUA tombol kelola
                 pindah ke modal "Lihat Progres" (openPimpinanProgres()):
                   - Edit Deadline  -> status Terlambat / Dibatalkan
                   - Batalkan       -> status Terbaru / Sedang diproses
                   - Revisi         -> status Ditolak
                   - Tolak / Terima -> status Menunggu (dulu numpang
                                       #reportDetailModal "Detail Aktivitas Laporan")
                 Makanya tombol "Lihat Progres" yang bawa
                 data-status/permintaan-id/deadline-raw/editable/alasan/laporan-id. --}}
            <button type="button" class="deadline-secondary small" onclick="openPermintaanDetailModal(this)"
                data-tujuan="{{ e($item->tujuanSatuan->nama ?? '-') }}"
                data-tujuan-kode="{{ e($item->tujuanSatuan->kode ?? '') }}"
                data-deadline="{{ e($item->deadline_at?->translatedFormat('d M Y H:i')) }}"
                data-perihal="{{ e($item->perihal) }}"
                data-kategori="{{ e($item->kategori ?: '-') }}"
                data-prioritas="{{ e($item->prioritas) }}"
                data-status="{{ $statusPimpinan }}"
                data-status-class="{{ $statusPimpinanClass }}"
                data-instruksi="{{ e($item->instruksi ?? '') }}"
                data-catatan="{{ e($catatanPenolakan) }}"
                data-satuan-id="{{ $item->tujuan_satuan_id }}"
                data-permintaan-id="{{ $item->id }}"
            >Lihat Detail</button>
            <button type="button" class="deadline-primary small" onclick="openPimpinanProgres(this)"
                data-perihal="{{ e($item->perihal) }}"
                data-tasks="{{ $tasksJson }}"
                data-status="{{ $statusPimpinan }}"
                data-riwayat="{{ $riwayatMode ? '1' : '0' }}"
                data-permintaan-id="{{ $item->id }}"
                data-deadline-raw="{{ $item->deadline_at?->format('Y-m-d\TH:i') }}"
                data-editable="{{ $bisaEditDeadline ? '1' : '0' }}"
                data-alasan="{{ e($alasanTidakBisaEdit) }}"
                data-laporan-id="{{ $item->laporan_id }}"
            >Lihat Progres</button>
        </div>
    </div>
</article>
