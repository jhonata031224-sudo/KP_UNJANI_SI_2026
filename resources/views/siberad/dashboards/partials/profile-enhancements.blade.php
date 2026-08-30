<script>
(function(){
  // Konfirmasi keluar SUDAH ditangani oleh modal custom di masing-masing
  // halaman (lihat #logoutConfirmOverlay di admin.blade.php, atau
  // initLogoutConfirm() di partials/global-shell-enhancements.blade.php —
  // dua-duanya ikut ter-include di semua halaman yang memuat partial ini).
  // Script ini dulu punya fallback window.confirm() sendiri di sini, tapi
  // karena partial ini jalan LEBIH DULU (synchronous, bukan nunggu
  // DOMContentLoaded) daripada modal custom yang dipasang belakangan, popup
  // bawaan browser itu selalu menang duluan dan muncul dobel bareng modal
  // custom-nya. Dihapus supaya cuma satu modal (yang custom) yang jalan.

  // Foto profil beneran tersimpan di server (tabel users.foto_path, disk
  // public) -- HTML foto/inisial-nya udah dirender server-side sesuai state
  // saat ini. Pilih file -> validasi ringan -> buka modal "Atur Foto Profil"
  // (geser + zoom, kayak Facebook/Instagram) -> begitu "Ganti Foto" diklik,
  // area lingkaran yang kelihatan di-crop lewat <canvas> jadi satu file baru,
  // ditempelin ke input file asli, baru form-nya (reload penuh, bukan AJAX,
  // biar konsisten sama pola form lain) di-submit.
  //
  // #hapusFotoOverlay & #aturFotoOverlay dicek lewat DOMContentLoaded (bukan
  // sinkron) karena di Admin & Pimpinan elemennya ditaruh JAUH di bawah
  // wrapper .content tempat partial ini ke-include -- dicek sinkron bakal
  // selalu null, jadi listener-nya nggak pernah kepasang.
  function initFotoProfil(){
    var input = document.getElementById('fotoProfilInput');
    var changeBtn = document.getElementById('gantiFotoBtn');
    var deleteBtn = document.getElementById('hapusFotoBtn');
    var formGanti = document.getElementById('formGantiFoto');
    if (!input || !changeBtn) return;

    var maxBytes = 10 * 1024 * 1024;
    var allowed = ['image/jpeg','image/png','image/webp'];

    // Pesan error validasi foto (format/ukuran), gaya sama kayak error
    // wajib-diisi di form lain (.profile-field-error) -- muncul di bawah
    // baris tombol "Ganti Foto"/"Hapus", bukan alert() bawaan browser.
    var actionsRow = changeBtn.closest('.profile-photo-actions');
    var fotoError = null;
    function showFotoError(text){
      if (!actionsRow) return;
      if (!fotoError) {
        fotoError = document.createElement('span');
        fotoError.className = 'profile-field-error';
        fotoError.style.display = 'none';
        actionsRow.insertAdjacentElement('afterend', fotoError);
      }
      fotoError.textContent = text;
      fotoError.style.display = 'flex';
    }
    function clearFotoError(){
      if (fotoError) fotoError.style.display = 'none';
    }

    changeBtn.addEventListener('click', function(){ input.click(); });

    // ===== Modal "Atur Foto Profil": geser (drag) + zoom (slider) =====
    var cropOverlay = document.getElementById('aturFotoOverlay');
    var cropStage = document.getElementById('cropStage');
    var cropImage = document.getElementById('cropImage');
    var cropZoomRange = document.getElementById('cropZoomRange');
    var cropCanUse = cropOverlay && cropStage && cropImage && cropZoomRange;

    var STAGE_SIZE = 270;   // harus sama kayak lebar/tinggi .crop-stage di CSS
    var OUTPUT_SIZE = 480;  // resolusi foto hasil crop yang diupload
    var objectUrl = null;
    var natW = 0, natH = 0;   // ukuran asli gambar
    var baseScale = 1;       // skala minimum biar gambar nutup penuh stage (cover-fit)
    var zoomMult = 1;        // dari slider, 1.0 - 3.0
    var offX = 0, offY = 0;  // posisi top-left gambar relatif ke stage
    var dragging = false, dragStartX = 0, dragStartY = 0, dragOffX = 0, dragOffY = 0;

    function displayedSize(){
      var s = baseScale * zoomMult;
      return { w: natW * s, h: natH * s };
    }

    function clampOffset(w, h){
      // Gambar nggak boleh nyisain area kosong -- sisi kiri/atas nggak boleh
      // > 0, sisi kanan/bawah nggak boleh nyisa celah dari tepi stage.
      var minX = STAGE_SIZE - w, minY = STAGE_SIZE - h;
      if (offX > 0) offX = 0;
      if (offY > 0) offY = 0;
      if (offX < minX) offX = minX;
      if (offY < minY) offY = minY;
    }

    function applyTransform(){
      var size = displayedSize();
      clampOffset(size.w, size.h);
      cropImage.style.width = size.w + 'px';
      cropImage.style.height = size.h + 'px';
      cropImage.style.transform = 'translate(' + offX + 'px,' + offY + 'px)';
    }

    cropImage.addEventListener('load', function(){
      natW = cropImage.naturalWidth; natH = cropImage.naturalHeight;
      if (!natW || !natH) return;
      baseScale = Math.max(STAGE_SIZE / natW, STAGE_SIZE / natH);
      zoomMult = 1;
      cropZoomRange.value = 100;
      var size = displayedSize();
      offX = (STAGE_SIZE - size.w) / 2;
      offY = (STAGE_SIZE - size.h) / 2;
      applyTransform();
    });

    cropZoomRange.addEventListener('input', function(){
      var before = displayedSize();
      // Titik yang lagi kelihatan persis di tengah stage -- dipertahankan
      // biar zoom kerasa natural (nggak nyentak ke pojok kiri-atas).
      var fracX = before.w ? (STAGE_SIZE / 2 - offX) / before.w : 0.5;
      var fracY = before.h ? (STAGE_SIZE / 2 - offY) / before.h : 0.5;
      zoomMult = Number(cropZoomRange.value) / 100;
      var after = displayedSize();
      offX = STAGE_SIZE / 2 - fracX * after.w;
      offY = STAGE_SIZE / 2 - fracY * after.h;
      applyTransform();
    });

    function pointerDown(e){
      dragging = true;
      cropStage.classList.add('is-dragging');
      var p = e.touches ? e.touches[0] : e;
      dragStartX = p.clientX; dragStartY = p.clientY;
      dragOffX = offX; dragOffY = offY;
      e.preventDefault();
    }
    function pointerMove(e){
      if (!dragging) return;
      var p = e.touches ? e.touches[0] : e;
      offX = dragOffX + (p.clientX - dragStartX);
      offY = dragOffY + (p.clientY - dragStartY);
      applyTransform();
    }
    function pointerUp(){
      dragging = false;
      cropStage.classList.remove('is-dragging');
    }
    if (cropCanUse) {
      cropStage.addEventListener('mousedown', pointerDown);
      document.addEventListener('mousemove', pointerMove);
      document.addEventListener('mouseup', pointerUp);
      cropStage.addEventListener('touchstart', pointerDown, { passive: false });
      document.addEventListener('touchmove', pointerMove, { passive: false });
      document.addEventListener('touchend', pointerUp);
    }

    function closeCrop(){
      cropOverlay.classList.remove('open');
      if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
    }
    function cancelCrop(){
      closeCrop();
      input.value = '';
    }
    document.getElementById('aturFotoBatal')?.addEventListener('click', cancelCrop);
    // Sengaja TANPA klik-di-luar-buat-batal -- pas lagi asik geser/zoom
    // foto, gampang banget nggak sengaja ngeklik area luar modal dan
    // ke-cancel semuanya. Cuma tombol Batal & Escape yang bisa nutup.
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && cropOverlay?.classList.contains('open')) cancelCrop(); });

    document.getElementById('aturFotoSimpan')?.addEventListener('click', function(){
      var canvas = document.createElement('canvas');
      canvas.width = OUTPUT_SIZE; canvas.height = OUTPUT_SIZE;
      var ctx = canvas.getContext('2d');
      var ratio = OUTPUT_SIZE / STAGE_SIZE;
      var size = displayedSize();
      ctx.drawImage(cropImage, offX * ratio, offY * ratio, size.w * ratio, size.h * ratio);
      canvas.toBlob(function(blob){
        if (!blob) { closeCrop(); return; }
        var croppedFile = new File([blob], 'foto-profil.jpg', { type: 'image/jpeg' });
        try {
          var dt = new DataTransfer();
          dt.items.add(croppedFile);
          input.files = dt.files;
        } catch (err) {
          // Browser lama tanpa dukungan DataTransfer.items -- upload file
          // aslinya aja (nggak ke-crop) daripada gagal total.
        }
        closeCrop();
        if (formGanti) formGanti.requestSubmit ? formGanti.requestSubmit() : formGanti.submit();
      }, 'image/jpeg', 0.92);
    });

    input.addEventListener('change', function(){
      var file = input.files && input.files[0];
      if (!file) return;
      clearFotoError();
      if (allowed.indexOf(file.type) === -1) {
        showFotoError('Hanya format JPG, PNG, atau WEBP yang diperbolehkan.');
        input.value = '';
        return;
      }
      if (file.size > maxBytes) {
        showFotoError('Ukuran foto melebihi batas maksimal 10 MB.');
        input.value = '';
        return;
      }
      if (!cropCanUse) {
        // Fallback kalau markup modalnya nggak ada: langsung upload apa
        // adanya, nggak crash.
        if (formGanti) formGanti.requestSubmit ? formGanti.requestSubmit() : formGanti.submit();
        return;
      }
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = URL.createObjectURL(file);
      cropImage.src = objectUrl;
      cropOverlay.classList.add('open');
    });

    var hapusOverlay = document.getElementById('hapusFotoOverlay');
    if (deleteBtn && hapusOverlay) {
      function closeHapusFoto(){ hapusOverlay.classList.remove('open'); }
      deleteBtn.addEventListener('click', function(){ hapusOverlay.classList.add('open'); });
      document.getElementById('hapusFotoBatal')?.addEventListener('click', closeHapusFoto);
      document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && hapusOverlay.classList.contains('open')) closeHapusFoto(); });
    }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initFotoProfil);
  else initFotoProfil();
})();

