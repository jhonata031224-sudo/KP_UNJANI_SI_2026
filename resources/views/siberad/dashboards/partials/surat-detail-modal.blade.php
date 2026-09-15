<div class="report-modal" id="suratDetailModal"><div class="report-modal-card"><div class="report-modal-head"><div style="min-width:0"><h3 id="suratDetailJudul" style="margin:0 0 4px">Detail Surat</h3><p id="suratDetailDari" style="margin:0;font-size:12px;color:var(--text-muted)">-</p></div></div><div class="surat-detail-body"><div class="surat-detail-col surat-detail-col-left"><div class="surat-detail-item"><div><div class="surat-detail-item-label">Tujuan</div><div class="surat-detail-item-value" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap"><span id="suratDetailTujuan">-</span><span class="satuan-pill" id="suratDetailTujuanKode" style="display:none"></span></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Perihal</div><div class="surat-detail-item-value" id="suratDetailPerihal">-</div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Kategori</div><div class="surat-detail-item-value" id="suratDetailKategori">-</div></div></div><div class="surat-detail-item-row"><div class="surat-detail-item"><div><div class="surat-detail-item-label">Prioritas</div><div class="surat-detail-item-value"><span class="priority-tag" id="suratDetailPrioritas">-</span></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Status</div><div class="surat-detail-item-value"><span class="status-badge" id="suratDetailStatusText">-</span></div></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Ringkasan</div><div class="surat-detail-item-value" id="suratDetailRingkasan">-</div></div></div>
{{-- Panel Disposisi & Tindakan --}}
<div class="surat-detail-item" id="suratDetailDisposisiPanel" style="display:none"><div style="border-top:1px solid var(--border);padding-top:12px;margin-top:4px"><div class="surat-detail-item-label">Disposisi</div><div class="surat-detail-item-value" id="suratDetailDisposisiVal" style="font-weight:600;color:var(--primary)">-</div><div class="surat-detail-item-label" style="margin-top:8px">Tindakan</div><div id="suratDetailTindakanWrap" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px"></div></div></div>
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

  // Disposisi & Tindakan
  var disposisiPanel = document.getElementById('suratDetailDisposisiPanel');
  var disposisiVal   = document.getElementById('suratDetailDisposisiVal');
  var tindakanWrap   = document.getElementById('suratDetailTindakanWrap');
  var disposisi = button.dataset.disposisi || '';
  var tindakan  = [];
  try { tindakan = JSON.parse(button.dataset.tindakan || '[]'); } catch(e){}
  if (disposisi || tindakan.length) {
    disposisiPanel.style.display = '';
    disposisiVal.textContent = disposisi || '-';
    tindakanWrap.innerHTML = '';
    tindakan.forEach(function(t){
      var tag = document.createElement('span');
      tag.className = 'status-badge status-sedang';
      tag.style.cssText = 'font-size:11px;padding:3px 10px;font-weight:500';
      tag.textContent = t;
      tindakanWrap.appendChild(tag);
    });
  } else {
    disposisiPanel.style.display = 'none';
  }

  // ── Timeline Riwayat Dinamis ──────────────────────────────────────────────
  var checkSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>';
  var timeline = document.getElementById('suratDetailTimeline');
  timeline.innerHTML = '';

  var riwayats = [];
  try { riwayats = JSON.parse(button.dataset.riwayat || '[]'); } catch(e){}

  if (riwayats.length > 0) {
    riwayats.forEach(function(r, idx){
      var item = document.createElement('div');
      item.className = 'surat-detail-timeline-item';
      var dotHtml = '<span class="surat-detail-timeline-dot">' + checkSvg + '</span>';
      var meta = r.pengirim + (r.penerima ? ' → ' + r.penerima : '') + ' • ' + r.tanggal;
      if (r.siklus > 1) meta += ' (Siklus ' + r.siklus + ')';
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
      item.innerHTML = dotHtml + '<div class="surat-detail-timeline-title">' + escHtml(r.aksi) + '</div><div class="surat-detail-timeline-sub">' + escHtml(meta) + '</div>' + extraHtml;
      timeline.appendChild(item);

      // Line complete antara langkah (kecuali item terakhir)
      if (idx < riwayats.length - 1) {
        void item.offsetHeight;
        requestAnimationFrame(function(){requestAnimationFrame(function(){
          item.classList.add('line-complete');
        });});
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
  var btnTeruskan            = document.getElementById('suratDetailTeruskan');
  var btnKeDanpus            = document.getElementById('suratDetailKeDanpus');
  var btnSelesai             = document.getElementById('suratDetailSelesai');
  var btnDisposisiUlang      = document.getElementById('suratDetailDisposisiUlang');

  // Reset semua action buttons terlebih dahulu (default: tersembunyi)
  btnKonfirmasi.hidden = true; btnKonfirmasi.onclick = null;
  btnKonfirmasiTembusan.hidden = true; btnKonfirmasiTembusan.onclick = null;
  btnTeruskan.hidden = true; btnTeruskan.onclick = null;
  btnKeDanpus.hidden = true; btnKeDanpus.onclick = null;
  btnSelesai.hidden = true; btnSelesai.onclick = null;
  btnDisposisiUlang.hidden = true; btnDisposisiUlang.onclick = null;

  // Tombol aksi HANYA muncul pada SURAT MASUK sesuai peran & kondisi surat.
  // Pada Surat Keluar dan Arsip, HANYA tombol "Tutup" yang ditampilkan.
  if (context === 'masuk') {
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
