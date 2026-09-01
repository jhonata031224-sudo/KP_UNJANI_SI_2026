@php
    $statusTampilan = $permintaan->statusTampilan();
    $deadlineClass = $permintaan->isTerlambat() ? 'bad' : ($permintaan->deadline_at->diffInHours(now()) <= 24 ? 'near' : 'normal');
    $latestProgresCheckpoint = $permintaan->laporans->where('status', \App\Models\Laporan::STATUS_PROGRES)->sortByDesc('id')->first();
    // Permintaan yang sudah final (laporan_id keisi, lagi "Menunggu
    // pemeriksaan" Pimpinan) -- checklist + tombol aksi jadi read-only,
    // lihat dcard-status-area.
    $isFinal = (bool) $permintaan->laporan_id;
    // Pill deadline di footer DISEMBUNYIKAN buat status yang bikin hitungan
    // mundur udah gak relevan -- semua KECUALI yang deadline-nya masih patokan
    // hidup ("Belum dikerjakan" / "Sedang dikerjakan" / "Revisi") atau alarm
    // aktif ("Terlambat"). "Revisi" IKUT nampilin pill: Pimpinan ngasih
    // deadline baru pas klik Revisi (revisiDariRiwayat), jadi satuan perlu
    // liat sisa waktu buat kirim ulang. Jadi Menunggu pemeriksaan, Dibatalkan,
    // Selesai -> pill deadline hilang total.
    $deadlineHidden = !in_array($statusTampilan, ['Belum dikerjakan', 'Sedang dikerjakan', 'Terlambat', 'Revisi'], true);
    // Terlambat/Dibatalkan: task yang belum diisi jadi silang (✕) & terkunci,
    // tombol Update Progres/Simpan Perubahan ilang. Balik normal otomatis
    // kalau deadline diperpanjang Pimpinan (isTerlambat() dihitung live).
    $isLocked = $permintaan->isTerlambat() || $permintaan->status === \App\Models\PermintaanLaporan::STATUS_DIBATALKAN;
    // Warna kotak ikon ikut warna badge prioritas yang sudah dipakai di
    // seluruh app (.priority-tag.prio-tinggi/sedang/rendah) -- bukan warna
    // acak per kartu, biar konsisten & langsung kebaca prioritasnya dari
    // warna ikon tanpa perlu baca teks.
    $dcardPrioClass = 'prio-' . strtolower($permintaan->prioritas);
    $dcardSenderName = $permintaan->pembuat->satuan->nama ?? $permintaan->pembuat->name ?? 'Pimpinan';
    $dcardTasksTotal = $permintaan->tasks->count();
    $dcardTasksDone = $permintaan->tasks->where('selesai', true)->count();
    // Permintaan yang statusnya MENTAH masih "Belum dikerjakan" (belum
    // dikonfirmasi satuan) -- dipakai buat data-belum-dikerjakan (tombol
    // Konfirmasi di modal Lihat Detail + pin-ke-atas pas sorting, lihat
    // apply() di permintaan-laporan-deadline.blade.php). SENGAJA pakai
    // status mentah, BUKAN statusTampilan() -- kalau permintaan ini juga
    // udah lewat deadline, statusTampilan() balikin "Terlambat" duluan
    // (lihat isTerlambat()), padahal secara mentah dia tetap "Belum
    // dikerjakan" & tetap butuh tombol Konfirmasi yang sama.
    $belumDikerjakanMentah = $permintaan->status === 'Belum dikerjakan';
    // $statusIsBaru KHUSUS buat badge/titik "Terbaru" -- pakai statusTampilan()
    // biar permintaan yang udah kepalang terlambat tetap kebaca "Terlambat"
    // (merah, mendesak), bukan "Terbaru" yang kesannya adem-adem aja.
    $statusIsBaru = $statusTampilan === 'Belum dikerjakan';
    // Laporan final ditolak? -- dipakai buat pecah "Selesai" jadi label
    // "Disetujui"/"Ditolak" (+ warna), sama kayak kartu Pimpinan
    // (permintaan-laporan-pimpinan-card.blade.php).
    $hasilAkhirDitolak = $statusTampilan === 'Selesai'
        && str_contains(strtolower($permintaan->laporan?->status ?? ''), 'tolak');
    // Catatan/keterangan penolakan yang ditulis Pimpinan waktu klik "Tolak"
    // (LaporanController::updateStatus -> laporans.catatan). Sampai sekarang
    // nilai ini gak pernah muncul di UI satuan sama sekali -- ditarik dari
    // laporan DITOLAK paling baru yang punya catatan, jadi kebaca juga pas
    // status permintaan udah balik "Revisi" (laporan_id direset null, tapi
    // $permintaan->laporans historis tetap simpan yang ditolak + catatannya).
    // Ikut cocokin status "revisi": pas Pimpinan klik Revisi dari Riwayat
    // (PermintaanLaporanController::revisiDariRiwayat) status laporan terakhir
    // di-flip dari "Ditolak" -> "Revisi", tapi teks catatannya TETAP nempel.
    $laporanDitolakTerakhir = $permintaan->laporans
        ->filter(fn ($l) => (str_contains(strtolower((string) $l->status), 'tolak') || str_contains(strtolower((string) $l->status), 'revisi')) && trim((string) $l->catatan) !== '')
        ->sortByDesc('id')
        ->first();
    $catatanPenolakan = trim((string) ($laporanDitolakTerakhir?->catatan ?? ''));
    // Label pill disamakan dengan kartu Pimpinan: "Menunggu" (bukan "Menunggu
    // pemeriksaan"), "Sedang diproses" (bukan "Sedang dikerjakan"), "Disetujui"/
    // "Ditolak" (bukan "Selesai"). "Terbaru" tetap ada di sisi satuan.
    $statusDisplay = match(true) {
        $statusIsBaru => 'Terbaru',
        $statusTampilan === 'Menunggu pemeriksaan' => 'Menunggu',
        $statusTampilan === 'Sedang dikerjakan' => 'Sedang diproses',
        $statusTampilan === 'Selesai' => $hasilAkhirDitolak ? 'Ditolak' : 'Disetujui',
        default => $statusTampilan,
    };
    $statusPillClass = match(true) {
        $statusTampilan === 'Dibatalkan' => 'bad',
        $statusTampilan === 'Terlambat' => 'bad',
        $statusTampilan === 'Revisi' => 'revisi',
        $statusTampilan === 'Menunggu pemeriksaan' => 'blue',
        $statusTampilan === 'Selesai' => $hasilAkhirDitolak ? 'bad' : 'ok',
        $statusIsBaru => 'new',
        default => 'wait',
    };