// ===== TOMBOL MATA TAMPILKAN/SEMBUNYIKAN PASSWORD (sama kayak di form login) =====
document.querySelectorAll('.field-toggle').forEach(function(btn){
  btn.addEventListener('click', function(){
    var input = document.getElementById(btn.dataset.target);
    if (!input) return;
    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.classList.toggle('is-visible', isHidden);
    btn.setAttribute('aria-label', isHidden ? 'Sembunyikan Password' : 'Tampilkan Password');
  });
});

// ===== TAB "PENGATURAN AKUN": FOTO PROFIL <-> GANTI PASSWORD =====
(function(){
  var tabs = document.querySelectorAll('.profile-subtab-btn');
  if (!tabs.length) return;
  tabs.forEach(function(btn){
    btn.addEventListener('click', function(){
      var target = btn.getAttribute('data-subtab-target');
      document.querySelectorAll('.profile-subtab-btn').forEach(function(b){
        var active = b === btn;
        b.classList.toggle('active', active);
        b.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      document.querySelectorAll('.profile-subtab-panel').forEach(function(p){
        p.classList.toggle('active', p.id === target);
      });
    });
  });
})();

// ===== FORM GANTI PASSWORD: validasi cocok + konfirmasi sebelum kirim =====
// Tidak ada di Admin (formGantiPassword tidak dirender di sana), jadi
// selector di bawah aman no-op kalau elemennya nggak ketemu. Dijalankan
// nunggu DOMContentLoaded (bukan langsung sinkron kayak sisa partial ini)
// karena di halaman Pimpinan, #kirimGantiPasswordOverlay letaknya JAUH di
// bawah wrapper .content tempat partial ini ke-include -- kalau dicek
// sinkron elemennya belum sempat ke-parse, jadi guard di bawah selalu
// nganggep "nggak ketemu" dan validasi custom-nya nggak pernah kepasang
// (makanya balik ke tooltip bawaan browser "Please fill out this field").
function initFormGantiPassword(){
  var form = document.getElementById('formGantiPassword');
  var overlay = document.getElementById('kirimGantiPasswordOverlay');
  if (!form || !overlay) return;

  var passBaru = document.getElementById('passBaru');
  var passKonfirmasi = document.getElementById('passKonfirmasi');

  function closeConfirm(){ overlay.classList.remove('open'); }

  // Validasi wajib-diisi custom (senada sama form login & Buat Permintaan
  // Laporan): ganti tooltip bawaan browser jadi pesan Bahasa Indonesia +
  // border merah di bawah field, reset otomatis begitu diisi ulang. Error
  // span yang sama juga dipakai ulang buat cek "konfirmasi cocok" di bawah
  // (satu field = satu slot error, bukan dua tempat beda buat dua alasan).
  var requiredMessages = {
    passBaru: 'Kata sandi baru wajib diisi.',
    passKonfirmasi: 'Konfirmasi kata sandi baru wajib diisi.',
  };
  var fieldErrors = {};
  form.querySelectorAll('input[required]').forEach(function(input){
    var anchor = input.closest('.profile-field-toggle-wrap') || input;
    var msg = anchor.nextElementSibling;
    if (!msg || !msg.classList.contains('profile-field-error')) {
      msg = document.createElement('span');
      msg.className = 'profile-field-error';
      msg.style.display = 'none';
      anchor.insertAdjacentElement('afterend', msg);
    }
    fieldErrors[input.id] = msg;
    input.addEventListener('invalid', function(e){
      e.preventDefault();
      input.classList.add('field-invalid');
      msg.textContent = requiredMessages[input.id] || 'Kolom ini wajib diisi.';
      msg.style.display = 'flex';
    });
    input.addEventListener('input', function(){
      input.classList.remove('field-invalid');
      msg.style.display = 'none';
    });
  });

  function showKonfirmasiError(text){
    var msg = fieldErrors.passKonfirmasi;
    if (!msg || !passKonfirmasi) return;
    passKonfirmasi.classList.add('field-invalid');
    msg.textContent = text;
    msg.style.display = 'flex';
  }
  function clearKonfirmasiError(){
    var msg = fieldErrors.passKonfirmasi;
    if (!msg || !passKonfirmasi) return;
    passKonfirmasi.classList.remove('field-invalid');
    msg.style.display = 'none';
  }

  form.addEventListener('submit', function(e){
    if (form.dataset.confirmed === '1') { form.dataset.confirmed = ''; return; }
    e.preventDefault();
    clearKonfirmasiError();
    if (passBaru && passKonfirmasi && passBaru.value !== passKonfirmasi.value) {
      showKonfirmasiError('Konfirmasi kata sandi baru tidak cocok.');
      return;
    }
    overlay.classList.add('open');
  });

  document.getElementById('kirimGantiPasswordYa')?.addEventListener('click', function(){
    closeConfirm();
    form.dataset.confirmed = '1';
    form.requestSubmit ? form.requestSubmit() : form.submit();
  });
  document.getElementById('kirimGantiPasswordBatal')?.addEventListener('click', closeConfirm);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && overlay.classList.contains('open')) closeConfirm(); });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initFormGantiPassword);
