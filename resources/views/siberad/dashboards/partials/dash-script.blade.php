<script>
  const menuBtn=document.getElementById('menuBtn');const sidebar=document.getElementById('sidebar');
  if(menuBtn&&sidebar){menuBtn.addEventListener('click',()=>sidebar.classList.toggle('open'));document.addEventListener('click',e=>{if(window.innerWidth<=900&&sidebar.classList.contains('open')&&!sidebar.contains(e.target)&&e.target!==menuBtn)sidebar.classList.remove('open');});}
  (function(){var style=document.createElement('style');style.setAttribute('data-danpus-submenu-fix','true');style.textContent='.side-dropdown-menu,.side-dropdown-menu ul,.side-dropdown-menu ol,.side-dropdown-menu li{list-style:none!important;list-style-type:none!important}.side-dropdown-menu li::marker{content:""!important;display:none!important}.side-dropdown-menu a::before,.side-dropdown-menu a::after,.side-dropdown-menu .side-sublink::before,.side-dropdown-menu .side-sublink::after{content:none!important;display:none!important}.side-dropdown-menu .dot,.side-dropdown-menu .side-sublink .dot{display:none!important;width:0!important;min-width:0!important;margin:0!important;padding:0!important}.side-dropdown-menu .side-sublink{padding-left:32px!important;padding-right:12px!important;gap:0!important;list-style:none!important;background-image:none!important;}';document.head.appendChild(style);})();
  (function(){function removeSubmenuBullets(link){if(!link)return;link.style.setProperty('list-style','none','important');link.style.setProperty('list-style-type','none','important');link.style.setProperty('background-image','none','important');var dot=link.querySelector('.dot');if(dot)dot.remove();}function initDanpusSidebar(){var nav=document.querySelector('.side-nav');if(!nav||!nav.querySelector('[data-tab-link="laporan-monitoring"]')||nav.dataset.dropdownReady==='1')return;nav.dataset.dropdownReady='1';var links=Array.prototype.slice.call(nav.querySelectorAll(':scope > a[data-tab-link]')),byTab={};links.forEach(function(link){byTab[link.getAttribute('data-tab-link')]=link;});var dashboard=byTab.ringkasan,laporan=byTab.laporan,monitoring=byTab['laporan-monitoring'],riwayat=byTab.riwayat,statusSatuan=byTab['status-satuan'];if(!dashboard||!laporan||!monitoring||!riwayat||!statusSatuan)return;function makeIcon(){var svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.setAttribute('viewBox','0 0 24 24');svg.setAttribute('class','side-dropdown-arrow');var p=document.createElementNS('http://www.w3.org/2000/svg','path');p.setAttribute('d','M6 9l6 6 6-6');svg.appendChild(p);return svg;}function makeGroup(label,children){var group=document.createElement('div');group.className='side-dropdown';var toggle=document.createElement('button');toggle.type='button';toggle.className='side-link side-dropdown-toggle';toggle.innerHTML='<span class="dot"></span><span class="side-link-label"></span>';toggle.querySelector('.side-link-label').textContent=label;toggle.appendChild(makeIcon());var menu=document.createElement('div');menu.className='side-dropdown-menu';children.forEach(function(link){link.classList.add('side-sublink');link.classList.remove('active');removeSubmenuBullets(link);menu.appendChild(link);});group.appendChild(toggle);group.appendChild(menu);toggle.addEventListener('click',function(){group.classList.toggle('open');});return group;}nav.innerHTML='';var label=document.createElement('div');label.className='side-nav-label';label.textContent='Menu';nav.appendChild(label);nav.appendChild(dashboard);nav.appendChild(makeGroup('Log Aktivitas',[monitoring,statusSatuan]));nav.appendChild(makeGroup('Pelaporan',[laporan,riwayat]));nav.querySelectorAll('.side-dropdown-menu a').forEach(removeSubmenuBullets);function enforce(){nav.querySelectorAll('.side-dropdown-menu a').forEach(removeSubmenuBullets);}enforce();setTimeout(enforce,50);setTimeout(enforce,250);setTimeout(enforce,1000);}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initDanpusSidebar);else initDanpusSidebar();})();
  function terapkanRowLimitWrap(wrap){if(!wrap)return;var limit=parseInt(wrap.getAttribute('data-row-limit'),10)||5,table=wrap.querySelector('table');if(!table)return;wrap.style.maxHeight='';wrap.classList.remove('tbl-scroll');var thead=table.querySelector('thead'),rows=Array.prototype.filter.call(table.querySelectorAll('tbody tr'),function(r){return r.style.display!=='none';});if(rows.length<=limit)return;var height=thead?thead.offsetHeight:0;for(var i=0;i<limit;i++)height+=rows[i].offsetHeight;if(height>0){wrap.style.maxHeight=height+'px';wrap.classList.add('tbl-scroll');}}window.terapkanRowLimitWrap=terapkanRowLimitWrap;function terapkanRowLimit(panel){if(!panel)return;panel.querySelectorAll('[data-row-limit]').forEach(terapkanRowLimitWrap);}
  const ADMIN_ACTIVE_TAB_KEY='siberad-admin-active-tab',ADMIN_GROUP_STATE_KEY='siberad-admin-group-';const links=document.querySelectorAll('[data-tab-link]'),panels=document.querySelectorAll('[data-tab-panel]');function activateAdminTab(target,skipSave){const targetPanel=document.querySelector(`[data-tab-panel="${target}"]`);if(!targetPanel)return false;links.forEach(l=>l.classList.remove('active'));panels.forEach(p=>p.classList.remove('active'));document.querySelectorAll('.side-nav-group').forEach(g=>g.classList.remove('has-active-child'));document.querySelectorAll(`[data-tab-link="${target}"]`).forEach(l=>{l.classList.add('active');var group=l.closest('.side-nav-group');if(group){group.classList.add('has-active-child');if(!sidebar||!sidebar.classList.contains('collapsed'))group.classList.add('open');try{sessionStorage.setItem(ADMIN_GROUP_STATE_KEY+group.id,'open');}catch(e){}}});targetPanel.classList.add('active');terapkanRowLimit(targetPanel);if(!skipSave)try{sessionStorage.setItem(ADMIN_ACTIVE_TAB_KEY,target);}catch(e){}return true;}links.forEach(link=>link.addEventListener('click',e=>{e.preventDefault();activateAdminTab(link.getAttribute('data-tab-link'));if(window.innerWidth<=900&&sidebar)sidebar.classList.remove('open');window.scrollTo({top:0,behavior:'smooth'});}));(function(){let savedTab=null;try{savedTab=sessionStorage.getItem(ADMIN_ACTIVE_TAB_KEY);}catch(e){}if(savedTab)activateAdminTab(savedTab,true);})();document.querySelectorAll('.tab-panel.active').forEach(terapkanRowLimit);
  (function(){var THEME_KEY='siberad-theme',btn=document.getElementById('themeToggleBtn');if(!btn||btn.dataset.uiBound)return;btn.dataset.uiBound='1';function applyTheme(theme){if(theme==='light')document.documentElement.setAttribute('data-theme','light');else document.documentElement.removeAttribute('data-theme');btn.setAttribute('aria-pressed',theme==='light'?'true':'false');}var saved='dark';try{saved=localStorage.getItem(THEME_KEY)||'dark';}catch(e){}applyTheme(saved);btn.addEventListener('click',function(){var current=document.documentElement.getAttribute('data-theme')==='light'?'light':'dark',next=current==='light'?'dark':'light';try{localStorage.setItem(THEME_KEY,next);}catch(e){}applyTheme(next);});})();
  (function(){function cleanDecorativeSeparators(root){if(!root)return;var walker=document.createTreeWalker(root,NodeFilter.SHOW_TEXT),nodes=[],node;while(node=walker.nextNode())nodes.push(node);nodes.forEach(function(textNode){if(!textNode.nodeValue||!textNode.nodeValue.trim())return;var value=textNode.nodeValue.replace(/\s*\/\/\s*/g,' ').replace(/(^|\s)[-—](?=\s|$)/g,'$1');textNode.nodeValue=value.replace(/ {2,}/g,' ');});}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){cleanDecorativeSeparators(document.body);});else cleanDecorativeSeparators(document.body);})();
  // Preview 1:1 memakai landing page AKTUAL. Form admin bersifat opsional;
  // preview tidak boleh gagal hanya karena ID form berubah.
  (function(){var form=document.getElementById('landingForm'),fakePreview=document.getElementById('lpPreview');if(!fakePreview)return;var browserFrame=fakePreview.closest('.lp-browser-frame');if(!browserFrame)return;var DESKTOP_CANVAS_WIDTH=1440;browserFrame.style.overflow='auto';browserFrame.style.overflowX='auto';browserFrame.style.overflowY='auto';browserFrame.style.position='relative';var iframe=document.createElement('iframe');iframe.id='lpLiveLandingFrame';iframe.title='Pratinjau landing page SIBERAD';iframe.src='/';iframe.setAttribute('loading','eager');iframe.setAttribute('referrerpolicy','same-origin');iframe.style.cssText='display:block;width:'+DESKTOP_CANVAS_WIDTH+'px;max-width:none;height:900px;min-height:700px;border:0;background:#151e19;';fakePreview.style.display='none';browserFrame.appendChild(iframe);var frameReady=false,pendingFileUrl=null;function value(name,fallback){if(!form)return fallback||'';var el=form.querySelector('[data-lp="'+name+'"]');return el?el.value:(fallback||'');}function text(el,val){if(el)el.textContent=val||'';}function frameDoc(){try{return iframe.contentDocument||iframe.contentWindow.document;}catch(e){return null;}}function setTheme(doc){if(!doc)return;var theme=document.documentElement.getAttribute('data-theme');if(theme==='light')doc.documentElement.setAttribute('data-theme','light');else doc.documentElement.removeAttribute('data-theme');}
    function updateLanding(){var doc=frameDoc();if(!doc||!frameReady)return;setTheme(doc);var heroBg=doc.querySelector('.hero-stats-bg'),eyebrow=doc.querySelector('.hero .eyebrow'),h1=doc.querySelector('.hero h1'),h1Em=h1?h1.querySelector('em'):null,h2=doc.querySelector('.hero h2'),heroP=doc.querySelector('.hero > .wrap .hero-inner > div:first-child > p');text(eyebrow,value('hero_eyebrow','PUSSIBERAD // SISTEM PENDUKUNG OPERASIONAL'));if(h1){var first=value('hero_judul_awal');Array.prototype.slice.call(h1.childNodes).forEach(function(n){if(n.nodeType===3)n.nodeValue='';});if(h1.firstChild&&h1.firstChild.nodeType===3)h1.firstChild.nodeValue=first;else h1.insertBefore(doc.createTextNode(first),h1Em||null);}text(h1Em,value('hero_judul_aksen'));text(h2,value('hero_subjudul'));text(heroP,value('hero_deskripsi'));if(heroBg&&pendingFileUrl)heroBg.style.backgroundImage="linear-gradient(115deg, var(--hero-ov-1) 0%, var(--hero-ov-2) 32%, var(--hero-ov-3) 58%, var(--hero-ov-4) 100%),linear-gradient(to top,var(--hero-ov-top) 0%,var(--hero-ov-top-fade) 26%),url('"+pendingFileUrl.replace(/'/g,"\\'")+"')";var featureCards=doc.querySelectorAll('.feature-grid .feature-card');if(form)form.querySelectorAll('[data-lp^="fitur_judul_"]').forEach(function(input){var match=input.getAttribute('data-lp').match(/_(\d+)$/);if(!match)return;var i=parseInt(match[1],10),card=featureCards[i];if(!card)return;text(card.querySelector('h4'),input.value);var desc=form.querySelector('[data-lp="fitur_deskripsi_'+i+'"]');if(desc)text(card.querySelector('p'),desc.value);});var about=doc.querySelector('#tentang-pussiberad');if(about){var wrap=about.querySelector('.about-top > div:last-child'),paras=wrap?wrap.querySelectorAll('p'):[],aboutValue=value('tentang_deskripsi'),paragraphs=aboutValue.split(/\n\s*\n/).map(function(p){return p.trim();}).filter(Boolean);if(wrap)paragraphs.forEach(function(p,i){if(paras[i])text(paras[i],p);});var moto=about.querySelector('.moto-panel');if(moto){text(moto.querySelector('h3'),value('tentang_moto_judul'));text(moto.querySelector('.moto-desc'),value('tentang_moto_deskripsi'));}}var footer=doc.querySelector('footer');if(footer){var cols=footer.querySelectorAll('.footer-grid > div'),contactCol=cols[3];if(contactCol){var items=contactCol.querySelectorAll('.footer-links li'),alamat=value('alamat'),telepon=value('telepon_kontak'),website=value('website');if(items[0])items[0].textContent=alamat;if(items[1])items[1].textContent=telepon;if(items[2]){var a=items[2].querySelector('a');if(a){a.href=website||'#';a.textContent=website.replace(/^https?:\/\//,'').replace(/\/$/,'');}}}}}
    function syncTheme(){var doc=frameDoc();if(doc)setTheme(doc);}iframe.addEventListener('load',function(){frameReady=true;syncTheme();updateLanding();});if(form){form.querySelectorAll('[data-lp]').forEach(function(el){el.addEventListener('input',updateLanding);el.addEventListener('change',updateLanding);});var heroImageInput=form.querySelector('[data-lp-image="hero_image"]');if(heroImageInput)heroImageInput.addEventListener('change',function(){var file=this.files&&this.files[0];if(!file){pendingFileUrl=null;updateLanding();return;}var reader=new FileReader();reader.onload=function(e){pendingFileUrl=e.target.result;updateLanding();};reader.readAsDataURL(file);});}var themeObserver=new MutationObserver(syncTheme);themeObserver.observe(document.documentElement,{attributes:true,attributeFilter:['data-theme']});function resizeFrame(){var h=Math.max(700,Math.min(1100,window.innerHeight-170));iframe.style.height=h+'px';iframe.style.width=DESKTOP_CANVAS_WIDTH+'px';}resizeFrame();window.addEventListener('resize',resizeFrame,{passive:true});})();

  // Kontrol zoom lama tetap tidak ditampilkan. Preview dikunci pada 75% sesuai tampilan yang sudah pas.
  (function(){
    var browserFrame=document.querySelector('.lp-browser-frame');
    var iframe=document.getElementById('lpLiveLandingFrame');
    if(!browserFrame||!iframe)return;

    var FIXED_SCALE=0.75;
    var NATURAL_WIDTH=1440;

    // Menghapus HANYA baris toolbar zoom lama ("- Fit Fit +"). Elemen ini
    // kadang disisipkan belakangan (asinkron) oleh skrip preview lama, jadi
    // fungsi ini dipanggil berulang lewat MutationObserver di bawah, bukan
    // cuma sekali saat load. Iframe pratinjau (#lpLiveLandingFrame) dan
    // elemen lain di dalam browserFrame tidak pernah disentuh.
    function stripZoomToolbar(){
      var toolbar=document.querySelector('.lp-zoom-toolbar');
      if(toolbar)toolbar.remove();

      var zoombars=browserFrame.querySelectorAll('.siberad-preview-zoombar, .lpv4-zoom-controls');
      Array.prototype.forEach.call(zoombars,function(el){
        var row=el.classList.contains('siberad-preview-zoombar')?el:el.parentElement;
        if(row&&row.parentElement)row.remove();
      });

      var legacyButtons=browserFrame.querySelectorAll('button');
      Array.prototype.forEach.call(legacyButtons,function(btn){
        var label=(btn.textContent||'').trim().toLowerCase();
        if(label==='-'||label==='−'||label==='+'||label==='fit'){
          var parent=btn.parentElement;
          if(parent){
            var labels=Array.prototype.map.call(parent.querySelectorAll('button'),function(b){return(b.textContent||'').trim().toLowerCase();});
            var hasMinus=labels.indexOf('-')!==-1||labels.indexOf('−')!==-1;
            var hasPlus=labels.indexOf('+')!==-1;
            var hasFit=labels.indexOf('fit')!==-1;
            if(hasMinus&&hasPlus&&hasFit)parent.remove();
          }
        }
      });
    }

    stripZoomToolbar();

    function applyFixedScale(){
      var naturalHeight=Math.max(700,parseFloat(iframe.style.height)||900);
      iframe.style.width=NATURAL_WIDTH+'px';
      iframe.style.height=naturalHeight+'px';
      iframe.style.transform='scale('+FIXED_SCALE+')';
      iframe.style.transformOrigin='top left';
      iframe.style.marginBottom=(naturalHeight*(FIXED_SCALE-1))+'px';
    }

    applyFixedScale();
    window.addEventListener('resize',applyFixedScale,{passive:true});
    iframe.addEventListener('load',function(){setTimeout(applyFixedScale,0);setTimeout(stripZoomToolbar,50);});

    // Skrip preview lama (v4 / fit-fix) dimuat secara asinkron dan bisa
    // menambahkan kembali toolbar zoom beberapa saat setelah iframe siap.
    // Observer ini memastikan baris tersebut tetap hilang tanpa mengganggu
    // iframe pratinjau atau elemen lain di dalam browserFrame.
    if(window.MutationObserver){
      var observer=new MutationObserver(stripZoomToolbar);
      observer.observe(browserFrame,{childList:true,subtree:true});
      window.setTimeout(function(){observer.disconnect();},10000);
    } else {
      [100,300,600,1000,2000,4000,8000].forEach(function(delay){window.setTimeout(stripZoomToolbar,delay);});
    }
  })();
</script>
@if(isset($pengaturan))
<script src="{{ asset('js/admin-landing-editor.js') }}"></script>
@endif
