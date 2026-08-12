<style>
/* Komponen select bergaya kayak dropdown sort di Log Aktivitas Pimpinan —
   trigger + popup menu melayang, dipasang otomatis ke SEMUA <select> di
   halaman (filter tabel maupun form modal). <select> aslinya tetap ada di
   DOM (disembunyikan pakai teknik visually-hidden, bukan display:none),
   supaya submit form & validasi "required" bawaan browser tetap jalan
   normal, dan logic filter yang sudah ada (terapkanTabelFilter dkk) tetap
   berfungsi tanpa diubah karena kita cuma set select.value + trigger event
   'change' seperti biasa.

   Warna netral (background/border/text idle) dipasang lewat var(--p-X,
   var(--global-X)) -- halaman Pimpinan (laporan-pimpinan.blade.php) punya
   token warna sendiri (--p-surface, --p-border, dst, beda dari token global
   --panel/--border) buat nyamain ke dropdown sort Log Aktivitas yang jadi
   acuan style-nya. Kalau --p-X ada (di halaman Pimpinan), itu yang dipakai;
   kalau nggak ada (Admin/Satlak), otomatis jatuh ke token global biasa.

   Warna AKSEN (border pas hover/kebuka, opsi yang lagi aktif) sengaja di-
   hardcode #c97a00/#b56d00 -- persis nilai yang dipakai dropdown sort Log
   Aktivitas asli (danpus-log-search.blade.php). Punya dropdown itu emang
   nggak ikut var(--gold) sama sekali, jadi kalau kita ikutin var(--p-accent)
   punya kita sendiri (yang di mode gelap jadi --gold-bright, kuning pucat
   terang), hasilnya malah beda kelihatan dari acuannya -- makanya di sini
   angka literalnya langsung disamain, bukan lewat variable. */
