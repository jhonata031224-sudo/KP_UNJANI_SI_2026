{{--
  Banner PERSISTEN Mode Maintenance untuk pengguna non-Admin. Di-include
  lewat InjectMaintenanceUi (middleware) ke SEMUA halaman pengguna non-Admin
  -- banner `hidden` selagi maintenance mati, dan skrip di bawah polling
  /maintenance/status (MaintenanceStatusController) sehingga saat Admin
  mengaktifkan/menonaktifkan Mode Maintenance, banner + penguncian tombol
  ikut menyala/mati REALTIME tanpa refresh. Admin tidak pernah melihat
  banner ini dan tetap bekerja normal.

  PENTING: script di bawah ini HANYA lapisan UX (feedback instan +
  cegah klik tombol yang jelas-jelas mengubah data) supaya sesuai
  permintaan "tombol tetap terlihat tapi disabled". Proteksi
  SESUNGGUHNYA ada di server lewat middleware EnforceMaintenanceMode --
  kalau script ini gagal jalan/di-bypass sekalipun, request tetap
  ditolak di server.
--}}
<style>
  .siberad-maintenance-banner{
    position:fixed;top:0;left:0;right:0;z-index:100050;
    display:flex;align-items:center;gap:10px;
    padding:11px 20px;
    /* SOLID: amber tipis di atas warna dasar halaman. Sebelumnya cuma
       var(--amber-dim) yang transparan (~14%) sehingga topbar di belakang
       banner tembus & kelihatan menumpuk. */
    background:linear-gradient(var(--amber-dim,rgba(224,168,58,.15)),var(--amber-dim,rgba(224,168,58,.15))),var(--bg,#f5f2e7);
    color:var(--amber,#a4700a);
    border-bottom:1px solid rgba(164,112,10,.3);
    font-family:var(--body);font-size:12.5px;font-weight:600;
    line-height:1.5;cursor:pointer;box-sizing:border-box;
    transition:background .15s ease;
  }
  .siberad-maintenance-banner:hover,.siberad-maintenance-banner:focus-visible{background:rgba(164,112,10,.22);outline:none;}
  .siberad-maintenance-banner .mm-icon{flex:0 0 auto;width:19px;height:19px;}
  .siberad-maintenance-banner .mm-text{flex:1 1 auto;min-width:0;overflow-wrap:anywhere;}
  .siberad-maintenance-banner .mm-detail{flex:0 0 auto;display:flex;align-items:center;gap:4px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;opacity:.85;white-space:nowrap;}
  .siberad-maintenance-banner .mm-detail svg{width:14px;height:14px;}
  /* .shell didorong turun sebesar tinggi banner (variabel --mm-banner-h,
     diisi script di bawah), dan .sidebar (sticky/fixed di top:0) digeser
     sama besar supaya logo-nya tidak nyelip di bawah banner saat di-scroll.

     .topbar: di halaman dashboard, public/js/siberad-fixed-header.js
     (disuntik InjectDashboardUi) membuat '.main > .topbar' position:fixed
     top:0 + memberi '.main{padding-top:82px}' sebagai ruang untuknya.
     Versi sebelumnya di sini MEMAKSA topbar jadi position:sticky, yang
     mengeluarkannya dari mode fixed -> topbar masuk kembali ke alur
     layout DAN padding 82px milik .main tetap ada, jadi seluruh isi
     halaman (topbar + konten) terdorong turun setinggi topbar (celah
     kosong di bawah banner). Sekarang position TIDAK disentuh sama
     sekali; cukup geser 'top' supaya topbar berhenti tepat di bawah
     banner (bukan tertimpa). Selector sengaja sama-sama '.main > .topbar'
     seperti aturan fixed-header supaya hanya kena topbar yang itu. Nilai
     default 0px = tidak mengubah apa pun kalau script belum jalan. */
  html.siberad-mm-on .shell{padding-top:var(--mm-banner-h,0px)!important;}
  html.siberad-mm-on body .shell .main > .topbar{top:var(--mm-banner-h,0px)!important;}
  html.siberad-mm-on .sidebar{top:var(--mm-banner-h,0px)!important;height:calc(100vh - var(--mm-banner-h,0px))!important;}
  .siberad-maintenance-banner[hidden]{display:none!important;}
  .siberad-maintenance-disabled{opacity:.5!important;cursor:not-allowed!important;filter:grayscale(.15);}
  @media(max-width:640px){.siberad-maintenance-banner{padding:9px 14px;font-size:11.5px;}.siberad-maintenance-banner .mm-detail span{display:none;}}
</style>
<div class="siberad-maintenance-banner" data-siberad-maintenance-banner role="button" tabindex="0" aria-label="Lihat detail pemeliharaan sistem"@unless($aktifMaintenance ?? false) hidden @endunless>
  <svg class="mm-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
  <span class="mm-text">⚠️ {{ $pesanMaintenance }}</span>
  <span class="mm-detail"><span>Lihat detail</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"></path></svg></span>
</div>
<script>
(function(){
  // Banner ini disisipkan tepat sebelum penutup body (titik sisip yang aman
  // -- lihat catatan di InjectMaintenanceUi.php), lalu ditampilkan lewat
  // position:fixed (BUKAN mengandalkan urutan/pemindahan node di DOM seperti
  // percobaan sebelumnya) supaya PASTI selebar viewport, tidak pernah ikut
  // sempit ke lebar kolom sidebar/manapun. Konten di bawahnya (.shell)
  // didorong turun lewat padding-top yang dihitung dinamis dari tinggi
  // banner ini sendiri, supaya tetap benar walau teks pesan panjang &
  // banner jadi 2 baris di layar kecil.
  var bannerEl = document.currentScript && document.currentScript.previousElementSibling;
  var textEl = bannerEl ? bannerEl.querySelector('.mm-text') : null;
  var root = document.documentElement;

  // ---- State maintenance (disinkronkan realtime lewat polling) ----
  var aktif = {!! json_encode((bool) ($aktifMaintenance ?? false)) !!};
  var PESAN_MAINTENANCE = {!! json_encode($pesanMaintenance) !!};
  // Waktu "Dikirim" di modal -- diambil dari notifikasi pemeliharaan yang sama
  // dengan yang tampil di lonceng (lihat InjectMaintenanceUi::waktuPengumumanMaintenance).
  var WAKTU_MAINTENANCE = {!! json_encode($waktuMaintenance ?? null) !!};
  var STATUS_URL = {!! json_encode(route('maintenance.status')) !!};
  var JUDUL_TOMBOL = 'Tidak dapat dilakukan selama maintenance.';

  function sesuaikanOffsetKonten(){
    if (!bannerEl) return;
    var tinggi = aktif ? bannerEl.offsetHeight : 0;
    root.style.setProperty('--mm-banner-h', tinggi + 'px');
    // .shell sudah didorong lewat aturan CSS (var --mm-banner-h). Halaman
    // tanpa .shell (layout lain) pakai padding body sebagai cadangan.
    if (!document.querySelector('.shell')) document.body.style.paddingTop = aktif ? (tinggi + 'px') : '';
  }
  window.addEventListener('resize', sesuaikanOffsetKonten);

  // Klik/Enter pada banner -> buka modal "Pengumuman" yang SAMA PERSIS
  // dipakai sistem notifikasi existing (lihat notification-controls.
  // blade.php, window.siberadTampilkanPengumuman) supaya pesan pemeliharaan
  // konsisten di banner MAUPUN lonceng notifikasi -- bukan tampilan ke-2
  // yang beda gaya. Kalau fungsinya entah kenapa belum siap (partial
  // notification-controls belum ke-load), klik banner tidak melakukan
  // apa-apa -- teks pesan di banner sendiri tetap kebaca tanpa modal.
  if (bannerEl) {
    var bukaDetailPemeliharaan = function(){
      if (typeof window.siberadTampilkanPengumuman === 'function') {
        window.siberadTampilkanPengumuman('Sistem Dalam Pemeliharaan', PESAN_MAINTENANCE, 'maintenance', WAKTU_MAINTENANCE);
      }
    };
    bannerEl.addEventListener('click', bukaDetailPemeliharaan);
    bannerEl.addEventListener('keydown', function(e){
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); bukaDetailPemeliharaan(); }
    });
  }

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
  // ---- Anti-banjir toast ----
  // Saat maintenance, satu klik pada aksi yang diblok dulu memunculkan DUA
  // toast: toast maintenance dari sini + toast error bawaan fitur itu sendiri
  // (mis. "Gagal mengkonfirmasi surat. Coba lagi." di .catch masing-masing
  // fitur, karena respons 423 dianggap gagal). Klik berulang membuat toast
  // menumpuk beruntun. Solusinya dipusatkan di sini (berlaku ke SEMUA fitur
  // & semua pengguna non-Admin, tanpa menyentuh tiap file fitur):
  //   1) toast maintenance di-throttle: maksimal 1 per TOAST_JEDA_MS;
  //   2) selama jendela "baru saja diblok" (blokirSampai), toast ERROR lain
  //      yang bukan pesan maintenance ditahan -- itu hanya efek samping
  //      dari aksi yang memang sengaja kita blok, bukan error sungguhan.
  var TOAST_JEDA_MS   = 3200;  // sedikit > umur toast (3 dtk) di dash-styles
  var BLOKIR_JENDELA  = 1500;  // jendela penahan toast error bawaan fitur
  var toastAsliFn     = null;  // siberadShowToast asli (sebelum dibungkus)
  var terakhirNotify  = 0;
  var blokirSampai    = 0;

  // Bungkus window.siberadShowToast sekali saja. Dipanggil lazy karena
  // fungsi aslinya bisa saja baru terdefinisi setelah skrip ini jalan.
  function pasangFilterToast(){
    var fn = window.siberadShowToast;
    if (typeof fn !== 'function' || fn.__mmFilter) return;
    toastAsliFn = fn;
    var pembungkus = function(type, message){
      if (type === 'error' && Date.now() < blokirSampai && message !== PESAN_MAINTENANCE) {
        return; // efek samping aksi yang diblok maintenance -- tahan
      }
      return fn.apply(this, arguments);
    };
    pembungkus.__mmFilter = true;
    window.siberadShowToast = pembungkus;
  }

  function notify(){
    var sekarang = Date.now();
    blokirSampai = sekarang + BLOKIR_JENDELA;   // selalu tahan toast error fitur
    pasangFilterToast();
    if (sekarang - terakhirNotify < TOAST_JEDA_MS) return; // sudah tampil, jangan numpuk
    terakhirNotify = sekarang;
    var tampil = toastAsliFn || window.siberadShowToast;
    if (tampil) tampil('error', PESAN_MAINTENANCE);
  }

  // ---- 1) Tandai & disable tombol submit pada form yang mengubah data ----
  // Hanya tombol yang BELUM disabled yang kita kunci (ditandai data-mm-locked)
  // -- supaya saat maintenance dimatikan, cuma tombol yang kita kunci yang
  // dibuka lagi (tombol yang memang disabled oleh fitur lain tetap disabled).
  function markForm(form){
    if (!aktif || !form || form.dataset.maintenanceLocked === '1') return;
    var method = effectiveFormMethod(form);
    if (MUTATING.indexOf(method) === -1) return;
    var action = form.getAttribute('action') || window.location.href;
    if (isExcluded(action)) return;
    form.dataset.maintenanceLocked = '1';
    form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type]), button[data-mm-aksi]').forEach(function(btn){
      if (btn.disabled) return;
      btn.dataset.mmLocked = '1';
      btn.dataset.mmTitle = btn.getAttribute('title') || '';
      btn.disabled = true;
      btn.setAttribute('title', JUDUL_TOMBOL);
      btn.classList.add('siberad-maintenance-disabled');
    });
  }
  function scan(root){
    if (!root || !root.querySelectorAll) return;
    root.querySelectorAll('form').forEach(markForm);
  }
  // Buka kunci SEMUA tombol/form yang tadi dikunci skrip ini (maintenance mati).
  function unlockAll(){
    document.querySelectorAll('[data-mm-locked="1"]').forEach(function(btn){
      btn.disabled = false;
      btn.classList.remove('siberad-maintenance-disabled');
      if (btn.dataset.mmTitle) btn.setAttribute('title', btn.dataset.mmTitle);
      else if (btn.getAttribute('title') === JUDUL_TOMBOL) btn.removeAttribute('title');
      delete btn.dataset.mmLocked;
      delete btn.dataset.mmTitle;
    });
    document.querySelectorAll('form[data-maintenance-locked="1"]').forEach(function(form){
      delete form.dataset.maintenanceLocked;
    });
  }

  // Observer form baru HANYA hidup selagi maintenance aktif (hemat: tidak
  // ada pengamatan DOM sama sekali saat sistem normal).
  var observer = null;
  function mulaiObserver(){
    if (observer) return;
    observer = new MutationObserver(function(mutations){
      if (!aktif) return;
      mutations.forEach(function(m){
        // Kode fitur kerap memanggil `tombol.disabled = false` (mis. saat modal
        // dibuka ulang). Selagi maintenance, kembalikan kuncinya seketika --
        // hanya untuk tombol yang memang kita kunci (data-mm-locked).
        if (m.type === 'attributes') {
          var t = m.target;
          if (t && t.dataset && t.dataset.mmLocked === '1' && !t.disabled) t.disabled = true;
          return;
        }
        (m.addedNodes || []).forEach(function(node){
          if (node.nodeType !== 1) return;
          if (node.tagName === 'FORM') markForm(node);
          scan(node);
        });
      });
    });
    observer.observe(document.documentElement, {childList:true, subtree:true, attributes:true, attributeFilter:['disabled']});
  }
  function hentikanObserver(){
    if (observer) { observer.disconnect(); observer = null; }
  }

  // ---- 2) Jaring pengaman submit form (kalau tombolnya sempat ke-enable
  //         lagi oleh script lain, atau elemen baru belum sempat discan) ----
  document.addEventListener('submit', function(e){
    if (!aktif) return;
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

  // ---- 2b) `form.submit()` dari JavaScript TIDAK memicu event 'submit', jadi
  //          lolos dari jaring pengaman di atas (dipakai ±30 tempat: Kirim
  //          Balasan, Teruskan, dst). Bungkus di level prototype supaya SEMUA
  //          pemanggil terlindungi tanpa mengubah file fiturnya satu per satu. ----
  var submitAsli = HTMLFormElement.prototype.submit;
  HTMLFormElement.prototype.submit = function(){
    try {
      if (aktif && MUTATING.indexOf(effectiveFormMethod(this)) !== -1) {
        var act = this.getAttribute('action') || window.location.href;
        if (!isExcluded(act)) { notify(); return; }
      }
    } catch (err) { /* kalau deteksi gagal, biarkan lolos -- server tetap menolak */ }
    return submitAsli.apply(this, arguments);
  };

  // ---- 3) Banyak aksi (Kirim/Konfirmasi/Disposisi/dsb) di sistem ini
  //         TIDAK lewat native form submit, tapi fetch() langsung dari
  //         script masing-masing fitur. Intercept di layer fetch supaya
  //         tetap dapat feedback instan tanpa menyentuh puluhan file JS
  //         satu-satu. Pemblokiran SEBENARNYA tetap di server. Intercept
  //         hanya berlaku selagi `aktif` TRUE. ----
  var originalFetch = window.fetch;
  if (typeof originalFetch === 'function') {
    window.fetch = function(input, init){
      try {
        if (aktif) {
          var url = (typeof input === 'string') ? input : (input && input.url) || '';
          var method = ((init && init.method) || (input && input.method) || 'GET').toUpperCase();
          if (MUTATING.indexOf(method) !== -1 && isSameOrigin(url) && !isExcluded(url)) {
            notify();
            return Promise.resolve(new Response(JSON.stringify({message: PESAN_MAINTENANCE}), {
              status: 423, headers: {'Content-Type': 'application/json'}
            }));
          }
        }
      } catch (err) { /* kalau deteksi gagal, biarkan lolos -- server tetap menolak */ }

      // Celah: Admin BARU SAJA menyalakan maintenance dan polling belum sempat
      // memutakhirkan `aktif`, jadi request lolos ke server dan dijawab 423.
      // Tanpa ini, fitur memunculkan toast error-nya sendiri + (setelah polling)
      // toast maintenance -> dobel. Di sini 423 diperlakukan sama dengan blokir
      // sisi klien: satu toast maintenance, toast error fitur ditahan, dan
      // status langsung disinkronkan (banner + kunci tombol) tanpa nunggu 4 dtk.
      var argumen = arguments;
      var metodeReq = 'GET', urlReq = '';
      try {
        urlReq = (typeof input === 'string') ? input : (input && input.url) || '';
        metodeReq = ((init && init.method) || (input && input.method) || 'GET').toUpperCase();
      } catch (err) {}
      return originalFetch.apply(this, argumen).then(function(res){
        try {
          if (res && res.status === 423 && MUTATING.indexOf(metodeReq) !== -1
              && isSameOrigin(urlReq) && !isExcluded(urlReq)) {
            notify();                // 1 toast + tahan toast error fitur
            poll();                  // sinkron status: nyalakan banner + kunci tombol
          }
        } catch (err) { /* abaikan -- jangan ganggu alur fetch fitur */ }
        return res;
      });
    };
  }

  // ---- 4) Terapkan status ke UI (dipakai saat load awal & tiap perubahan
  //         dari polling). Tanpa reload: banner muncul/hilang, offset konten
  //         disesuaikan, tombol dikunci/dibuka. ----
  function terapkan(aktifBaru, pesanBaru, waktuBaru, tampilkanToast){
    var berubahAktif = aktifBaru !== aktif;
    var berubahPesan = aktifBaru && typeof pesanBaru === 'string' && pesanBaru !== PESAN_MAINTENANCE;
    if (!berubahAktif && !berubahPesan) {
      if (aktifBaru && waktuBaru) WAKTU_MAINTENANCE = waktuBaru;
      return;
    }

    aktif = aktifBaru;
    if (typeof pesanBaru === 'string' && pesanBaru !== '') PESAN_MAINTENANCE = pesanBaru;
    if (aktifBaru && waktuBaru) WAKTU_MAINTENANCE = waktuBaru;
    if (!aktifBaru) WAKTU_MAINTENANCE = null;

    if (textEl) textEl.textContent = '\u26A0\uFE0F ' + PESAN_MAINTENANCE;
    if (bannerEl) bannerEl.hidden = !aktif;
    root.classList.toggle('siberad-mm-on', aktif);
    sesuaikanOffsetKonten();

    if (aktif) {
      scan(document);
      mulaiObserver();
    } else {
      hentikanObserver();
      unlockAll();
    }

    if (tampilkanToast && berubahAktif && window.siberadShowToast) {
      if (aktif) notify();   // throttle: tidak dobel dengan toast dari aksi yang baru diblok
      else window.siberadShowToast('success', 'Pemeliharaan selesai. Sistem kembali normal.');
    }
  }

  // ---- 5) Polling status (realtime). Jeda saat tab disembunyikan, langsung
  //         sinkron lagi begitu tab dibuka / koneksi pulih. ----
  var polling = false;
  function poll(){
    if (polling || document.hidden) return;
    polling = true;
    originalFetch.call(window, STATUS_URL + '?_=' + Date.now(), {
      credentials: 'same-origin', cache: 'no-store',
      headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
    }).then(function(r){ return r.ok ? r.json() : null; })
      .then(function(data){
        if (!data || typeof data.aktif !== 'boolean') return;
        terapkan(data.aktif, data.pesan, data.waktu, true);
      })
      .catch(function(){})
      .then(function(){ polling = false; });
  }

  // Status awal dari server.
  pasangFilterToast();
  root.classList.toggle('siberad-mm-on', aktif);
  sesuaikanOffsetKonten();
  if (aktif) { scan(document); mulaiObserver(); }

  window.setInterval(poll, 4000);
  document.addEventListener('visibilitychange', function(){ if (!document.hidden) poll(); });
  window.addEventListener('online', poll);
})();
</script>