else initFormGantiPassword();

// ===== AKSI LAPORAN MASUK DIPINDAHKAN KE DETAIL =====
(function(){
  function injectStyles(){
    if (document.getElementById('laporanDetailActionStyles')) return;
    var style = document.createElement('style');
    style.id = 'laporanDetailActionStyles';
    style.textContent = `
      .review-actions form,
      .action-row form { display:none !important; }
      #detailActions,
      #reportDetailModal .modal-actions {
        align-items:center;
        gap:10px;
      }
      #detailActions form,
      #reportDetailModal .modal-actions form { display:inline-flex !important; margin:0; }
      #detailActions button,
      #reportDetailModal .modal-actions button { border:1px solid var(--border); background:var(--panel-alt); color:var(--text); border-radius:7px; padding:7px 11px; font-size:11px; cursor:pointer; font-family:var(--body); }
      #detailActions .approve,
      #reportDetailModal .modal-actions .approve { border-color:var(--success); color:var(--success); }
      #detailActions .revise,
      #reportDetailModal .modal-actions .revise { border-color:var(--amber); color:var(--amber); }
      #detailActions .reject,
      #reportDetailModal .modal-actions .reject { border-color:var(--red); color:var(--red); }
      #detailActions .detail-action-note,
      #reportDetailModal .modal-actions .detail-action-note { margin-right:auto; font-size:11px; color:var(--text-muted); }
    `;
    document.head.appendChild(style);
  }

  function getModalActions(){
    return document.getElementById('detailActions') || document.querySelector('#reportDetailModal .modal-actions');
  }

  function populateDetailActions(detailButton){
    var actions = getModalActions();
    if (!actions) return;

    var container = detailButton.closest('.review-actions, .action-row');
    var forms = container ? Array.prototype.slice.call(container.querySelectorAll('form')) : [];
    actions.innerHTML = '';

    if (!forms.length) {
      // Modal Detail Laporan Kendala (Kasansi/Danpus/Tembusan) sengaja tidak
      // pakai catatan "Mode pemantauan..." di bagian bawah -- lihat
      // data-kendala-report pada partial kendala-*.
      if (detailButton.dataset.kendalaReport === '1') return;
      var note = document.createElement('span');
      note.className = 'detail-action-note';
      note.textContent = detailButton.dataset.readonly === '1'
        ? 'Mode pemantauan — detail ini hanya untuk melihat aktivitas laporan.'
        : 'Tidak ada tindakan yang tersedia untuk laporan ini.';
      actions.appendChild(note);
      return;
    }

    var note = document.createElement('span');
    note.className = 'detail-action-note';
    note.textContent = 'Tindak lanjuti laporan dari detail ini.';
    actions.appendChild(note);

    forms.forEach(function(originalForm){
      var clone = originalForm.cloneNode(true);
      clone.classList.add('detail-action-form');
      clone.style.display = 'inline-flex';
      var statusInput = clone.querySelector('input[name="status"]');
      var status = statusInput ? String(statusInput.value || '').toLowerCase() : '';
      var button = clone.querySelector('button[type="submit"]');
      if (button) {
        button.classList.remove('approve','revise','reject');
        if (status.indexOf('diterima') !== -1 || status.indexOf('disetujui') !== -1) button.classList.add('approve');
        else if (status.indexOf('revisi') !== -1) button.classList.add('revise');
        else if (status.indexOf('tolak') !== -1) button.classList.add('reject');
      }
      actions.appendChild(clone);
    });
  }

  function removeRedundantSummaryMenu(){
    document.querySelectorAll('.side-sub-link').forEach(function(link){
      if (link.textContent.trim() === 'Ringkasan 4 Satlak') link.remove();
    });
  }

  injectStyles();
  removeRedundantSummaryMenu();

  // Delegasi event agar berlaku untuk semua dashboard role yang memakai
  // tombol Detail + form aksi pada Laporan Masuk.
  document.addEventListener('click', function(e){
    var detailButton = e.target.closest('.review-actions .detail-btn, .action-row .detail-btn');
    if (!detailButton) return;
    // Sama seperti di global-shell-enhancements.blade.php: tombol Detail laporan
    // tembusan udah punya form feedback wajib yang dirakit sendiri di dalam
    // window.openReportDetail (data-tembusan-feedback). Listener generik ini cuma
    // ngerti pola form approve/reject/Tandai-Dibaca biasa, jadi kalau dibiarkan
    // jalan dia nimpa ulang detailActions dan bikin form feedback wajib itu ilang.
    if (detailButton.dataset.tembusanFeedback === '1') return;
    window.setTimeout(function(){ populateDetailActions(detailButton); }, 0);
  });
})();
</script>