.styled-select-wrap{position:relative;display:inline-block;width:100%}
.styled-select-native{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
.styled-select-trigger{width:100%;box-sizing:border-box;height:38px;display:flex;align-items:center;justify-content:space-between;gap:8px;border:1px solid var(--p-border,var(--border));border-radius:9px;background:var(--p-surface-2,var(--panel-alt));color:var(--p-text,var(--text));font:inherit;font-size:12px;padding:0 11px;cursor:pointer;transition:border-color .15s ease,box-shadow .15s ease,background .15s ease}
.styled-select-trigger:hover,.styled-select-wrap.open .styled-select-trigger{border-color:#c97a00;background:var(--p-surface,var(--panel));box-shadow:0 3px 12px rgba(15,23,42,.06)}
.styled-select-trigger:focus-visible{outline:none;border-color:#c97a00;box-shadow:0 0 0 3px rgba(201,122,0,.10)}
.styled-select-trigger.disabled{opacity:.55;cursor:not-allowed}
.styled-select-trigger .ss-label{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:left;flex:1;min-width:0}
.styled-select-trigger .ss-chevron{width:14px;height:14px;flex:0 0 auto;color:var(--p-muted,var(--text-muted));transition:transform .2s ease}
.styled-select-wrap.open .ss-chevron{transform:rotate(180deg)}
/* position:fixed + elemen-nya di-pindah jadi anak langsung <body> (lihat JS) —
   bukan cuma position:fixed doang, soalnya kalau menu masih nested di dalam
   modal (.user-modal-card punya transform buat animasi buka/tutup), transform
   di ancestor bikin browser anggap situ containing-block baru buat elemen
   fixed, jadi tetap ke-crop sama overflow-y:auto punya modal. Pindah ke body
   beneran ngilangin masalah itu. left/top/min-width/max-height semua di-set
   lewat JS tiap kali dibuka (lihat positionMenu/applyMaxHeight). */
.styled-select-menu{position:fixed;width:max-content;max-width:320px;max-height:260px;overflow-y:auto;padding:6px;background:var(--p-surface,var(--panel));border:1px solid var(--p-border,var(--border));border-radius:12px;box-shadow:0 14px 32px rgba(0,0,0,.28);z-index:100060;opacity:0;visibility:hidden;pointer-events:none;transform:translateY(-4px);transition:opacity .15s ease,transform .15s ease,visibility .15s ease}
/* Menu-nya dipindah jadi anak <body> (bukan lagi anak .styled-select-wrap),
   jadi status buka/tutupnya nggak bisa lagi dibaca lewat selector turunan
   ".wrap.open .menu" -- makanya class "open" dipasang langsung ke menu-nya
   sendiri lewat JS, bukan cuma ke wrap. */
.styled-select-menu.open{opacity:1;visibility:visible;pointer-events:auto;transform:translateY(0)}
.styled-select-menu::-webkit-scrollbar{width:6px}
.styled-select-menu::-webkit-scrollbar-track{background:transparent}
.styled-select-menu::-webkit-scrollbar-thumb{background:var(--p-border,var(--border));border-radius:8px}
.styled-select-menu::-webkit-scrollbar-thumb:hover{background:#c97a00}
.styled-select-menu{scrollbar-width:thin;scrollbar-color:var(--p-border,var(--border)) transparent}
.styled-select-option{width:100%;border:0;background:transparent;color:var(--p-text,var(--text));border-radius:8px;padding:9px 11px;display:flex;align-items:center;gap:8px;font:inherit;font-size:12px;line-height:1.35;cursor:pointer;text-align:left;transition:background .15s ease,color .15s ease}
.styled-select-option:hover{background:var(--p-surface-2,var(--panel-alt))}
.styled-select-option.active{background:rgba(201,122,0,.10);color:#b56d00;font-weight:700}
.styled-select-option .ss-opt-text{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.styled-select-option .ss-check{width:13px;height:13px;flex:0 0 auto;opacity:0}
.styled-select-option.active .ss-check{opacity:1}
.table-toolbar .styled-select-wrap,.rpt-filter-bar .styled-select-wrap{width:auto;min-width:170px;flex-shrink:0}
@media(max-width:480px){.styled-select-menu{max-width:calc(100vw - 40px)}.table-toolbar .styled-select-wrap,.rpt-filter-bar .styled-select-wrap{width:100%}}
</style>
<script>
(function(){
  var CHEVRON_SVG='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:100%;height:100%;"><path d="m6 9 6 6 6-6"></path></svg>';
  var CHECK_SVG='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:100%;height:100%;"><path d="M5 13l4 4L19 7"></path></svg>';

  function closeAll(except){
    document.querySelectorAll('.styled-select-wrap.open').forEach(function(w){
      if(w===except)return;
      w.classList.remove('open');
      if(w.__ssMenu)w.__ssMenu.classList.remove('open');
      var t=w.querySelector('.styled-select-trigger');
      if(t)t.setAttribute('aria-expanded','false');
    });
  }

  function enhanceSelect(select){
    if(!select||select.tagName!=='SELECT')return;
    if(select.dataset.styledSelectReady==='1')return;
    if(select.hasAttribute('data-skip-styled-select'))return;
    select.dataset.styledSelectReady='1';

    var wrap=document.createElement('div');
    wrap.className='styled-select-wrap';

    var trigger=document.createElement('button');
    trigger.type='button';
    trigger.className='styled-select-trigger';
    trigger.setAttribute('aria-haspopup','listbox');
    trigger.setAttribute('aria-expanded','false');

    var label=document.createElement('span');
    label.className='ss-label';

    var chevron=document.createElement('span');
    chevron.className='ss-chevron';
    chevron.innerHTML=CHEVRON_SVG;

    trigger.appendChild(label);
    trigger.appendChild(chevron);

    var menu=document.createElement('div');
    menu.className='styled-select-menu';
    menu.setAttribute('role','listbox');

    select.parentNode.insertBefore(wrap,select);
    wrap.appendChild(select);
    select.classList.add('styled-select-native');
    select.tabIndex=-1;
    wrap.appendChild(trigger);
    document.body.appendChild(menu);
    wrap.__ssMenu=menu;

    function rebuildMenu(){
      menu.innerHTML='';
      Array.prototype.forEach.call(select.options,function(opt){
        var item=document.createElement('button');
        item.type='button';
        item.className='styled-select-option'+(opt.value===select.value?' active':'');
        item.setAttribute('role','option');
        var txt=document.createElement('span');
        txt.className='ss-opt-text';
        txt.textContent=opt.text;
        txt.title=opt.text;
        var check=document.createElement('span');
        check.className='ss-check';
        check.innerHTML=CHECK_SVG;
        item.appendChild(txt);
        item.appendChild(check);
        item.addEventListener('click',function(e){
          e.stopPropagation();
          if(select.value!==opt.value){
            select.value=opt.value;
            select.dispatchEvent(new Event('change',{bubbles:true}));
          }
          sync();
          close();
        });
        menu.appendChild(item);
      });
    }

    function sync(){
      var selected=select.options[select.selectedIndex];
      label.textContent=selected?selected.text:'';
      Array.prototype.forEach.call(menu.children,function(child,i){
        child.classList.toggle('active',select.options[i]&&select.options[i].value===select.value);
      });
      trigger.classList.toggle('disabled',select.disabled);
    }

    function applyMaxHeight(){
      // Batasi tinggi menu biar pas nampung persis 4 opsi -- <=4 opsi nggak
      // perlu scroll sama sekali, opsi ke-5 baru munculin scroll. Diukur dari
      // tinggi opsi yang beneran di-render (bukan angka px ditebak) supaya
      // konsisten walau isi teksnya beda panjang antar select/tabel/modal.
      var first=menu.querySelector('.styled-select-option');
      if(!first)return;
      var itemH=first.getBoundingClientRect().height;
      var cs=getComputedStyle(menu);
      var extra=parseFloat(cs.paddingTop)+parseFloat(cs.paddingBottom)+parseFloat(cs.borderTopWidth)+parseFloat(cs.borderBottomWidth);
      menu.style.maxHeight=(itemH*4+extra)+'px';
    }

    function positionMenu(){
      var r=trigger.getBoundingClientRect();
      menu.style.minWidth=r.width+'px';
      menu.style.left=r.left+'px';
      menu.style.top=(r.bottom+6)+'px';
      var mr=menu.getBoundingClientRect();
      var vw=window.innerWidth,vh=window.innerHeight;
      var left=r.left;
      if(mr.right>vw-8)left=Math.max(8,r.right-mr.width);
      if(left<8)left=8;
      var top=r.bottom+6;
      if(top+mr.height>vh-8){
        var above=r.top-6-mr.height;
        top=above>8?above:8;
      }
      menu.style.left=left+'px';
      menu.style.top=top+'px';
    }

    function open(){
      if(select.disabled)return;
      closeAll(wrap);
      wrap.classList.add('open');
      menu.classList.add('open');
      trigger.setAttribute('aria-expanded','true');
      applyMaxHeight();
      positionMenu();
    }
    function close(){
      wrap.classList.remove('open');
      menu.classList.remove('open');
      trigger.setAttribute('aria-expanded','false');
    }

    trigger.addEventListener('click',function(e){
      e.stopPropagation();
      if(wrap.classList.contains('open')){close();return}
      rebuildMenu();
      sync();
      open();
    });

    wrap.__syncStyledSelect=function(){rebuildMenu();sync()};

    rebuildMenu();
    sync();
  }

  function enhanceAll(root){
    (root||document).querySelectorAll('select').forEach(enhanceSelect);
  }

  document.addEventListener('click',function(){closeAll()});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')closeAll()});
  // Scroll di mana aja (termasuk di dalam .user-modal-card yang overflow-y:auto)
  // nutup dropdown yang lagi kebuka -- soalnya posisinya di-pin lewat JS pas
  // dibuka, jadi kalau kontennya di-scroll tanpa ini menunya bakal keliatan
  // "ngambang" nggak ngikut triggernya lagi. Tapi scroll DI DALAM menu-nya
  // sendiri (buat liat opsi ke-5 dst pas listnya lebih dari 4) jangan sampai
  // ikut nutup -- makanya scroll yang asalnya dari menu yang lagi kebuka itu
  // sendiri dikecualikan.
  window.addEventListener('scroll',function(e){
    var openWrap=document.querySelector('.styled-select-wrap.open');
    if(!openWrap)return;
    var menu=openWrap.__ssMenu;
    if(menu&&(e.target===menu||(menu.contains&&menu.contains(e.target))))return;
    closeAll();
  },true);

  function boot(){
    enhanceAll(document);

    // Select yang dibuat belakangan lewat JS (mis. filter tabel yang
    // di-generate dinamis) tetap otomatis ke-enhance begitu muncul di DOM.
    new MutationObserver(function(mutations){
      mutations.forEach(function(m){
        Array.prototype.forEach.call(m.addedNodes,function(node){
          if(node.nodeType!==1)return;
          if(node.tagName==='SELECT')enhanceSelect(node);
          else if(node.querySelectorAll)node.querySelectorAll('select').forEach(enhanceSelect);
        });
      });
    }).observe(document.body,{childList:true,subtree:true});

    // Modal yang pre-fill value select-nya lewat JS (Ubah Pengguna/Ubah
    // Satuan) sebelum dibuka -- begitu modal kebuka, sinkronkan ulang label
    // trigger-nya supaya sesuai value asli yang baru di-set.
    new MutationObserver(function(mutations){
      mutations.forEach(function(m){
        if(m.attributeName!=='class')return;
        var el=m.target;
        if(!el.classList||!el.classList.contains('open'))return;
        el.querySelectorAll('select').forEach(function(select){
          enhanceSelect(select);
          var wrap=select.closest('.styled-select-wrap');
          if(wrap&&wrap.__syncStyledSelect)wrap.__syncStyledSelect();
        });
      });
    }).observe(document.body,{attributes:true,attributeFilter:['class'],subtree:true});
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
</script>
