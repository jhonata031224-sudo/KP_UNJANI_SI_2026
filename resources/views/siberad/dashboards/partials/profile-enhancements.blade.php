<script>
(function(){
  // Konfirmasi keluar dibuat sama seperti perilaku di dashboard main.
  document.querySelectorAll('.logout-form').forEach(function(form){
    if (form.dataset.logoutConfirmBound === '1') return;
    form.dataset.logoutConfirmBound = '1';
    form.addEventListener('submit', function(e){
      if (!window.confirm('Keluar dari akun SIBERAD?')) e.preventDefault();
    });
  });

  // Fitur foto profil mengikuti mekanisme dashboard main: lokal per akun,
  // tanpa mengubah data akun/backend. JPG, PNG, WEBP maksimal 5 MB.
  var modalView = document.getElementById('profilePhotoView');
  if (!modalView) return;

  var userKey = @json($user->id ?? 'default');
  var storageKey = 'siberad-profile-photo-' + userKey;
  var maxBytes = 5 * 1024 * 1024;
  var allowed = ['image/jpeg','image/png','image/webp'];

  var head = modalView.querySelector('.profile-dropdown-head-lg');
  if (!head) return;

  var avatarLarge = head.querySelector('.profile-dropdown-avatar-lg');
  var initialLarge = avatarLarge ? avatarLarge.querySelector('.profile-initial') : null;
  var profileBtn = document.querySelector('.profile-menu-btn');
  var profileDropdownAvatar = document.querySelector('#profileDropdown .profile-dropdown-avatar');
  var initialBtn = profileBtn ? profileBtn.querySelector('.profile-initial') : null;
  var initialDropdown = profileDropdownAvatar ? profileDropdownAvatar.querySelector('.profile-initial') : null;

  function makePhoto(id, cls){
    var img = document.createElement('img');
    img.id = id;
    img.className = 'profile-photo' + (cls ? ' ' + cls : '');
    img.alt = 'Foto profil {{ e($user->name ?? "Pengguna") }}';
    img.style.display = 'none';
    img.style.objectFit = 'cover';
    return img;
  }

  var photoLarge = document.getElementById('profilePhotoLarge');
  if (!photoLarge && avatarLarge) { photoLarge = makePhoto('profilePhotoLarge'); avatarLarge.appendChild(photoLarge); }
  var photoBtn = document.getElementById('profilePhotoBtn');
  if (!photoBtn && profileBtn) { photoBtn = makePhoto('profilePhotoBtn'); profileBtn.appendChild(photoBtn); }
  var photoDropdown = document.getElementById('profilePhotoDropdown');
  if (!photoDropdown && profileDropdownAvatar) { photoDropdown = makePhoto('profilePhotoDropdown'); profileDropdownAvatar.appendChild(photoDropdown); }

  var controls = document.getElementById('profilePhotoControls');
  if (!controls) {
    controls = document.createElement('div');
    controls.id = 'profilePhotoControls';
    controls.style.marginTop = '16px';
    controls.innerHTML = '<button type="button" class="profile-dropdown-item" id="gantiFotoBtn">Ganti Foto Profil</button>' +
      '<button type="button" class="profile-dropdown-item" id="hapusFotoBtn" style="display:none;color:var(--red);">Hapus Foto Profil</button>' +
      '<input type="file" id="fotoProfilInput" accept="image/png,image/jpeg,image/webp" hidden>';
    modalView.appendChild(controls);
  }

  var input = document.getElementById('fotoProfilInput');
  var changeBtn = document.getElementById('gantiFotoBtn');
  var deleteBtn = document.getElementById('hapusFotoBtn');
  if (!input || !changeBtn || !deleteBtn) return;

  function setVisible(img, visible){ if (img) img.style.display = visible ? 'block' : 'none'; }
  function showPhoto(dataUrl){
    [photoBtn, photoDropdown, photoLarge].forEach(function(img){ if (img) { img.src = dataUrl; setVisible(img, true); img.classList.add('visible'); } });
    [initialBtn, initialDropdown, initialLarge].forEach(function(el){ if (el) el.style.display = 'none'; });
    deleteBtn.style.display = 'flex';
  }
  function clearPhoto(){
    [photoBtn, photoDropdown, photoLarge].forEach(function(img){ if (img) { img.removeAttribute('src'); setVisible(img, false); img.classList.remove('visible'); } });
    [initialBtn, initialDropdown, initialLarge].forEach(function(el){ if (el) el.style.display = ''; });
    deleteBtn.style.display = 'none';
  }

  try { var saved = localStorage.getItem(storageKey); if (saved) showPhoto(saved); } catch(e) {}

  changeBtn.addEventListener('click', function(){ input.click(); });
  deleteBtn.addEventListener('click', function(){
    if (!window.confirm('Hapus foto profil?')) return;
    clearPhoto();
    try { localStorage.removeItem(storageKey); } catch(e) {}
  });
  input.addEventListener('change', function(){
    var file = input.files && input.files[0];
    if (!file) return;
    if (allowed.indexOf(file.type) === -1) {
      alert('Hanya format JPG, PNG, atau WEBP yang diperbolehkan.');
      input.value = '';
      return;
    }
    if (file.size > maxBytes) {
      alert('Ukuran foto maksimal 5 MB.');
      input.value = '';
      return;
    }
    var reader = new FileReader();
    reader.onload = function(e){
      var dataUrl = e.target.result;
      showPhoto(dataUrl);
      try { localStorage.setItem(storageKey, dataUrl); }
      catch(err){ alert('Foto berhasil ditampilkan, tetapi gagal disimpan di browser.'); }
      input.value = '';
    };
    reader.onerror = function(){ alert('Gagal membaca foto. Silakan coba lagi.'); input.value = ''; };
    reader.readAsDataURL(file);
  });
})();

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
      #reportDetailModal .modal-actions form button { border:1px solid var(--border); background:var(--panel-alt); color:var(--text); border-radius:7px; padding:7px 11px; font-size:11px; cursor:pointer; font-family:var(--body); }
      #detailActions .approve,
      #reportDetailModal .modal-actions .approve { border-color:var(--green); color:var(--green); }
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
    window.setTimeout(function(){ populateDetailActions(detailButton); }, 0);
  });
})();
</script>