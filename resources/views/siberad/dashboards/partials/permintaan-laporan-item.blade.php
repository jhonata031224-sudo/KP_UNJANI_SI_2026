@php
    $statusTampilan = $permintaan->statusTampilan();
    $deadlineClass = $permintaan->isTerlambat() ? 'bad' : ($permintaan->deadline_at->diffInHours(now()) <= 24 ? 'near' : 'normal');
    $latestProgresCheckpoint = $permintaan->laporans->where('status', \App\Models\Laporan::STATUS_PROGRES)->sortByDesc('id')->first();
@endphp
<article class="deadline-sender-item {{ $deadlineClass }}" data-realtime-permintaan-id="{{ $permintaan->id }}" data-search="{{ strtolower($permintaan->perihal) }}" data-kategori="{{ e($permintaan->kategori ?? '') }}" data-prioritas="{{ e($permintaan->prioritas) }}">
    <div class="deadline-sender-main">
        <div class="deadline-sender-title">{{ $permintaan->perihal }}</div>
        <div class="deadline-sender-meta">Dari {{ $permintaan->pembuat->satuan->nama ?? $permintaan->pembuat->name ?? 'Pimpinan' }} · Deadline {{ $permintaan->deadline_at->translatedFormat('d M Y H:i') }}</div>
        @if($permintaan->instruksi)<div class="deadline-sender-instruction">{{ $permintaan->instruksi }}</div>@endif
    </div>
    <div class="deadline-sender-side">
        <span class="deadline-pill {{ $statusTampilan === 'Dibatalkan' ? 'bad' : ($statusTampilan === 'Terlambat' ? 'bad' : ($statusTampilan === 'Revisi' ? 'revisi' : ($statusTampilan === 'Menunggu pemeriksaan' ? 'blue' : ($statusTampilan === 'Selesai' ? 'ok' : 'wait')))) }}">{{ $statusTampilan }}</span>
        <span class="deadline-progress-badge">{{ $permintaan->progres }}%</span>
        @if($permintaan->status === \App\Models\PermintaanLaporan::STATUS_DIBATALKAN)
            <span class="deadline-complete cancelled">✕ Dibatalkan Pimpinan</span>
        @elseif(!$permintaan->laporan_id)
            @if($permintaan->status !== 'Belum dikerjakan' && $permintaan->tasks->isNotEmpty())
                @php $dtActive = false; @endphp
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
                @if($permintaan->status === 'Belum dikerjakan')
                    <button type="button" class="deadline-secondary small permintaan-lihat-detail-btn">Lihat Detail</button>
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
                @endif
            </div>
        @else
            <span class="deadline-complete">✓ Laporan sudah dikirim</span>
        @endif
    </div>
</article>
