{{--
  Banner PERSISTEN Mode Maintenance untuk pengguna non-Admin. Hanya
  di-include lewat InjectMaintenanceUi (middleware) saat
  Pengaturan::current()->mode_maintenance_aktif TRUE dan role pengguna
  BUKAN Admin -- Admin tidak pernah melihat banner ini dan tetap bekerja
  normal.

  PENTING: script di bawah ini HANYA lapisan UX (feedback instan +
  cegah klik tombol yang jelas-jelas mengubah data) supaya sesuai
  permintaan "tombol tetap terlihat tapi disabled". Proteksi
  SESUNGGUHNYA ada di server lewat middleware EnforceMaintenanceMode --
  kalau script ini gagal jalan/di-bypass sekalipun, request tetap
  ditolak di server.
--}}
<style>
  .siberad-maintenance-banner{
    display:flex;align-items:center;gap:10px;
    padding:10px 18px;
    background:var(--amber-dim,rgba(224,168,58,.15));
    color:var(--amber,#a4700a);
    border-bottom:1px solid rgba(164,112,10,.3);
    font-family:var(--body);font-size:12.5px;font-weight:600;
    line-height:1.5;
  }
  .siberad-maintenance-banner svg{flex:0 0 auto;width:18px;height:18px;}
  .siberad-maintenance-banner span{flex:1 1 auto;}
  .siberad-maintenance-disabled{opacity:.5!important;cursor:not-allowed!important;filter:grayscale(.15);}
  @media(max-width:640px){.siberad-maintenance-banner{padding:9px 14px;font-size:11.5px;}}
</style>
<div class="siberad-maintenance-banner" role="alert" data-siberad-maintenance-banner>
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
  <span>⚠️ {{ $pesanMaintenance }}</span>
</div>
<script>
(function(){
  // Banner ini disisipkan tepat sebelum penutup body (titik sisip yang aman
  // -- lihat catatan di InjectMaintenanceUi.php), bukan di awal body, supaya
  // tidak pernah salah menabrak komentar/string di bagian head yang
  // kebetulan menyebut kata "body". Supaya tetap TAMPIL PALING ATAS
  // (persisten, bukan nempel di bawah konten), elemennya dipindah jadi anak
  // PERTAMA body di sini, secepatnya begitu script ini jalan (masih dalam
  // parsing dokumen yang sama, jadi tidak sempat kelihatan "loncat").
  var bannerEl = document.currentScript && document.currentScript.previousElementSibling;
  if (bannerEl && bannerEl.hasAttribute && bannerEl.hasAttribute('data-siberad-maintenance-banner') && document.body.firstChild !== bannerEl) {
    document.body.insertBefore(bannerEl, document.body.firstChild);
  }

  var PESAN_MAINTENANCE = {!! json_encode($pesanMaintenance) !!};
  var MUTATING = ['POST','PUT','PATCH','DELETE'];

  // Route/URL yang SENGAJA dikecualikan -- harus SAMA PERSIS dengan
  // ROUTE_TERKECUALI di app/Http/Middleware/EnforceMaintenanceMode.php,
  // supaya tombol yang di sisi server memang tetap diizinkan (login,
  // logout, housekeeping notifikasi milik sendiri, dst) tidak ikut
  // didisable/diblok di sisi klien.
  var EXCLUDED_PATTERNS = [
    /^\/login\/?$/i,
    /^\/logout\/?$/i,
    /^\/captcha\//i,
    /^\/permintaan-reset-password\/?$/i,
    /^\/notifikasi(\/.*)?$/i,
    /^\/push\/(subscribe|unsubscribe)\/?$/i
  ];

  function pathOf(url){
    try{ return new URL(url, window.location.origin).pathname; }
    catch(e){ return String(url||''); }
  }
  function isExcluded(url){
    var path = pathOf(url);
    for (var i=0;i<EXCLUDED_PATTERNS.length;i++){ if (EXCLUDED_PATTERNS[i].test(path)) return true; }
    return false;
  }
  function isSameOrigin(url){
    try{ return new URL(url, window.location.origin).origin === window.location.origin; }
    catch(e){ return true; }
  }
  function effectiveFormMethod(form){
    var hidden = form.querySelector('input[name="_method"]');
    if (hidden && hidden.value) return hidden.value.toUpperCase();
    return (form.getAttribute('method') || 'GET').toUpperCase();
  }
  function notify(){
    window.siberadShowToast && window.siberadShowToast('error', PESAN_MAINTENANCE);
  }

  // ---- 1) Tandai & disable tombol submit pada form yang mengubah data ----
  function markForm(form){
    if (!form || form.dataset.maintenanceLocked === '1') return;
    var method = effectiveFormMethod(form);
    if (MUTATING.indexOf(method) === -1) return;
    var action = form.getAttribute('action') || window.location.href;
    if (isExcluded(action)) return;
    form.dataset.maintenanceLocked = '1';
    form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])').forEach(function(btn){
      btn.disabled = true;
      btn.setAttribute('title', 'Tidak dapat dilakukan selama maintenance.');
      btn.classList.add('siberad-maintenance-disabled');
    });
  }
  function scan(root){
    if (!root || !root.querySelectorAll) return;
    root.querySelectorAll('form').forEach(markForm);
  }
  scan(document);
  new MutationObserver(function(mutations){
    mutations.forEach(function(m){
      (m.addedNodes || []).forEach(function(node){
        if (node.nodeType !== 1) return;
        if (node.tagName === 'FORM') markForm(node);
        scan(node);
      });
    });
  }).observe(document.documentElement, {childList:true, subtree:true});

  // ---- 2) Jaring pengaman submit form (kalau tombolnya sempat ke-enable
  //         lagi oleh script lain, atau elemen baru belum sempat discan) ----
  document.addEventListener('submit', function(e){
    var form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    var method = effectiveFormMethod(form);
    if (MUTATING.indexOf(method) === -1) return;
    var action = form.getAttribute('action') || window.location.href;
    if (isExcluded(action)) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    notify();
  }, true);

  // ---- 3) Banyak aksi (Kirim/Konfirmasi/Disposisi/dsb) di sistem ini
  //         TIDAK lewat native form submit, tapi fetch() langsung dari
  //         script masing-masing fitur. Intercept di layer fetch supaya
  //         tetap dapat feedback instan tanpa menyentuh puluhan file JS
  //         satu-satu. Pemblokiran SEBENARNYA tetap di server. ----
  var originalFetch = window.fetch;
  if (typeof originalFetch === 'function') {
    window.fetch = function(input, init){
      try {
        var url = (typeof input === 'string') ? input : (input && input.url) || '';
        var method = ((init && init.method) || (input && input.method) || 'GET').toUpperCase();
        if (MUTATING.indexOf(method) !== -1 && isSameOrigin(url) && !isExcluded(url)) {
          notify();
          return Promise.resolve(new Response(JSON.stringify({message: PESAN_MAINTENANCE}), {
            status: 423, headers: {'Content-Type': 'application/json'}
          }));
        }
      } catch (err) { /* kalau deteksi gagal, biarkan lolos -- server tetap menolak */ }
      return originalFetch.apply(this, arguments);
    };
  }
})();
</script>
