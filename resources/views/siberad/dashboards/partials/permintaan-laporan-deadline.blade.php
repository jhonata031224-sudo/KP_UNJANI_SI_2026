@php
    $kodeRoleDeadline = strtoupper((string) ($satuan->kode ?? ''));
    $isPimpinanDeadline = in_array($kodeRoleDeadline, ['DANPUS', 'WADAN'], true);
    $pengirimDeadline = ['SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK', 'SATLAKDUKTEK', 'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT'];
@endphp

<div id="permintaanLaporanFeature" style="display:none;">
@if($isPimpinanDeadline)
<section id="permintaan-laporan" class="section-block deadline-section">
    <div class="section-head-clean">
        <div>
            <h2>Permintaan Laporan</h2>
            <p>Berikan tugas pelaporan kepada satu atau beberapa satuan, lengkap dengan instruksi dan batas waktu.</p>
        </div>
        <button type="button" class="deadline-primary" id="togglePermintaanForm">+ Buat Permintaan</button>
    </div>

    <div id="permintaanFormWrap" class="deadline-form-wrap" style="display:none;">
        <form method="POST" action="{{ route('permintaan-laporan.store') }}" class="deadline-form">
            @csrf
            <div class="deadline-form-grid">
                <div class="deadline-field full">
                    <label>Ditujukan kepada</label>
                    <div class="deadline-check-grid">
                        @foreach($satuanPermintaanLaporan as $target)
                            <label class="deadline-check">
                                <input type="checkbox" name="tujuan_satuan_ids[]" value="{{ $target->id }}">
                                <span>{{ $target->kode }}</span>
                                <small>{{ $target->nama }}</small>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="deadline-field">
                    <label for="deadlinePerihal">Perihal</label>
                    <input id="deadlinePerihal" name="perihal" maxlength="255" required placeholder="Contoh: Laporan Kegiatan Mingguan">
                </div>
                <div class="deadline-field">
                    <label for="deadlineAt">Deadline</label>
                    <input id="deadlineAt" type="datetime-local" name="deadline_at" required>
                </div>
                <div class="deadline-field">
                    <label for="deadlinePrioritas">Prioritas</label>
                    <select id="deadlinePrioritas" name="prioritas" required>
                        <option value="Rendah">Rendah</option>
                        <option value="Sedang" selected>Sedang</option>
                        <option value="Tinggi">Tinggi</option>
                    </select>
                </div>
                <div class="deadline-field full">
                    <label for="deadlineInstruksi">Instruksi</label>
                    <textarea id="deadlineInstruksi" name="instruksi" maxlength="5000" placeholder="Tuliskan informasi yang perlu dilaporkan atau arahan khusus..."></textarea>
                </div>
            </div>
            <div class="deadline-form-actions">
                <button type="button" class="deadline-secondary" id="cancelPermintaanForm">Batal</button>
                <button type="submit" class="deadline-primary">Kirim Permintaan</button>
            </div>
        </form>
    </div>

    <div class="deadline-table-wrap">
        <table class="deadline-table">
            <thead>
                <tr><th>Perihal</th><th>Ditujukan</th><th>Deadline</th><th>Status</th><th>Laporan</th></tr>
            </thead>
            <tbody>
            @forelse($permintaanLaporan as $permintaan)
                @php $statusTampilan = $permintaan->statusTampilan(); @endphp
                <tr>
                    <td><strong>{{ $permintaan->perihal }}</strong>@if($permintaan->instruksi)<small>{{ $permintaan->instruksi }}</small>@endif</td>
                    <td>{{ $permintaan->tujuanSatuan->kode ?? $permintaan->tujuanSatuan->nama ?? '-' }}</td>
                    <td>{{ $permintaan->deadline_at->translatedFormat('d M Y H:i') }}</td>
                    <td><span class="deadline-pill {{ $statusTampilan === 'Terlambat' ? 'bad' : ($statusTampilan === 'Selesai' ? 'ok' : ($statusTampilan === 'Menunggu pemeriksaan' ? 'blue' : 'wait')) }}">{{ $statusTampilan }}</span></td>
                    <td>{{ $permintaan->laporan_id ? 'Terkirim' : 'Belum dikirim' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="deadline-empty">Belum ada permintaan laporan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@elseif(in_array($kodeRoleDeadline, $pengirimDeadline, true))
<section id="permintaan-laporan" class="tab-panel report-card deadline-sender-section">
    <div class="deadline-sender-head">
        <div>
            <h3>Permintaan Laporan</h3>
            <p>Daftar tugas pelaporan dari Danpus/Wadan beserta batas waktu pengiriman.</p>
        </div>
        <span class="deadline-count">{{ $permintaanLaporan->count() }} aktif</span>
    </div>
    <div class="deadline-sender-list">
        @forelse($permintaanLaporan as $permintaan)
            @php
                $statusTampilan = $permintaan->statusTampilan();
                $deadlineClass = $permintaan->isTerlambat() ? 'bad' : ($permintaan->deadline_at->diffInHours(now()) <= 24 ? 'near' : 'normal');
            @endphp
            <article class="deadline-sender-item {{ $deadlineClass }}">
                <div class="deadline-sender-main">
                    <div class="deadline-sender-title">{{ $permintaan->perihal }}</div>
                    <div class="deadline-sender-meta">
                        Dari {{ $permintaan->pembuat->satuan->nama ?? $permintaan->pembuat->name ?? 'Pimpinan' }} · Deadline {{ $permintaan->deadline_at->translatedFormat('d M Y H:i') }}
                    </div>
                    @if($permintaan->instruksi)<div class="deadline-sender-instruction">{{ $permintaan->instruksi }}</div>@endif
                </div>
                <div class="deadline-sender-side">
                    <span class="deadline-pill {{ $statusTampilan === 'Terlambat' ? 'bad' : ($statusTampilan === 'Menunggu pemeriksaan' ? 'blue' : ($statusTampilan === 'Selesai' ? 'ok' : 'wait')) }}">{{ $statusTampilan }}</span>
                    @if(!$permintaan->laporan_id)
                        <div class="deadline-actions">
                            @if($permintaan->status === 'Belum dikerjakan')
                                <form method="POST" action="{{ route('permintaan-laporan.mulai', $permintaan) }}">@csrf @method('PATCH')<button type="submit" class="deadline-secondary small">Mulai kerjakan</button></form>
                            @endif
                            <button type="button" class="deadline-primary small use-permintaan" data-request-id="{{ $permintaan->id }}" data-target-id="{{ $permintaan->tujuan_satuan_id }}" data-perihal="{{ e($permintaan->perihal) }}" data-prioritas="{{ e($permintaan->prioritas) }}" data-instruksi="{{ e($permintaan->instruksi ?? '') }}">Buat Laporan</button>
                        </div>
                    @else
                        <span class="deadline-complete">✓ Laporan sudah dikirim</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="deadline-empty sender">Belum ada permintaan laporan aktif dari pimpinan.</div>
        @endforelse
    </div>
</section>
@endif
</div>

<style>
.deadline-section,.deadline-sender-section{position:relative;overflow:hidden}.deadline-primary,.deadline-secondary{border:1px solid transparent;border-radius:9px;padding:9px 14px;font-size:11px;font-weight:700;cursor:pointer;transition:.15s ease}.deadline-primary{background:var(--p-accent,var(--gold-bright));color:#fff}.deadline-primary:hover{filter:brightness(1.06);transform:translateY(-1px)}.deadline-secondary{background:var(--p-surface-2,var(--panel-alt));border-color:var(--p-border,var(--border));color:var(--p-text,var(--text))}.deadline-secondary:hover{border-color:var(--p-accent,var(--gold-bright))}.deadline-primary.small,.deadline-secondary.small{padding:7px 10px;font-size:10px}.deadline-form-wrap{margin:0 0 18px;padding:16px;border:1px solid var(--p-border,var(--border-soft));border-radius:12px;background:var(--p-surface-2,var(--panel-alt))}.deadline-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.deadline-field{display:flex;flex-direction:column;gap:6px}.deadline-field.full{grid-column:1/-1}.deadline-field label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--p-muted,var(--text-muted))}.deadline-field input,.deadline-field select,.deadline-field textarea{width:100%;box-sizing:border-box;border:1px solid var(--p-border,var(--border));border-radius:8px;background:var(--p-surface,var(--panel));color:var(--p-text,var(--text));padding:9px 10px;font:inherit;font-size:12px}.deadline-field textarea{min-height:100px;resize:vertical}.deadline-check-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}.deadline-check{display:grid;grid-template-columns:auto 1fr;column-gap:7px;align-items:center;padding:9px;border:1px solid var(--p-border,var(--border));border-radius:9px;background:var(--p-surface,var(--panel));cursor:pointer}.deadline-check input{width:auto}.deadline-check span{font-family:var(--mono);font-size:10px;font-weight:800;color:var(--p-accent,var(--gold-bright))}.deadline-check small{grid-column:2;font-size:9px;color:var(--p-muted,var(--text-muted));line-height:1.35}.deadline-form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}.deadline-table-wrap{overflow-x:auto}.deadline-table{width:100%;border-collapse:collapse;min-width:720px}.deadline-table th{font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:var(--p-muted,var(--text-muted));text-align:left;padding:10px;border-bottom:1px solid var(--p-border,var(--border))}.deadline-table td{padding:11px 10px;border-bottom:1px solid var(--p-border,var(--border));font-size:11px;vertical-align:middle}.deadline-table td strong{display:block}.deadline-table td small{display:block;color:var(--p-muted,var(--text-muted));margin-top:3px;max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.deadline-pill{display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:9px;font-weight:800;border:1px solid transparent;white-space:nowrap}.deadline-pill.wait{color:#a36d00;background:rgba(224,168,58,.12);border-color:rgba(224,168,58,.3)}.deadline-pill.ok{color:#16834b;background:rgba(63,194,125,.12);border-color:rgba(63,194,125,.28)}.deadline-pill.bad{color:#c83b3b;background:rgba(181,52,47,.08);border-color:rgba(198,40,40,.28)}.deadline-pill.blue{color:#2476ad;background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.25)}.deadline-empty{text-align:center!important;color:var(--p-muted,var(--text-muted));padding:25px!important}.deadline-empty.sender{padding:16px!important}.deadline-sender-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}.deadline-sender-head h3{margin:0 0 4px}.deadline-sender-head p{margin:0;font-size:11px;color:var(--text-muted);line-height:1.5}.deadline-count{font-family:var(--mono);font-size:10px;color:var(--gold-bright);white-space:nowrap}.deadline-sender-list{display:grid;gap:10px}.deadline-sender-item{display:flex;justify-content:space-between;gap:16px;padding:13px;border:1px solid var(--border-soft);border-radius:10px;background:var(--panel-alt)}.deadline-sender-item.near{border-color:rgba(224,168,58,.45)}.deadline-sender-item.bad{border-color:rgba(198,40,40,.35)}.deadline-sender-main{min-width:0}.deadline-sender-title{font-size:13px;font-weight:800}.deadline-sender-meta{font-size:10px;color:var(--text-muted);margin-top:4px}.deadline-sender-instruction{font-size:11px;line-height:1.55;color:var(--text-muted);margin-top:8px;white-space:pre-wrap}.deadline-sender-side{display:flex;flex-direction:column;align-items:flex-end;justify-content:space-between;gap:8px;flex-shrink:0}.deadline-actions{display:flex;gap:6px;align-items:center;justify-content:flex-end}.deadline-complete{font-size:10px;font-weight:700;color:var(--green)}
@media(max-width:850px){.deadline-check-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.deadline-form-grid{grid-template-columns:1fr}.deadline-field.full{grid-column:auto}.deadline-sender-item{flex-direction:column}.deadline-sender-side{align-items:flex-start}.deadline-actions{justify-content:flex-start}}@media(max-width:550px){.deadline-check-grid{grid-template-columns:1fr}.deadline-sender-head{display:block}.deadline-count{display:inline-block;margin-top:8px}}
</style>

<script>
(function(){
    function initPermintaanLaporanFeature(){
        var root=document.getElementById('permintaanLaporanFeature');
        if(!root || root.dataset.initialized==='1') return;
        root.dataset.initialized='1';
        var target = document.querySelector('.pimp-page') || document.querySelector('.content');
        if(!target) return;

        var section=root.querySelector('#permintaan-laporan');
        if(!section) return;
        target.insertBefore(section, target.querySelector('#monitoring, #riwayat') || null);
        root.remove();

        var navGroup=document.getElementById('{{ $isPimpinanDeadline ? 'reportGroup' : 'laporanGroup' }}');
        if(navGroup){
            var nav=navGroup.querySelector('.side-subnav > div');
            if(nav && !nav.querySelector('a[href="#permintaan-laporan"]')){
                var link=document.createElement('a');
                link.href='#permintaan-laporan'; link.className='side-sub-link';
                link.innerHTML='<span class="sub-dot"></span>Permintaan Laporan';
                nav.appendChild(link);

                /* Link ini dibuat setelah script utama shell (laporan-role/laporan-pimpinan)
                   selesai mengikat event klik ke submenu yang ada di HTML awal, jadi perlu
                   diikat manual di sini agar perilakunya sama seperti submenu lain. */
                link.addEventListener('click', function(e){
                    e.preventDefault();
                    document.querySelectorAll('.tab-panel').forEach(function(p){ p.classList.remove('active'); });
                    section.classList.add('active');
                    document.querySelectorAll('.side-link,.side-sub-link').forEach(function(x){ x.classList.remove('active'); });
                    link.classList.add('active');
                    document.querySelectorAll('.side-nav-group').forEach(function(g){ g.classList.remove('has-active-child'); });
                    var group=link.closest('.side-nav-group');
                    if(group){
                        group.classList.add('has-active-child');
                        var sidebar=document.getElementById('sidebar');
                        if(sidebar && sidebar.classList.contains('collapsed')){
                            group.classList.remove('open');
                            try{sessionStorage.setItem('{{ $isPimpinanDeadline ? 'siberad-pimpinan-group-' : 'siberad-role-group-' }}'+group.id,'closed')}catch(err){}
                            window.siberadRepositionSubnavFlyouts?.();
                        }
                    }
                    document.getElementById('sidebar')?.classList.remove('open');
                    try{sessionStorage.setItem('{{ $isPimpinanDeadline ? 'siberad-pimpinan-active-tab' : 'siberad-role-active-tab' }}','permintaan-laporan')}catch(err){}
                });
            }
        }

        var toggle=document.getElementById('togglePermintaanForm');
        var wrap=document.getElementById('permintaanFormWrap');
        var cancel=document.getElementById('cancelPermintaanForm');
        toggle?.addEventListener('click',function(){if(wrap)wrap.style.display=wrap.style.display==='none'?'block':'none';});
        cancel?.addEventListener('click',function(){if(wrap)wrap.style.display='none';});

        document.querySelectorAll('.use-permintaan').forEach(function(btn){
            btn.addEventListener('click',function(){
                var form=document.querySelector('form[action$="/laporan"]');
                var kirim=document.getElementById('kirim');
                if(!form || !kirim) return;
                var hidden=form.querySelector('input[name="permintaan_laporan_id"]');
                if(!hidden){hidden=document.createElement('input');hidden.type='hidden';hidden.name='permintaan_laporan_id';form.appendChild(hidden)}
                hidden.value=btn.dataset.requestId||'';
                var tujuan=form.querySelector('[name="tujuan_satuan_id"]'); if(tujuan && btn.dataset.targetId) tujuan.value=btn.dataset.targetId;
                var perihal=form.querySelector('[name="perihal"]'); if(perihal && btn.dataset.perihal) perihal.value=btn.dataset.perihal;
                var prioritas=form.querySelector('[name="prioritas"]'); if(prioritas && btn.dataset.prioritas) prioritas.value=btn.dataset.prioritas;
                var deskripsi=form.querySelector('[name="deskripsi"]'); if(deskripsi && btn.dataset.instruksi && !deskripsi.value.trim()) deskripsi.value=btn.dataset.instruksi;
                window.location.hash='kirim';
                var link=document.querySelector('.side-sub-link[href="#kirim"]'); link?.click();
                perihal?.focus();
            });
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initPermintaanLaporanFeature); else initPermintaanLaporanFeature();
})();
</script>
