@foreach($permintaanLaporan as $permintaan)
@php
    $statusTampilan = $permintaan->statusTampilan();
    $deadlineClass = $permintaan->isTerlambat() ? 'bad' : ($permintaan->deadline_at->diffInHours(now()) <= 24 ? 'near' : 'normal');
@endphp
<article class="deadline-sender-item {{ $deadlineClass }}" data-realtime-permintaan-id="{{ $permintaan->id }}" data-search="{{ strtolower($permintaan->perihal) }}">
    <div class="deadline-sender-main">
        <div class="deadline-sender-title">{{ $permintaan->perihal }}</div>
        <div class="deadline-sender-meta">Dari {{ $permintaan->pembuat->satuan->nama ?? $permintaan->pembuat->name ?? 'Pimpinan' }} · Deadline {{ $permintaan->deadline_at->translatedFormat('d M Y H:i') }}</div>
        @if($permintaan->instruksi)<div class="deadline-sender-instruction">{{ $permintaan->instruksi }}</div>@endif
    </div>
    <div class="deadline-sender-side">
        <span class="deadline-pill {{ $statusTampilan === 'Terlambat' ? 'bad' : ($statusTampilan === 'Menunggu pemeriksaan' ? 'blue' : ($statusTampilan === 'Selesai' ? 'ok' : 'wait')) }}">{{ $statusTampilan }}</span>
        <span class="deadline-progress-badge">{{ $permintaan->progres }}%</span>
        @if(!$permintaan->laporan_id)
            <div class="deadline-actions">
                @if($permintaan->status === 'Belum dikerjakan')
                    <form method="POST" action="{{ route('permintaan-laporan.mulai', $permintaan) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="deadline-secondary small confirm-btn">Konfirmasi</button>
                    </form>
                @endif
            </div>
        @else
            <span class="deadline-complete">✓ Laporan sudah dikirim</span>
        @endif
    </div>
</article>
@endforeach
