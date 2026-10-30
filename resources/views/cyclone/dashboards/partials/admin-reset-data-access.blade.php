{{-- Modal akses menu "Reset Data Laporan" (Admin). Tampilan/CSS memakai kelas
     .admin-access-* dari admin-pengaturan-access.blade.php (disuntik bersamaan
     oleh InjectPengaturanAccessUi). Pola sama dengan Pengaturan Umum:
     password + captcha, dicabut saat pindah ke menu lain. --}}
@php($resetAccessGranted = session('reset_data_terverifikasi') === true)
<div class="admin-access-overlay" id="adminResetAccessModal" aria-hidden="true">
<div class="admin-access-card" role="dialog" aria-modal="true" aria-labelledby="adminResetAccessTitle">
<button type="button" class="admin-access-close" id="adminResetAccessClose" aria-label="Tutup"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
<div class="admin-access-icon"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><circle cx="12" cy="15" r="1"/><path d="M12 16v2"/></svg></div>
<h2 class="admin-access-title" id="adminResetAccessTitle">Konfirmasi Akses Reset Data Laporan</h2>
<p class="admin-access-desc">Menu ini dapat menghapus data laporan secara permanen. Masukkan password dan captcha untuk melanjutkan.</p>
<div class="admin-access-field"><label class="admin-access-label" for="adminResetAccessPassword">Password</label><div class="admin-access-input-wrap"><input id="adminResetAccessPassword" name="reset_data_access_password" class="admin-access-input" type="password" autocomplete="new-password" placeholder="Masukkan password"><button type="button" class="admin-access-password-toggle" id="adminResetAccessToggle" aria-label="Tampilkan password"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></div>
<div class="admin-access-field"><label class="admin-access-label" for="adminResetAccessCaptcha">Captcha</label><div class="admin-access-captcha-row"><img id="adminResetAccessCaptchaImage" class="admin-access-captcha-image" alt="Captcha verifikasi akses"><button type="button" class="admin-access-refresh" id="adminResetAccessRefresh" aria-label="Muat ulang captcha"><svg viewBox="0 0 24 24"><path d="M20 11a8 8 0 0 0-14.9-3.8L3 10"/><path d="M3 4v6h6"/><path d="M4 13a8 8 0 0 0 14.9 3.8L21 14"/><path d="M21 20v-6h-6"/></svg></button><input id="adminResetAccessCaptcha" name="reset_data_access_captcha" class="admin-access-input admin-access-captcha-input" type="text" inputmode="text" maxlength="5" autocomplete="off" placeholder="Masukan captcha"></div></div>
<div class="admin-access-error" id="adminResetAccessError" role="alert"></div>
<div class="admin-access-actions"><button type="button" class="admin-access-btn cancel" id="adminResetAccessCancel">Batal</button><button type="button" class="admin-access-btn submit" id="adminResetAccessSubmit"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Verifikasi &amp; Buka</button></div>
</div></div>
<script>
(function(){
var granted=@json($resetAccessGranted),verifyUrl=@json(route('admin.reset-data-laporan.access')),captchaUrl=@json(route('admin.pengaturan.access-captcha'));
var TAB='reset-data-laporan';
var modal=document.getElementById('adminResetAccessModal'),pass=document.getElementById('adminResetAccessPassword'),captcha=document.getElementById('adminResetAccessCaptcha'),img=document.getElementById('adminResetAccessCaptchaImage'),refresh=document.getElementById('adminResetAccessRefresh'),error=document.getElementById('adminResetAccessError'),submit=document.getElementById('adminResetAccessSubmit'),lockStyle=null,pendingLink=null;
var csrfMeta=document.querySelector('meta[name="csrf-token"]');
var csrfToken=csrfMeta?csrfMeta.content:@json(csrf_token());
if(!modal)return;
function syncCsrfToken(t){if(!t)return;csrfToken=t;if(csrfMeta)csrfMeta.content=t;document.querySelectorAll('input[name="_token"]').forEach(function(i){i.value=t})}
function setError(m){error.textContent=m||'';error.classList.toggle('show',!!m)}
function lockPanel(){if(lockStyle||granted)return;lockStyle=document.createElement('style');lockStyle.id='adminResetAccessPanelLock';lockStyle.textContent='[data-tab-panel="'+TAB+'"]{display:none!important;}';document.head.appendChild(lockStyle)}
function unlockPanel(){if(lockStyle){lockStyle.remove();lockStyle=null}}
function refreshCaptcha(){refresh.classList.remove('spinning');void refresh.offsetWidth;refresh.classList.add('spinning');setError('');captcha.value='';img.src=captchaUrl+'?t='+Date.now()+(window.matchMedia&&window.matchMedia('(max-width:620px)').matches?'&c=1'+(img.clientWidth&&img.clientHeight?'&r='+(img.clientWidth/img.clientHeight).toFixed(2):''):'');captcha.focus()}
function openModal(){pass.value='';captcha.value='';pass.type='password';modal.classList.add('open');modal.setAttribute('aria-hidden','false');setError('');refreshCaptcha();setTimeout(function(){pass.value='';pass.focus()},80)}
function closeModal(){modal.classList.remove('open');modal.setAttribute('aria-hidden','true');pass.value='';captcha.value='';pass.type='password';setError('')}
function post(body){return fetch(verifyUrl,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},body:JSON.stringify(body)})}
function revoke(){if(!granted)return;granted=false;lockPanel();post({action:'revoke'}).catch(function(){})}
function bukaTab(){var link=pendingLink||document.querySelector('[data-tab-link="'+TAB+'"]');pendingLink=null;if(link)link.click()}
function verify(){var pw=pass.value.trim(),cp=captcha.value.trim();if(!pw||cp.length!==5){setError('Masukkan password dan 5 karakter captcha.');return}submit.disabled=true;setError('');post({action:'verify',password:pw,captcha:cp}).then(function(r){return r.json().catch(function(){return {ok:false,message:'Respons server tidak valid.'}}).then(function(d){if(!r.ok||!d.ok)throw new Error(d.message||'Verifikasi gagal.');syncCsrfToken(d.csrf_token);granted=true;unlockPanel();closeModal();bukaTab()})}).catch(function(err){var m=err.message||'Verifikasi gagal.';refreshCaptcha();setError(m)}).finally(function(){submit.disabled=false})}
document.addEventListener('click',function(e){var t=e.target;if(!t.closest)return;var link=t.closest('[data-tab-link="'+TAB+'"]');if(link&&!granted){e.preventDefault();e.stopImmediatePropagation();pendingLink=link;openModal();return}if(granted&&t.closest('[data-tab-link]')&&!link)revoke()},true);
refresh.addEventListener('click',refreshCaptcha);submit.addEventListener('click',verify);
pass.addEventListener('keydown',function(e){if(e.key==='Enter')verify()});captcha.addEventListener('keydown',function(e){if(e.key==='Enter')verify()});
document.getElementById('adminResetAccessToggle').addEventListener('click',function(){pass.type=pass.type==='password'?'text':'password'});
document.getElementById('adminResetAccessClose').addEventListener('click',closeModal);document.getElementById('adminResetAccessCancel').addEventListener('click',closeModal);
modal.addEventListener('click',function(e){if(e.target===modal)closeModal()});
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&modal.classList.contains('open'))closeModal()});
var panel=document.querySelector('[data-tab-panel="'+TAB+'"]');if(panel&&window.MutationObserver){new MutationObserver(function(){if(granted&&!panel.classList.contains('active'))revoke()}).observe(panel,{attributes:true,attributeFilter:['class']})}
if(!granted)lockPanel();
img.addEventListener('error',function(){setError('Captcha tidak dapat dimuat. Klik refresh captcha.');img.removeAttribute('src')});
})();
</script>
