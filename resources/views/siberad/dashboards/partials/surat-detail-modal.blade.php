<div class="report-modal" id="suratDetailModal"><div class="report-modal-card"><div class="report-modal-head"><div style="min-width:0"><h3 id="suratDetailJudul" style="margin:0 0 4px">Detail Surat</h3><p id="suratDetailDari" style="margin:0;font-size:12px;color:var(--text-muted)">-</p></div><button type="button" class="btn-icon-close" id="suratDetailClose" aria-label="Tutup" style="margin-left:auto"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div><div class="surat-detail-body"><div class="surat-detail-col surat-detail-col-left"><div class="surat-detail-item" id="suratDetailTujuanItem"><div><div class="surat-detail-item-label">Tujuan</div><div class="surat-detail-item-value" id="suratDetailTujuanValue" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap"><span id="suratDetailTujuan">-</span><span class="satuan-pill" id="suratDetailTujuanKode" style="display:none"></span></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Perihal</div><div class="surat-detail-item-value" id="suratDetailPerihal">-</div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Kategori</div><div class="surat-detail-item-value" id="suratDetailKategori">-</div></div></div><div class="surat-detail-item-row"><div class="surat-detail-item"><div><div class="surat-detail-item-label">Prioritas</div><div class="surat-detail-item-value"><span class="priority-tag" id="suratDetailPrioritas">-</span></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Status</div><div class="surat-detail-item-value"><span class="status-badge" id="suratDetailStatusText">-</span></div></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Ringkasan</div><div class="surat-detail-item-value" id="suratDetailRingkasan">-</div></div></div>
{{-- Disposisi: kalimat siapa mendisposisi ke siapa --}}
<div class="surat-detail-item" id="suratDetailDisposisiPanel" style="display:none"><div style="border-top:1px solid var(--border);padding-top:12px;margin-top:4px"><div class="surat-detail-item-label">Disposisi</div><div class="surat-detail-item-value" id="suratDetailDisposisiVal" style="font-weight:600;color:var(--primary)">-</div></div></div>
{{-- Tindakan: kolom terpisah, instruksi konkret dari pemberi disposisi --}}
<div class="surat-detail-item" id="suratDetailTindakanPanel" style="display:none"><div><div class="surat-detail-item-label">Tindakan</div><div id="suratDetailTindakanWrap" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px"></div></div></div>
@php
    $isWadanDashboard = strtoupper($satuan->kode ?? '') === 'WADAN';
@endphp
@if($isWadanDashboard)
    @php
        // Disposisi versi Wadan langsung berupa daftar satuan (bukan daftar
        // label jabatan) -- kecuali Danpus (tidak boleh didisposisi turun ke
        // Danpus) dan Wadan sendiri.
        $wadanDisposisiSatuan = \App\Models\Satuan::orderBy('nama')->get()->filter(function ($st) {
            return ! in_array(strtoupper($st->kode), ['ADMIN', 'DANPUS', 'WADAN'], true);
        });
        $wadanTindakanOptions = \App\Models\LaporanSurat::TINDAKAN_WADAN_OPTIONS;
    @endphp
    {{-- Form Disposisi & Tindakan mode simpel Wadan dipindah ke sub-modal
         #wadanDisposisiModal (muncul setelah klik tombol "Konfirmasi"),
         lihat blok di bawah setelah penutup modal utama. --}}
@endif
</div><div class="surat-detail-col surat-detail-col-right"><div class="surat-detail-panel"><div class="surat-detail-panel-title">Riwayat Alur</div><div class="surat-detail-timeline" id="suratDetailTimeline"></div></div><div class="surat-detail-panel" id="suratDetailDokumenPanel" hidden><div class="surat-detail-panel-title">Dokumen</div><div id="suratDetailDokumenWrap"></div></div></div></div>
<div class="modal-actions" id="suratDetailActions">
    <button type="button" class="btn" id="suratDetailTutup">Tutup</button>
    {{-- Konfirmasi penerima utama --}}
    <button type="button" class="btn btn-primary" id="suratDetailKonfirmasi" hidden>Konfirmasi / ACC &amp; Terima</button>
    {{-- Konfirmasi tembusan / view only --}}
    <button type="button" class="btn btn-primary" id="suratDetailKonfirmasiTembusan" hidden>Konfirmasi Mengetahui</button>
    {{-- Teruskan (Wadan → Satrap) --}}
    <button type="button" class="btn btn-primary" id="suratDetailTeruskan" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><polyline points="9 18 15 12 9 6"/></svg>Disposisi &amp; Teruskan
    </button>
    {{-- Kembalikan ke Danpus (Wadan setelah terima laporan) --}}
    <button type="button" class="btn btn-primary" id="suratDetailKeDanpus" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><polyline points="15 18 9 12 15 6"/></svg>Teruskan ke Danpus
    </button>
    {{-- Teruskan ke Wadan (Urdal setelah cek & konfirmasi Surat Keluar Satlak) --}}
    <button type="button" class="btn btn-primary" id="suratDetailKeWadan" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><polyline points="15 18 9 12 15 6"/></svg>Teruskan ke Wadan
    </button>
    {{-- Selesai (Danpus) --}}
    <button type="button" class="btn btn-success" id="suratDetailSelesai" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><path d="M5 13l4 4L19 7"/></svg>Konfirmasi Selesai
    </button>
    {{-- Disposisi Ulang (Danpus) --}}
    <button type="button" class="btn btn-warning" id="suratDetailDisposisiUlang" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>Tindakan / Disposisi Baru
    </button>
    {{-- Konfirmasi Wadan (mode simpel): klik langsung mengirim konfirmasi via
         AJAX (modal TIDAK tertutup) -- lalu tombol ini disable & tombol
         "Teruskan Surat" di sebelahnya jadi aktif. --}}
    <button type="button" class="btn btn-secondary" id="suratDetailKonfirmasiWadan" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><path d="M5 13l4 4L19 7"/></svg>Konfirmasi
    </button>
    {{-- Teruskan Surat (mode simpel Wadan): hanya aktif setelah "Konfirmasi"
         di atas diklik. Klik membuka sub-modal #wadanDisposisiModal berisi
         form Disposisi & Tindakan + tombol "Disposisi & Teruskan". --}}
    <button type="button" class="btn btn-primary" id="suratDetailTeruskanWadan" hidden disabled>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><polyline points="9 18 15 12 9 6"/></svg>Teruskan Surat
    </button>
</div></div></div>

@if($isWadanDashboard)
{{-- ═══════════════════════════════════════════════════
     SUB-MODAL: DISPOSISI & TERUSKAN (mode simpel Wadan)
     Muncul setelah Wadan klik "Konfirmasi" di modal Surat Masuk.
     ═══════════════════════════════════════════════════ --}}
