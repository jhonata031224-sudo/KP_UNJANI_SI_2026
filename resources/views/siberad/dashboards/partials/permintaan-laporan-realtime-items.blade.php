@foreach($permintaanLaporan as $permintaan)
@php
    $statusTampilan = $permintaan->statusTampilan();
    $deadlineClass = $permintaan->isTerlambat() ? 'bad' : ($permintaan->deadline_at->diffInHours(now()) <= 24 ? 'near' : 'normal');
    $laporanAktif = $permintaan->laporans->sortByDesc('id')->first();
    $bolehUpdateProgres = !$permintaan->laporan_id && $permintaan->status === 'Sedang dikerjakan';
    $bolehUpdateCheckpoint = $laporanAktif && $laporanAktif->status === \App\Models\Laporan::STATUS_PROGRES && !$permintaan->laporan_id;
    $bolehUpdate = $bolehUpdateProgres || $bolehUpdateCheckpoint;
@endphp
<article class="deadline-sender-item {{ $deadlineClass }}" data-realtime-permintaan-id="{{ $permintaan->id }}" data-search="{{ strtolower($permintaan->perihal) }}">
    <div class="deadline-sender-main">
        <div class="deadline-sender-title">{{ $permintaan->perihal }}</div>
        <div class="deadline-sender-meta">Dari {{ $permintaan->pembuat->satuan->nama ?? $permintaan->pembuat->name ?? 'Pimpinan' }} · Deadline {{ $permintaan->deadline_at->translatedFormat('d M Y H:i') }}</div>
        @if($permintaan->instruksi)<div class="deadline-sender-instruction">{{ $permintaan->instruksi }}</div>@endif
    </div>
    <div class="deadline-sender-side">
        <span class="deadline-pill {{ $statusTampilan === 'Terlambat' ? 'bad' : ($statusTampilan === 'Revisi' ? 'revisi' : ($statusTampilan === 'Menunggu pemeriksaan' ? 'blue' : ($statusTampilan === 'Selesai' ? 'ok' : 'wait'))) }}">{{ $statusTampilan }}</span>
        <span class="deadline-progress-badge">{{ $permintaan->progres }}%</span>
        @if(!$permintaan->laporan_id)
            <div class="deadline-actions">
                @if($permintaan->status === 'Belum dikerjakan')
                    <form method="POST" action="{{ route('permintaan-laporan.mulai', $permintaan) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="deadline-secondary small confirm-btn">Konfirmasi</button>
                    </form>
                @endif
                @if($bolehUpdate)
                    <button type="button" class="deadline-secondary small update-progress-btn" data-progress-modal="progressModal-{{ $permintaan->id }}">Update Progres</button>
                @endif
            </div>
        @else
            <span class="deadline-complete">✓ Laporan sudah dikirim</span>
        @endif
    </div>
</article>

@if($bolehUpdate)
<div class="progress-update-modal" id="progressModal-{{ $permintaan->id }}" aria-hidden="true">
    <div class="progress-update-backdrop" data-progress-close="progressModal-{{ $permintaan->id }}"></div>
    <div class="progress-update-card" role="dialog" aria-modal="true" aria-labelledby="progressTitle-{{ $permintaan->id }}">
        <div class="progress-update-head">
            <div>
                <div class="progress-update-kicker">LAPORAN / PROGRES</div>
                <h3 id="progressTitle-{{ $permintaan->id }}">{{ $permintaan->perihal }}</h3>
            </div>
            <button type="button" class="progress-update-close" data-progress-close="progressModal-{{ $permintaan->id }}" aria-label="Tutup">×</button>
        </div>
        @if($bolehUpdateCheckpoint)
            <form method="POST" action="{{ route('laporan.update-progres', $laporanAktif) }}" enctype="multipart/form-data" class="progress-update-form">
                @csrf @method('PATCH')
        @else
            <form method="POST" action="{{ route('laporan.store') }}" enctype="multipart/form-data" class="progress-update-form">
                @csrf
                <input type="hidden" name="tujuan_satuan_id" value="{{ $permintaan->pembuat->satuan_id }}">
                <input type="hidden" name="permintaan_laporan_id" value="{{ $permintaan->id }}">
                <input type="hidden" name="perihal" value="{{ $permintaan->perihal }}">
        @endif
            <div class="progress-update-grid">
                <label class="progress-update-field full">
                    <span>Deskripsi progres <b>*</b></span>
                    <textarea name="deskripsi" rows="4" required placeholder="Jelaskan pekerjaan/progres yang sudah dilakukan..."></textarea>
                </label>
                <label class="progress-update-field">
                    <span>Persentase progres <b>*</b></span>
                    <input type="number" name="progres" min="{{ max(0, (int)$permintaan->progres) }}" max="99" value="{{ max(0, (int)$permintaan->progres) }}" required>
                </label>
                <label class="progress-update-field">
                    <span>Prioritas <b>*</b></span>
                    <select name="prioritas" required>
                        <option value="Rendah">Rendah</option>
                        <option value="Sedang" selected>Sedang</option>
                        <option value="Tinggi">Tinggi</option>
                    </select>
                </label>
                <label class="progress-update-field full">
                    <span>Kendala</span>
                    <textarea name="kendala" rows="3" placeholder="Isi jika ada kendala, kosongkan jika tidak ada."></textarea>
                </label>
                <label class="progress-update-field full">
                    <span>Lampiran PDF</span>
                    <input type="file" name="lampiran" accept="application/pdf,.pdf">
                    <small>Maksimal 20 MB.</small>
                </label>
            </div>
            <div class="progress-update-actions">
                <button type="button" class="deadline-secondary small" data-progress-close="progressModal-{{ $permintaan->id }}">Batal</button>
                <button type="submit" class="deadline-secondary small progress-submit-btn">Simpan Progres</button>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach

