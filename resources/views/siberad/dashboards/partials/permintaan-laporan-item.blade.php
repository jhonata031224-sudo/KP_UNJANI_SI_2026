@php
    $statusTampilan = $permintaan->statusTampilan();
    $deadlineClass = $permintaan->isTerlambat() ? 'bad' : ($permintaan->deadline_at->diffInHours(now()) <= 24 ? 'near' : 'normal');
    $latestProgresCheckpoint = $permintaan->laporans->where('status', \App\Models\Laporan::STATUS_PROGRES)->sortByDesc('id')->first();
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
    $statusDisplay = $statusIsBaru ? 'Terbaru' : $statusTampilan;
    $statusPillClass = match(true) {
        $statusTampilan === 'Dibatalkan' => 'bad',
        $statusTampilan === 'Terlambat' => 'bad',
        $statusTampilan === 'Revisi' => 'revisi',
        $statusTampilan === 'Menunggu pemeriksaan' => 'blue',
        $statusTampilan === 'Selesai' => 'ok',
        $statusIsBaru => 'new',
        default => 'wait',
    };
@endphp
<article class="deadline-sender-item {{ $deadlineClass }}" data-realtime-permintaan-id="{{ $permintaan->id }}" data-search="{{ strtolower($permintaan->perihal) }}" data-kategori="{{ e($permintaan->kategori ?? '') }}" data-prioritas="{{ e($permintaan->prioritas) }}" data-belum-dikerjakan="{{ $belumDikerjakanMentah ? '1' : '0' }}" data-pengirim-kode="{{ e($permintaan->pembuat->satuan->kode ?? '') }}" data-deadline-at="{{ $permintaan->deadline_at->timestamp }}" data-ditandai="0">
    {{-- Tombol tanda manual (checkbox bulat) di pojok kiri-atas -- status
         "ditandai"-nya PURE client-side (localStorage, lihat initDcardPinButtons
         di permintaan-laporan-deadline.blade.php), karena kita nggak boleh
         nambah kolom DB baru buat ini. data-ditandai di atas cuma placeholder
         default, langsung ditimpa JS begitu halaman/kartu ini render. --}}
    <button type="button" class="dcard-pin-btn" aria-pressed="false" aria-label="Tandai permintaan ini">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"></path></svg>
    </button>
    <div class="dcard-menu-wrap">
        <button type="button" class="dcard-menu-btn dcard-menu-toggle" aria-haspopup="true" aria-expanded="false" aria-label="Menu kartu">
            <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="12" cy="19" r="1.8"></circle></svg>
        </button>
        <div class="dcard-menu">
            {{-- Arsipkan: UI dulu, fungsinya belum diaktifkan -- nunggu instruksi lanjutan sebelum disambungkan ke route apa pun. --}}
            <button type="button" class="dcard-menu-item dcard-archive-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="4" rx="1"></rect><path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"></path><path d="M10 12h4"></path></svg>
                Arsipkan
            </button>
        </div>
    </div>
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
        <span class="dcard-deadline-pill {{ $deadlineClass }}" title="{{ $permintaan->deadline_at->translatedFormat('d M Y H:i') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            {{ $permintaan->deadline_at->diffForHumans(null, \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}
        </span>
    </div>
    <div class="dcard-status-area">
    @if($permintaan->status === \App\Models\PermintaanLaporan::STATUS_DIBATALKAN)
        <span class="deadline-complete cancelled">✕ Dibatalkan Pimpinan</span>
    @elseif(!$permintaan->laporan_id)
        @if($permintaan->status !== 'Belum dikerjakan' && $permintaan->tasks->isNotEmpty())
            @php $dtActive = false; @endphp
            {{-- Track chevron horizontal ini sengaja TIDAK ditampilkan lagi di
                 kartu (lihat .deadline-task-track{display:none} di
                 permintaan-laporan-deadline.blade.php) -- markup & logic PHP-nya
                 dipertahankan PERSIS seperti semula supaya jadi satu-satunya
                 sumber data (state done/active/pending per task, plus semua
                 data-* checkpoint) yang dipakai JS buat ngerender ulang sidebar
                 step wizard di dalam modal #kirimLaporanModal (lihat
                 buildWizardSidebar() di permintaan-laporan-deadline.blade.php).
                 Klik step di sidebar wizard tinggal proxy .click() ke tombol
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
                            data-update-url="{{ route('laporan.update-progres', $taskLaporan) }}"
                            data-tujuan-satuan-id="{{ $taskLaporan->tujuan_satuan_id }}"
                            data-perihal="{{ e($taskLaporan->perihal) }}"
                            data-proyek="{{ e($taskLaporan->proyek ?? '') }}"
                            data-prioritas="{{ e($taskLaporan->prioritas) }}"
                            data-deskripsi="{{ e($taskLaporan->deskripsi) }}"
                            data-kendala="{{ e($taskLaporan->kendala ?? '') }}"
                            data-lampiran="{{ $taskLaporan->lampiran_path ? e(asset('storage/'.$taskLaporan->lampiran_path)) : '' }}"
                            data-lampiran-nama="{{ $taskLaporan->lampiran_path ? e($taskLaporan->lampiran_nama_asli ?: basename($taskLaporan->lampiran_path)) : '' }}"
                            data-progres="{{ $permintaan->progres }}"
                            data-has-tasks="1">
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
                        <button type="button" class="deadline-task-step {{ $dtState }} {{ $dtState !== 'pending' ? 'use-permintaan' : '' }}" title="{{ $task->deskripsi }}" {{ $dtState === 'pending' ? 'disabled' : '' }}
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
                            data-task-action="selesaikan">
                            <span class="deadline-task-num">{{ $loop->iteration }}</span>
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
            @if($permintaan->status === 'Belum dikerjakan')
                <button type="button" class="deadline-primary small" disabled title="Konfirmasi dulu lewat &quot;Lihat Detail&quot; sebelum bisa update progres">Update Progres</button>
            @elseif($permintaan->isSedangRevisi())
                <button type="button" class="deadline-primary small deadline-revisi use-permintaan" data-request-id="{{ $permintaan->id }}" data-target-id="{{ $permintaan->pembuat->satuan_id }}" data-perihal="{{ e($permintaan->perihal) }}" data-kategori="{{ e($permintaan->kategori ?? '') }}" data-prioritas="{{ e($permintaan->prioritas) }}" data-instruksi="{{ e($permintaan->instruksi ?? '') }}" data-progres="{{ $permintaan->progres }}" data-has-tasks="{{ $permintaan->tasks->isNotEmpty() ? '1' : '0' }}">Revisi</button>
            @elseif($permintaan->tasks->isEmpty())
                {{-- Permintaan tanpa task (dibuat sebelum fitur checklist task ada)
                     gak punya apa pun buat diklik di deadline-task-track (yang malah
                     gak dirender sama sekali kalau tasks kosong) -- tombol ini
                     satu-satunya jalan masuk buat submit/edit progres-nya, jadi
                     TETAP dipertahankan cuma buat kasus ini. Begitu ada task,
                     klik langsung di task-step (lihat deadline-task-track di atas)
                     yang buka modal Update Progres/Edit-nya, jadi tombol terpisah
                     ini sengaja gak dirender lagi biar gak dobel. --}}
                <button type="button" class="deadline-primary small use-permintaan" data-request-id="{{ $permintaan->id }}" data-target-id="{{ $permintaan->pembuat->satuan_id }}" data-perihal="{{ e($permintaan->perihal) }}" data-kategori="{{ e($permintaan->kategori ?? '') }}" data-prioritas="{{ e($permintaan->prioritas) }}" data-instruksi="{{ e($permintaan->instruksi ?? '') }}" data-progres="{{ $permintaan->progres }}" data-has-tasks="0">Update Progres</button>
                @if($latestProgresCheckpoint)
                    <button type="button" class="deadline-secondary small edit-progres-btn" data-update-url="{{ route('laporan.update-progres', $latestProgresCheckpoint) }}" data-tujuan-satuan-id="{{ $latestProgresCheckpoint->tujuan_satuan_id }}" data-perihal="{{ e($latestProgresCheckpoint->perihal) }}" data-proyek="{{ e($latestProgresCheckpoint->proyek ?? '') }}" data-prioritas="{{ e($latestProgresCheckpoint->prioritas) }}" data-deskripsi="{{ e($latestProgresCheckpoint->deskripsi) }}" data-kendala="{{ e($latestProgresCheckpoint->kendala ?? '') }}" data-lampiran="{{ $latestProgresCheckpoint->lampiran_path ? e(asset('storage/'.$latestProgresCheckpoint->lampiran_path)) : '' }}" data-lampiran-nama="{{ $latestProgresCheckpoint->lampiran_path ? e($latestProgresCheckpoint->lampiran_nama_asli ?: basename($latestProgresCheckpoint->lampiran_path)) : '' }}" data-progres="{{ $permintaan->progres }}" data-has-tasks="0">Edit</button>
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
                <button type="button" class="deadline-primary small deadline-wizard-entry-btn">Update Progres</button>
            @endif
        </div>
    @else
        <span class="deadline-complete">✓ Laporan sudah dikirim</span>
    @endif
    </div>
</article>
