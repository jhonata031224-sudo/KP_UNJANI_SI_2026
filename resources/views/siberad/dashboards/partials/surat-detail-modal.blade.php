<div class="report-modal" id="suratDetailModal"><div class="report-modal-card"><div class="report-modal-head"><div style="min-width:0"><h3 style="margin:0 0 4px">Detail Surat</h3><p id="suratDetailDari" style="margin:0;font-size:12px;color:var(--text-muted)">-</p></div></div><div class="surat-detail-body"><div class="surat-detail-col surat-detail-col-left"><div class="surat-detail-item"><div><div class="surat-detail-item-label">Tujuan</div><div class="surat-detail-item-value" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap"><span id="suratDetailTujuan">-</span><span class="satuan-pill" id="suratDetailTujuanKode" style="display:none"></span></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Perihal</div><div class="surat-detail-item-value" id="suratDetailPerihal">-</div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Kategori</div><div class="surat-detail-item-value" id="suratDetailKategori">-</div></div></div><div class="surat-detail-item-row"><div class="surat-detail-item"><div><div class="surat-detail-item-label">Prioritas</div><div class="surat-detail-item-value"><span class="priority-tag" id="suratDetailPrioritas">-</span></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Status</div><div class="surat-detail-item-value"><span class="status-badge" id="suratDetailStatusText">-</span></div></div></div></div><div class="surat-detail-item"><div><div class="surat-detail-item-label">Ringkasan</div><div class="surat-detail-item-value" id="suratDetailRingkasan">-</div></div></div></div><div class="surat-detail-col surat-detail-col-right"><div class="surat-detail-panel"><div class="surat-detail-panel-title">Riwayat Status</div><div class="surat-detail-timeline" id="suratDetailTimeline"></div></div><div class="surat-detail-panel" id="suratDetailDokumenPanel" hidden><div class="surat-detail-panel-title">Dokumen</div><div id="suratDetailDokumenWrap"></div></div></div></div><div class="modal-actions"><button type="button" class="btn" id="suratDetailTutup">Tutup</button><button type="button" class="btn" id="suratDetailKonfirmasi" hidden>Konfirmasi</button></div></div></div>
<script>
window.openSuratDetail = function(button){
  var modal = document.getElementById('suratDetailModal');
  if (!modal) return;
  var wasOpen = modal.classList.contains('open');
  var prevSudahDikonfirmasi = document.getElementById('suratDetailStatusText').classList.contains('status-dikonfirmasi');
  var card = button.closest('.surat-file-card');
  modal.dataset.openSuratId = card ? (card.dataset.suratId || '') : '';
  document.getElementById('suratDetailDari').textContent = 'Dari ' + (button.dataset.dari || '-');
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
  statusEl.textContent = sudahDikonfirmasi ? 'Dikonfirmasi' : 'Menunggu';
  statusEl.className = 'status-badge ' + (sudahDikonfirmasi ? 'status-dikonfirmasi' : 'status-menunggu');
  document.getElementById('suratDetailRingkasan').textContent = button.dataset.deskripsi || '-';

  var checkSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>';
  var timeline = document.getElementById('suratDetailTimeline');
  timeline.innerHTML = '';
  var sudahKonfirmasi = !!button.dataset.dikonfirmasiTanggal;
  var justConfirmed = wasOpen && !prevSudahDikonfirmasi && sudahKonfirmasi;
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
    // Garis penghubung "line-complete" WAJIB ditambahin belakangan (bukan
    // langsung di className pas elemen dibuat) + dipaksa reflow dulu (2x rAF,
    // niru pola flipPlay di surat-terkirim-realtime.blade.php) -- elemen yang
    // "line-complete" dari lahir gak pernah kepaint dalam state awalnya
    // (scaleY(0)), jadi transition CSS-nya gak ada apa-apa buat dianimasiin
    // dari situ (asal-asalan langsung "dep" kepenuhan).
    void dibuat.offsetHeight;
    requestAnimationFrame(function(){requestAnimationFrame(function(){
      dibuat.classList.add('line-complete');
    });});
  }
  if (justConfirmed) {
    statusEl.classList.add('siberad-row-updated');
    konfirmasi.classList.add('siberad-row-updated');
    var justDot = konfirmasi.querySelector('.surat-detail-timeline-dot');
    if (justDot) justDot.classList.add('just-confirmed');
    if (window.siberadShowToast) window.siberadShowToast('success', 'Surat sudah dikonfirmasi.');
  }

  var dokWrap = document.getElementById('suratDetailDokumenWrap');
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

  var confirmBtn = document.getElementById('suratDetailKonfirmasi');
  if (button.dataset.canConfirm === '1') {
    confirmBtn.hidden = false;
    confirmBtn.onclick = function(){
      if (typeof window.bukaKonfirmasiSurat === 'function') {
        window.bukaKonfirmasiSurat(button.dataset.confirmAction, button.dataset.confirmToken, button.dataset.dari);
      }
    };
  } else {
    confirmBtn.hidden = true;
    confirmBtn.onclick = null;
  }

  modal.dataset.openSuratSig = button.outerHTML.replace(/>\s+</g,'><').trim();
  void modal.offsetHeight;
  modal.classList.add('open');
};

// Selagi modal Detail Surat lagi kebuka, poll realtime (surat-terkirim-realtime.blade.php)
// manggil ini tiap habis sync -- kalau kartu surat yang lagi ditampilkan berubah
// (mis. status Menunggu -> Dikonfirmasi gara-gara penerima baru aja konfirmasi
// selagi pengirim masih buka detailnya), modal ikut kebarui otomatis tanpa
// perlu ditutup-buka lagi. Signature dibanding dulu biar gak render ulang
// (dan animasi .just-confirmed gak keputer ulang) kalau sebenernya gak ada
// yang berubah dari poll sebelumnya.
window.siberadRefreshSuratDetailIfOpen = function(){
  var modal = document.getElementById('suratDetailModal');
  if (!modal || !modal.classList.contains('open')) return;
  var id = modal.dataset.openSuratId;
  if (!id) return;
  var card = document.querySelector('.surat-file-card[data-surat-id="' + id + '"]:not(.siberad-card-leaving)');
  var btn = card ? card.querySelector('.surat-file-card-btn') : null;
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
