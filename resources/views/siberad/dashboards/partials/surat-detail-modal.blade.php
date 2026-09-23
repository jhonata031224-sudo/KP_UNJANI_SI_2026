{{-- Wrapper #suratDetailModal .report-modal-card jadi non-scroll (overflow:hidden,
     lihat surat-card-styles.blade.php) supaya sudut membulat tidak terpotong scrollbar
     bawaan browser -- scroll asli dipindah ke .surat-detail-scroll di dalamnya. --}}
<div class="report-modal" id="suratDetailModal"><div class="report-modal-card"><div class="surat-detail-scroll"><div class="report-modal-head"><div style="min-width:0"><h3 id="suratDetailJudul" style="margin:0 0 4px">Detail Surat</h3><p id="suratDetailDari" style="margin:0;font-size:12px;color:var(--text-muted)">-</p></div><button type="button" class="btn-icon-close" id="suratDetailClose" aria-label="Tutup" style="margin-left:auto"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div><div class="surat-detail-body"><div class="surat-detail-col surat-detail-col-left"><div class="surat-detail-item" id="suratDetailTujuanItem"><div><div class="surat-detail-item-label">Tujuan</div><div class="surat-detail-item-value" id="suratDetailTujuanValue" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap"><span id="suratDetailTujuan">-</span><span class="satuan-pill" id="suratDetailTujuanKode" style="display:none"></span></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Perihal</div><div class="surat-detail-item-value" id="suratDetailPerihal">-</div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Kategori</div><div class="surat-detail-item-value" id="suratDetailKategori">-</div></div></div><div class="surat-detail-item-row"><div class="surat-detail-item"><div><div class="surat-detail-item-label">Jenis Surat</div><div class="surat-detail-item-value"><span class="priority-tag" id="suratDetailPrioritas">-</span></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Status</div><div class="surat-detail-item-value"><span class="status-badge" id="suratDetailStatusText">-</span></div></div></div></div><div class="surat-detail-item" id="suratDetailDeadlineItem" style="display:none"><div><div class="surat-detail-item-label" style="display:flex;align-items:center;gap:5px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;flex-shrink:0;color:#f59e0b"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>Tenggat Waktu (Kilat)</div><div class="surat-detail-item-value" id="suratDetailDeadlineValue" style="font-weight:700;color:#f59e0b">-</div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Ringkasan</div><div class="surat-detail-item-value" id="suratDetailRingkasan">-</div></div></div>
{{-- Tindakan: kolom terpisah, instruksi konkret dari pemberi disposisi --}}
<div class="surat-detail-item" id="suratDetailTindakanPanel" style="display:none"><div><div class="surat-detail-item-label">Tindakan</div><div id="suratDetailTindakanWrap" style="display:flex;flex-wrap:wrap;gap:2px 16px;margin-top:0"></div></div></div>
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
</div></div></div></div>

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

{{-- ═══════════════════════════════════════════════════
     POPUP KONFIRMASI: Teruskan surat (alur naik) ke Danpus.
     Pengganti confirm() bawaan browser. Gaya .confirm-overlay/.confirm-box
     sudah global (dash-styles), z-index-nya di atas #suratDetailModal.
     ═══════════════════════════════════════════════════ --}}
<div class="confirm-overlay" id="keDanpusConfirmOverlay">
    <div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="keDanpusConfirmTitle" aria-describedby="keDanpusConfirmBody">
        <div class="confirm-icon" style="background:rgba(245,158,11,.14);color:#d98a0b">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </div>
        <h3 id="keDanpusConfirmTitle">Teruskan ke Danpus?</h3>
        <p id="keDanpusConfirmBody">Surat <strong id="keDanpusConfirmPerihal">ini</strong> akan diteruskan kembali ke Danpus untuk keputusan akhir.</p>
        <div class="confirm-actions">
            <button type="button" class="btn" id="keDanpusConfirmBatal">Batal</button>
            <button type="button" class="btn btn-primary" id="keDanpusConfirmYa">Ya, Teruskan</button>
        </div>
    </div>