<style>
.deadline-actions{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.update-progress-btn{border-color:var(--gold,#c97a00)!important;color:var(--gold-bright,#c97a00)!important;background:var(--panel,#fff)!important}
.update-progress-btn:hover{background:var(--gold-dim,rgba(201,122,0,.1))!important}
.progress-update-modal{position:fixed;inset:0;z-index:100500;display:none;align-items:center;justify-content:center;padding:20px;box-sizing:border-box}
.progress-update-modal.open{display:flex}
.progress-update-backdrop{position:absolute;inset:0;background:rgba(2,8,23,.58);backdrop-filter:blur(2px)}
.progress-update-card{position:relative;width:min(680px,100%);max-height:min(90vh,760px);overflow:auto;background:var(--panel,#fff);border:1px solid var(--border-soft,#e2e8f0);border-radius:16px;padding:22px;box-shadow:0 24px 70px rgba(0,0,0,.3);box-sizing:border-box}
.progress-update-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}.progress-update-kicker{font:700 10px var(--mono,monospace);letter-spacing:.09em;color:var(--gold-bright,#c97a00);margin-bottom:5px}.progress-update-head h3{margin:0;font-size:17px;line-height:1.35;color:var(--text,#17212b)}
.progress-update-close{width:34px;height:34px;border:1px solid var(--border,#e2e8f0);border-radius:8px;background:transparent;color:var(--text-muted,#64748b);font-size:22px;line-height:1;cursor:pointer}.progress-update-close:hover{border-color:var(--red,#c83b3b);color:var(--red,#c83b3b)}
.progress-update-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.progress-update-field{display:flex;flex-direction:column;gap:7px;color:var(--text,#17212b);font-size:12px;font-weight:700}.progress-update-field.full{grid-column:1/-1}.progress-update-field span{color:var(--text-muted,#64748b)}.progress-update-field b{color:var(--red,#c83b3b)}.progress-update-field input,.progress-update-field select,.progress-update-field textarea{width:100%;box-sizing:border-box;border:1px solid var(--border,#e2e8f0);border-radius:8px;background:var(--panel-alt,#f8fafc);color:var(--text,#17212b);padding:9px 10px;font:inherit;font-size:13px}.progress-update-field textarea{resize:vertical}.progress-update-field small{font-size:10px;color:var(--text-dim,#64748b);font-weight:500}.progress-update-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:18px;padding-top:14px;border-top:1px solid var(--border-soft,#e2e8f0)}
@media(max-width:650px){.progress-update-grid{grid-template-columns:1fr}.progress-update-field.full{grid-column:auto}.progress-update-card{padding:16px}}
</style>
<script>
(function(){
    function openModal(id){var el=document.getElementById(id);if(!el)return;el.classList.add('open');el.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';}
    function closeModal(id){var el=document.getElementById(id);if(!el)return;el.classList.remove('open');el.setAttribute('aria-hidden','true');if(!document.querySelector('.progress-update-modal.open'))document.body.style.overflow='';}
    document.addEventListener('click',function(e){
        var open=e.target.closest('[data-progress-modal]');
        if(open){e.preventDefault();openModal(open.getAttribute('data-progress-modal'));return;}
        var close=e.target.closest('[data-progress-close]');
        if(close){e.preventDefault();closeModal(close.getAttribute('data-progress-close'));}
    });
    document.addEventListener('keydown',function(e){
        if(e.key==='Escape')document.querySelectorAll('.progress-update-modal.open').forEach(function(el){closeModal(el.id);});
    });
})();
</script>
