(function(){
  'use strict';

  var ADMIN_USER_ORDER = [
    { key:'danpus', label:'Danpus' },
    { key:'wadan', label:'Wadan' },
    { key:'sdirbinfung', label:'Sdirbinfung' },
    { key:'sdirbinum', label:'Sdirbinum' },
    { key:'sdirbindiklat', label:'Sdirbindiklat' },
    { key:'sdirbinmat', label:'Sdirbinmat' },
    { key:'satlakkal', label:'Satlak Kal' },
    { key:'satlakdak', label:'Satlak Dak' },
    { key:'satlaksisos', label:'Satlak Siber Sos' },
    { key:'satlakduktek', label:'Satlak Dukteksi' }
  ];
  var ADMIN_USER_ORDER_INDEX = ADMIN_USER_ORDER.reduce(function(map,item,index){map[item.key]=index;return map;},{});
  var ADMIN_USER_LABELS = ADMIN_USER_ORDER.reduce(function(map,item){map[item.key]=item.label;return map;},{});

  function normalizeUserKey(value){
    return String(value||'').toLowerCase().trim().replace(/[^a-z0-9]+/g,'');
  }

  function applyAdminUserListOrder(){
    var table=document.getElementById('tblPengguna');
    if(!table)return false;
    var thead=table.querySelector('thead');
    var tbody=table.querySelector('tbody');
    if(!tbody)return false;

    var headers=thead?Array.prototype.map.call(thead.querySelectorAll('th'),function(th){return normalizeUserKey(th.textContent)}):[];
    var nameIndex=headers.indexOf('nama');
    var usernameIndex=headers.indexOf('username');
    if(nameIndex<0)nameIndex=1;
    if(usernameIndex<0)usernameIndex=2;

    var rows=Array.prototype.slice.call(tbody.querySelectorAll('tr'));
    if(!rows.length)return true;

    rows.forEach(function(row,originalIndex){
      var cells=row.children;
      var usernameCell=cells[usernameIndex];
      var key=normalizeUserKey(usernameCell?usernameCell.textContent:'');
      row.dataset.adminUserOrderKey=key;
      row.dataset.adminUserOriginalIndex=String(originalIndex);
      var mappedLabel=ADMIN_USER_LABELS[key];
      var nameCell=cells[nameIndex];
      if(mappedLabel&&nameCell){
        nameCell.textContent=mappedLabel;
      }
    });

    rows.sort(function(a,b){
      var aKey=a.dataset.adminUserOrderKey||'';
      var bKey=b.dataset.adminUserOrderKey||'';
      var aRank=Object.prototype.hasOwnProperty.call(ADMIN_USER_ORDER_INDEX,aKey)?ADMIN_USER_ORDER_INDEX[aKey]:999;
      var bRank=Object.prototype.hasOwnProperty.call(ADMIN_USER_ORDER_INDEX,bKey)?ADMIN_USER_ORDER_INDEX[bKey]:999;
      if(aRank!==bRank)return aRank-bRank;
      if(aKey!==bKey)return aKey.localeCompare(bKey,'id');
      return Number(a.dataset.adminUserOriginalIndex||0)-Number(b.dataset.adminUserOriginalIndex||0);
    });

    rows.forEach(function(row,index){
      if(row.cells[0] && !row.classList.contains('table-empty-row')){
        row.cells[0].textContent=String(index+1);
      }
      tbody.appendChild(row);
    });
    return true;
  }

  function applyReportFix(){
    var center=document.getElementById('siberadAdminReportCenter');
    if(!center){
      applyAdminUserListOrder();
      return false;
    }

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

    applyAdminUserListOrder();
    return true;
  }

  function clearReportSearch(){
    var search=document.getElementById('siberadReportSearch');
    if(!search||search.matches(':focus'))return;
    search.value='';
    search.defaultValue='';
  }

  function boot(){applyReportFix();clearReportSearch();applyAdminUserListOrder();}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
  window.addEventListener('pageshow',boot);
  window.addEventListener('resize',applyReportFix,{passive:true});
  document.addEventListener('click',function(e){
    var tab=e.target.closest&&e.target.closest('[data-report-kind]');
    if(tab)setTimeout(function(){clearReportSearch();applyReportFix();applyAdminUserListOrder();},0);
  });
  var observer=new MutationObserver(function(){if(applyReportFix())clearReportSearch();applyAdminUserListOrder();});
  observer.observe(document.body,{childList:true,subtree:true});
  setTimeout(function(){observer.disconnect();},15000);
})();