</div>
<script>
(function(){
    var overlay = document.getElementById('keDanpusConfirmOverlay');
    if (!overlay) return;
    var btnYa    = document.getElementById('keDanpusConfirmYa');
    var btnBatal = document.getElementById('keDanpusConfirmBatal');
    var perihalEl = document.getElementById('keDanpusConfirmPerihal');
    var ya = 'Ya, Teruskan';
    var pending = null;

    function tutup(){ overlay.classList.remove('open'); pending = null; }

    // Dipanggil dari surat-detail-modal (tombol "Teruskan ke Danpus", Wadan).
    window.bukaKonfirmasiKeDanpus = function(opts){
        if (!opts || !opts.action) return;
        pending = { action: opts.action, csrf: opts.csrf || '' };
        perihalEl.textContent = opts.perihal ? '\u201C' + opts.perihal + '\u201D' : 'ini';
        btnYa.disabled = false; btnBatal.disabled = false; btnYa.textContent = ya;
        overlay.classList.add('open');
        setTimeout(function(){ btnYa.focus(); }, 30);
    };

    btnBatal.addEventListener('click', tutup);
    overlay.addEventListener('click', function(e){ if (e.target === overlay) tutup(); });

    // Esc cukup menutup popup ini -- jangan ikut menutup modal Detail Surat di
    // belakangnya (listener Esc modal itu ada di fase bubble; ini fase capture).
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && overlay.classList.contains('open')) {
            e.stopImmediatePropagation();
            tutup();
        }
    }, true);

    btnYa.addEventListener('click', function(){
        if (!pending || btnYa.disabled) return;
        // Cegah klik ganda selagi form dikirim.
        btnYa.disabled = true; btnBatal.disabled = true; btnYa.textContent = 'Meneruskan\u2026';
        var f = document.createElement('form');
        f.method = 'POST'; f.action = pending.action;
        var t = document.createElement('input');
        t.type = 'hidden'; t.name = '_token'; t.value = pending.csrf;
        f.appendChild(t);
        document.body.appendChild(f); f.submit();
    });
})();
</script>
@endif

<script>
// Teruskan surat (alur naik) ke Danpus: pakai popup konfirmasi custom; kalau
// popup tidak ada di halaman (mis. dashboard non-Wadan) jatuh ke confirm() bawaan.
window.suratKonfirmasiKeDanpus = function(action, csrf, perihal){
  if (!action) return;
  if (typeof window.bukaKonfirmasiKeDanpus === 'function') {
    window.bukaKonfirmasiKeDanpus({ action: action, csrf: csrf, perihal: perihal });
    return;
  }
  if (confirm('Teruskan surat ini kembali ke Danpus untuk keputusan akhir?')) {
    var f = document.createElement('form');
    f.method = 'POST'; f.action = action;
    var t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = csrf || '';
    f.appendChild(t); document.body.appendChild(f); f.submit();
  }
};
</script>

