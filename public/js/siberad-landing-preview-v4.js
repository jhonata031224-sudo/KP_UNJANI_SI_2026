(function () {
  'use strict';

  var zoom = 1;
  var activePage = 'beranda';
  var bound = false;
  var resizeTimer = null;

  function $(id) { return document.getElementById(id); }
  function value(form, name, fallback) {
    var el = form && form.querySelector('[data-lp="' + name + '"]');
    var v = el ? String(el.value || '').trim() : '';
    return v || fallback || '';
  }
  function esc(v) {
    return String(v || '').replace(/[&<>"']/g, function (c) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[c];
    });
  }

  function boot() {
    var form = $('landingForm');
    var viewport = $('lpPreview');
    if (!form || !viewport) return;
    installCss();
    build(viewport);
    bind(form, viewport);
    render(form);
    setTimeout(fit, 0);
    setTimeout(fit, 180);
    window.addEventListener('resize', scheduleFit, {passive:true});
  }

  function build(viewport) {
    viewport.innerHTML = `
      <div class="lpv4-toolbar" role="toolbar" aria-label="Navigasi preview">
        <div class="lpv4-page-tabs">
          <button type="button" data-page="beranda" class="active">Beranda</button>
          <button type="button" data-page="fitur">Fitur</button>
          <button type="button" data-page="tentang">Tentang</button>
          <button type="button" data-page="kontak">Kontak</button>
        </div>
        <div class="lpv4-zoom-controls">
          <button type="button" data-zoom="out" aria-label="Perkecil">−</button>
          <button type="button" data-zoom="fit">Fit</button>
          <span id="lpv4ZoomLabel">Fit</span>
          <button type="button" data-zoom="in" aria-label="Perbesar">+</button>
        </div>
      </div>
      <div id="lpPreviewStage" class="lpv4-stage">
        <div id="lpPreviewCanvas" class="lpv4-canvas">
          <header class="lpv4-header">
            <div class="lpv4-brand">
              <img class="lpv4-logo" src="${esc(getLogo())}" alt="Lambang Pussiberad">
              <div><strong>SIBER<span>AD</span></strong><small>PUSSIBERAD · TNI AD</small></div>
            </div>
            <nav>
              <button data-nav="beranda">Beranda</button><button data-nav="fitur">Fitur</button><button data-nav="tentang">Tentang</button><button data-nav="kontak">Kontak</button>
            </nav>
          </header>

          <main class="lpv4-pages">
            <section class="lpv4-page active" data-page-view="beranda">
              <div class="lpv4-hero" id="lpv4Hero">
                <div class="lpv4-overlay"></div>
                <div class="lpv4-hero-content">
                  <div class="lpv4-eyebrow" id="lpv4Eyebrow"></div>
                  <h1><span id="lpv4Title1"></span><em id="lpv4Title2"></em></h1>
                  <h2 id="lpv4Subtitle"></h2>
                  <p id="lpv4Description"></p>
                  <button type="button" class="lpv4-primary" data-nav="fitur">SELENGKAPNYA</button>
                </div>
              </div>
            </section>

            <section class="lpv4-page" data-page-view="fitur">
              <div class="lpv4-page-inner">
                <div class="lpv4-kicker">FITUR UNGGULAN</div>
                <h2>Solusi digital untuk alur pelaporan</h2>
                <p class="lpv4-lead">Empat fungsi utama yang membantu proses pelaporan, verifikasi, pemantauan, dan keamanan data.</p>
                <div class="lpv4-features" id="lpv4Features"></div>
              </div>
            </section>

            <section class="lpv4-page" data-page-view="tentang">
              <div class="lpv4-page-inner lpv4-about">
                <div class="lpv4-kicker">TENTANG PUSSIBERAD</div>
                <h2>Pusat Siber Angkatan Darat</h2>
                <p id="lpv4About"></p>
                <div class="lpv4-moto"><b id="lpv4MotoTitle"></b><span id="lpv4Moto"></span></div>
              </div>
            </section>

            <section class="lpv4-page" data-page-view="kontak">
              <div class="lpv4-page-inner">
                <div class="lpv4-kicker">TERHUBUNG</div>
                <h2>Kontak & informasi</h2>
                <div class="lpv4-contact">
                  <div><b>Alamat</b><span id="lpv4Address"></span></div>
                  <div><b>Telepon</b><span id="lpv4Phone"></span></div>
                  <div><b>Email</b><span id="lpv4Email"></span></div>
                  <div><b>Website</b><span id="lpv4Website"></span></div>
                </div>
                <div id="lpv4Social" class="lpv4-social"></div>
              </div>
              <footer class="lpv4-footer">© PUSSIBERAD · TNI ANGKATAN DARAT</footer>
            </section>
          </main>
        </div>
      </div>`;

    viewport.addEventListener('click', function (e) {
      var page = e.target.closest('[data-page], [data-nav]');
      if (page) {
        var target = page.getAttribute('data-page') || page.getAttribute('data-nav');
        if (target) showPage(target);
      }
      var z = e.target.closest('[data-zoom]');
      if (z) {
        var action = z.getAttribute('data-zoom');
        if (action === 'fit') zoom = 1;
        if (action === 'in') zoom = Math.min(3, +(zoom + .25).toFixed(2));
        if (action === 'out') zoom = Math.max(.5, +(zoom - .25).toFixed(2));
        applyScale();
      }
    });
  }

  function getLogo() {
    var img = document.querySelector('.side-brand img');
    return img && img.src ? img.src : '/images/logo-pussiberad.jpg';
  }
  function set(id, text) { var el = $(id); if (el) el.textContent = text || ''; }

  function showPage(page) {
    activePage = page;
    document.querySelectorAll('#lpPreview [data-page-view]').forEach(function (el) {
      el.classList.toggle('active', el.getAttribute('data-page-view') === page);
    });
    document.querySelectorAll('#lpPreview [data-page], #lpPreview [data-nav]').forEach(function (el) {
      var target = el.getAttribute('data-page') || el.getAttribute('data-nav');
      el.classList.toggle('active', target === page);
    });
    zoom = 1;
    fit();
  }

  function render(form) {
    set('lpv4Eyebrow', value(form, 'hero_eyebrow', 'PUSSIBERAD // SISTEM PENDUKUNG OPERASIONAL'));
    set('lpv4Title1', value(form, 'hero_judul_awal', 'SIBER'));
    set('lpv4Title2', value(form, 'hero_judul_aksen', 'AD'));
    set('lpv4Subtitle', value(form, 'hero_subjudul', 'Sistem Informasi Berbasis Elektronik Angkatan Darat'));
    set('lpv4Description', value(form, 'hero_deskripsi', 'Mendigitalisasi alur pelaporan kegiatan seluruh Satuan Pelaksana Pusat Siber Angkatan Darat dari input laporan di lapangan, verifikasi berjenjang, hingga visualisasi real-time bagi pengambil keputusan.'));
    set('lpv4About', value(form, 'tentang_deskripsi', 'Pusat Siber Angkatan Darat mendukung digitalisasi dan pengamanan informasi secara terintegrasi.'));
    set('lpv4MotoTitle', value(form, 'tentang_moto_judul', 'SATRIA'));
    set('lpv4Moto', value(form, 'tentang_moto_deskripsi', 'Kesatria atau pejuang yang gagah berani, jujur, dan membela kebenaran.'));
    set('lpv4Address', value(form, 'alamat', 'Alamat belum diisi'));
    set('lpv4Phone', value(form, 'telepon_kontak', 'Telepon belum diisi'));
    set('lpv4Email', value(form, 'email_kontak', 'Email belum diisi'));
    set('lpv4Website', value(form, 'website', 'Website belum diisi'));

    var features = $('lpv4Features');
    if (features) {
      features.innerHTML = '';
      for (var i=0;i<4;i++) {
        var t=form.querySelector('[data-lp="fitur_judul_'+i+'"]');
        var d=form.querySelector('[data-lp="fitur_deskripsi_'+i+'"]');
        if (!t) continue;
        var card=document.createElement('article');
        card.innerHTML='<b></b><span></span>';
        card.querySelector('b').textContent=String(t.value||'').trim()||('Fitur '+(i+1));
        card.querySelector('span').textContent=String(d ? d.value||'' : '').trim()||'Deskripsi fitur';
        features.appendChild(card);
      }
    }

    var social=$('lpv4Social');
    if (social) {
      social.innerHTML='';
      for (var j=0;j<20;j++) {
        var platform=form.querySelector('[data-lp="sosial_platform_'+j+'"]');
        if (!platform) break;
        var label=form.querySelector('[data-lp="sosial_label_'+j+'"]');
        var url=form.querySelector('[data-lp="sosial_url_'+j+'"]');
        var text=String((label&&label.value)||(url&&url.value)||'').trim();
        if (!text) continue;
        var chip=document.createElement('span'); chip.textContent=text; social.appendChild(chip);
      }
    }

    var image=form.querySelector('[data-lp-image="hero_image"]');
    var hero=$('lpv4Hero');
    if (image && hero && image.files && image.files[0] && image.files[0].type.indexOf('image/')===0) {
      var reader=new FileReader();
      reader.onload=function(e){hero.style.backgroundImage='url("'+e.target.result+'")';};
      reader.readAsDataURL(image.files[0]);
    }
    scheduleFit();
  }

  function bind(form) {
    if (bound) return;
    bound=true;
    form.querySelectorAll('[data-lp], [data-lp-image]').forEach(function(el){
      el.addEventListener('input',function(){render(form);});
      el.addEventListener('change',function(){render(form);});
    });
  }

  function scheduleFit(){clearTimeout(resizeTimer);resizeTimer=setTimeout(applyScale,30);}
  function fit(){zoom=1;applyScale();}

  function applyScale() {
    var viewport=$('lpPreview'), stage=$('lpPreviewStage'), canvas=$('lpPreviewCanvas');
    if(!viewport||!stage||!canvas) return;
    canvas.style.transform='none';
    canvas.style.width='100%';
    canvas.style.height='auto';
    stage.style.width='100%';
    stage.style.height='auto';

    var naturalWidth=Math.max(canvas.scrollWidth,canvas.offsetWidth,1);
    var naturalHeight=Math.max(canvas.scrollHeight,canvas.offsetHeight,1);
    var vw=Math.max(viewport.clientWidth,1), vh=Math.max(viewport.clientHeight-52,1);
    var fitScale=Math.min(vw/naturalWidth,vh/naturalHeight,1);
    if(!isFinite(fitScale)||fitScale<=0) fitScale=1;
    var scale=Math.min(3,Math.max(.05,fitScale*zoom));
    stage.style.width=Math.ceil(naturalWidth*scale)+'px';
    stage.style.height=Math.ceil(naturalHeight*scale)+'px';
    canvas.style.width=naturalWidth+'px';
    canvas.style.height=naturalHeight+'px';
    canvas.style.transform='scale('+scale+')';
    canvas.style.transformOrigin='top left';
    viewport.classList.toggle('lp-preview-zoomed',zoom!==1);
    var label=$('lpv4ZoomLabel'); if(label) label.textContent=zoom===1?'Fit':Math.round(zoom*100)+'%';
  }

  function installCss(){
    if($('siberad-live-preview-v4-style')) return;
    var style=document.createElement('style'); style.id='siberad-live-preview-v4-style';
    style.textContent=`
      #lpPreview{position:relative!important;width:100%!important;height:760px!important;min-height:560px!important;max-width:none!important;overflow:hidden!important;background:#eef2f5!important;border:1px solid #dbe3eb!important;border-radius:12px!important;box-sizing:border-box!important}
      #lpPreview.lp-preview-zoomed{overflow:auto!important}
      #lpPreview .lpv4-toolbar{height:52px;position:absolute;left:0;right:0;top:0;z-index:30;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 12px;background:rgba(255,255,255,.97);border-bottom:1px solid #e2e8f0;box-sizing:border-box}
      #lpPreview .lpv4-page-tabs{display:flex;gap:6px;flex-wrap:wrap}
      #lpPreview .lpv4-page-tabs button{border:1px solid #dbe3eb;background:#fff;color:#64748b;border-radius:8px;padding:7px 13px;font:600 11px var(--body,Arial);cursor:pointer}
      #lpPreview .lpv4-page-tabs button.active{border-color:#c97a00;background:rgba(201,122,0,.1);color:#b56c00}
      #lpPreview .lpv4-zoom-controls{display:flex;align-items:center;gap:4px}
      #lpPreview .lpv4-zoom-controls button{height:30px;min-width:30px;border:1px solid #dbe3eb;background:#fff;border-radius:7px;color:#334155;font:700 11px var(--mono,monospace);cursor:pointer}
      #lpPreview .lpv4-zoom-controls button:hover{border-color:#c97a00;color:#c97a00}
      #lpPreview .lpv4-zoom-controls span{min-width:42px;text-align:center;font:10px var(--mono,monospace);color:#64748b}
      #lpPreview .lpv4-stage{position:relative;margin-top:52px;padding:0;box-sizing:border-box}
      #lpPreview .lpv4-canvas{position:relative;background:#fff;color:#17212b;font-family:var(--body,Arial,sans-serif);box-sizing:border-box;overflow:hidden}
      #lpPreview .lpv4-header{height:86px;display:flex;align-items:center;justify-content:space-between;gap:28px;padding:16px 44px;background:#fff;border-bottom:1px solid #e5eaf0;box-sizing:border-box}
      #lpPreview .lpv4-brand{display:flex;align-items:center;gap:12px;min-width:0}
      #lpPreview .lpv4-logo{width:50px;height:50px;object-fit:cover;border-radius:50%;flex:0 0 50px}
      #lpPreview .lpv4-brand strong{font-size:24px;line-height:1;font-weight:800;color:#17212b}.lpv4-brand strong span{color:#c97a00}
      #lpPreview .lpv4-brand small{display:block;margin-top:6px;font:9px var(--mono,monospace);letter-spacing:.17em;color:#7b8794}
      #lpPreview .lpv4-header nav{display:flex;gap:24px}.lpv4-header nav button{border:0;background:transparent;color:#64748b;font:600 11px var(--mono,monospace);cursor:pointer}.lpv4-header nav button.active{color:#c97a00}
      #lpPreview .lpv4-pages{width:100%}.lpv4-page{display:none!important;min-height:620px}.lpv4-page.active{display:block!important}
      #lpPreview .lpv4-hero{position:relative;min-height:620px;padding:70px 80px;display:flex;align-items:center;box-sizing:border-box;background:#edf1f4 center/cover no-repeat}
      #lpPreview .lpv4-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(255,255,255,.95),rgba(255,255,255,.74) 58%,rgba(255,255,255,.48))}
      #lpPreview .lpv4-hero-content{position:relative;z-index:2;max-width:900px}.lpv4-eyebrow{display:inline-block;padding:9px 14px;margin-bottom:22px;border:1px solid rgba(201,122,0,.28);border-radius:8px;background:rgba(255,255,255,.96);font:12px var(--mono,monospace);letter-spacing:.14em;color:#c97a00}
      #lpPreview .lpv4-hero h1{margin:0 0 18px;font-size:82px;line-height:.92;letter-spacing:-.04em;font-weight:800;color:#17212b}.lpv4-hero h1 em{font-style:normal;color:#c97a00}.lpv4-hero h2{margin:0 0 16px;font-size:29px;line-height:1.25;color:#17212b}.lpv4-hero p{max-width:820px;margin:0;font-size:17px;line-height:1.65;color:#52606d}.lpv4-primary{margin-top:28px;border:0;border-radius:9px;background:#d98200;color:#111;padding:14px 26px;font:700 12px var(--mono,monospace);letter-spacing:.08em;cursor:pointer}
      #lpPreview .lpv4-page-inner{min-height:620px;padding:70px 80px;background:#fff;box-sizing:border-box}.lpv4-kicker{margin-bottom:12px;font:700 11px var(--mono,monospace);letter-spacing:.15em;color:#c97a00}.lpv4-page-inner h2{margin:0 0 14px;font-size:34px;color:#17212b}.lpv4-lead{max-width:780px;margin:0 0 30px;font-size:15px;line-height:1.65;color:#64748b}
      #lpPreview .lpv4-features{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.lpv4-features article{padding:24px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc}.lpv4-features b{display:block;margin-bottom:8px;font-size:18px;color:#17212b}.lpv4-features span{font-size:13px;line-height:1.6;color:#64748b}
      #lpPreview .lpv4-about{background:#f8fafc}.lpv4-about>p{max-width:900px;font-size:16px;line-height:1.75;color:#52606d}.lpv4-moto{margin-top:30px;padding:22px;border-left:3px solid #c97a00;background:#fff;display:flex;flex-direction:column;gap:6px}.lpv4-moto b{font-size:22px;color:#17212b}.lpv4-moto span{font-size:14px;color:#64748b}
      #lpPreview .lpv4-contact{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:30px}.lpv4-contact>div{padding:22px;border:1px solid #e2e8f0;border-radius:11px;background:#f8fafc}.lpv4-contact b{display:block;margin-bottom:8px;font:700 10px var(--mono,monospace);letter-spacing:.1em;text-transform:uppercase;color:#c97a00}.lpv4-contact span{display:block;font-size:14px;line-height:1.55;color:#52606d;overflow-wrap:anywhere}.lpv4-social{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}.lpv4-social span{padding:7px 11px;border:1px solid #e2e8f0;border-radius:999px;font-size:11px;color:#64748b;background:#fff}.lpv4-footer{margin:80px -80px -70px;padding:22px 80px;background:#202428;color:#fff;font:10px var(--mono,monospace);letter-spacing:.08em}
      @media(max-width:800px){#lpPreview{height:650px!important}.lpv4-toolbar{align-items:flex-start!important}.lpv4-header{padding:14px 24px!important}.lpv4-header nav{gap:8px!important}.lpv4-header nav button{font-size:9px!important}.lpv4-hero,.lpv4-page-inner{padding:50px 35px!important}.lpv4-hero h1{font-size:58px!important}.lpv4-hero h2{font-size:22px!important}.lpv4-features,.lpv4-contact{grid-template-columns:1fr!important}.lpv4-footer{margin-left:-35px!important;margin-right:-35px!important;padding-left:35px!important;padding-right:35px!important}}
    `;
    document.head.appendChild(style);
  }

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',boot,{once:true}); else boot();
})();