<div class="report-modal" id="wadanDisposisiModal">
    <div class="report-modal-card" style="max-width:560px">
        <div class="report-modal-head">
            <div style="min-width:0">
                <h3 style="margin:0 0 4px">Disposisi &amp; Teruskan Surat</h3>
                <p style="margin:0;font-size:12px;color:var(--text-muted)">Pilih satuan tujuan dan tindakan yang harus dilaksanakan sebelum meneruskan.</p>
            </div>
            <button type="button" class="btn-icon-close" id="wadanDisposisiClose" aria-label="Tutup" style="margin-left:auto">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="form-group" style="margin-bottom:14px">
            <label class="form-label" for="wadanDisposisiSatuanSel">Disposisi <span style="color:var(--red)">*</span></label>
            <select id="wadanDisposisiSatuanSel" class="form-select">
                <option value="">— Pilih Satuan Tujuan —</option>
                @foreach($wadanDisposisiSatuan as $st)
                    <option value="{{ $st->id }}" data-label="{{ e($st->nama) }}">{{ $st->nama }} ({{ $st->kode }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Tindakan <span style="color:var(--red)">*</span> <span style="font-size:11px;color:var(--text-muted);font-weight:400">(Pilih minimal satu)</span></label>
            <div id="wadanDisposisiTindakanGrid" style="display:grid;grid-template-columns:1fr 1fr;gap:6px 16px;margin-top:6px;max-height:220px;overflow-y:auto;padding:2px 0;border:1px solid transparent;border-radius:10px">
                @foreach($wadanTindakanOptions as $opt)
                    <label class="surat-tindakan-check-label" style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;padding:4px 6px;border-radius:6px;transition:background .15s">
                        <input type="checkbox" value="{{ $opt }}" class="wadan-disposisi-tindakan-check" style="width:15px;height:15px;accent-color:var(--primary);cursor:pointer;flex-shrink:0">
                        <span>{{ $opt }}</span>
                    </label>
                @endforeach
            </div>
            <span id="wadanDisposisiTindakanError" style="display:none;align-items:center;gap:6px;color:var(--red);font-size:10.5px;margin-top:4px">Tindakan wajib dipilih (minimal satu).</span>
        </div>

        <div class="modal-actions" style="gap:10px">
            <button type="button" class="btn" id="wadanDisposisiBatal">Batal</button>
            <button type="button" class="btn btn-primary" id="wadanDisposisiSubmit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Disposisi &amp; Teruskan
            </button>
        </div>
    </div>
</div>
<script>
(function(){
    var modal = document.getElementById('wadanDisposisiModal');
    if (!modal) return;

    var sel        = document.getElementById('wadanDisposisiSatuanSel');
    var grid       = document.getElementById('wadanDisposisiTindakanGrid');
    var errEl      = document.getElementById('wadanDisposisiTindakanError');
    var currentAction = '';
    var currentCsrf   = '';

    function close(){ modal.classList.remove('open'); }

    document.getElementById('wadanDisposisiClose').addEventListener('click', close);
    document.getElementById('wadanDisposisiBatal').addEventListener('click', close);
    modal.addEventListener('click', function(e){ if (e.target === modal) close(); });
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && modal.classList.contains('open')) close();
    });

    // Dipanggil dari surat-detail-modal saat tombol "Konfirmasi" (mode simpel Wadan) diklik
    window.bukaWadanDisposisiModal = function(opts){
        currentAction = opts.action || '';
        currentCsrf   = opts.csrf || '';

        sel.value = '';
        sel.style.borderColor = '';
        grid.querySelectorAll('.wadan-disposisi-tindakan-check').forEach(function(c){ c.checked = false; });
        grid.style.borderColor = 'transparent';
        grid.style.boxShadow = 'none';
        errEl.style.display = 'none';

        modal.classList.add('open');
    };

    document.getElementById('wadanDisposisiSubmit').addEventListener('click', function(){
        var tujuanId = sel.value;
        var tindakanChecked = Array.prototype.map.call(grid.querySelectorAll('.wadan-disposisi-tindakan-check:checked'), function(c){ return c.value; });
        var valid = true;

        if (!tujuanId) {
            sel.style.borderColor = 'var(--red)';
            valid = false;
        } else {
            sel.style.borderColor = '';
        }

        if (tindakanChecked.length === 0) {
            grid.style.borderColor = 'var(--red)';
            grid.style.boxShadow = '0 0 0 3px color-mix(in srgb,var(--red) 15%,transparent)';
            errEl.style.display = 'flex';
            valid = false;
        }

        if (!valid || !currentAction) return;

        var selectedOpt = sel.options[sel.selectedIndex];
        var disposisiLabel = selectedOpt ? (selectedOpt.dataset.label || selectedOpt.textContent) : '';

        var f = document.createElement('form');
        f.method = 'POST'; f.action = currentAction;
        var html = '<input name="_token" value="' + String(currentCsrf).replace(/&/g,'&amp;').replace(/"/g,'&quot;') + '">';
        html += '<input name="tujuan_satuan_id" value="' + String(tujuanId).replace(/&/g,'&amp;').replace(/"/g,'&quot;') + '">';
        html += '<input name="disposisi" value="' + String(disposisiLabel).replace(/&/g,'&amp;').replace(/"/g,'&quot;') + '">';
        tindakanChecked.forEach(function(t){
            html += '<input name="tindakan[]" value="' + String(t).replace(/&/g,'&amp;').replace(/"/g,'&quot;') + '">';
        });
        f.innerHTML = html;
        document.body.appendChild(f); f.submit();
    });
})();
</script>
@endif

<script>
window.openSuratDetail = function(button){
  var modal = document.getElementById('suratDetailModal');
  if (!modal) return;
  var wasOpen = modal.classList.contains('open');
  var prevStatusClass = document.getElementById('suratDetailStatusText').className;
  var card = button.closest('.surat-file-card');
  modal.dataset.openSuratId = card ? (card.dataset.suratId || '') : '';

  var context = button.dataset.context || 'masuk';
  var judulEl = document.getElementById('suratDetailJudul');
  var subDariEl = document.getElementById('suratDetailDari');
  if (context === 'keluar') {
    if (judulEl) judulEl.textContent = 'Detail Surat Keluar';
    if (subDariEl) subDariEl.textContent = 'Kepada ' + (button.dataset.tujuan || '-');
  } else if (context === 'arsip') {
    if (judulEl) judulEl.textContent = 'Detail Arsip Surat';
    if (subDariEl) subDariEl.textContent = button.dataset.dari ? ('Dari ' + button.dataset.dari) : ('Kepada ' + (button.dataset.tujuan || '-'));
  } else {
    if (judulEl) judulEl.textContent = 'Detail Surat Masuk';
    if (subDariEl) subDariEl.textContent = 'Dari ' + (button.dataset.dari || '-');
  }

  var tujuanValueWrap = document.getElementById('suratDetailTujuanValue');
  var tujuanHops = [];
  try { tujuanHops = JSON.parse(button.dataset.tujuanHops || '[]'); } catch(e){}
  var arrowSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';

  if (tujuanHops.length > 1) {
    // Alur diteruskan lebih dari 1 langkah -- tampilkan sebagai chip alur
    // workflow (mis. Wadan → Satlak Dukteksi) supaya satuan perantara
    // tetap kelihatan jelas, bukan teks digabung jadi satu baris.
    tujuanValueWrap.className = 'surat-detail-item-value surat-tujuan-flow';
    tujuanValueWrap.innerHTML = tujuanHops.map(function(hop, idx){
      var stepHtml = '<span class="surat-tujuan-flow-step' + (idx === tujuanHops.length - 1 ? ' is-final' : '') + '">' + escHtml(hop.nama || '-') + '</span>';
      return (idx > 0 ? '<span class="surat-tujuan-flow-arrow" aria-hidden="true">' + arrowSvg + '</span>' : '') + stepHtml;
    }).join('');
  } else {
    tujuanValueWrap.className = 'surat-detail-item-value';
    tujuanValueWrap.innerHTML = '<span id="suratDetailTujuan">-</span><span class="satuan-pill" id="suratDetailTujuanKode" style="display:none"></span>';
    tujuanValueWrap.style.display = 'flex';
    tujuanValueWrap.style.alignItems = 'center';
    tujuanValueWrap.style.gap = '8px';
    tujuanValueWrap.style.flexWrap = 'wrap';
    document.getElementById('suratDetailTujuan').textContent = button.dataset.tujuan || '-';
    var tujuanKode = document.getElementById('suratDetailTujuanKode');
    tujuanKode.textContent = button.dataset.tujuanKode || '';
    tujuanKode.style.display = button.dataset.tujuanKode ? '' : 'none';
  }
  var tujuanItem = document.getElementById('suratDetailTujuanItem');
  if (tujuanItem) tujuanItem.style.display = (button.dataset.hideTujuan === '1') ? 'none' : '';
  var wadanSimple = button.dataset.wadanSimple === '1';
  document.getElementById('suratDetailPerihal').textContent = button.dataset.perihal || '-';
  document.getElementById('suratDetailKategori').textContent = button.dataset.kategori || 'Umum';
  var prio = document.getElementById('suratDetailPrioritas');
  prio.textContent = button.dataset.prioritas || '-';
  prio.className = 'priority-tag prio-' + (button.dataset.prioritas || '').toLowerCase();
  var statusEl = document.getElementById('suratDetailStatusText');
  var sudahDikonfirmasi = button.dataset.status === 'Dikonfirmasi';
  statusEl.textContent = button.dataset.status || '-';
  statusEl.className = 'status-badge ' + (sudahDikonfirmasi ? 'status-dikonfirmasi' : (button.dataset.isSelesai === '1' ? 'status-disetujui' : 'status-menunggu'));
  var ringkasanEl = document.getElementById('suratDetailRingkasan');
  var isRingkasanRahasia = button.dataset.rahasia === '1' && !button.dataset.deskripsi;
  ringkasanEl.textContent = isRingkasanRahasia ? 'Ringkasan bersifat rahasia dan tidak ditampilkan.' : (button.dataset.deskripsi || '-');
  ringkasanEl.classList.toggle('surat-ringkasan-rahasia', isRingkasanRahasia);

  // Disposisi (kalimat) & Tindakan (kolom terpisah)
  var disposisiPanel = document.getElementById('suratDetailDisposisiPanel');
  var disposisiVal   = document.getElementById('suratDetailDisposisiVal');
  var tindakanPanel  = document.getElementById('suratDetailTindakanPanel');
  var tindakanWrap   = document.getElementById('suratDetailTindakanWrap');
  var disposisi = button.dataset.disposisi || '';
  var dariKode  = button.dataset.dariKode || '';
  var tindakan  = [];
  try { tindakan = JSON.parse(button.dataset.tindakan || '[]'); } catch(e){}

  var hideDisposisi = button.dataset.hideDisposisi === '1';
  if (disposisi && !wadanSimple && !hideDisposisi) {
    disposisiPanel.style.display = '';
    var namaPengirim = dariKode ? toTitleCase(dariKode) : 'Pengirim';
    disposisiVal.textContent = namaPengirim + ' mendisposisi kepada ' + disposisi.toLowerCase();
  } else {
    disposisiPanel.style.display = 'none';
  }

  if (tindakan.length) {
    tindakanPanel.style.display = '';
    tindakanWrap.innerHTML = '';
    tindakan.forEach(function(t){
      var tag = document.createElement('span');
      tag.className = 'status-badge status-sedang';
      tag.style.cssText = 'font-size:11px;padding:3px 10px;font-weight:500';
      tag.textContent = t;
      tindakanWrap.appendChild(tag);
    });
  } else {
    tindakanPanel.style.display = 'none';
  }

  // ── Timeline Riwayat Dinamis ──────────────────────────────────────────────
  var checkSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>';
  var timeline = document.getElementById('suratDetailTimeline');
  timeline.innerHTML = '';

  // Badge status per-langkah (step) di timeline -- supaya tiap baris riwayat
  // (Danpus buat surat, Wadan konfirmasi, Wadan teruskan ke Satuan, dst)
  // kelihatan jelas status konfirmasinya SENDIRI-SENDIRI, bukan cuma judul
  // aksi generik. Warna dibikin inline supaya nggak gantung ke class
  // .status-sedang/.status-disetujui yang belum ada definisi CSS-nya.
  function stepStatusPill(aksiKode){
    var map = {
      'BUAT_SURAT'  : { label: 'Terkirim',     bg: 'rgba(52,152,219,.12)', fg: '#2476ad', bd: 'rgba(52,152,219,.3)' },
      'SURAT_KELUAR': { label: 'Terkirim',     bg: 'rgba(52,152,219,.12)', fg: '#2476ad', bd: 'rgba(52,152,219,.3)' },
      'KONFIRMASI'  : { label: 'Dikonfirmasi', bg: 'rgba(61,186,126,.14)', fg: '#2e9e68', bd: 'rgba(61,186,126,.35)' },
      'TERUSKAN'    : { label: 'Diteruskan',   bg: 'rgba(99,102,241,.13)', fg: '#6366f1', bd: 'rgba(99,102,241,.32)' },
      'SELESAI'     : { label: 'Selesai (Final)', bg: 'rgba(61,186,126,.16)', fg: '#1f7a4f', bd: 'rgba(61,186,126,.4)' },
    };
    var s = map[aksiKode];
    if (!s) return '';
    return '<span style="display:inline-flex;align-items:center;margin-left:8px;padding:1px 8px;border-radius:999px;font-size:10px;font-weight:800;' +
      'letter-spacing:.02em;white-space:nowrap;vertical-align:middle;background:' + s.bg + ';color:' + s.fg + ';border:1px solid ' + s.bd + '">' + s.label + '</span>';
  }

  // Ikon per jenis aksi + kelas tint kartu -- supaya tiap step di alur
  // (Dibuat/Konfirmasi/Diteruskan/Selesai) kelihatan beda jenisnya sekilas
  // pandang, bukan cuma warna teks. Dipakai di kartu bernomor step-by-step.
  function stepIconAndClass(aksiKode){
    var iconDoc    = '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>';
    var iconUser   = '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>';
    var iconArrow  = '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>';
    var iconFlag   = '<path d="M5 21V4a1 1 0 0 1 1-1c3 0 3 2 6 2s3-2 6-2a1 1 0 0 1 1 1v10c0 1-3-2-6-2s-3 2-6 2"/>';
    var map = {
      'BUAT_SURAT'  : { icon: iconDoc,   cls: 'aksi-buat' },
      'SURAT_KELUAR': { icon: iconDoc,   cls: 'aksi-keluar' },
      'KONFIRMASI'  : { icon: iconUser,  cls: 'aksi-konfirmasi' },
      'TERUSKAN'    : { icon: iconArrow, cls: 'aksi-teruskan' },
      'SELESAI'     : { icon: iconFlag,  cls: 'aksi-selesai' },
    };
    return map[aksiKode] || { icon: checkSvg.replace(/<svg[^>]*>|<\/svg>/g,''), cls: '' };
  }

  // Panah kecil "↓" antar kartu step -- ganti garis polos supaya kesan alur
  // step-by-step (bukan cuma daftar riwayat) lebih kena.
  var connectorArrowSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="4" x2="12" y2="17"/><polyline points="7 13 12 18 17 13"/></svg>';
  // Panah cabang (1 masuk -> mekar ke 2+ arah) buat step yang punya paralel.
  var forkArrowSvg = '<svg viewBox="0 0 60 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="30" y1="0" x2="30" y2="7"/><path d="M30 7 L12 7 L12 15"/><path d="M30 7 L48 7 L48 15"/><polyline points="8 12 12 16 16 12"/><polyline points="44 12 48 16 52 12"/></svg>';
  // Ikon jam kecil buat baris jam/tanggal di tiap kartu step (niru gaya
  // referensi "RIWAYAT ALUR": judul, "Oleh ...", lalu jam+tanggal di baris sendiri).
  var clockSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
  // Kolom nomor besar di kiri kartu (bukan badge kecil nempel di ikon) --
  // terhubung garis vertikal ke nomor step berikutnya kalau bukan step terakhir.
  function timelineStepColHtml(stepNum, isLast){
    return '<div class="timeline-step-col"><span class="timeline-step-num">' + stepNum + '</span>' +
      (isLast ? '' : '<div class="timeline-step-line"></div>') + '</div>';
  }

  var riwayats = [];
  try { riwayats = JSON.parse(button.dataset.riwayat || '[]'); } catch(e){}

  if (riwayats.length > 0) {
    riwayats.forEach(function(r, idx){
      var isLast = idx === riwayats.length - 1;

      // ── Cek apakah step ini punya cabang paralel (kirim bersamaan ke 2+ pihak) ──
      var paralel = (r.paralel && r.paralel.length > 0) ? r.paralel : [];
      var hasParalel = paralel.length > 0;

      // ── Bangun extra info (disposisi, tindakan, catatan, lampiran) ──
      var extraHtml = '';
      if (r.disposisi) extraHtml += '<div class="surat-detail-timeline-sub" style="margin-top:2px;color:var(--primary);font-weight:500">Disposisi: ' + escHtml(r.disposisi) + '</div>';
      if (r.tindakan && r.tindakan.length) {
        extraHtml += '<div class="surat-detail-timeline-sub" style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px">';
        r.tindakan.forEach(function(t){ extraHtml += '<span style="background:var(--primary-subtle,rgba(59,130,246,.1));color:var(--primary);border-radius:4px;padding:1px 7px;font-size:11px">' + escHtml(t) + '</span>'; });
        extraHtml += '</div>';
      }
      if (r.catatan && r.aksi !== 'Surat Dibuat') {
        extraHtml += '<div class="surat-detail-timeline-sub" style="margin-top:3px;font-style:italic;color:var(--text-muted)">' + escHtml(r.catatan) + '</div>';
      }
      if (r.lampiran_url) {
        extraHtml += '<div class="surat-detail-timeline-sub" style="margin-top:4px"><a href="' + r.lampiran_url + '" target="_blank" rel="noopener" style="color:var(--primary);font-size:12px;text-decoration:underline">📎 ' + escHtml(r.lampiran_nama || 'Lampiran') + '</a></div>';
      }

      var stepNum = idx + 1;
      var iconInfo = stepIconAndClass(r.aksi_kode);

      if (hasParalel) {
        // ── MODE BRANCHING: step ini mengirim ke 2 arah bersamaan ──
        // Tampilkan sebagai kartu bernomor + cabang visual: penerima utama + penerima paralel
        var oleh = 'Oleh ' + r.pengirim + (r.siklus > 1 ? ' (Siklus ' + r.siklus + ')' : '');

        var item = document.createElement('div');
        item.className = 'surat-detail-timeline-item timeline-card surat-timeline-branch-root';
        item.innerHTML =
          timelineStepColHtml(stepNum, isLast) +
          '<div class="timeline-card-inner ' + iconInfo.cls + '">' +
            '<span class="timeline-card-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">' + iconInfo.icon + '</svg></span>' +
            '<div class="timeline-card-body">' +
              '<div class="surat-detail-timeline-title">' + escHtml(r.aksi) + stepStatusPill(r.aksi_kode) + '</div>' +
              '<div class="surat-detail-timeline-sub">' + escHtml(oleh) + '</div>' +
              '<div class="timeline-card-meta">' + clockSvg + '<span>' + escHtml(r.tanggal) + '</span></div>' +
              extraHtml +
            '</div>' +
          '</div>' +
          // Panah cabang mekar ke 2+ arah
          '<div class="timeline-branch-fork">' + forkArrowSvg + '</div>' +
          // Blok cabang 2 arah
          '<div class="surat-timeline-branch-wrap" style="display:flex;gap:8px;flex-wrap:wrap">' +
            // Cabang UTAMA (penerima_satuan) -- ikon orang, samain sama "Konfirmasi Satuan"
            (r.penerima ? (
              '<div class="surat-timeline-branch-node surat-timeline-branch-utama" style="' +
                'flex:1;min-width:120px;border:1.5px solid var(--primary);border-radius:8px;padding:7px 10px;' +
                'background:var(--primary-subtle,rgba(59,130,246,.07));position:relative' +
              '">' +
                '<div style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:var(--primary);margin-bottom:2px">' +
                  '<span style="display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;background:var(--primary);border-radius:50%;color:#fff"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>' +
                  'Penerima Utama' +
                '</div>' +
                '<div style="font-size:12px;font-weight:600;color:var(--text)">' + escHtml(r.penerima) + '</div>' +
                '<div style="font-size:10.5px;color:var(--text-muted);margin-top:1px">Perlu konfirmasi / ACC</div>' +
              '</div>'
            ) : '') +
            // Cabang PARALEL (view_only / tembusan) — satu per entry. Urdal
            // dapet ikon gear khusus (beda dari "mata"/view-only biasa) biar
            // langsung kebaca sebagai "Konfirmasi Urdal" di alur, niru style
            // referensi step 3/4 mekar dua arah.
            paralel.map(function(p){
              var isViewOnly = p.jenis === 'view_only';
              var isHasilRc  = p.jenis === 'hasil_rc';
              var isUrdal    = (p.satuan_kode || '').toUpperCase() === 'URDAL';
              var borderCol  = isViewOnly ? 'var(--border)' : (isHasilRc ? '#16a34a' : 'var(--warning,#f59e0b)');
              var bgCol      = isViewOnly ? 'var(--surface)' : (isHasilRc ? 'rgba(22,163,74,.07)' : 'rgba(245,158,11,.07)');
              var labelCol   = isViewOnly ? 'var(--text-muted)' : (isHasilRc ? '#16a34a' : 'var(--warning,#f59e0b)');
              var badgeLabel = escHtml(p.label_jenis || p.jenis);
              var nodeIcon   = isUrdal
                ? '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'
                : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
              return (
                '<div class="surat-timeline-branch-node" style="' +
                  'flex:1;min-width:120px;border:1.5px solid ' + borderCol + ';border-radius:8px;padding:7px 10px;' +
                  'background:' + bgCol + ';position:relative' +
                '">' +
                  '<div style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:' + labelCol + ';margin-bottom:2px">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px">' + nodeIcon + '</svg>' +
                    (isUrdal ? 'Urdal' : badgeLabel) +
                  '</div>' +
                  '<div style="font-size:12px;font-weight:600;color:var(--text)">' + escHtml(p.satuan) + '</div>' +
                  (p.satuan_kode ? '<div style="font-size:10px;color:var(--text-muted);margin-top:1px">' + escHtml(p.satuan_kode) + (isUrdal ? ' — Administrasi' : ' — Hanya lihat') + '</div>' : '') +
                  // Status konfirmasi tembusan/view-only INI SENDIRI (mis. Urdal
                  // sudah klik "Konfirmasi Mengetahui" atau belum) -- terpisah
                  // dari status konfirmasi penerima utama di sebelahnya.
                  (p.sudah_konfirmasi
                    ? '<div style="display:inline-flex;align-items:center;gap:3px;margin-top:5px;padding:1px 7px;border-radius:999px;font-size:9.5px;font-weight:800;background:rgba(61,186,126,.14);color:#2e9e68;border:1px solid rgba(61,186,126,.35)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:9px;height:9px"><path d="M5 13l4 4L19 7"/></svg> Dikonfirmasi' + (p.dikonfirmasi_at ? ' • ' + escHtml(p.dikonfirmasi_at) : '') + '</div>'
                    : '<div style="display:inline-flex;align-items:center;margin-top:5px;padding:1px 7px;border-radius:999px;font-size:9.5px;font-weight:800;background:rgba(224,168,58,.14);color:#c99a2e;border:1px solid rgba(224,168,58,.35)">Menunggu Konfirmasi</div>') +
                '</div>'
              );
            }).join('') +
          '</div>';

        timeline.appendChild(item);

        if (!isLast) {
          var forkEl = item.querySelector('.timeline-branch-fork');
          var lineEl = item.querySelector('.timeline-step-line');
          void item.offsetHeight;
          requestAnimationFrame(function(){requestAnimationFrame(function(){
            if (forkEl) forkEl.classList.add('line-complete');
            if (lineEl) lineEl.classList.add('line-complete');
          });});
        }

      } else {
        // ── MODE NORMAL: step tunggal bernomor (tidak ada cabang paralel) ──
        var item = document.createElement('div');
        item.className = 'surat-detail-timeline-item timeline-card';
        var oleh = 'Oleh ' + r.pengirim + (r.penerima ? ' → ' + r.penerima : '') + (r.siklus > 1 ? ' (Siklus ' + r.siklus + ')' : '');
        item.innerHTML =
          timelineStepColHtml(stepNum, isLast) +
          '<div class="timeline-card-inner ' + iconInfo.cls + '">' +
            '<span class="timeline-card-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">' + iconInfo.icon + '</svg></span>' +
            '<div class="timeline-card-body">' +
              '<div class="surat-detail-timeline-title">' + escHtml(r.aksi) + stepStatusPill(r.aksi_kode) + '</div>' +
              '<div class="surat-detail-timeline-sub">' + escHtml(oleh) + '</div>' +
              '<div class="timeline-card-meta">' + clockSvg + '<span>' + escHtml(r.tanggal) + '</span></div>' +
              extraHtml +
            '</div>' +
          '</div>';
        timeline.appendChild(item);

        if (!isLast) {
          var lineEl = item.querySelector('.timeline-step-line');
          void item.offsetHeight;
          requestAnimationFrame(function(){requestAnimationFrame(function(){ if (lineEl) lineEl.classList.add('line-complete'); });});
        }
      }
    });

    // ── Step SAAT INI (pending) ──────────────────────────────────────────
    // Baris terakhir riwayat cuma nyatet apa yang SUDAH terjadi. Supaya
    // kelihatan jelas surat ini masih "nyangkut" di satuan mana & belum
    // tuntas semua (bukan cuma alasan satu langkah tengah yang konfirmasi),
    // tambahin satu baris pending di ujung timeline kalau belum is_selesai.
    if (button.dataset.isSelesai !== '1') {
      // Tandai step terakhir yang sudah dibuat sebagai "belum akhir" supaya
      // garis vertikalnya nyambung ke baris pending ini.
      var lastLineEl = timeline.querySelector('.surat-detail-timeline-item.timeline-card:last-child .timeline-step-col');
      if (lastLineEl && !lastLineEl.querySelector('.timeline-step-line')) {
        var extraLine = document.createElement('div');
        extraLine.className = 'timeline-step-line line-complete';
        lastLineEl.appendChild(extraLine);
      }

      var tujuanSaatIni = button.dataset.tujuan || '-';
      var statusSaatIni = button.dataset.status || 'Menunggu Konfirmasi';

      var pending = document.createElement('div');
      pending.className = 'surat-detail-timeline-item timeline-card is-pending';
      pending.innerHTML =
        timelineStepColHtml(riwayats.length + 1, true) +
        '<div class="timeline-card-inner">' +
          '<span class="timeline-card-badge">' + clockSvg + '</span>' +
          '<div class="timeline-card-body">' +
            '<div class="surat-detail-timeline-title">Saat Ini: ' + escHtml(tujuanSaatIni) + stepStatusPill(statusSaatIni === 'Dikonfirmasi' ? 'KONFIRMASI' : null) + '</div>' +
            '<div class="surat-detail-timeline-sub">' + escHtml(statusSaatIni) + '<br>belum masuk Arsip sampai seluruh alur tuntas</div>' +
          '</div>' +
        '</div>';
      timeline.appendChild(pending);
    }
  } else {
    // Fallback (riwayat kosong -- mis. data lama sebelum pencatatan riwayat
    // lengkap): tetap tampilkan sebagai kartu bernomor 2 langkah
    // (Dibuat + Dikonfirmasi) biar konsisten sama gaya step-by-step di atas.
    var sudahKonfirmasi = !!button.dataset.dikonfirmasiTanggal;
    var justConfirmed   = wasOpen && prevStatusClass.indexOf('status-dikonfirmasi') === -1 && sudahKonfirmasi;
    var iconDibuat = stepIconAndClass('BUAT_SURAT');
    var iconKonf   = stepIconAndClass('KONFIRMASI');

    var dibuat = document.createElement('div');
    dibuat.className = 'surat-detail-timeline-item timeline-card';
    dibuat.innerHTML =
      timelineStepColHtml(1, false) +
      '<div class="timeline-card-inner ' + iconDibuat.cls + '">' +
        '<span class="timeline-card-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">' + iconDibuat.icon + '</svg></span>' +
        '<div class="timeline-card-body"><div class="surat-detail-timeline-title">Dibuat</div><div class="surat-detail-timeline-sub"></div><div class="timeline-card-meta"></div></div>' +
      '</div>';
    dibuat.querySelector('.surat-detail-timeline-sub').textContent = 'Oleh ' + (button.dataset.dibuatOleh || '-');
    var dibuatMeta = dibuat.querySelector('.timeline-card-meta');
    dibuatMeta.innerHTML = clockSvg;
    dibuatMeta.appendChild(document.createTextNode(button.dataset.dibuatTanggal || '-'));
    timeline.appendChild(dibuat);

    var connector = dibuat.querySelector('.timeline-step-line');

    var konfirmasi = document.createElement('div');
    konfirmasi.className = 'surat-detail-timeline-item timeline-card' + (sudahKonfirmasi ? '' : ' is-pending');
    konfirmasi.innerHTML =
      timelineStepColHtml(2, button.dataset.isSelesai === '1') +
      '<div class="timeline-card-inner' + (sudahKonfirmasi ? ' ' + iconKonf.cls : '') + '">' +
        '<span class="timeline-card-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">' + iconKonf.icon + '</svg></span>' +
        '<div class="timeline-card-body"><div class="surat-detail-timeline-title">Dikonfirmasi</div><div class="surat-detail-timeline-sub"></div><div class="timeline-card-meta"></div></div>' +
      '</div>';
    konfirmasi.querySelector('.surat-detail-timeline-sub').textContent = sudahKonfirmasi
      ? ('Oleh ' + (button.dataset.dikonfirmasiOleh || '-'))
      : 'Menunggu konfirmasi penerima';
    if (sudahKonfirmasi) {
      var konfMeta = konfirmasi.querySelector('.timeline-card-meta');
      konfMeta.innerHTML = clockSvg;
      konfMeta.appendChild(document.createTextNode(button.dataset.dikonfirmasiTanggal || '-'));
    }
    timeline.appendChild(konfirmasi);

    if (sudahKonfirmasi && connector) {
      void connector.offsetHeight;
      requestAnimationFrame(function(){requestAnimationFrame(function(){ connector.classList.add('line-complete'); });});
    }
    if (justConfirmed) {
      statusEl.classList.add('siberad-row-updated');
      konfirmasi.classList.add('siberad-row-updated');
      var justBadge = konfirmasi.querySelector('.timeline-card-badge');
      var justNum   = konfirmasi.querySelector('.timeline-step-num');
      if (justBadge) justBadge.classList.add('just-confirmed');
      if (justNum) justNum.classList.add('just-confirmed');
      if (window.siberadShowToast) window.siberadShowToast('success', 'Surat sudah dikonfirmasi.');
    }

    // ── Step 3: Saat Ini (pending) ──────────────────────────────────────
    // Mode fallback ini dulu cuma nampilin 2 langkah tetap (Dibuat +
    // Dikonfirmasi) tanpa kotak lanjutan, padahal selama surat belum
    // "selesai" (mis. Wadan masih harus meneruskan ke Satuan & Urdal),
    // seharusnya ada kotak step berikutnya yang menandakan alur masih
    // berjalan -- samain sama kotak "Saat Ini" di mode riwayat normal.
    if (button.dataset.isSelesai !== '1') {
      var tujuanSaatIni = button.dataset.tujuan || '-';
      var statusSaatIni = button.dataset.status || 'Menunggu Konfirmasi';

      var pendingFallback = document.createElement('div');
      pendingFallback.className = 'surat-detail-timeline-item timeline-card is-pending';
      pendingFallback.innerHTML =
        timelineStepColHtml(3, true) +
        '<div class="timeline-card-inner">' +
          '<span class="timeline-card-badge">' + clockSvg + '</span>' +
          '<div class="timeline-card-body">' +
            '<div class="surat-detail-timeline-title">Saat Ini: ' + escHtml(tujuanSaatIni) + stepStatusPill(statusSaatIni === 'Dikonfirmasi' ? 'KONFIRMASI' : null) + '</div>' +
            '<div class="surat-detail-timeline-sub">' + escHtml(statusSaatIni) + '<br>belum masuk Arsip sampai seluruh alur tuntas</div>' +
          '</div>' +
        '</div>';
      timeline.appendChild(pendingFallback);

      // Garis penghubung step 2 -> step 3 (hijau kalau step 2 sudah dikonfirmasi)
      var line23 = konfirmasi.querySelector('.timeline-step-line');
      if (line23 && sudahKonfirmasi) {
        void line23.offsetHeight;
        requestAnimationFrame(function(){requestAnimationFrame(function(){ line23.classList.add('line-complete'); });});
      }
    }
  }

  // ── Dokumen Lampiran ──────────────────────────────────────────────────────
  var dokWrap  = document.getElementById('suratDetailDokumenWrap');
  var dokPanel = document.getElementById('suratDetailDokumenPanel');
  dokWrap.innerHTML = '';
  if (button.dataset.lampiranUrl) {
    dokPanel.hidden = false;
    var badge = (window.siberadLampiranBadge && window.siberadLampiranBadge(button.dataset.lampiranNama)) || { text: 'FILE', cls: 'lfx-other' };
    var row = document.createElement('div');
    row.className = 'surat-detail-dokumen-row';
    var icon = document.createElement('span');
    icon.className = 'surat-detail-dokumen-icon lampiran-file-row-icon ' + badge.cls;
    icon.textContent = badge.text;
    var info = document.createElement('div');
    info.className = 'surat-detail-dokumen-info';
    var name = document.createElement('a');
    name.className = 'surat-detail-dokumen-name';
    name.target = '_blank'; name.rel = 'noopener';
    name.href = button.dataset.lampiranUrl;
    name.textContent = button.dataset.lampiranNama || 'Lampiran';
    var size = document.createElement('div');
    size.className = 'surat-detail-dokumen-size';
    size.textContent = button.dataset.lampiranSize || '';
    info.appendChild(name); info.appendChild(size);
    var dl = document.createElement('a');
    dl.className = 'surat-detail-dokumen-download';
    dl.target = '_blank'; dl.rel = 'noopener';
    dl.href = button.dataset.lampiranUrl;
    dl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg><span>Unduh</span>';
    row.appendChild(icon); row.appendChild(info); row.appendChild(dl);
    dokWrap.appendChild(row);
  } else {
    dokPanel.hidden = true;
  }

  // ── Tombol Aksi Kontekstual ───────────────────────────────────────────────
  var btnKonfirmasi          = document.getElementById('suratDetailKonfirmasi');
  var btnKonfirmasiTembusan  = document.getElementById('suratDetailKonfirmasiTembusan');
  var btnKonfirmasiWadan     = document.getElementById('suratDetailKonfirmasiWadan');
  var btnTeruskanWadan       = document.getElementById('suratDetailTeruskanWadan');
  var btnTeruskan            = document.getElementById('suratDetailTeruskan');
  var btnKeDanpus            = document.getElementById('suratDetailKeDanpus');
  var btnKeWadan             = document.getElementById('suratDetailKeWadan');
  var btnSelesai             = document.getElementById('suratDetailSelesai');
  var btnDisposisiUlang      = document.getElementById('suratDetailDisposisiUlang');
  var btnTutup               = document.getElementById('suratDetailTutup');

  // Reset semua action buttons terlebih dahulu (default: tersembunyi)
  btnKonfirmasi.hidden = true; btnKonfirmasi.onclick = null;
  btnKonfirmasiTembusan.hidden = true; btnKonfirmasiTembusan.onclick = null;
  if (btnKonfirmasiWadan) {
    btnKonfirmasiWadan.hidden = true; btnKonfirmasiWadan.onclick = null;
    btnKonfirmasiWadan.disabled = false; btnKonfirmasiWadan.style.opacity = ''; btnKonfirmasiWadan.style.cursor = '';
    btnKonfirmasiWadan.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><path d="M5 13l4 4L19 7"/></svg>Konfirmasi';
  }
  if (btnTeruskanWadan) {
    btnTeruskanWadan.hidden = true; btnTeruskanWadan.onclick = null;
    btnTeruskanWadan.disabled = true; btnTeruskanWadan.style.opacity = ''; btnTeruskanWadan.style.cursor = '';
  }
  btnTeruskan.hidden = true; btnTeruskan.onclick = null;
  btnKeDanpus.hidden = true; btnKeDanpus.onclick = null;
  if (btnKeWadan) { btnKeWadan.hidden = true; btnKeWadan.onclick = null; }
  btnSelesai.hidden = true; btnSelesai.onclick = null;
  btnDisposisiUlang.hidden = true; btnDisposisiUlang.onclick = null;
  if (btnTutup) btnTutup.hidden = false;

  // Tombol aksi HANYA muncul pada SURAT MASUK sesuai peran & kondisi surat.
  // Pada Surat Keluar dan Arsip, HANYA tombol "Tutup" (X) yang ditampilkan.
  if (context === 'masuk' && wadanSimple) {
    // ── Mode simpel Wadan: dua tombol berdampingan, "Konfirmasi" dan
    //    "Teruskan Surat".
    //    1) Klik "Konfirmasi" -> kirim AJAX (PATCH) ke endpoint konfirmasi.
    //       Modal TIDAK tertutup. Setelah sukses, tombol "Konfirmasi"
    //       jadi disable dan tombol "Teruskan Surat" jadi aktif.
    //    2) Klik "Teruskan Surat" (baru bisa diklik setelah langkah 1) ->
    //       membuka sub-modal #wadanDisposisiModal berisi form Disposisi
    //       & Tindakan, lalu submit ke endpoint teruskan.
    if (btnTutup) btnTutup.hidden = true;

    var canTeruskanWadan = button.dataset.canTeruskan === '1';
    if (btnKonfirmasiWadan && btnTeruskanWadan && canTeruskanWadan) {
      btnKonfirmasiWadan.hidden = false;
      btnTeruskanWadan.hidden = false;

      // Helper: set tampilan tombol "Teruskan Surat" aktif / non-aktif.
      function setTeruskanWadanEnabled(enabled){
        btnTeruskanWadan.disabled = !enabled;
        btnTeruskanWadan.style.opacity = enabled ? '' : '.5';
        btnTeruskanWadan.style.cursor = enabled ? '' : 'not-allowed';
      }

      // Helper: set tampilan tombol "Konfirmasi" aktif / non-aktif (dipakai
      // baik saat render awal maupun setelah sukses konfirmasi via AJAX).
      function setKonfirmasiWadanEnabled(enabled){
        btnKonfirmasiWadan.disabled = !enabled;
        btnKonfirmasiWadan.style.opacity = enabled ? '' : '.5';
        btnKonfirmasiWadan.style.cursor = enabled ? '' : 'not-allowed';
      }

      // Kondisi awal: kalau surat ini sebelumnya SUDAH dikonfirmasi (mis.
      // modal dibuka ulang / refresh realtime), Konfirmasi langsung
      // non-aktif dan Teruskan Surat langsung aktif. Kalau belum, sebaliknya.
      var sudahKonfirmasiWadanAwal = button.dataset.sudahDikonfirmasiWadan === '1';
      setKonfirmasiWadanEnabled(!sudahKonfirmasiWadanAwal);
      setTeruskanWadanEnabled(sudahKonfirmasiWadanAwal);

      btnKonfirmasiWadan.onclick = function(){
        if (btnKonfirmasiWadan.disabled) return;
        var actionUrl = button.dataset.konfirmasiWadanAction || button.dataset.confirmAction;
        if (!actionUrl) return;

        setKonfirmasiWadanEnabled(false);
        var originalHtml = btnKonfirmasiWadan.innerHTML;
        btnKonfirmasiWadan.textContent = 'Memproses...';

        fetch(actionUrl, {
          method: 'PATCH',
          credentials: 'same-origin',
          headers: {
            'X-CSRF-TOKEN': button.dataset.csrf || '',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        }).then(function(res){
          if (!res.ok) throw new Error('Gagal konfirmasi');
          return res.json().catch(function(){ return {}; });
        }).then(function(data){
          // Sukses: tandai di dataset supaya render ulang modal (tanpa
          // menutupnya) langsung menampilkan Konfirmasi non-aktif &
          // Teruskan Surat aktif, plus timeline & badge status terbaru.
          button.dataset.sudahDikonfirmasiWadan = '1';
          button.dataset.status = 'Dikonfirmasi';
          button.dataset.dikonfirmasiOleh = (data && data.dikonfirmasi_oleh) || button.dataset.tujuan || '-';
          button.dataset.dikonfirmasiTanggal = (data && data.dikonfirmasi_tanggal) || '';

          window.openSuratDetail(button);

          if (window.siberadShowToast) window.siberadShowToast('success', 'Surat berhasil dikonfirmasi. Silakan lanjutkan dengan "Teruskan Surat".');
        }).catch(function(){
          btnKonfirmasiWadan.innerHTML = originalHtml;
          setKonfirmasiWadanEnabled(true);
          if (window.siberadShowToast) window.siberadShowToast('error', 'Gagal mengkonfirmasi surat. Coba lagi.');
        });
      };

      btnTeruskanWadan.onclick = function(){
        if (btnTeruskanWadan.disabled) return;
        if (typeof window.bukaWadanDisposisiModal === 'function') {
          window.bukaWadanDisposisiModal({
            action : button.dataset.teruskanAction,
            csrf   : button.dataset.csrf || ''
          });
        }
      };
    }
  } else if (context === 'masuk') {
    var csrf = button.dataset.csrf || '';

    // 1. Konfirmasi penerima utama (Surat baru masuk, belum di-ACC)
    if (button.dataset.canConfirm === '1') {
      btnKonfirmasi.hidden = false;
      btnKonfirmasi.onclick = function(){
        if (typeof window.bukaKonfirmasiSurat === 'function') {
          window.bukaKonfirmasiSurat(button.dataset.confirmAction, button.dataset.confirmToken, button.dataset.dari);
        } else {
          var f = document.createElement('form');
          f.method = 'POST'; f.action = button.dataset.confirmAction;
          f.innerHTML = '<input name="_token" value="' + (button.dataset.confirmToken || csrf) + '"><input name="_method" value="PATCH">';
          document.body.appendChild(f); f.submit();
        }
      };
    }

    // 2. Konfirmasi tembusan / view only (Urdal / penerima tembusan)
    if (button.dataset.canConfirmTembusan === '1') {
      btnKonfirmasiTembusan.hidden = false;
      btnKonfirmasiTembusan.onclick = function(){
        var f = document.createElement('form');
        f.method = 'POST'; f.action = button.dataset.confirmTembusanAction;
        f.innerHTML = '<input name="_token" value="' + csrf + '"><input name="_method" value="PATCH">';
        document.body.appendChild(f); f.submit();
      };
    }

    // 3. Disposisi & Teruskan (Wadan → Satrap)
    if (button.dataset.canTeruskan === '1') {
      btnTeruskan.hidden = false;
      btnTeruskan.onclick = function(){
        if (typeof window.bukaSuratTeruskanModal === 'function') {
          window.bukaSuratTeruskanModal({
            action   : button.dataset.teruskanAction,
            method   : 'POST',
            title    : 'Disposisi & Teruskan Surat',
            sub      : 'Pilih satuan tujuan, disposisi, dan tindakan yang harus dilaksanakan.',
            btnLabel : 'Teruskan Surat',
          });
        }
      };
    }

    // 4. Kembalikan ke Danpus (Wadan setelah menerima laporan hasil Satrap)
    if (button.dataset.canKeDanpus === '1') {
      btnKeDanpus.hidden = false;
      btnKeDanpus.onclick = function(){
        if (confirm('Teruskan surat ini kembali ke Danpus untuk keputusan akhir?')) {
          var f = document.createElement('form');
          f.method = 'POST'; f.action = button.dataset.keDanpusAction;
          f.innerHTML = '<input name="_token" value="' + csrf + '">';
          document.body.appendChild(f); f.submit();
        }
      };
    }

    // 4b. Teruskan ke Wadan (Urdal setelah cek & konfirmasi Surat Keluar Satlak)
    if (btnKeWadan && button.dataset.canKeWadan === '1') {
      btnKeWadan.hidden = false;
      btnKeWadan.onclick = function(){
        if (confirm('Teruskan surat ini ke Wadan?')) {
          var f = document.createElement('form');
          f.method = 'POST'; f.action = button.dataset.keWadanAction;
          f.innerHTML = '<input name="_token" value="' + csrf + '">';
          document.body.appendChild(f); f.submit();
        }
      };
    }

    // 5. Selesai (Danpus - Surat selesai & RC ke Urdal)
    if (button.dataset.canSelesai === '1') {
      btnSelesai.hidden = false;
      btnSelesai.onclick = function(){
        if (confirm('Konfirmasi surat ini sebagai SELESAI? Hasil / RC akan otomatis diteruskan ke Urdal.')) {
          var f = document.createElement('form');
          f.method = 'POST'; f.action = button.dataset.selesaiAction;
          f.innerHTML = '<input name="_token" value="' + csrf + '">';
          document.body.appendChild(f); f.submit();
        }
      };
    }

    // 6. Disposisi Ulang (Danpus - siklus baru)
    if (button.dataset.canDisposisiUlang === '1') {
      btnDisposisiUlang.hidden = false;
      btnDisposisiUlang.onclick = function(){
        if (typeof window.bukaSuratTeruskanModal === 'function') {
          window.bukaSuratTeruskanModal({
            action   : button.dataset.disposisiUlangAction,
            method   : 'POST',
            title    : 'Tindakan / Disposisi Baru',
            sub      : 'Buat siklus disposisi baru. Riwayat siklus sebelumnya tetap tersimpan.',
            btnLabel : 'Kirim Disposisi Baru',
          });
        }
      };
    }
  }

  modal.dataset.openSuratSig = button.outerHTML.replace(/>\s+</g,'><').trim();
  void modal.offsetHeight;
  modal.classList.add('open');
};

// Helper: escape HTML
function escHtml(str){
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Helper: "DANPUS" / "DANSATLAK KALSI" -> "Danpus" / "Dansatlak Kalsi"
function toTitleCase(str){
  return String(str).toLowerCase().replace(/(^|\s)\S/g, function(c){ return c.toUpperCase(); });
}

// Selagi modal Detail Surat lagi kebuka, poll realtime manggil ini tiap habis sync
window.siberadRefreshSuratDetailIfOpen = function(){
  var modal = document.getElementById('suratDetailModal');
  if (!modal || !modal.classList.contains('open')) return;
  var id = modal.dataset.openSuratId;
  if (!id) return;
  var card = document.querySelector('.surat-file-card[data-surat-id="' + id + '"]:not(.siberad-card-leaving)');
  var btn  = card ? card.querySelector('.surat-file-card-btn') : null;
  if (!btn) return;
  var sig = btn.outerHTML.replace(/>\s+</g,'><').trim();
  if (sig === modal.dataset.openSuratSig) return;
  window.openSuratDetail(btn);
};

(function(){
  var modal = document.getElementById('suratDetailModal');
  if (!modal) return;
  function close(){ modal.classList.remove('open'); modal.dataset.openSuratId = ''; modal.dataset.openSuratSig = ''; }
  document.getElementById('suratDetailClose')?.addEventListener('click', close);
  document.getElementById('suratDetailTutup')?.addEventListener('click', close);
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && modal.classList.contains('open')) close();
  });
})();
</script>