<script>
window.openSuratDetail = function(button){
  var modal = document.getElementById('suratDetailModal');
  if (!modal) return;
  var wasOpen = modal.classList.contains('open');
  var prevStatusClass = document.getElementById('suratDetailStatusText').className;
  var card = button.closest('.surat-file-card');
  modal.dataset.openSuratId = card ? (card.dataset.suratId || '') : '';
  // Surat yang SAMA bisa muncul di dua grid sekaligus (mis. Danpus: di
  // "Surat Keluar" sebagai pengirim awal DAN di "Surat Masuk" sebagai
  // balasan naik dari satuan). Simpan grid asal kartu yang diklik supaya
  // refresh realtime tidak salah ambil kartu di grid lain (yang bikin modal
  // "Surat Masuk" berubah sendiri jadi "Surat Keluar").
  var asalGrid = card ? card.closest('[id$="Grid"]') : null;
  modal.dataset.openSuratGrid = asalGrid ? asalGrid.id : '';

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
  var alurHops = tujuanHops;
  if (!alurHops.length) { try { alurHops = JSON.parse(button.dataset.alurHops || '[]'); } catch(e){ alurHops = []; } }
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

  // Tenggat waktu: hanya tampil jika surat prioritas Kilat dan ada deadline_at
  var deadlineItem = document.getElementById('suratDetailDeadlineItem');
  var deadlineVal  = document.getElementById('suratDetailDeadlineValue');
  var deadlineStr  = button.dataset.deadline || '';
  if (deadlineItem && deadlineVal) {
    if (deadlineStr && (button.dataset.prioritas || '').toLowerCase() === 'kilat') {
      deadlineVal.textContent = deadlineStr;
      deadlineItem.style.display = '';
    } else {
      deadlineItem.style.display = 'none';
    }
  }

  // Tindakan (kolom terpisah). Keterangan Disposisi TIDAK ditampilkan di panel
  // kiri -- sudah tercakup di Riwayat Alur (step Diteruskan ke / Disposisi).
  var tindakanPanel  = document.getElementById('suratDetailTindakanPanel');
  var tindakanWrap   = document.getElementById('suratDetailTindakanWrap');
  var tindakan  = [];
  try { tindakan = JSON.parse(button.dataset.tindakan || '[]'); } catch(e){}

  if (tindakan.length) {
    tindakanPanel.style.display = '';
    tindakanWrap.innerHTML = '';
    tindakan.forEach(function(t){
      var tag = document.createElement('span');
      tag.className = 'status-badge status-sedang';
      tag.style.cssText = 'font-size:11px;padding:2px 0;font-weight:500';
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
  // URL lampiran balasan yang sudah tampil di Riwayat Alur (fase naik) --
  // dipakai untuk menyembunyikan panel "Dokumen" supaya tidak dobel.
  var lampiranDiRiwayat = [];

  if (riwayats.length > 0) {
    // ══ ALUR BERBASIS "PEMEGANG SURAT" ═══════════════════════════════════
    // Riwayat mentah (Buat, Konfirmasi, Teruskan, ...) diringkas jadi langkah
    // alur yang dilihat manusia, MURNI dari catatan riwayat -- bukan dari
    // status surat saat ini. Jadi ketika Wadan meneruskan ke satuan (status
    // surat kembali "menunggu" untuk satuan baru), step yang sudah selesai
    // TIDAK ikut ter-reset:
    //   1. Dibuat          (mis. Danpus -> Wadan)
    //   2. Konfirmasi Wadan (+ info diteruskan ke satuan pilihan Wadan)
    //   3. Konfirmasi Satuan (mis. Satlak Dukteksi) ... dst kalau alur lanjut.
    var isSelesaiSurat = button.dataset.isSelesai === '1';

    // Riwayat dipecah jadi FASE terpisah supaya alur turun & naik tidak
    // menumpuk jadi satu timeline panjang (>3 step):
    //   - TURUN : Dibuat (Danpus) -> Konfirmasi Wadan -> Konfirmasi Satuan
    //   - NAIK  : Balasan dikirim Satuan -> Konfirmasi Wadan -> Konfirmasi Danpus
    // Fase NAIK selalu diawali riwayat SURAT_KELUAR (balasan satuan). Disposisi
    // ulang Danpus (siklus baru) memulai fase TURUN baru. Tiap fase punya
    // penomoran step sendiri mulai dari 1.
    var groups = [];
    var grupAktif = null;
    var pemegangAktif = null;
    var siklusTerakhir = null;

    function mulaiGrup(fase){
      pemegangAktif = null;
      // Grup yang masih kosong dipakai ulang (jangan sampai ada fase kosong).
      if (grupAktif && grupAktif.langkah.length === 0) { grupAktif.fase = fase; return; }
      grupAktif = { fase: fase, langkah: [] };
      groups.push(grupAktif);
    }
    function tambahPemegang(r){
      pemegangAktif = { tipe: 'pemegang', nama: r.penerima, masuk: r, konfirmasi: null, diteruskan: null, dibalas: false, selesai: false };
      grupAktif.langkah.push(pemegangAktif);
    }

    riwayats.forEach(function(r){
      var kode = r.aksi_kode;
      var siklusR = r.siklus || 1;
      if (!grupAktif) mulaiGrup('turun');

      if (kode === 'BUAT_SURAT') {
        mulaiGrup('turun');
        grupAktif.langkah.push({ tipe: 'buat', r: r });
        if (r.penerima) tambahPemegang(r);
      } else if (kode === 'SURAT_KELUAR') {
        // Satuan membalas: step satuan di fase turun cukup berhenti di
        // "Dikonfirmasi"; pengiriman balasannya jadi step 1 fase NAIK.
        if (pemegangAktif && !pemegangAktif.diteruskan) pemegangAktif.dibalas = true;
        mulaiGrup('naik');
        grupAktif.langkah.push({ tipe: 'balasan', r: r });
        if (r.penerima) tambahPemegang(r);
      } else if (kode === 'KONFIRMASI') {
        if (pemegangAktif && !pemegangAktif.konfirmasi) pemegangAktif.konfirmasi = r;
      } else if (kode === 'TERUSKAN') {
        // Pemegang sebelumnya menyerahkan surat -> otomatis dianggap sudah menerima.
        if (pemegangAktif && !pemegangAktif.diteruskan) pemegangAktif.diteruskan = r;
        var disposisiUlang = grupAktif.fase === 'naik' && siklusTerakhir !== null && siklusR > siklusTerakhir;
        if (disposisiUlang) mulaiGrup('turun'); // Danpus mendisposisi ulang -> fase turun baru
        pemegangAktif = null;
        if (r.penerima) tambahPemegang(r);
      } else if (kode === 'SELESAI') {
        if (pemegangAktif) pemegangAktif.selesai = true;
        grupAktif.langkah.push({ tipe: 'selesai', r: r });
        pemegangAktif = null;
      }
      siklusTerakhir = siklusR;
    });

    // Surat baru dari pengirim awal ke Wadan: satuan tujuan akhir BELUM
    // ditentukan (Wadan yang memilih saat meneruskan) -> tampilkan sebagai
    // step berikutnya yang menunggu, supaya alurnya kelihatan utuh sejak awal.
    if (!isSelesaiSurat && grupAktif && grupAktif.fase === 'turun' && pemegangAktif && !pemegangAktif.diteruskan
        && pemegangAktif.masuk && pemegangAktif.masuk.aksi_kode === 'BUAT_SURAT'
        && /wadan/i.test(pemegangAktif.nama || '')) {
      grupAktif.langkah.push({ tipe: 'placeholder' });
    }

    // Judul fase -- hanya dirender kalau surat punya lebih dari 1 fase.
    var arrowDownSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>';
    var arrowUpSvg   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>';
    function faseHeaderEl(fase, nomorFase){
      var naik = fase === 'naik';
      var h = document.createElement('div');
      h.className = 'timeline-phase-head ' + (naik ? 'is-naik' : 'is-turun');
      h.innerHTML =
        '<span class="timeline-phase-icon">' + (naik ? arrowUpSvg : arrowDownSvg) + '</span>' +
        '<div class="timeline-phase-text">' +
          '<div class="timeline-phase-title">' + (naik ? 'Alur Naik' : 'Alur Turun') + (nomorFase > 1 ? ' ' + nomorFase : '') + '</div>' +
          '<div class="timeline-phase-sub">' + (naik ? 'Balasan dari satuan kembali ke atas' : 'Surat diteruskan dari atas ke satuan') + '</div>' +
        '</div>';
      return h;
    }

    function svgWrap(inner){
      return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">' + inner + '</svg>';
    }

    // Disposisi / catatan / lampiran milik satu riwayat. Keterangan Tindakan
    // (chip SESUAI JUK KOMANDAN, dst) sengaja TIDAK ditampilkan di Riwayat Alur
    // karena sudah ada di panel "Tindakan" terpisah.
    // Ikon tautan (rantai) untuk link lampiran di Riwayat Alur -- SVG, bukan emoji,
    // supaya tampil konsisten di semua perangkat.
    var linkSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
    function extraRiwayatHtml(r, sembunyikanCatatanOtomatis, tanpaLampiran, tanpaDisposisi, tanpaCatatan){
      var html = '';
      if (r.disposisi && !tanpaDisposisi) html += '<div class="surat-detail-timeline-sub" style="margin-top:2px;color:var(--primary);font-weight:500">Disposisi: ' + escHtml(r.disposisi) + '</div>';
      var catatanOtomatis = sembunyikanCatatanOtomatis && /^Diteruskan ke .+ oleh /.test(r.catatan || '');
      if (r.catatan && r.aksi !== 'Surat Dibuat' && !catatanOtomatis && !tanpaCatatan) {
        html += '<div class="surat-detail-timeline-sub" style="margin-top:3px;font-style:italic;color:var(--text-muted)">' + escHtml(r.catatan) + '</div>';
      }
      if (r.lampiran_url && !tanpaLampiran) {
        html += '<div class="surat-detail-timeline-sub" style="margin-top:4px"><a class="timeline-lampiran-link" href="' + escHtml(r.lampiran_url) + '" target="_blank" rel="noopener">' + linkSvg + '<span>' + escHtml(r.lampiran_nama || 'Lampiran') + '</span></a></div>';
      }
      return html;
    }

    // Penerima paralel (View Only Urdal, tembusan, dst) sebagai chip kecil.
    function paralelChipsHtml(list){
      if (!list || !list.length) return '';
      return '<div class="surat-detail-timeline-sub" style="display:flex;flex-wrap:wrap;gap:4px;margin-top:6px">' +
        list.map(function(p){
          var sudah = !!p.sudah_konfirmasi;
          return '<span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;border:1px solid ' +
            (sudah ? 'rgba(61,186,126,.35)' : 'var(--border)') + ';background:' + (sudah ? 'rgba(61,186,126,.12)' : 'var(--panel)') + ';color:' +
            (sudah ? '#2e9e68' : 'var(--text-muted)') + '">' + escHtml(p.label_jenis || p.jenis) + ': ' + escHtml(p.satuan) + (sudah ? ' ✓' : '') + '</span>';
        }).join('') + '</div>';
    }

    // Keterangan di Riwayat Alur: Urdal ikut menerima salinan surat, tapi
    // HANYA untuk dilihat (View Only) -- tanpa konfirmasi/tindak lanjut.
    // Ditempel di step yang meneruskan surat (data dari riwayat.paralel).
    var eyeSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    function viewOnlyNoteHtml(list){
      if (!list || !list.length) return '';
      var nama  = list.map(function(p){ return p.satuan; }).join(', ');
      return '<div class="timeline-viewonly-note">' +
        '<span class="timeline-viewonly-icon">' + eyeSvg + '</span>' +
        '<div class="timeline-viewonly-text">' +
          '<div class="timeline-viewonly-title">' + escHtml(nama) + ' juga menerima surat ini <span class="timeline-viewonly-tag">View Only</span></div>' +
          '<div class="timeline-viewonly-desc">Salinan surat diterima ' + escHtml(nama) + ' hanya untuk dilihat, tanpa konfirmasi atau tindak lanjut.</div>' +
        '</div></div>';
    }

    function metaHtml(tanggal){
      return tanggal ? '<div class="timeline-card-meta">' + clockSvg + '<span>' + escHtml(tanggal) + '</span></div>' : '';
    }

    function bangunItem(nomor, isLast, selesai, cls, badgeHtml, bodyHtml){
      var el = document.createElement('div');
      el.className = 'surat-detail-timeline-item timeline-card' + (selesai ? '' : ' is-pending');
      el.innerHTML =
        timelineStepColHtml(nomor, isLast) +
        '<div class="timeline-card-inner' + (selesai && cls ? ' ' + cls : '') + '">' +
          '<span class="timeline-card-badge">' + badgeHtml + '</span>' +
          '<div class="timeline-card-body">' + bodyHtml + '</div>' +
        '</div>';
      return el;
    }

    // Lampiran surat awal sudah tersedia di panel "Dokumen" (tombol Unduh), jadi
    // tidak diulang di step "Dibuat" maupun di step "Diteruskan" kalau file-nya
    // masih sama. Tautan hanya muncul di step yang membawa file BARU.
    var lampiranAwalUrl = '';
    riwayats.forEach(function(r){ if (!lampiranAwalUrl && r.aksi_kode === 'BUAT_SURAT' && r.lampiran_url) lampiranAwalUrl = r.lampiran_url; });

    var jumlahFaseSama = { turun: 0, naik: 0 };
    groups.forEach(function(g){
    var langkah = g.langkah;
    jumlahFaseSama[g.fase]++;
    if (groups.length > 1) timeline.appendChild(faseHeaderEl(g.fase, jumlahFaseSama[g.fase]));
    var itemsRendered = [];
    langkah.forEach(function(l, idx){
      var nomor  = idx + 1;
      var isLast = idx === langkah.length - 1;
      var el, done = true;

      if (l.tipe === 'buat') {
        var r = l.r;
        var infoBuat = stepIconAndClass('BUAT_SURAT');
        var olehBuat = 'Oleh ' + r.pengirim + (r.penerima ? ' → ' + r.penerima : '') + (r.siklus > 1 ? ' (Siklus ' + r.siklus + ')' : '');
        el = bangunItem(nomor, isLast, true, infoBuat.cls, svgWrap(infoBuat.icon),
          '<div class="surat-detail-timeline-title">Dibuat</div>' +
          '<div class="surat-detail-timeline-sub">' + escHtml(olehBuat) + '</div>' +
          metaHtml(r.tanggal) + extraRiwayatHtml(r, false, true) + paralelChipsHtml(r.paralel));

      } else if (l.tipe === 'balasan') {
        // Step 1 fase NAIK: satuan mengirim balasan (padanan "Dibuat" di fase turun).
        var rb = l.r;
        var infoBalas = stepIconAndClass('SURAT_KELUAR');
        var olehBalas = 'Oleh ' + rb.pengirim + (rb.penerima ? ' → ' + rb.penerima : '') + (rb.siklus > 1 ? ' (Siklus ' + rb.siklus + ')' : '');
        var balasLampiranTampil = !!rb.lampiran_url && rb.lampiran_url !== lampiranAwalUrl;
        if (balasLampiranTampil) lampiranDiRiwayat.push(rb.lampiran_url);
        // Ringkasan/catatan balasan sengaja TIDAK ditampilkan di Riwayat Alur
        // (tanpaCatatan = true); lampiran balasan cukup tampil di sini.
        el = bangunItem(nomor, isLast, true, infoBalas.cls, svgWrap(infoBalas.icon),
          '<div class="surat-detail-timeline-title">Balasan Dikirim</div>' +
          '<div class="surat-detail-timeline-sub">' + escHtml(olehBalas) + '</div>' +
          metaHtml(rb.tanggal) +
          extraRiwayatHtml(rb, false, !balasLampiranTampil, true, true) +
          paralelChipsHtml(rb.paralel));

      } else if (l.tipe === 'pemegang') {
        done = !!(l.konfirmasi || l.diteruskan || l.dibalas || l.selesai);
        var infoKonf = stepIconAndClass('KONFIRMASI');
        var dari = l.masuk ? l.masuk.pengirim : '-';
        var tglDone = l.konfirmasi ? l.konfirmasi.tanggal : (l.diteruskan ? l.diteruskan.tanggal : '');
        var body = '<div class="surat-detail-timeline-title">' + (done ? 'Dikonfirmasi ' : 'Menunggu Konfirmasi ') + escHtml(l.nama) + '</div>' +
          '<div class="surat-detail-timeline-sub">' + (done ? 'Diterima dari ' : 'Surat dari ') + escHtml(dari) + (done ? '' : ' belum dikonfirmasi') +
          (l.masuk && l.masuk.siklus > 1 ? ' (Siklus ' + l.masuk.siklus + ')' : '') + '</div>' +
          (done ? metaHtml(tglDone) : '');
        if (l.diteruskan) {
          var f = l.diteruskan;
          var paralelAll   = f.paralel || [];
          var viewOnlyList = paralelAll.filter(function(p){ return p.jenis === 'view_only'; });
          var paralelLain  = paralelAll.filter(function(p){ return p.jenis !== 'view_only'; });
          body += '<div class="timeline-forward-block">' +
            '<div class="timeline-forward-row"><span class="timeline-forward-label">' +
              (f.aksi_kode === 'SURAT_KELUAR' ? 'Dibalas ke' : 'Diteruskan ke') + '</span>' +
              '<span class="timeline-forward-value">' + escHtml(f.penerima || '-') + '</span></div>' +
            (f.disposisi ? '<div class="timeline-forward-row"><span class="timeline-forward-label">Disposisi</span>' +
              '<span class="timeline-forward-value">' + escHtml(f.disposisi) + '</span></div>' : '') +
            '</div>' +
            extraRiwayatHtml(f, true, !f.lampiran_url || f.lampiran_url === lampiranAwalUrl, true) +
            viewOnlyNoteHtml(viewOnlyList) + paralelChipsHtml(paralelLain);
        }
        el = bangunItem(nomor, isLast, done, infoKonf.cls, done ? svgWrap(infoKonf.icon) : clockSvg, body);

      } else if (l.tipe === 'selesai') {
        var infoSel = stepIconAndClass('SELESAI');
        el = bangunItem(nomor, isLast, true, infoSel.cls, svgWrap(infoSel.icon),
          '<div class="surat-detail-timeline-title">Selesai (Final)</div>' +
          '<div class="surat-detail-timeline-sub">Oleh ' + escHtml(l.r.pengirim) + '</div>' +
          metaHtml(l.r.tanggal) + extraRiwayatHtml(l.r, false));

      } else { // placeholder: satuan tujuan akhir belum dipilih Wadan
        done = false;
        el = bangunItem(nomor, isLast, false, '', clockSvg,
          '<div class="surat-detail-timeline-title">Menunggu Konfirmasi Satuan</div>' +
          '<div class="surat-detail-timeline-sub">Satuan tujuan akan dipilih oleh Wadan saat meneruskan surat</div>');
      }

      timeline.appendChild(el);
      itemsRendered.push({ el: el, done: done });
    });

    // Garis penghubung antar step: hijau kalau step tujuannya sudah selesai
    // (dua-duanya ceklis), abu-abu kalau step berikutnya masih menunggu.
    itemsRendered.forEach(function(it, i){
      var next = itemsRendered[i + 1];
      var ln = it.el.querySelector('.timeline-step-line');
      if (ln && next && next.done) {
        void ln.offsetHeight;
        requestAnimationFrame(function(){ requestAnimationFrame(function(){ ln.classList.add('line-complete'); }); });
      }
    });
    }); // akhir groups.forEach
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

    // Penerima pertama surat (mis. Wadan). Kalau penerima itu Wadan, alur nyata
    // sistemnya: Wadan konfirmasi dulu -> Wadan memilih satuan tujuan ->
    // satuan tsb konfirmasi. Step 2 & 3 mengikuti kondisi itu.
    var hopPertama = (alurHops && alurHops.length) ? alurHops[0] : null;
    var namaPenerima = (hopPertama && hopPertama.nama) || button.dataset.tujuan || '-';
    var penerimaIsWadan = !!hopPertama && /wadan/i.test((hopPertama.kode || '') + ' ' + (hopPertama.nama || ''));
    var judulStep2 = penerimaIsWadan
      ? (sudahKonfirmasi ? 'Dikonfirmasi ' : 'Menunggu Konfirmasi ') + namaPenerima
      : 'Dikonfirmasi';

    var konfirmasi = document.createElement('div');
    konfirmasi.className = 'surat-detail-timeline-item timeline-card' + (sudahKonfirmasi ? '' : ' is-pending');
    konfirmasi.innerHTML =
      timelineStepColHtml(2, button.dataset.isSelesai === '1') +
      '<div class="timeline-card-inner' + (sudahKonfirmasi ? ' ' + iconKonf.cls : '') + '">' +
        '<span class="timeline-card-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">' + iconKonf.icon + '</svg></span>' +
        '<div class="timeline-card-body"><div class="surat-detail-timeline-title"></div><div class="surat-detail-timeline-sub"></div><div class="timeline-card-meta"></div></div>' +
      '</div>';
    konfirmasi.querySelector('.surat-detail-timeline-title').textContent = judulStep2;
    konfirmasi.querySelector('.surat-detail-timeline-sub').textContent = sudahKonfirmasi
      ? ('Oleh ' + (button.dataset.dikonfirmasiOleh || '-'))
      : (penerimaIsWadan ? 'Surat belum dikonfirmasi oleh Wadan' : 'Menunggu konfirmasi penerima');
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
      var tujuanSaatIni = button.dataset.saatIni || button.dataset.tujuan || '-';
      var statusSaatIni = button.dataset.status || 'Menunggu Konfirmasi';

      var pendingFallback = document.createElement('div');
      pendingFallback.className = 'surat-detail-timeline-item timeline-card is-pending';
      var step3Html = penerimaIsWadan
        ? '<div class="surat-detail-timeline-title">Menunggu Konfirmasi Satuan</div>' +
          '<div class="surat-detail-timeline-sub">Satuan tujuan akan dipilih oleh Wadan saat meneruskan surat</div>'
        : '<div class="surat-detail-timeline-title">Saat Ini: ' + escHtml(tujuanSaatIni) + stepStatusPill(statusSaatIni === 'Dikonfirmasi' ? 'KONFIRMASI' : null) + '</div>' +
          '<div class="surat-detail-timeline-sub">' + escHtml(statusSaatIni) + '<br>belum masuk Arsip sampai seluruh alur tuntas</div>';
      pendingFallback.innerHTML =
        timelineStepColHtml(3, true) +
        '<div class="timeline-card-inner">' +
          '<span class="timeline-card-badge">' + clockSvg + '</span>' +
          '<div class="timeline-card-body">' + step3Html + '</div>' +
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
  if (button.dataset.lampiranUrl && lampiranDiRiwayat.indexOf(button.dataset.lampiranUrl) === -1) {
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
  // Label tombol Teruskan Wadan: default "Teruskan Surat"; berubah jadi
  // "Teruskan ke Danpus" khusus surat BALASAN dari satuan (alur naik).
  function setLabelTeruskanWadan(teks){
    var n = btnTeruskanWadan ? btnTeruskanWadan.lastChild : null;
    if (n && n.nodeType === 3) n.nodeValue = teks;
  }
  if (btnTeruskanWadan) {
    btnTeruskanWadan.hidden = true; btnTeruskanWadan.onclick = null;
    btnTeruskanWadan.disabled = true; btnTeruskanWadan.style.opacity = ''; btnTeruskanWadan.style.cursor = '';
    setLabelTeruskanWadan('Teruskan Surat');
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

    // Alur naik: surat BALASAN dari satuan pelaksana. Wadan tetap
    // Konfirmasi dulu, lalu "Teruskan ke Danpus" (tanpa form disposisi &
    // tindakan -- field surat tidak diinput ulang).
    var wadanNaik = button.dataset.wadanNaik === '1';
    var canTeruskanWadan = button.dataset.canTeruskan === '1' || wadanNaik;
    if (btnKonfirmasiWadan && btnTeruskanWadan && canTeruskanWadan) {
      if (wadanNaik) setLabelTeruskanWadan('Teruskan ke Danpus');
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

          if (window.siberadShowToast) window.siberadShowToast('success', wadanNaik
            ? 'Surat berhasil dikonfirmasi. Silakan lanjutkan dengan "Teruskan ke Danpus".'
            : 'Surat berhasil dikonfirmasi. Silakan lanjutkan dengan "Teruskan Surat".');
        }).catch(function(){
          btnKonfirmasiWadan.innerHTML = originalHtml;
          setKonfirmasiWadanEnabled(true);
          if (window.siberadShowToast) window.siberadShowToast('error', 'Gagal mengkonfirmasi surat. Coba lagi.');
        });
      };

      btnTeruskanWadan.onclick = function(){
        if (btnTeruskanWadan.disabled) return;
        if (wadanNaik) {
          window.suratKonfirmasiKeDanpus(button.dataset.keDanpusAction, button.dataset.csrf || '', button.dataset.perihal || '');
          return;
        }
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
            sub      : 'Pilih satuan tujuan dan tindakan yang harus dilaksanakan sebelum meneruskan.',
            btnLabel : 'Disposisi & Teruskan',
            isWadan  : true,
          });
        }
      };
    }

    // 4. Kembalikan ke Danpus (Wadan setelah menerima laporan hasil Satrap)
    if (button.dataset.canKeDanpus === '1') {
      btnKeDanpus.hidden = false;
      btnKeDanpus.onclick = function(){
        window.suratKonfirmasiKeDanpus(button.dataset.keDanpusAction, csrf, button.dataset.perihal || '');
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
            isWadan  : false,
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

// Selagi modal Detail Surat lagi kebuka, poll realtime manggil ini tiap habis sync
window.siberadRefreshSuratDetailIfOpen = function(){
  var modal = document.getElementById('suratDetailModal');
  if (!modal || !modal.classList.contains('open')) return;
  var id = modal.dataset.openSuratId;
  if (!id) return;
  var gridId = modal.dataset.openSuratGrid || '';
  var scope  = gridId ? document.getElementById(gridId) : document;
  if (!scope) return;
  var card = scope.querySelector('.surat-file-card[data-surat-id="' + id + '"]:not(.siberad-card-leaving)');
  var btn  = card ? card.querySelector('.surat-file-card-btn') : null;
  if (!btn) return;
  var sig = btn.outerHTML.replace(/>\s+</g,'><').trim();
  if (sig === modal.dataset.openSuratSig) return;
  window.openSuratDetail(btn);
};

(function(){
  var modal = document.getElementById('suratDetailModal');
  if (!modal) return;
  function close(){ modal.classList.remove('open'); modal.dataset.openSuratId = ''; modal.dataset.openSuratGrid = ''; modal.dataset.openSuratSig = ''; }
  document.getElementById('suratDetailClose')?.addEventListener('click', close);
  document.getElementById('suratDetailTutup')?.addEventListener('click', close);
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && modal.classList.contains('open')) close();
  });
})();
</script>
