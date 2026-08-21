(function(){
  'use strict';

  function applyReportFix(){
    var center=document.getElementById('siberadAdminReportCenter');
    if(!center)return false;

    var wrap=center.querySelector('.siberad-report-table-wrap');
    var table=wrap&&wrap.querySelector('table');
    if(wrap&&table){
      wrap.classList.add('tbl-wrap','tbl-scroll');
      wrap.setAttribute('data-row-limit','6');
      wrap.style.maxHeight='';
      table.classList.add('dtbl');
      if(typeof window.terapkanRowLimitWrap==='function'){
        window.terapkanRowLimitWrap(wrap);
      }else{
        var head=table.querySelector('thead');
        var rows=Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
        if(rows.length>6){
          var h=head?head.offsetHeight:0;
          rows.slice(0,6).forEach(function(row){h+=row.offsetHeight});
          wrap.style.maxHeight=h+'px';
          wrap.style.overflowY='auto';
        }else{
          wrap.style.maxHeight='';
          wrap.style.overflowY='visible';
        }
      }
    }

    var search=document.getElementById('siberadReportSearch');
    if(search&&!search.dataset.reportFixBound){
      search.dataset.reportFixBound='1';
      search.setAttribute('autocomplete','new-password');
      search.setAttribute('autocorrect','off');
      search.setAttribute('autocapitalize','none');
      search.setAttribute('spellcheck','false');
      search.setAttribute('name','report_lookup_term');
      search.value='';
      search.defaultValue='';
      search.readOnly=true;
      search.addEventListener('focus',function(){
        search.value='';
        search.defaultValue='';
        search.readOnly=false;
      });
      search.addEventListener('input',function(){search.readOnly=false;});
    }
    return true;
  }

  function clearReportSearch(){
    var search=document.getElementById('siberadReportSearch');
    if(!search||search.matches(':focus'))return;
    search.value='';
    search.defaultValue='';
  }

  function boot(){applyReportFix();clearReportSearch();}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
  window.addEventListener('pageshow',boot);
  window.addEventListener('resize',applyReportFix,{passive:true});
  document.addEventListener('click',function(e){
    var tab=e.target.closest&&e.target.closest('[data-report-kind]');
    if(tab)setTimeout(function(){clearReportSearch();applyReportFix();},0);
  });
  var observer=new MutationObserver(function(){if(applyReportFix())clearReportSearch();});
  observer.observe(document.body,{childList:true,subtree:true});
  setTimeout(function(){observer.disconnect();},15000);
})();
