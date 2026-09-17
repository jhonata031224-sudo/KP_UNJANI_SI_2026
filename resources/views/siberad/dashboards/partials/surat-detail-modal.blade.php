<div class="report-modal" id="suratDetailModal"><div class="report-modal-card"><div class="report-modal-head"><div style="min-width:0"><h3 id="suratDetailJudul" style="margin:0 0 4px">Detail Surat</h3><p id="suratDetailDari" style="margin:0;font-size:12px;color:var(--text-muted)">-</p></div><button type="button" class="btn-icon-close" id="suratDetailClose" aria-label="Tutup" style="margin-left:auto"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div><div class="surat-detail-body"><div class="surat-detail-col surat-detail-col-left"><div class="surat-detail-item" id="suratDetailTujuanItem"><div><div class="surat-detail-item-label">Tujuan</div><div class="surat-detail-item-value" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap"><span id="suratDetailTujuan">-</span><span class="satuan-pill" id="suratDetailTujuanKode" style="display:none"></span></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Perihal</div><div class="surat-detail-item-value" id="suratDetailPerihal">-</div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Kategori</div><div class="surat-detail-item-value" id="suratDetailKategori">-</div></div></div><div class="surat-detail-item-row"><div class="surat-detail-item"><div><div class="surat-detail-item-label">Prioritas</div><div class="surat-detail-item-value"><span class="priority-tag" id="suratDetailPrioritas">-</span></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Status</div><div class="surat-detail-item-value"><span class="status-badge" id="suratDetailStatusText">-</span></div></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Ringkasan</div><div class="surat-detail-item-value" id="suratDetailRingkasan">-</div></div></div>
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
    {{-- Selesai (Danpus) --}}
    <button type="button" class="btn btn-success" id="suratDetailSelesai" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><path d="M5 13l4 4L19 7"/></svg>Konfirmasi Selesai
    </button>
    {{-- Disposisi Ulang (Danpus) --}}
    <button type="button" class="btn btn-warning" id="suratDetailDisposisiUlang" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>Tindakan / Disposisi Baru
    </button>
    {{-- Konfirmasi Wadan (mode simpel): klik membuka sub-modal #wadanDisposisiModal
         berisi form Disposisi & Tindakan + tombol "Disposisi & Teruskan". --}}
    <button type="button" class="btn btn-secondary" id="suratDetailKonfirmasiWadan" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><path d="M5 13l4 4L19 7"/></svg>Konfirmasi
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

  document.getElementById('suratDetailTujuan').textContent = button.dataset.tujuan || '-';
  var tujuanKode = document.getElementById('suratDetailTujuanKode');
  tujuanKode.textContent = button.dataset.tujuanKode || '';
  tujuanKode.style.display = button.dataset.tujuanKode ? '' : 'none';
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

      if (hasParalel) {
        // ── MODE BRANCHING: step ini mengirim ke 2 arah bersamaan ──
        // Tampilkan sebagai blok dengan cabang visual: penerima utama + penerima paralel
        var checkSvgSmall = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:10px;height:10px"><path d="M5 13l4 4L19 7"/></svg>';

        var meta = r.pengirim + ' • ' + r.tanggal;
        if (r.siklus > 1) meta += ' (Siklus ' + r.siklus + ')';

        // Header step utama (pengirim & aksi)
        var item = document.createElement('div');
        item.className = 'surat-detail-timeline-item surat-timeline-branch-root';
        item.innerHTML =
          '<span class="surat-detail-timeline-dot">' + checkSvg + '</span>' +
          '<div class="surat-detail-timeline-title">' + escHtml(r.aksi) + stepStatusPill(r.aksi_kode) + '</div>' +
          '<div class="surat-detail-timeline-sub">' + escHtml(meta) + '</div>' +
          extraHtml +
          // Label "Dikirim bersamaan ke:"
          '<div style="margin-top:10px;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">Dikirim bersamaan ke:</div>' +
          // Blok cabang 2 arah
          '<div class="surat-timeline-branch-wrap" style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap">' +
            // Cabang UTAMA (penerima_satuan)
            (r.penerima ? (
              '<div class="surat-timeline-branch-node surat-timeline-branch-utama" style="' +
                'flex:1;min-width:120px;border:1.5px solid var(--primary);border-radius:8px;padding:7px 10px;' +
                'background:var(--primary-subtle,rgba(59,130,246,.07));position:relative' +
              '">' +
                '<div style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:var(--primary);margin-bottom:2px">' +
                  '<span style="display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;background:var(--primary);border-radius:50%;color:#fff">' + checkSvgSmall + '</span>' +
                  'Penerima Utama' +
                '</div>' +
                '<div style="font-size:12px;font-weight:600;color:var(--text)">' + escHtml(r.penerima) + '</div>' +
                '<div style="font-size:10.5px;color:var(--text-muted);margin-top:1px">Perlu konfirmasi / ACC</div>' +
              '</div>'
            ) : '') +
            // Cabang PARALEL (view_only / tembusan) — satu per entry
            paralel.map(function(p){
              var isViewOnly = p.jenis === 'view_only';
              var isHasilRc  = p.jenis === 'hasil_rc';
              var borderCol  = isViewOnly ? 'var(--border)' : (isHasilRc ? '#16a34a' : 'var(--warning,#f59e0b)');
              var bgCol      = isViewOnly ? 'var(--surface)' : (isHasilRc ? 'rgba(22,163,74,.07)' : 'rgba(245,158,11,.07)');
              var labelCol   = isViewOnly ? 'var(--text-muted)' : (isHasilRc ? '#16a34a' : 'var(--warning,#f59e0b)');
              var badgeLabel = escHtml(p.label_jenis || p.jenis);
              return (
                '<div class="surat-timeline-branch-node" style="' +
                  'flex:1;min-width:120px;border:1.5px solid ' + borderCol + ';border-radius:8px;padding:7px 10px;' +
                  'background:' + bgCol + ';position:relative' +
                '">' +
                  '<div style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:' + labelCol + ';margin-bottom:2px">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>' +
                    badgeLabel +
                  '</div>' +
                  '<div style="font-size:12px;font-weight:600;color:var(--text)">' + escHtml(p.satuan) + '</div>' +
                  (p.satuan_kode ? '<div style="font-size:10px;color:var(--text-muted);margin-top:1px">' + escHtml(p.satuan_kode) + ' — Hanya lihat</div>' : '') +
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
          void item.offsetHeight;
          requestAnimationFrame(function(){requestAnimationFrame(function(){ item.classList.add('line-complete'); });});
        }

      } else {
        // ── MODE NORMAL: step tunggal (tidak ada cabang paralel) ──
        var item = document.createElement('div');
        item.className = 'surat-detail-timeline-item';
        var dotHtml = '<span class="surat-detail-timeline-dot">' + checkSvg + '</span>';
        var meta = r.pengirim + (r.penerima ? ' → ' + r.penerima : '') + ' • ' + r.tanggal;
        if (r.siklus > 1) meta += ' (Siklus ' + r.siklus + ')';
        item.innerHTML = dotHtml + '<div class="surat-detail-timeline-title">' + escHtml(r.aksi) + stepStatusPill(r.aksi_kode) + '</div><div class="surat-detail-timeline-sub">' + escHtml(meta) + '</div>' + extraHtml;
        timeline.appendChild(item);

        if (!isLast) {
          void item.offsetHeight;
          requestAnimationFrame(function(){requestAnimationFrame(function(){ item.classList.add('line-complete'); });});
        }
      }
    });

    // ── Step SAAT INI (pending) ──────────────────────────────────────────
    // Baris terakhir riwayat cuma nyatet apa yang SUDAH terjadi. Supaya
    // kelihatan jelas surat ini masih "nyangkut" di satuan mana & belum
    // tuntas semua (bukan cuma alasan satu langkah tengah yang konfirmasi),
    // tambahin satu baris pending di ujung timeline kalau belum is_selesai.
    if (button.dataset.isSelesai !== '1') {
      var tujuanSaatIni = button.dataset.tujuan || '-';
      var statusSaatIni = button.dataset.status || 'Menunggu Konfirmasi';
      var pending = document.createElement('div');
      pending.className = 'surat-detail-timeline-item is-pending';
      pending.innerHTML =
        '<span class="surat-detail-timeline-dot"></span>' +
        '<div class="surat-detail-timeline-title">Saat Ini: ' + escHtml(tujuanSaatIni) + stepStatusPill(statusSaatIni === 'Dikonfirmasi' ? 'KONFIRMASI' : null) + '</div>' +
        '<div class="surat-detail-timeline-sub">' + escHtml(statusSaatIni) + ' — belum masuk Arsip sampai seluruh alur tuntas</div>';
      timeline.appendChild(pending);
    }
  } else {
    // Fallback: tampilkan timeline 2 langkah lama (Dibuat + Dikonfirmasi)
    var sudahKonfirmasi = !!button.dataset.dikonfirmasiTanggal;
    var justConfirmed   = wasOpen && prevStatusClass.indexOf('status-dikonfirmasi') === -1 && sudahKonfirmasi;

    var dibuat = document.createElement('div');
    dibuat.className = 'surat-detail-timeline-item';
    dibuat.innerHTML = '<span class="surat-detail-timeline-dot">' + checkSvg + '</span><div class="surat-detail-timeline-title">Dibuat</div><div class="surat-detail-timeline-sub"></div>';
    dibuat.querySelector('.surat-detail-timeline-sub').textContent = 'Oleh ' + (button.dataset.dibuatOleh || '-') + ' • ' + (button.dataset.dibuatTanggal || '-');
    timeline.appendChild(dibuat);

    var konfirmasi = document.createElement('div');
    konfirmasi.className = 'surat-detail-timeline-item' + (sudahKonfirmasi ? '' : ' is-pending');
    konfirmasi.innerHTML = '<span class="surat-detail-timeline-dot">' + (sudahKonfirmasi ? checkSvg : '') + '</span><div class="surat-detail-timeline-title">Dikonfirmasi</div><div class="surat-detail-timeline-sub"></div>';
    konfirmasi.querySelector('.surat-detail-timeline-sub').textContent = sudahKonfirmasi
      ? ('Oleh ' + (button.dataset.dikonfirmasiOleh || '-') + ' • ' + button.dataset.dikonfirmasiTanggal)
      : 'Menunggu konfirmasi penerima';
    timeline.appendChild(konfirmasi);

    if (sudahKonfirmasi) {
      void dibuat.offsetHeight;
      requestAnimationFrame(function(){requestAnimationFrame(function(){ dibuat.classList.add('line-complete'); });});
    }
    if (justConfirmed) {
      statusEl.classList.add('siberad-row-updated');
      konfirmasi.classList.add('siberad-row-updated');
      var justDot = konfirmasi.querySelector('.surat-detail-timeline-dot');
      if (justDot) justDot.classList.add('just-confirmed');
      if (window.siberadShowToast) window.siberadShowToast('success', 'Surat sudah dikonfirmasi.');
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
  var btnTeruskan            = document.getElementById('suratDetailTeruskan');
  var btnKeDanpus            = document.getElementById('suratDetailKeDanpus');
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
  btnTeruskan.hidden = true; btnTeruskan.onclick = null;
  btnKeDanpus.hidden = true; btnKeDanpus.onclick = null;
  btnSelesai.hidden = true; btnSelesai.onclick = null;
  btnDisposisiUlang.hidden = true; btnDisposisiUlang.onclick = null;
  if (btnTutup) btnTutup.hidden = false;

  // Tombol aksi HANYA muncul pada SURAT MASUK sesuai peran & kondisi surat.
  // Pada Surat Keluar dan Arsip, HANYA tombol "Tutup" (X) yang ditampilkan.
  if (context === 'masuk' && wadanSimple) {
    // ── Mode simpel Wadan: satu-satunya aksi di modal utama adalah tombol
    //    "Konfirmasi". Klik tombol ini TIDAK langsung menyimpan apa pun --
    //    ia cuma membuka sub-modal #wadanDisposisiModal berisi form
    //    Disposisi & Tindakan. Konfirmasi + Teruskan baru benar-benar
    //    tersimpan saat Wadan submit "Disposisi & Teruskan" di sub-modal
    //    tsb (backend otomatis menandai surat terkonfirmasi lalu
    //    meneruskannya sekaligus).
    if (btnTutup) btnTutup.hidden = true;

    var canTeruskanWadan = button.dataset.canTeruskan === '1';
    if (btnKonfirmasiWadan && canTeruskanWadan) {
      btnKonfirmasiWadan.hidden = false;
      btnKonfirmasiWadan.onclick = function(){
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
