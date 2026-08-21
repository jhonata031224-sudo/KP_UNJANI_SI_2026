@php($landingAccessGranted = session('pengaturan_umum_terverifikasi') === true)
<style id="admin-pengaturan-access-style">
.admin-access-overlay{position:fixed;inset:0;z-index:100500;display:flex;align-items:center;justify-content:center;padding:20px;box-sizing:border-box;background:rgba(15,23,42,.56);backdrop-filter:blur(5px);-webkit-backdrop-filter:blur(5px);opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease}
.admin-access-overlay.open{opacity:1;visibility:visible;pointer-events:auto}
.admin-access-card{width:560px;max-width:calc(100vw - 36px);box-sizing:border-box;background:#fff;color:#17212b;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 28px 72px rgba(0,0,0,.32);padding:30px 34px 28px;position:relative;transform:translateY(10px) scale(.985);transition:transform .2s ease}
.admin-access-overlay.open .admin-access-card{transform:none}
.admin-access-close{position:absolute;top:18px;right:18px;width:40px;height:40px;border:1px solid #dbe3ec;border-radius:10px;background:#fff;color:#64748b;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:transform .2s ease,border-color .2s ease,color .2s ease}
.admin-access-close:hover{transform:rotate(90deg);border-color:#cbd5e1;color:#17212b}
.admin-access-close svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.admin-access-icon{width:62px;height:62px;border-radius:16px;background:#fff3dc;color:#c4720a;display:flex;align-items:center;justify-content:center;margin-bottom:20px}
.admin-access-icon svg{width:30px;height:30px;stroke:currentColor;fill:none;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}
.admin-access-title{margin:0 52px 8px 0;font-family:var(--display);font-size:25px;line-height:1.2;font-weight:700;color:#17212b}
.admin-access-desc{margin:0 28px 22px 0;font-family:var(--body);font-size:14px;line-height:1.6;color:#64748b}
.admin-access-field{margin-top:16px}
.admin-access-label{display:block;margin:0 0 8px;font-family:var(--body);font-size:12px;font-weight:800;letter-spacing:.045em;text-transform:uppercase;color:#17212b}
.admin-access-input-wrap{position:relative}
.admin-access-input{width:100%;height:54px;box-sizing:border-box;border:1px solid #dbe3ec;border-radius:11px;background:#f8fafc;color:#17212b;font-family:var(--body);font-size:15px;padding:0 48px 0 52px;outline:none;transition:border-color .2s ease,box-shadow .2s ease}
.admin-access-input:focus{border-color:#c97a00;box-shadow:0 0 0 3px rgba(201,122,0,.12);background:#fff}
.admin-access-input::placeholder{color:#8a97a6}
.admin-access-input-wrap::before{content:"";position:absolute;left:16px;top:50%;width:22px;height:22px;transform:translateY(-50%);background-repeat:no-repeat;background-position:center;background-size:22px 22px;pointer-events:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2317212b' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='5' y='10' width='14' height='10' rx='2'/%3E%3Cpath d='M8 10V7a4 4 0 0 1 8 0v3'/%3E%3Ccircle cx='12' cy='15' r='1'/%3E%3Cpath d='M12 16v2'/%3E%3C/svg%3E")}
.admin-access-password-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);width:34px;height:34px;border:0;background:transparent;color:#64748b;display:flex;align-items:center;justify-content:center;cursor:pointer;border-radius:8px}
.admin-access-password-toggle:hover{background:#eef2f7;color:#17212b}
.admin-access-password-toggle svg{width:20px;height:20px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.admin-access-captcha-row{display:grid;grid-template-columns:minmax(0,1fr) 54px minmax(0,1fr);gap:10px;align-items:center}
.admin-access-captcha-image{width:100%;height:54px;display:block;object-fit:cover;border-radius:11px;border:1px solid #dbe3ec;background:#071b12}
.admin-access-refresh{width:54px;height:54px;border-radius:11px;border:1px solid #dbe3ec;background:#fff;color:#64748b;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:transform .2s ease,border-color .2s ease,color .2s ease,background .2s ease}
.admin-access-refresh:hover{border-color:#c97a00;color:#c97a00;background:#fffaf1}
.admin-access-refresh.spinning svg{animation:admin-access-spin .5s ease}
.admin-access-refresh svg{width:20px;height:20px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
@keyframes admin-access-spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}
.admin-access-captcha-input{min-width:0}
.admin-access-error{display:none;margin-top:10px;padding:10px 12px;border:1px solid rgba(175,55,46,.24);background:rgba(175,55,46,.07);color:#af372e;border-radius:9px;font-size:12px;line-height:1.45}
.admin-access-error.show{display:block}
.admin-access-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:24px}
.admin-access-btn{min-height:44px;border-radius:10px;padding:10px 18px;box-sizing:border-box;font-family:var(--body);font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;transition:transform .15s ease,background .2s ease,border-color .2s ease,color .2s ease}
.admin-access-btn:active{transform:scale(.98)}
.admin-access-btn.cancel{border:1px solid #dbe3ec;background:#fff;color:#64748b;min-width:86px}
.admin-access-btn.cancel:hover{border-color:#cbd5e1;background:#f8fafc;color:#17212b}
.admin-access-btn.submit{border:1px solid #c97a00;background:#d99a23;color:#111827;min-width:176px;box-shadow:0 5px 14px rgba(201,122,0,.18)}
.admin-access-btn.submit:hover{background:#cd8f1d;border-color:#cd8f1d}
.admin-access-btn.submit[disabled]{opacity:.65;cursor:not-allowed}
.admin-access-btn.submit svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
@media(max-width:620px){.admin-access-card{width:520px;max-width:calc(100vw - 24px);padding:26px 22px 24px}.admin-access-title{font-size:22px}.admin-access-desc{font-size:13.5px;margin-right:0}.admin-access-captcha-row{grid-template-columns:minmax(0,1fr) 50px minmax(0,1fr);gap:8px}.admin-access-refresh{width:50px;height:54px}.admin-access-actions{justify-content:stretch}.admin-access-btn{flex:1}.admin-access-btn.submit{min-width:0}}
@media(max-width:500px){.admin-access-captcha-row{grid-template-columns:minmax(0,1fr) 50px}.admin-access-captcha-input{grid-column:1/-1}.admin-access-actions{flex-direction:column-reverse}.admin-access-btn{width:100%}}
</style>
<div class="admin-access-overlay" id="adminPengaturanAccessModal" aria-hidden="true">
<div class="admin-access-card" role="dialog" aria-modal="true" aria-labelledby="adminAccessTitle">
<button type="button" class="admin-access-close" id="adminAccessClose" aria-label="Tutup"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
<div class="admin-access-icon"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><circle cx="12" cy="15" r="1"/><path d="M12 16v2"/></svg></div>
<h2 class="admin-access-title" id="adminAccessTitle">Konfirmasi Akses Pengaturan Umum</h2>
<p class="admin-access-desc">Menu ini berisi pengaturan yang dapat mengubah tampilan landing page. Masukkan password Admin dan captcha untuk melanjutkan.</p>
<div class="admin-access-field"><label class="admin-access-label" for="adminAccessPassword">Password Admin</label><div class="admin-access-input-wrap"><input id="adminAccessPassword" class="admin-access-input" type="password" autocomplete="current-password" placeholder="Masukkan password admin"><button type="button" class="admin-access-password-toggle" id="adminAccessToggle" aria-label="Tampilkan password"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></div>
<div class="admin-access-field"><label class="admin-access-label" for="adminAccessCaptcha">Captcha</label><div class="admin-access-captcha-row"><img id="adminAccessCaptchaImage" class="admin-access-captcha-image" alt="Captcha verifikasi akses"><button type="button" class="admin-access-refresh" id="adminAccessRefresh" aria-label="Muat ulang captcha"><svg viewBox="0 0 24 24"><path d="M20 11a8 8 0 0 0-14.9-3.8L3 10"/><path d="M3 4v6h6"/><path d="M4 13a8 8 0 0 0 14.9 3.8L21 14"/><path d="M21 20v-6h-6"/></svg></button><input id="adminAccessCaptcha" class="admin-access-input admin-access-captcha-input" type="text" inputmode="text" maxlength="5" autocomplete="off" placeholder="Ketik 5 karakter"></div></div>
<div class="admin-access-error" id="adminAccessError" role="alert"></div>
<div class="admin-access-actions"><button type="button" class="admin-access-btn cancel" id="adminAccessCancel">Batal</button><button type="button" class="admin-access-btn submit" id="adminAccessSubmit"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Verifikasi &amp; Buka</button></div>
</div></div>
<script>
(function(){
var granted=@json($landingAccessGranted),verifyUrl=@json(route('admin.pengaturan.access')),captchaUrl=@json(route('admin.pengaturan.access-captcha'));
var modal=document.getElementById('adminPengaturanAccessModal'),pass=document.getElementById('adminAccessPassword'),captcha=document.getElementById('adminAccessCaptcha'),img=document.getElementById('adminAccessCaptchaImage'),refresh=document.getElementById('adminAccessRefresh'),error=document.getElementById('adminAccessError'),submit=document.getElementById('adminAccessSubmit'),token=document.querySelector('meta[name="csrf-token"]'),lockStyle=null;
if(!modal)return;
function setError(msg){error.textContent=msg||'';error.classList.toggle('show',!!msg)}
function lockPanel(){if(lockStyle||granted)return;lockStyle=document.createElement('style');lockStyle.id='adminPengaturanAccessPanelLock';lockStyle.textContent='[data-tab-panel="pengaturan-umum"]{display:none!important;}';document.head.appendChild(lockStyle)}
function unlockPanel(){if(lockStyle){lockStyle.remove();lockStyle=null}}
function refreshCaptcha(){if(!img)return;refresh.classList.remove('spinning');void refresh.offsetWidth;refresh.classList.add('spinning');setError('');img.src=captchaUrl+'?t='+Date.now()}
function openModal(){modal.classList.add('open');modal.setAttribute('aria-hidden','false');setError('');refreshCaptcha();setTimeout(function(){pass.focus()},80)}
function closeModal(){modal.classList.remove('open');modal.setAttribute('aria-hidden','true');pass.value='';captcha.value='';setError('')}
function activateSettingTab(){unlockPanel();document.querySelectorAll('[data-tab-panel]').forEach(function(p){p.classList.remove('active')});document.querySelectorAll('[data-tab-link]').forEach(function(l){l.classList.remove('active')});document.querySelectorAll('[data-tab-panel="pengaturan-umum"]').forEach(function(p){p.classList.add('active')});document.querySelectorAll('[data-tab-link="pengaturan-umum"]').forEach(function(l){l.classList.add('active')})}
function revoke(){if(!granted)return;granted=false;lockPanel();fetch(verifyUrl,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token?token.content:''},body:JSON.stringify({action:'revoke'})}).catch(function(){})}
function verify(){var pw=pass.value.trim(),cp=captcha.value.trim();if(!pw||cp.length!==5){setError('Masukkan password Admin dan 5 karakter captcha.');return}submit.disabled=true;setError('');fetch(verifyUrl,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token?token.content:''},body:JSON.stringify({action:'verify',password:pw,captcha:cp})}).then(function(r){return r.json().catch(function(){return {ok:false,message:'Respons server tidak valid.'}}).then(function(data){if(!r.ok||!data.ok)throw new Error(data.message||'Verifikasi gagal.');granted=true;unlockPanel();closeModal();activateSettingTab()})}).catch(function(err){setError(err.message||'Verifikasi gagal.');refreshCaptcha()}).finally(function(){submit.disabled=false})}
document.addEventListener('click',function(e){var target=e.target;var settingLink=target.closest&&target.closest('[data-tab-link="pengaturan-umum"]');if(settingLink&&!granted){e.preventDefault();e.stopImmediatePropagation();openModal();return}if(granted&&target.closest&&target.closest('[data-tab-link]')&&!target.closest('[data-tab-link="pengaturan-umum"]'))revoke()},true);
refresh.addEventListener('click',refreshCaptcha);submit.addEventListener('click',verify);pass.addEventListener('keydown',function(e){if(e.key==='Enter')verify()});captcha.addEventListener('keydown',function(e){if(e.key==='Enter')verify()});document.getElementById('adminAccessToggle').addEventListener('click',function(){pass.type=pass.type==='password'?'text':'password'});document.getElementById('adminAccessClose').addEventListener('click',closeModal);document.getElementById('adminAccessCancel').addEventListener('click',closeModal);modal.addEventListener('click',function(e){if(e.target===modal)closeModal()});document.addEventListener('keydown',function(e){if(e.key==='Escape'&&modal.classList.contains('open'))closeModal()});
var panel=document.querySelector('[data-tab-panel="pengaturan-umum"]');if(panel&&window.MutationObserver){var observer=new MutationObserver(function(){if(granted&&!panel.classList.contains('active'))revoke();});observer.observe(panel,{attributes:true,attributeFilter:['class']})}
if(!granted)lockPanel();
img.addEventListener('error',function(){setError('Captcha tidak dapat dimuat. Klik refresh captcha.');img.removeAttribute('src')});
})();
</script>
