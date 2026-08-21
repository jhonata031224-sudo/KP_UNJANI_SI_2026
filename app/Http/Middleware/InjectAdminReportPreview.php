<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectAdminReportPreview
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->routeIs('dashboard') || ! $request->user()) {
            return $response;
        }

        if (strtoupper(trim((string) $request->user()->satuan?->kode)) !== 'ADMIN') {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || $html === '' || ! str_contains($html, 'Laporan Pengguna &amp; Aktivitas')) {
            return $response;
        }

        $previewUser = route('admin.laporan.cetak', ['jenis' => 'pengguna']);
        $previewActivity = route('admin.laporan.cetak', ['jenis' => 'aktivitas']);
        $downloadUser = route('admin.laporan.export-pengguna');
        $downloadActivity = route('admin.laporan.export-aktivitas');

        $injection = '<style id="siberad-admin-report-preview-style">
.admin-report-preview{display:none;margin-top:18px;border:1px solid var(--border-soft);border-radius:14px;background:var(--panel);box-shadow:0 10px 28px rgba(15,23,42,.08);overflow:hidden;}
.admin-report-preview.is-open{display:block;}
.admin-report-preview-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:15px 18px;border-bottom:1px solid var(--border-soft);background:var(--panel-alt);}
.admin-report-preview-title{min-width:0;}
.admin-report-preview-title strong{display:block;font-family:var(--display);font-size:15px;font-weight:700;color:var(--text);}
.admin-report-preview-title span{display:block;margin-top:3px;font-family:var(--body);font-size:11px;color:var(--text-muted);}
.admin-report-preview-actions{display:flex;align-items:center;gap:8px;flex-shrink:0;}
.admin-report-preview-btn{height:36px;padding:0 12px;border-radius:9px;border:1px solid var(--border);background:var(--panel);color:var(--text);font-family:var(--body);font-size:11px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:7px;cursor:pointer;white-space:nowrap;}
.admin-report-preview-btn:hover{border-color:var(--gold);background:var(--gold-dim);}
.admin-report-preview-btn.primary{background:var(--gold);border-color:var(--gold);color:#111827;box-shadow:0 6px 16px rgba(201,122,0,.16);}
.admin-report-preview-btn.primary:hover{filter:brightness(1.04);}
.admin-report-preview-close{width:36px;padding:0;}
.admin-report-preview-frame-wrap{padding:14px;background:var(--bg);}
.admin-report-preview-frame{display:block;width:100%;height:720px;border:1px solid var(--border-soft);border-radius:10px;background:#fff;}
.admin-report-preview-loading{display:none;align-items:center;justify-content:center;min-height:180px;color:var(--text-muted);font-size:12px;}
.admin-report-preview.loading .admin-report-preview-loading{display:flex;}
.admin-report-preview.loading .admin-report-preview-frame{display:none;}
@media(max-width:760px){.admin-report-preview-head{align-items:flex-start;flex-direction:column;}.admin-report-preview-actions{width:100%;flex-wrap:wrap;}.admin-report-preview-btn{flex:1 1 auto;}.admin-report-preview-frame{height:560px;}}
</style>
<script id="siberad-admin-report-preview-script">
(function(){
  if(window.__SIBERAD_ADMIN_REPORT_PREVIEW__) return;
  window.__SIBERAD_ADMIN_REPORT_PREVIEW__=true;

  var URLS={
    userPreview:'.json_encode($previewUser, JSON_UNESCAPED_SLASHES).',
    activityPreview:'.json_encode($previewActivity, JSON_UNESCAPED_SLASHES).',
    userDownload:'.json_encode($downloadUser, JSON_UNESCAPED_SLASHES).',
    activityDownload:'.json_encode($downloadActivity, JSON_UNESCAPED_SLASHES).'
  };

  function text(el){return (el && (el.textContent||'') || '').replace(/\\s+/g,' ').trim().toUpperCase();}
  function relevant(el){
    var t=text(el),h=(el.getAttribute&&el.getAttribute('href'))||'';
    return /EXPORT PENGGUNA|EXPORT AKTIVITAS|CETAK PENGGUNA|CETAK AKTIVITAS/.test(t) || /export-pengguna|export-aktivitas/.test(h);
  }
  function findToolbar(){
    var buttons=[].slice.call(document.querySelectorAll('a,button')).filter(relevant);
    if(buttons.length<2) return null;
    var candidate=buttons[0].parentElement;
    for(var depth=0;candidate&&depth<8;depth++,candidate=candidate.parentElement){
      var count=[].slice.call(candidate.querySelectorAll('a,button')).filter(relevant).length;
      if(count>=4) return candidate;
    }
    return buttons[0].parentElement;
  }
  function ensurePreview(){
    var toolbar=findToolbar();
    if(!toolbar) return null;
    var old=document.getElementById('adminReportPreview');
    if(old && old.parentElement===toolbar.parentElement) return old;

    if(old) old.remove();
    var panel=document.createElement('div');
    panel.id='adminReportPreview';
    panel.className='admin-report-preview';
    panel.innerHTML='\n      <div class="admin-report-preview-head">\n        <div class="admin-report-preview-title"><strong id="adminReportPreviewTitle">Pratinjau Laporan</strong><span id="adminReportPreviewSubtitle">Pilih tombol laporan untuk melihat pratinjau di bawah.</span></div>\n        <div class="admin-report-preview-actions">\n          <a id="adminReportPreviewDownload" class="admin-report-preview-btn primary" href="#" target="_blank" rel="noopener">Unduh</a>\n          <button id="adminReportPreviewPrint" type="button" class="admin-report-preview-btn">Cetak / PDF</button>\n          <button id="adminReportPreviewClose" type="button" class="admin-report-preview-btn admin-report-preview-close" aria-label="Tutup pratinjau">Tutup</button>\n        </div>\n      </div>\n      <div class="admin-report-preview-frame-wrap">\n        <div class="admin-report-preview-loading">Memuat pratinjau laporan...</div>\n        <iframe id="adminReportPreviewFrame" class="admin-report-preview-frame" title="Pratinjau laporan" loading="lazy"></iframe>\n      </div>';

    toolbar.insertAdjacentElement('afterend',panel);

    document.getElementById('adminReportPreviewClose').addEventListener('click',function(){
      panel.classList.remove('is-open');
    });
    document.getElementById('adminReportPreviewPrint').addEventListener('click',function(){
      var frame=document.getElementById('adminReportPreviewFrame');
      if(frame && frame.contentWindow){try{frame.contentWindow.focus();frame.contentWindow.print();}catch(e){window.open(frame.src,'_blank','noopener');}}
    });
    return panel;
  }
  function openPreview(kind,mode){
    var panel=ensurePreview();
    if(!panel) return false;
    var frame=document.getElementById('adminReportPreviewFrame');
    var title=document.getElementById('adminReportPreviewTitle');
    var subtitle=document.getElementById('adminReportPreviewSubtitle');
    var download=document.getElementById('adminReportPreviewDownload');
    panel.classList.add('is-open','loading');

    if(kind==='user'){
      title.textContent='Pratinjau Data Pengguna';
      subtitle.textContent='Pratinjau halaman laporan pengguna sebelum diunduh.';
      download.href=URLS.userDownload;
      download.textContent='Unduh Pengguna';
      frame.src=URLS.userPreview+'?preview=1&t='+Date.now();
    }else{
      title.textContent='Pratinjau Aktivitas Sistem';
      subtitle.textContent='Pratinjau halaman laporan aktivitas sebelum diunduh.';
      download.href=URLS.activityDownload;
      download.textContent='Unduh Aktivitas';
      frame.src=URLS.activityPreview+'?preview=1&t='+Date.now();
    }
    frame.onload=function(){panel.classList.remove('loading');};
    var section=panel.closest('[data-tab-panel]')||panel.parentElement;
    setTimeout(function(){panel.scrollIntoView({behavior:'smooth',block:'start'});},50);
    return true;
  }

  function bind(){
    var buttons=[].slice.call(document.querySelectorAll('a,button')).filter(relevant);
    buttons.forEach(function(btn){
      if(btn.dataset.reportPreviewBound==='1') return;
      btn.dataset.reportPreviewBound='1';
      btn.addEventListener('click',function(e){
        var t=text(btn),h=(btn.getAttribute('href')||'').toLowerCase();
        var kind=/PENGGUNA/.test(t)||/export-pengguna/.test(h)||/cetak\\/pengguna/.test(h)?'user':'activity';
        if(relevant(btn)){
          e.preventDefault();
          e.stopPropagation();
          openPreview(kind, /CETAK/.test(t)?'print':'export');
        }
      },true);
    });
  }
  function init(){bind();}
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init); else init();
  var observer=new MutationObserver(function(){bind();});
  observer.observe(document.body,{childList:true,subtree:true});
  window.setTimeout(function(){observer.disconnect();bind();},10000);
})();
</script>';

        $pos = strripos($html, '</body>');
        if ($pos !== false) {
            $html = substr($html, 0, $pos).$injection.substr($html, $pos);
            $response->setContent($html);
        }

        return $response;
    }
}
