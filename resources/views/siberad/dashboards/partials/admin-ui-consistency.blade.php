<style>
/* existing admin UI consistency rules */
</style>

<style>
/* Backup upload: gunakan tombol di header riwayat + modal, bukan panel upload inline. */
.main .content [data-tab-panel="backup"] .backup-upload-panel{display:none !important;}
.main .content [data-tab-panel="backup"] .backup-history-head{display:flex !important;align-items:center !important;justify-content:space-between !important;gap:14px !important;}
.main .content [data-tab-panel="backup"] .backup-upload-trigger{flex-shrink:0;}
.siberad-backup-upload-modal{position:fixed;inset:0;z-index:10080;display:flex;align-items:center;justify-content:center;padding:22px;box-sizing:border-box;background:rgba(2,4,6,.64);backdrop-filter:blur(5px);-webkit-backdrop-filter:blur(5px);opacity:0;visibility:hidden;pointer-events:none;transition:opacity .18s ease,visibility .18s ease;}
.siberad-backup-upload-modal.open{opacity:1;visibility:visible;pointer-events:auto;}
.siberad-backup-upload-card{width:430px;max-width:100%;box-sizing:border-box;background:var(--panel);border:1px solid var(--border-soft);border-radius:15px;box-shadow:0 24px 70px rgba(0,0,0,.45);padding:22px;transform:translateY(10px) scale(.98);transition:transform .18s ease;}
.siberad-backup-upload-modal.open .siberad-backup-upload-card{transform:none;}
.siberad-backup-upload-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:18px;}
.siberad-backup-upload-head h3{margin:0;font-family:var(--display);font-size:17px;color:var(--text);}
.siberad-backup-upload-head p{margin:5px 0 0;font-size:12px;line-height:1.6;color:var(--text-muted);}
.siberad-backup-upload-close{width:34px;height:34px;flex:0 0 auto;border:1px solid var(--border-soft);border-radius:9px;background:transparent;color:var(--text-muted);cursor:pointer;font-size:18px;line-height:1;}
.siberad-backup-upload-close:hover{border-color:var(--red);color:var(--red);}
.siberad-backup-upload-drop{border:1px dashed var(--border-strong);border-radius:11px;padding:18px;background:var(--panel-alt);}
.siberad-backup-upload-drop input[type="file"]{width:100%;box-sizing:border-box;color:var(--text);font-size:12px;}
.siberad-backup-upload-meta{margin-top:9px;font-size:11px;color:var(--text-dim);line-height:1.5;}
.siberad-backup-upload-actions{display:flex;justify-content:flex-end;gap:9px;margin-top:18px;}
@media(max-width:600px){.siberad-backup-upload-modal{padding:14px;}.siberad-backup-upload-card{padding:18px;}.siberad-backup-upload-actions{flex-direction:column-reverse;}.siberad-backup-upload-actions .btn{width:100%;}}
</style>

<script>
(function(){
  function bootBackupUploadModal(){
    var panel=document.querySelector('[data-tab-panel="backup"]');
    if(!panel || panel.dataset.backupModalBound==='1') return;
    panel.dataset.backupModalBound='1';

    var historyHead=panel.querySelector('.panel:nth-of-type(2) .panel-head');
    if(!historyHead) {
      var heads=panel.querySelectorAll('.panel-head');
      historyHead=heads.length ? heads[heads.length-1] : null;
    }
    if(!historyHead) return;

    historyHead.classList.add('backup-history-head');

    var oldPanel=panel.querySelector('.backup-upload-panel');
    var oldForm=oldPanel ? oldPanel.querySelector('form') : null;
    var uploadAction=oldForm && oldForm.action ? oldForm.action : (window.location.origin + '/admin/backup/upload');

    var trigger=document.createElement('button');
    trigger.type='button';
    trigger.className='btn btn-primary btn-sm backup-upload-trigger';
    trigger.textContent='Upload Backup';
    historyHead.appendChild(trigger);

    var modal=document.createElement('div');
    modal.className='siberad-backup-upload-modal';
    modal.setAttribute('aria-hidden','true');
    modal.innerHTML=''+
      '<div class="siberad-backup-upload-card" role="dialog" aria-modal="true" aria-labelledby="backupUploadTitle">'+
        '<div class="siberad-backup-upload-head">'+
          '<div><h3 id="backupUploadTitle">Upload Backup</h3><p>Pilih file backup yang ingin ditambahkan ke riwayat backup.</p></div>'+
          '<button type="button" class="siberad-backup-upload-close" aria-label="Tutup">×</button>'+
        '</div>'+
        '<form class="siberad-backup-upload-form" method="POST" enctype="multipart/form-data">'+
          '<div class="siberad-backup-upload-drop">'+
            '<input type="file" name="backup_file" accept=".sql,.sqlite" required>'+
            '<div class="siberad-backup-upload-meta">Format yang didukung: .sql atau .sqlite. Maksimal 50 MB.</div>'+
          '</div>'+
          '<div class="siberad-backup-upload-actions">'+
            '<button type="button" class="btn btn-ghost backup-upload-cancel">Batal</button>'+
            '<button type="submit" class="btn btn-primary">Upload</button>'+
          '</div>'+
        '</form>'+ 
      '</div>';
    document.body.appendChild(modal);

    var form=modal.querySelector('.siberad-backup-upload-form');
    form.action=uploadAction;
    var tokenInput=document.querySelector('input[name="_token"]');
    if(tokenInput){
      var csrf=document.createElement('input');
      csrf.type='hidden';csrf.name='_token';csrf.value=tokenInput.value;
      form.insertBefore(csrf,form.firstChild);
    }

    function close(){ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); }
    function open(){ modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); setTimeout(function(){ modal.querySelector('input[type="file"]')?.focus(); },30); }

    trigger.addEventListener('click',open);
    modal.querySelector('.siberad-backup-upload-close').addEventListener('click',close);
    modal.querySelector('.backup-upload-cancel').addEventListener('click',close);
    modal.addEventListener('click',function(e){if(e.target===modal)close();});
    document.addEventListener('keydown',function(e){if(e.key==='Escape' && modal.classList.contains('open'))close();});

    form.addEventListener('submit',function(e){
      var file=form.querySelector('input[type="file"]').files[0];
      if(!file){e.preventDefault();window.alert('Pilih file backup terlebih dahulu.');return;}
      var ext=(file.name.split('.').pop()||'').toLowerCase();
      if(['sql','sqlite'].indexOf(ext)===-1){e.preventDefault();window.alert('Format backup hanya boleh .sql atau .sqlite.');return;}
      if(file.size > 50*1024*1024){e.preventDefault();window.alert('Ukuran backup maksimal 50 MB.');return;}
      var ok=window.confirm('Upload backup ini ke riwayat backup?');
      if(!ok){e.preventDefault();return;}
    });
  }

  function init(){window.setTimeout(bootBackupUploadModal,0);}
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init); else init();
  document.addEventListener('click',function(e){
    if(e.target.closest && e.target.closest('[data-tab-link="backup"]')) window.setTimeout(bootBackupUploadModal,60);
  });
})();
</script>
