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
                            // Task cuma bisa diklik buat menyelesaikan/membatalkan lewat
                            // form "Update Progres" (harus isi deskripsi checkpoint dulu),
                            // bukan langsung toggle diam-diam -- klik-nya numpang di
                            // mekanisme .use-permintaan yang sudah ada (lihat
                            // initUsePermintaanButtons di permintaan-laporan-deadline.blade.php).
                            // Progres yang ditampilkan di form itu SENGAJA progres SAAT
                            // INI ($permintaan->progres, sama kayak tombol "Update Progres"
                            // biasa) -- bukan prediksi hasil abis toggle, biar gak nunjukin
                            // angka yang membingungkan (mis. 0% pas mau batalin task 1 dari
                            // 1/5 yang udah selesai). Angka barunya baru kelihatan setelah
                            // checkpoint-nya beneran disubmit.
                        @endphp
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
                            data-task-action="{{ $task->selesai ? 'batalkan' : 'selesaikan' }}">
                            <span class="deadline-task-num">{{ $task->selesai ? '✓' : $loop->iteration }}</span>
                            <span class="deadline-task-label">{{ $task->deskripsi }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
            <div class="deadline-actions">
                @if($permintaan->status === 'Belum dikerjakan')
                    <button type="button" class="deadline-secondary small permintaan-lihat-detail-btn">Lihat Detail</button>
                @elseif($permintaan->isSedangRevisi())
                    <button type="button" class="deadline-primary small deadline-revisi use-permintaan" data-request-id="{{ $permintaan->id }}" data-target-id="{{ $permintaan->pembuat->satuan_id }}" data-perihal="{{ e($permintaan->perihal) }}" data-kategori="{{ e($permintaan->kategori ?? '') }}" data-prioritas="{{ e($permintaan->prioritas) }}" data-instruksi="{{ e($permintaan->instruksi ?? '') }}" data-progres="{{ $permintaan->progres }}" data-has-tasks="{{ $permintaan->tasks->isNotEmpty() ? '1' : '0' }}">Revisi</button>
                @else
                    <button type="button" class="deadline-primary small use-permintaan" data-request-id="{{ $permintaan->id }}" data-target-id="{{ $permintaan->pembuat->satuan_id }}" data-perihal="{{ e($permintaan->perihal) }}" data-kategori="{{ e($permintaan->kategori ?? '') }}" data-prioritas="{{ e($permintaan->prioritas) }}" data-instruksi="{{ e($permintaan->instruksi ?? '') }}" data-progres="{{ $permintaan->progres }}" data-has-tasks="{{ $permintaan->tasks->isNotEmpty() ? '1' : '0' }}">Update Progres</button>
                    @if($latestProgresCheckpoint)
                        <button type="button" class="deadline-secondary small edit-progres-btn" data-update-url="{{ route('laporan.update-progres', $latestProgresCheckpoint) }}" data-tujuan-satuan-id="{{ $latestProgresCheckpoint->tujuan_satuan_id }}" data-perihal="{{ e($latestProgresCheckpoint->perihal) }}" data-proyek="{{ e($latestProgresCheckpoint->proyek ?? '') }}" data-prioritas="{{ e($latestProgresCheckpoint->prioritas) }}" data-deskripsi="{{ e($latestProgresCheckpoint->deskripsi) }}" data-kendala="{{ e($latestProgresCheckpoint->kendala ?? '') }}" data-progres="{{ $permintaan->progres }}" data-has-tasks="{{ $permintaan->tasks->isNotEmpty() ? '1' : '0' }}">Edit</button>
                    @endif
                @endif
            </div>
        @else
            <span class="deadline-complete">✓ Laporan sudah dikirim</span>
        @endif
    </div>
</article>
