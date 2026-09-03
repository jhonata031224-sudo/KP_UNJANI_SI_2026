@php
    // Arsip Surat gabungan dua arah -- kartu ini bisa mewakili surat yang
    // KIRIM sendiri (satuan_id === $satuan->id) atau surat yang MASUK ke
    // satuan ini dan sudah DIA SENDIRI konfirmasi
    // (tujuan_satuan_id === $satuan->id). $lawan = pihak SEBERANG (siapa
    // yang bukan $satuan) buat field "Dari/Ke" & data detail modal.
    $isSent = (int) $s->satuan_id === (int) $satuan->id;
    $lawan = $isSent ? $s->tujuanSatuan : $s->satuan;
@endphp
<div class="surat-file-card" data-surat-id="{{ $s->id }}" data-created-at="{{ $s->created_at->timestamp }}" data-search="{{ strtolower($s->perihal.' '.($lawan->nama ?? '').' '.($lawan->kode ?? '')) }}" data-prioritas="{{ $s->prioritas }}">
    <div class="surat-file-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></div>
    <span class="status-badge status-dikonfirmasi surat-file-card-badge">Dikonfirmasi</span>
    <div class="surat-file-card-title">{{ $s->perihal }}</div>
    <div><div class="surat-file-card-dari-label">{{ $isSent ? 'Ke' : 'Dari' }}</div><div class="surat-file-card-dari-value"><span>{{ $lawan->nama ?? '-' }}</span><span class="satuan-pill">{{ $lawan->kode ?? $lawan->nama ?? '-' }}</span></div></div>
    <div class="surat-file-card-divider"></div>
    <div class="surat-file-card-meta"><span class="surat-file-card-meta-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span><div><div class="surat-file-card-meta-label">Tanggal Dibuat</div><div class="surat-file-card-meta-value">{{ $s->created_at->translatedFormat('d M Y H:i') }}</div></div></div>
    <div class="surat-file-card-divider"></div>
    <button type="button" class="surat-file-card-btn" onclick="openSuratDetail(this)"
        data-perihal="{{ e($s->perihal) }}"
        data-tujuan="{{ e($isSent ? ($lawan->nama ?? '-') : ($satuan->nama ?? '-')) }}"
        data-tujuan-kode="{{ e($isSent ? ($lawan->kode ?? '') : ($satuan->kode ?? '')) }}"
        data-kategori="{{ e($s->kategori ?: 'Umum') }}"
        data-prioritas="{{ e($s->prioritas) }}"
        data-status="{{ $s->labelStatus() }}"
        data-deskripsi="{{ e($s->deskripsi) }}"
        data-dari="{{ e($isSent ? ($satuan->nama ?? '-') : ($lawan->nama ?? '-')) }}"
        data-dibuat-oleh="{{ e($isSent ? ($satuan->nama ?? '-') : ($lawan->nama ?? '-')) }}"
        data-dibuat-tanggal="{{ e($s->created_at->translatedFormat('d M Y H:i')) }}"
        data-dikonfirmasi-oleh="{{ e($s->dikonfirmasiOleh->name ?? '') }}"
        data-dikonfirmasi-tanggal="{{ $s->dikonfirmasi_at ? e($s->dikonfirmasi_at->translatedFormat('d M Y H:i')) : '' }}"
        data-lampiran-url="{{ $s->lampiran_path ? asset('storage/'.$s->lampiran_path) : '' }}"
        data-lampiran-nama="{{ $s->lampiran_path ? ($s->lampiran_nama_asli ?: basename($s->lampiran_path)) : '' }}"
        data-lampiran-size="{{ $s->lampiran_size ?? '' }}"
        data-can-confirm="0"
    >Lihat Detail</button>
</div>
