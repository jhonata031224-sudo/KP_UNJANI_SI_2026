<style>
/* Permintaan Laporan menggunakan shell DANPUS yang sama dengan tab Pelaporan lainnya. */
#danpus-permintaan-template-wrap{display:none!important}
#permintaan-laporan.danpus-request-panel{display:none;background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px;padding:20px;box-shadow:var(--p-shadow);margin:0 0 20px;}
body.danpus-request-active .pimp-page{display:none!important}
body.danpus-request-active #permintaan-laporan.danpus-request-panel{display:block!important}
.danpus-request-panel .request-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:16px;}
.danpus-request-panel .request-head h2{font-family:var(--display);font-size:19px;margin:0;color:var(--p-text);}
.danpus-request-panel .request-head p{margin:5px 0 0;font-size:12px;color:var(--p-muted);line-height:1.5;}
.danpus-request-panel .request-new{border:1px solid var(--p-accent);background:var(--p-accent);color:#fff;border-radius:9px;padding:9px 13px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;}
.danpus-request-panel .request-new:hover{filter:brightness(1.06)}
.danpus-request-panel .request-table-wrap{overflow-x:auto}
.danpus-request-panel .request-table{width:100%;border-collapse:collapse;min-width:760px}
.danpus-request-panel .request-table th{font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:var(--p-muted);text-align:left;padding:11px 12px;border-bottom:1px solid var(--p-border);white-space:nowrap}
.danpus-request-panel .request-table td{padding:13px 12px;border-bottom:1px solid var(--p-border);font-size:12px;color:var(--p-text);vertical-align:middle}
.danpus-request-panel .request-table tbody tr:last-child td{border-bottom:0}
.danpus-request-panel .request-subject{font-weight:800}.danpus-request-panel .request-muted{font-size:10px;color:var(--p-muted);margin-top:3px}
.danpus-request-panel .request-deadline{font-weight:700}.danpus-request-panel .request-deadline.late{color:var(--p-red)}.danpus-request-panel .request-deadline.soon{color:var(--p-yellow)}
.danpus-request-panel .request-status{display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:10px;font-weight:800;white-space:nowrap;border:1px solid transparent}
.danpus-request-panel .request-status.wait{color:var(--p-yellow);background:rgba(224,168,58,.12);border-color:rgba(224,168,58,.35)}
.danpus-request-panel .request-status.work{color:#2563a5;background:rgba(59,130,246,.1);border-color:rgba(59,130,246,.25)}
.danpus-request-panel .request-status.ok{color:var(--p-green);background:rgba(63,194,125,.12);border-color:rgba(63,194,125,.28)}
.danpus-request-panel .request-status.bad{color:var(--p-red);background:rgba(181,52,47,.12);border-color:rgba(198,40,40,.3)}
.danpus-request-panel .request-empty{padding:28px 20px;text-align:center;color:var(--p-muted);font-size:12px;background:var(--p-surface-2);border:1px dashed var(--p-border);border-radius:10px}
.danpus-request-modal{position:fixed;inset:0;background:rgba(15,23,42,.48);display:none;align-items:center;justify-content:center;padding:20px;z-index:10030}.danpus-request-modal.open{display:flex}
.danpus-request-form-card{width:min(720px,100%);max-height:90vh;overflow:auto;background:var(--p-surface);border:1px solid var(--p-border);border-radius:16px;padding:22px;box-shadow:0 25px 70px rgba(15,23,42,.25)}
.danpus-request-form-head{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:18px}.danpus-request-form-head h3{font-family:var(--display);font-size:20px;margin:0;color:var(--p-text)}
.danpus-request-close{border:1px solid var(--p-border);background:var(--p-surface-2);color:var(--p-muted);width:34px;height:34px;border-radius:9px;cursor:pointer;font-size:19px}
.danpus-request-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.danpus-request-field{display:flex;flex-direction:column;gap:6px}.danpus-request-field.full{grid-column:1/-1}.danpus-request-field label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--p-muted)}
.danpus-request-field input,.danpus-request-field textarea,.danpus-request-field select{width:100%;box-sizing:border-box;border:1px solid var(--p-border);border-radius:9px;background:var(--p-surface-2);color:var(--p-text);padding:10px 11px;font:inherit;font-size:12px;outline:none}.danpus-request-field textarea{min-height:100px;resize:vertical}.danpus-request-field input:focus,.danpus-request-field textarea:focus,.danpus-request-field select:focus{border-color:var(--p-accent);box-shadow:0 0 0 3px rgba(201,122,0,.1)}
.danpus-request-check-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}.danpus-request-check{display:flex;align-items:center;gap:8px;padding:9px 10px;border:1px solid var(--p-border);border-radius:9px;background:var(--p-surface-2);font-size:11px;color:var(--p-text);cursor:pointer}.danpus-request-check input{width:auto!important}
.danpus-request-form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:18px}.danpus-request-secondary,.danpus-request-primary{border:1px solid var(--p-border);border-radius:9px;padding:9px 14px;font-size:11px;font-weight:700;cursor:pointer}.danpus-request-secondary{background:var(--p-surface-2);color:var(--p-text)}.danpus-request-primary{border-color:var(--p-accent);background:var(--p-accent);color:#fff}
@media(max-width:700px){.danpus-request-panel{padding:15px}.danpus-request-panel .request-head{display:block}.danpus-request-panel .request-new{margin-top:12px}.danpus-request-form-grid{grid-template-columns:1fr}.danpus-request-field.full{grid-column:auto}.danpus-request-check-grid{grid-template-columns:1fr}}
</style>

<div id="danpus-permintaan-template-wrap">
  <section id="permintaan-laporan" class="danpus-request-panel" aria-label="Permintaan Laporan">
    <div class="request-head">
      <div>
        <h2>Permintaan Laporan</h2>
        <p>Berikan tugas pelaporan kepada satu atau beberapa satuan, lengkap dengan instruksi dan batas waktu.</p>
      </div>
      <button class="request-new" type="button" id="danpusOpenRequestForm">+ Buat Permintaan</button>
    </div>

    @if(session('status'))
      <div class="alert alert-success" style="margin-bottom:16px">{{ session('status') }}</div>
    @endif

    <div class="request-table-wrap">
      <table class="request-table">
        <thead>
          <tr><th>Perihal</th><th>Ditujukan</th><th>Deadline</th><th>Prioritas</th><th>Status</th></tr>
        </thead>
        <tbody>
          @forelse($permintaanLaporan as $item)
            @php
              $late = $item->isTerlambat();
              $soon = !$late && !$item->laporan_id && $item->deadline_at && $item->deadline_at->diffInHours(now()) <= 24;
              $statusClass = $late ? 'bad' : ($item->status === \App\Models\PermintaanLaporan::STATUS_SELESAI ? 'ok' : ($item->status === \App\Models\PermintaanLaporan::STATUS_DIKERJAKAN ? 'work' : 'wait'));
            @endphp
            <tr>
              <td><div class="request-subject">{{ $item->perihal }}</div>@if($item->instruksi)<div class="request-muted">{{ \Illuminate\Support\Str::limit($item->instruksi,90) }}</div>@endif</td>
              <td>{{ $item->tujuanSatuan->nama ?? '-' }}</td>
              <td><div class="request-deadline {{ $late ? 'late' : ($soon ? 'soon' : '') }}">{{ $item->deadline_at?->format('d M Y, H:i') ?? '-' }}</div>@if($late)<div class="request-muted">Melewati batas waktu</div>@elseif($soon && !$item->laporan_id)<div class="request-muted">Deadline mendekat</div>@endif</td>
              <td>{{ $item->prioritas }}</td>
              <td><span class="request-status {{ $statusClass }}">{{ $item->statusTampilan() }}</span></td>
            </tr>
          @empty
            <tr><td colspan="5"><div class="request-empty">Belum ada permintaan laporan. Klik <strong>+ Buat Permintaan</strong> untuk memberikan tugas pelaporan kepada satuan.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <div class="danpus-request-modal" id="danpusRequestModal">
    <div class="danpus-request-form-card">
      <div class="danpus-request-form-head"><h3>Buat Permintaan Laporan</h3><button type="button" class="danpus-request-close" id="danpusCloseRequestForm">×</button></div>
      <form method="POST" action="{{ route('permintaan-laporan.store') }}">
        @csrf
        <div class="danpus-request-form-grid">
          <div class="danpus-request-field full">
            <label>Satuan Tujuan</label>
            <div class="danpus-request-check-grid">
              @foreach($satuanPermintaanLaporan as $tujuan)
                <label class="danpus-request-check"><input type="checkbox" name="tujuan_satuan_ids[]" value="{{ $tujuan->id }}"> <span>{{ $tujuan->nama }}</span></label>
              @endforeach
            </div>
          </div>
          <div class="danpus-request-field full"><label for="danpusRequestPerihal">Perihal</label><input id="danpusRequestPerihal" name="perihal" required maxlength="255" placeholder="Contoh: Laporan Kegiatan Mingguan"></div>
          <div class="danpus-request-field full"><label for="danpusRequestInstruksi">Instruksi</label><textarea id="danpusRequestInstruksi" name="instruksi" maxlength="5000" placeholder="Jelaskan informasi yang perlu dilaporkan..."></textarea></div>
          <div class="danpus-request-field"><label for="danpusRequestDeadline">Deadline</label><input id="danpusRequestDeadline" name="deadline_at" type="datetime-local" required></div>
          <div class="danpus-request-field"><label for="danpusRequestPrioritas">Prioritas</label><select id="danpusRequestPrioritas" name="prioritas" required><option value="Sedang">Sedang</option><option value="Tinggi">Tinggi</option><option value="Rendah">Rendah</option></select></div>
        </div>
        <div class="danpus-request-form-actions"><button type="button" class="danpus-request-secondary" id="danpusCancelRequestForm">Batal</button><button type="submit" class="danpus-request-primary">Kirim Permintaan</button></div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  const dashboardUrl=@json(route('dashboard'));
  const requestUrl=dashboardUrl+'?section=permintaan';
  const requestLinkText='permintaan laporan';

  function clean(el){return (el?.textContent||'').replace(/\s+/g,' ').trim().toLowerCase();}
  function getPanel(){return document.getElementById('permintaan-laporan');}

  // Pindahkan template ke area konten agar tampil persis dalam shell DANPUS.
  const templateWrap=document.getElementById('danpus-permintaan-template-wrap');
  const content=document.querySelector('.content');
  if(templateWrap&&content&&!getPanel()) content.appendChild(templateWrap);

  let requestLink=Array.from(document.querySelectorAll('.side-nav-group .side-sub-link,.side-sub-link,.danpus-request-menu')).find(a=>clean(a)===requestLinkText);
  if(!requestLink){
    const group=Array.from(document.querySelectorAll('.side-nav-group')).find(g=>clean(g.querySelector('.side-nav-group-title'))==='pelaporan');
    const subnav=group?.querySelector('.side-subnav > div')||group?.querySelector('.side-subnav');
    if(subnav){
      requestLink=document.createElement('a');
      requestLink.className='side-sub-link';
      requestLink.innerHTML='<span class="sub-dot"></span><span>Permintaan Laporan</span>';
      subnav.appendChild(requestLink);
    }
  }
  if(requestLink) requestLink.href=requestUrl;

  function activateRequest(push){
    const panel=getPanel();
    if(!panel)return;
    document.body.classList.add('danpus-request-active');
    document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.side-sub-link,.side-link,.danpus-request-menu').forEach(a=>a.classList.remove('active'));
    requestLink?.classList.add('active');
    requestLink?.closest('.side-nav-group')?.classList.add('has-active-child');
    if(push) history.pushState({},'',requestUrl);
    window.scrollTo({top:0,behavior:'smooth'});
    try{sessionStorage.setItem('siberad-pimpinan-active-tab','permintaan-laporan')}catch(e){}
  }

  function deactivateRequest(){
    document.body.classList.remove('danpus-request-active');
    const panel=getPanel();
    if(panel) panel.style.display='none';
    try{
      const url=new URL(window.location.href);
      if(url.searchParams.get('section')==='permintaan'){
        url.searchParams.delete('section');
        history.replaceState({},'',url.pathname+(url.search||'')+(url.hash||''));
      }
      if(sessionStorage.getItem('siberad-pimpinan-active-tab')==='permintaan-laporan')sessionStorage.removeItem('siberad-pimpinan-active-tab');
    }catch(e){}
  }

  requestLink?.addEventListener('click',function(e){
    e.preventDefault();
    e.stopPropagation();
    activateRequest(true);
  });

  // Saat memilih menu lain, kembalikan shell normal dan biarkan handler tab bawaan bekerja.
  document.addEventListener('click',function(e){
    const link=e.target.closest?.('.side-link,.side-sub-link');
    if(!link||link===requestLink)return;
    setTimeout(deactivateRequest,0);
  },true);

  window.addEventListener('popstate',function(){
    if(new URLSearchParams(window.location.search).get('section')==='permintaan') activateRequest(false);
    else deactivateRequest();
  });

  const initialSection=new URLSearchParams(window.location.search).get('section');
  if(initialSection==='permintaan') activateRequest(false);

  const modal=document.getElementById('danpusRequestModal');
  function closeRequest(){modal?.classList.remove('open')}
  document.getElementById('danpusOpenRequestForm')?.addEventListener('click',()=>modal?.classList.add('open'));
  document.getElementById('danpusCloseRequestForm')?.addEventListener('click',closeRequest);
  document.getElementById('danpusCancelRequestForm')?.addEventListener('click',closeRequest);
  modal?.addEventListener('click',e=>{if(e.target===modal)closeRequest()});
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closeRequest()});
})();
</script>
