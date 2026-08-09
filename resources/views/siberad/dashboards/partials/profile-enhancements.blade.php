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
</script>