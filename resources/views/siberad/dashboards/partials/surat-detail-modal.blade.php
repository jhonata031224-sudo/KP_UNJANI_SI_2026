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
    {{-- Form inline Disposisi & Tindakan (mode simpel Wadan, langsung teruskan) --}}
    <div class="surat-detail-item" id="suratDetailWadanForm" style="display:none"><div style="border-top:1px solid var(--border);padding-top:12px;margin-top:4px">
        <div class="form-group" style="margin-bottom:14px">
            <label class="form-label" for="suratDetailWadanDisposisi">Disposisi <span style="color:var(--red)">*</span></label>
            <select id="suratDetailWadanDisposisi" class="form-select">
                <option value="">— Pilih Satuan Tujuan —</option>
                @foreach($wadanDisposisiSatuan as $st)
                    <option value="{{ $st->id }}" data-label="{{ e($st->nama) }}">{{ $st->nama }} ({{ $st->kode }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Tindakan <span style="color:var(--red)">*</span> <span style="font-size:11px;color:var(--text-muted);font-weight:400">(Pilih minimal satu)</span></label>
            <div id="suratDetailWadanTindakanGrid" style="display:grid;grid-template-columns:1fr 1fr;gap:6px 16px;margin-top:6px;max-height:200px;overflow-y:auto;padding:2px 0;border:1px solid transparent;border-radius:10px">
                @foreach($wadanTindakanOptions as $opt)
                    <label class="surat-tindakan-check-label" style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;padding:4px 6px;border-radius:6px;transition:background .15s">
                        <input type="checkbox" value="{{ $opt }}" class="surat-detail-wadan-tindakan-check" style="width:15px;height:15px;accent-color:var(--primary);cursor:pointer;flex-shrink:0">
                        <span>{{ $opt }}</span>
                    </label>
                @endforeach
            </div>
            <span id="suratDetailWadanTindakanError" style="display:none;align-items:center;gap:6px;color:var(--red);font-size:10.5px;margin-top:4px">Tindakan wajib dipilih (minimal satu).</span>
        </div>
    </div></div>
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
    {{-- Konfirmasi Wadan: muncul saat surat belum dikonfirmasi, surat TETAP di Surat Masuk setelah klik --}}
    <button type="button" class="btn btn-secondary" id="suratDetailKonfirmasiWadan" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><path d="M5 13l4 4L19 7"/></svg>Konfirmasi
    </button>
    {{-- Teruskan Surat (Wadan - mode simpel: konfirmasi + disposisi + tindakan sekaligus) --}}
    <button type="button" class="btn btn-primary" id="suratDetailTeruskanSimpel" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Disposisi &amp; Teruskan
    </button>
</div></div></div>

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

  if (disposisi && !wadanSimple) {
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
          '<div class="surat-detail-timeline-title">' + escHtml(r.aksi) + '</div>' +
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
        item.innerHTML = dotHtml + '<div class="surat-detail-timeline-title">' + escHtml(r.aksi) + '</div><div class="surat-detail-timeline-sub">' + escHtml(meta) + '</div>' + extraHtml;
        timeline.appendChild(item);

        if (!isLast) {
          void item.offsetHeight;
          requestAnimationFrame(function(){requestAnimationFrame(function(){ item.classList.add('line-complete'); });});
        }
      }
    });
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
  var btnTeruskanSimpel      = document.getElementById('suratDetailTeruskanSimpel');
  var btnTutup               = document.getElementById('suratDetailTutup');
  var wadanForm              = document.getElementById('suratDetailWadanForm');
  var wadanDisposisiSel      = document.getElementById('suratDetailWadanDisposisi');
  var wadanTindakanGrid      = document.getElementById('suratDetailWadanTindakanGrid');
  var wadanTindakanError     = document.getElementById('suratDetailWadanTindakanError');

  // Reset semua action buttons terlebih dahulu (default: tersembunyi)
  btnKonfirmasi.hidden = true; btnKonfirmasi.onclick = null;
  btnKonfirmasiTembusan.hidden = true; btnKonfirmasiTembusan.onclick = null;
  if (btnKonfirmasiWadan) { btnKonfirmasiWadan.hidden = true; btnKonfirmasiWadan.onclick = null; }
  btnTeruskan.hidden = true; btnTeruskan.onclick = null;
  btnKeDanpus.hidden = true; btnKeDanpus.onclick = null;
  btnSelesai.hidden = true; btnSelesai.onclick = null;
  btnDisposisiUlang.hidden = true; btnDisposisiUlang.onclick = null;
  if (btnTeruskanSimpel) { btnTeruskanSimpel.hidden = true; btnTeruskanSimpel.onclick = null; }
  if (btnTutup) btnTutup.hidden = false;
  if (wadanForm) wadanForm.style.display = 'none';
  if (wadanDisposisiSel) wadanDisposisiSel.value = '';
  if (wadanTindakanGrid) {
    wadanTindakanGrid.querySelectorAll('.surat-detail-wadan-tindakan-check').forEach(function(c){ c.checked = false; });
    wadanTindakanGrid.style.borderColor = 'transparent';
    wadanTindakanGrid.style.boxShadow = 'none';
  }
  if (wadanTindakanError) wadanTindakanError.style.display = 'none';

  // Tombol aksi HANYA muncul pada SURAT MASUK sesuai peran & kondisi surat.
  // Pada Surat Keluar dan Arsip, HANYA tombol "Tutup" (X) yang ditampilkan.
  if (context === 'masuk' && wadanSimple) {
    // ── Mode simpel Wadan: form Disposisi & Tindakan selalu tampil.
    //    Tombol "Konfirmasi" muncul jika surat belum dikonfirmasi Wadan.
    //    Tombol "Disposisi & Teruskan" selalu muncul.
    //    Tombol Tutup lama disembunyikan, cukup pakai ikon X di header.
    if (btnTutup) btnTutup.hidden = true;
    if (wadanForm) wadanForm.style.display = '';

    // Tombol Konfirmasi Wadan (terpisah, muncul jika belum dikonfirmasi)
    var canKonfirmasiWadan = button.dataset.canKonfirmasiWadan === '1';
    var sudahDikonfirmasiWadan = button.dataset.sudahDikonfirmasiWadan === '1';
    if (btnKonfirmasiWadan) {
      if (canKonfirmasiWadan) {
        btnKonfirmasiWadan.hidden = false;
        btnKonfirmasiWadan.onclick = function() {
          var csrf = button.dataset.csrf || '';
          var action = button.dataset.konfirmasiWadanAction || '';
          if (!action) return;
          if (!confirm('Konfirmasi surat ini sebagai diterima oleh Wadan?\nSurat akan tetap di Surat Masuk sampai Anda meneruskannya.')) return;
          var f = document.createElement('form');
          f.method = 'POST'; f.action = action;
          f.innerHTML = '<input name="_token" value="' + escHtml(csrf) + '"><input name="_method" value="PATCH">';
          document.body.appendChild(f); f.submit();
        };
      } else if (sudahDikonfirmasiWadan) {
        // Sudah dikonfirmasi: tampilkan badge status saja (disabled)
        btnKonfirmasiWadan.hidden = false;
        btnKonfirmasiWadan.disabled = true;
        btnKonfirmasiWadan.style.opacity = '0.55';
        btnKonfirmasiWadan.style.cursor = 'default';
        btnKonfirmasiWadan.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:5px"><path d="M5 13l4 4L19 7"/></svg>Terkonfirmasi';
      }
    }

    if (btnTeruskanSimpel) {
      btnTeruskanSimpel.hidden = false;
      btnTeruskanSimpel.onclick = function(){
        var tujuanId = wadanDisposisiSel ? wadanDisposisiSel.value : '';
        var tindakanChecked = wadanTindakanGrid
          ? Array.prototype.map.call(wadanTindakanGrid.querySelectorAll('.surat-detail-wadan-tindakan-check:checked'), function(c){ return c.value; })
          : [];
        var valid = true;

        if (!tujuanId) {
          if (wadanDisposisiSel) { wadanDisposisiSel.style.borderColor = 'var(--red)'; }
          valid = false;
        } else if (wadanDisposisiSel) {
          wadanDisposisiSel.style.borderColor = '';
        }

        if (tindakanChecked.length === 0) {
          if (wadanTindakanGrid) {
            wadanTindakanGrid.style.borderColor = 'var(--red)';
            wadanTindakanGrid.style.boxShadow = '0 0 0 3px color-mix(in srgb,var(--red) 15%,transparent)';
          }
          if (wadanTindakanError) wadanTindakanError.style.display = 'flex';
          valid = false;
        }

        if (!valid) return;

        var selectedOpt = wadanDisposisiSel.options[wadanDisposisiSel.selectedIndex];
        var disposisiLabel = selectedOpt ? (selectedOpt.dataset.label || selectedOpt.textContent) : '';

        var f = document.createElement('form');
        f.method = 'POST'; f.action = button.dataset.teruskanAction;
        var html = '<input name="_token" value="' + escHtml(button.dataset.csrf || '') + '">';
        html += '<input name="tujuan_satuan_id" value="' + escHtml(tujuanId) + '">';
        html += '<input name="disposisi" value="' + escHtml(disposisiLabel) + '">';
        tindakanChecked.forEach(function(t){
          html += '<input name="tindakan[]" value="' + escHtml(t) + '">';
        });
        f.innerHTML = html;
        document.body.appendChild(f); f.submit();
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