@endphp
<article class="deadline-sender-item {{ $deadlineClass }}" data-realtime-permintaan-id="{{ $permintaan->id }}" data-search="{{ strtolower($permintaan->perihal) }}" data-kategori="{{ e($permintaan->kategori ?? '') }}" data-prioritas="{{ e($permintaan->prioritas) }}" data-belum-dikerjakan="{{ $belumDikerjakanMentah ? '1' : '0' }}" data-locked="{{ $isLocked ? '1' : '0' }}" data-terlambat="{{ $permintaan->isTerlambat() ? '1' : '0' }}" data-pengirim-kode="{{ e($permintaan->pembuat->satuan->kode ?? '') }}" data-catatan-penolakan="{{ e($catatanPenolakan) }}" data-deadline-at="{{ $permintaan->deadline_at->timestamp }}" data-archived-at="{{ $permintaan->archived_at?->timestamp ?? 0 }}" data-ditandai="0">
    {{-- Tombol tanda manual (checkbox bulat) di pojok kiri-atas -- status
         "ditandai"-nya PURE client-side (localStorage, lihat initDcardPinButtons
         di permintaan-laporan-deadline.blade.php), karena kita nggak boleh
         nambah kolom DB baru buat ini. data-ditandai di atas cuma placeholder
         default, langsung ditimpa JS begitu halaman/kartu ini render. --}}
    <button type="button" class="dcard-pin-btn" aria-pressed="false" aria-label="Tandai permintaan ini">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"></path></svg>
    </button>
    {{-- Menu titik-3 "Arsipkan" SENGAJA cuma ada di kartu Pimpinan
         (permintaan-laporan-pimpinan-card.blade.php) -- hak arsip permintaan
         laporan ada di Pimpinan, bukan satuan. --}}
    <div class="dcard-head">
        <div class="dcard-icon {{ $dcardPrioClass }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="1"></rect><path d="m9 14 2 2 4-4"></path></svg>
        </div>
    </div>
    <div class="dcard-body">
        <div class="deadline-sender-title">{{ $permintaan->perihal }}</div>
        {{-- Ganti avatar pengirim jadi badge status (reuse .deadline-pill yang
             sudah ada) -- pola yang sama kayak status pill di tabel Permintaan
             Laporan versi Pimpinan (permintaan-laporan-pimpinan-row.blade.php),
             cuma di sini teksnya tetap dari statusTampilan() milik satuan
             sendiri, bukan status khusus POV Pimpinan. --}}
        <span class="deadline-pill dcard-status-pill {{ $statusPillClass }}">{{ $statusDisplay }}</span>
        {{-- Elemen di bawah ini SENGAJA dipertahankan di DOM (teks & format
             persis sama seperti sebelumnya) tapi disembunyikan visual (bukan
             dihapus) -- openDetail() di permintaan-laporan-realtime.blade.php
             masih baca .deadline-sender-meta (teks "Dari X · Deadline Y") dan
             .deadline-sender-instruction (fallback isi instruksi) buat ngisi
             modal "Lihat Detail". Kalau dihapus beneran, modal detail-nya
             jadi rusak. --}}
        <span class="deadline-sender-meta dcard-meta-hidden">Dari {{ $dcardSenderName }} · Deadline {{ $permintaan->deadline_at->translatedFormat('d M Y H:i') }}</span>
        @if($permintaan->instruksi)<div class="deadline-sender-instruction dcard-meta-hidden">{{ $permintaan->instruksi }}</div>@endif
    </div>
    <div class="dcard-progress">
        <div class="dcard-progress-head"><span class="dcard-progress-label">Progres</span><span class="dcard-progress-value">{{ $permintaan->progres }}%</span></div>
        <div class="dcard-progress-track"><div class="dcard-progress-fill" style="width:{{ min(100, max(0, (int) $permintaan->progres)) }}%"></div></div>
    </div>
    <div class="dcard-footer">
        <span class="dcard-tasks-summary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            @if($dcardTasksTotal > 0)
                {{ $dcardTasksDone }}/{{ $dcardTasksTotal }} tugas selesai
            @else
                {{ $permintaan->kategori ?: 'Prioritas '.$permintaan->prioritas }}
            @endif
        </span>
        {{-- Deadline disembunyikan buat status terminal (lihat $deadlineHidden
             di atas) -- persis pola kartu Pimpinan
             (permintaan-laporan-pimpinan-card.blade.php). --}}
        @unless($deadlineHidden)
        <span class="dcard-deadline-pill {{ $deadlineClass }}" title="{{ $permintaan->deadline_at->translatedFormat('d M Y H:i') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            {{ $permintaan->deadline_at->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}
        </span>
        @endunless
    </div>
    <div class="dcard-status-area">
        {{-- $isFinal: read-only "Lihat Progres". $isLocked: task kosong jadi ✕. --}}
        @if($permintaan->status !== 'Belum dikerjakan' && $permintaan->tasks->isNotEmpty())
            @php $dtActive = false; @endphp
            {{-- Track chevron horizontal ini sengaja TIDAK ditampilkan lagi di
                 kartu (lihat .deadline-task-track{display:none} di
                 permintaan-laporan-deadline.blade.php) -- markup & logic PHP-nya
                 dipertahankan PERSIS seperti semula supaya jadi satu-satunya
                 sumber data (state done/active/pending per task, plus semua
                 data-* checkpoint) yang dipakai JS buat ngerender ulang topbar
                 step wizard horizontal di dalam modal #kirimLaporanModal (lihat
                 buildWizardTopbar() di permintaan-laporan-deadline.blade.php).
                 Klik step di topbar wizard tinggal proxy .click() ke tombol
                 asli di sini, jadi handler initUsePermintaanButtons/
                 initEditProgresButtons yang lama tidak perlu diubah sama sekali. --}}
            <div class="deadline-task-track" data-permintaan-task-track>
                @foreach($permintaan->tasks as $task)
                    @php
                        $dtState = $task->selesai ? 'done' : ($dtActive ? 'pending' : 'active');
                        if (!$task->selesai) { $dtActive = true; }
                        // Task yang SUDAH selesai punya checkpoint-nya sendiri
                        // (task_id) -- diklik lagi bukan buat "selesaikan/
                        // batalkan" via form kosong lagi, tapi buka mode EDIT
                        // (numpang mekanisme .edit-progres-btn yang sudah ada
                        // di initEditProgresButtons, sama kayak tombol Edit di
                        // tabel Riwayat Laporan) supaya isi laporan/kendala/
                        // lampiran yang PERNAH dikirim buat task ini kelihatan
                        // & bisa dikoreksi, bukan form Update Progres kosong.
                        $taskLaporan = $task->selesai ? $task->laporans->sortByDesc('id')->first() : null;
                    @endphp
                    @if($task->selesai && $taskLaporan)
                        <button type="button" class="deadline-task-step done edit-progres-btn" title="{{ $task->deskripsi }}"
                            data-task-id="{{ $task->id }}"
                            data-update-url="{{ route('laporan.update-progres', $taskLaporan) }}"
                            data-tujuan-satuan-id="{{ $taskLaporan->tujuan_satuan_id }}"
                            data-perihal="{{ e($taskLaporan->perihal) }}"
                            data-proyek="{{ e($taskLaporan->proyek ?? '') }}"
                            data-prioritas="{{ e($taskLaporan->prioritas) }}"
                            data-deskripsi="{{ e($taskLaporan->deskripsi) }}"
                            data-kendala="{{ e($taskLaporan->kendala ?? '') }}"
                            data-task-detail="{{ e($task->detail ?? '') }}"
                            data-lampiran="{{ $taskLaporan->semuaLampiran->map(fn($x) => ['id' => $x->id ?? 'legacy', 'url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values()->toJson() }}"
                            data-progres="{{ $permintaan->progres }}"
                            data-has-tasks="1"
                            data-readonly="{{ ($isFinal || $isLocked) ? '1' : '0' }}"
                            data-readonly-reason="{{ $isFinal ? 'final' : ($isLocked ? 'locked' : '') }}"
                            data-terlambat="{{ $permintaan->isTerlambat() ? '1' : '0' }}">
                            <span class="deadline-task-num">✓</span>
                            <span class="deadline-task-label">{{ $task->deskripsi }}</span>
                        </button>
                    @else
                        {{-- Task cuma bisa diklik buat menyelesaikan lewat form "Update
                             Progres" (harus isi deskripsi checkpoint dulu), bukan langsung
                             toggle diam-diam -- klik-nya numpang di mekanisme .use-permintaan
                             yang sudah ada (lihat initUsePermintaanButtons). Progres yang
                             ditampilkan di form itu SENGAJA progres SAAT INI
                             ($permintaan->progres, sama kayak tombol "Update Progres" biasa),
                             bukan prediksi hasil abis toggle. --}}
                        <button type="button" class="deadline-task-step {{ $isLocked ? 'locked locked-view' : $dtState }} {{ (!$isLocked && $dtState !== 'pending') ? 'use-permintaan' : '' }}" title="{{ $task->deskripsi }}" {{ (!$isLocked && $dtState === 'pending') ? 'disabled' : '' }}
                            data-request-id="{{ $permintaan->id }}"
                            data-target-id="{{ $permintaan->pembuat->satuan_id }}"
                            data-perihal="{{ e($permintaan->perihal) }}"
                            data-kategori="{{ e($permintaan->kategori ?? '') }}"
                            data-prioritas="{{ e($permintaan->prioritas) }}"
                            data-instruksi="{{ e($permintaan->instruksi ?? '') }}"
                            data-progres="{{ $permintaan->progres }}"
                            data-has-tasks="1"
                            data-task-id="{{ $task->id }}"
                            data-task-label="{{ e($task->deskripsi) }}"
                            data-task-detail="{{ e($task->detail ?? '') }}"
                            data-task-action="selesaikan"
                            data-terlambat="{{ $permintaan->isTerlambat() ? '1' : '0' }}">
                            <span class="deadline-task-num">{{ $isLocked ? '✕' : $loop->iteration }}</span>
                            <span class="deadline-task-label">{{ $task->deskripsi }}</span>
                        </button>
                    @endif
                @endforeach
            </div>
        @endif
        <div class="deadline-actions">
            {{-- "Lihat Detail" & "Update Progres" SEKARANG SELALU tampil
                 bareng (dulu "Lihat Detail" cuma muncul pas status "Belum
                 dikerjakan" terus ilang lagi setelah dikonfirmasi). Kalau
                 belum dikonfirmasi (data-belum-dikerjakan="1" di <article>),
                 "Update Progres" dirender disabled -- baru aktif setelah user
                 konfirmasi lewat modal "Lihat Detail" (lihat openDetail() di
                 permintaan-laporan-realtime.blade.php, yang juga nyembunyiin
                 tombol Konfirmasi di modal itu begitu status-nya udah lewat
                 "Belum dikerjakan", karena gak perlu dikonfirmasi ulang). --}}
            <button type="button" class="deadline-secondary small permintaan-lihat-detail-btn">Lihat Detail</button>
            @if($isFinal)
                {{-- Final: buka modal yang sama, mode Lihat Progres (read-only). --}}
                @if($permintaan->tasks->isNotEmpty())
                    <button type="button" class="deadline-primary small deadline-wizard-entry-btn">Lihat Progres</button>
                @elseif($permintaan->laporan)
                    <button type="button" class="deadline-primary small edit-progres-btn"
                        data-update-url="{{ route('laporan.update-progres', $permintaan->laporan) }}"
                        data-tujuan-satuan-id="{{ $permintaan->laporan->tujuan_satuan_id }}"
                        data-perihal="{{ e($permintaan->laporan->perihal) }}"
                        data-proyek="{{ e($permintaan->laporan->proyek ?? '') }}"
                        data-prioritas="{{ e($permintaan->laporan->prioritas) }}"
                        data-deskripsi="{{ e($permintaan->laporan->deskripsi) }}"
                        data-kendala="{{ e($permintaan->laporan->kendala ?? '') }}"
                        data-lampiran="{{ $permintaan->laporan->semuaLampiran->map(fn($x) => ['id' => $x->id ?? 'legacy', 'url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values()->toJson() }}"
                        data-progres="{{ $permintaan->progres }}"
                        data-has-tasks="0"
                        data-readonly="1"
                        data-readonly-reason="final">Lihat Progres</button>
                @endif
            @elseif($permintaan->status === 'Belum dikerjakan')
                <button type="button" class="deadline-primary small" disabled title="Konfirmasi dulu lewat &quot;Lihat Detail&quot; sebelum bisa update progres">Update Progres</button>
            {{-- Permintaan "Revisi" (laporan final dikembalikan Pimpinan lewat
                 revisiDariRiwayat) SENGAJA gak punya cabang/tombol khusus lagi
                 -- dia jatuh ke alur "Update Progres" biasa di bawah persis
                 kayak permintaan "Sedang dikerjakan". Checkpoint task final yang
                 tadi ditolak boleh diedit ulang lewat wizard yang sama (guard di
                 LaporanController::updateProgres dilonggarin buat kasus ini). --}}
            @elseif($permintaan->tasks->isEmpty())
                {{-- Permintaan tanpa task (dibuat sebelum fitur checklist task ada)
                     gak punya apa pun buat diklik di deadline-task-track (yang malah
                     gak dirender sama sekali kalau tasks kosong) -- tombol ini
                     satu-satunya jalan masuk buat submit/edit progres-nya, jadi
                     TETAP dipertahankan cuma buat kasus ini. Begitu ada task,
                     klik langsung di task-step (lihat deadline-task-track di atas)
                     yang buka modal Update Progres/Edit-nya, jadi tombol terpisah
                     ini sengaja gak dirender lagi biar gak dobel. --}}
                @if($isLocked)
                    @if($latestProgresCheckpoint)
                        <button type="button" class="deadline-secondary small edit-progres-btn" data-update-url="{{ route('laporan.update-progres', $latestProgresCheckpoint) }}" data-tujuan-satuan-id="{{ $latestProgresCheckpoint->tujuan_satuan_id }}" data-perihal="{{ e($latestProgresCheckpoint->perihal) }}" data-proyek="{{ e($latestProgresCheckpoint->proyek ?? '') }}" data-prioritas="{{ e($latestProgresCheckpoint->prioritas) }}" data-deskripsi="{{ e($latestProgresCheckpoint->deskripsi) }}" data-kendala="{{ e($latestProgresCheckpoint->kendala ?? '') }}" data-lampiran="{{ $latestProgresCheckpoint->semuaLampiran->map(fn($x) => ['id' => $x->id ?? 'legacy', 'url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values()->toJson() }}" data-progres="{{ $permintaan->progres }}" data-has-tasks="0" data-readonly="1" data-readonly-reason="locked">Lihat Progres</button>
                    @else
                        <button type="button" class="deadline-primary small locked-view" data-request-id="{{ $permintaan->id }}" data-target-id="{{ $permintaan->pembuat->satuan_id }}" data-perihal="{{ e($permintaan->perihal) }}" data-kategori="{{ e($permintaan->kategori ?? '') }}" data-prioritas="{{ e($permintaan->prioritas) }}" data-instruksi="{{ e($permintaan->instruksi ?? '') }}" data-progres="{{ $permintaan->progres }}" data-has-tasks="0">Lihat Progres</button>
                    @endif
                @else
                    <button type="button" class="deadline-primary small use-permintaan" data-request-id="{{ $permintaan->id }}" data-target-id="{{ $permintaan->pembuat->satuan_id }}" data-perihal="{{ e($permintaan->perihal) }}" data-kategori="{{ e($permintaan->kategori ?? '') }}" data-prioritas="{{ e($permintaan->prioritas) }}" data-instruksi="{{ e($permintaan->instruksi ?? '') }}" data-progres="{{ $permintaan->progres }}" data-has-tasks="0">Update Progres</button>
                    @if($latestProgresCheckpoint)
                        <button type="button" class="deadline-secondary small edit-progres-btn" data-update-url="{{ route('laporan.update-progres', $latestProgresCheckpoint) }}" data-tujuan-satuan-id="{{ $latestProgresCheckpoint->tujuan_satuan_id }}" data-perihal="{{ e($latestProgresCheckpoint->perihal) }}" data-proyek="{{ e($latestProgresCheckpoint->proyek ?? '') }}" data-prioritas="{{ e($latestProgresCheckpoint->prioritas) }}" data-deskripsi="{{ e($latestProgresCheckpoint->deskripsi) }}" data-kendala="{{ e($latestProgresCheckpoint->kendala ?? '') }}" data-lampiran="{{ $latestProgresCheckpoint->semuaLampiran->map(fn($x) => ['id' => $x->id ?? 'legacy', 'url' => asset('storage/'.$x->path), 'nama' => $x->nama_asli])->values()->toJson() }}" data-progres="{{ $permintaan->progres }}" data-has-tasks="0">Edit</button>
                    @endif
                @endif
            @else
                {{-- Permintaan dengan checklist task, sedang dikerjakan (bukan
                     revisi) -- di desain lama satu-satunya cara masuk ke sini
                     adalah klik langsung salah satu chevron task-step (yang
                     sekarang disembunyikan dari kartu). Tombol ini tombol BARU
                     yang cuma jadi proxy: klik-nya nyari step "active" (atau
                     "done" kalau semua sudah selesai) di .deadline-task-track
                     tersembunyi lalu manggil .click() ke situ (lihat
                     initWizardEntryButtons di permintaan-laporan-deadline.blade.php)
                     -- BUKAN alur baru, cuma pemicu baru buat alur yang sama persis. --}}
                <button type="button" class="deadline-primary small deadline-wizard-entry-btn">{{ $isLocked ? 'Lihat Progres' : 'Update Progres' }}</button>
            @endif
        </div>
    </div>
</article>
