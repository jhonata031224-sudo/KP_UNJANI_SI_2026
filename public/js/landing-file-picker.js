(function () {
  function initFilePickers() {
    document.querySelectorAll('input[type="file"]').forEach(function (input) {
      if (input.dataset.filePickerReady === '1') return;
      input.dataset.filePickerReady = '1';

      var wrap = document.createElement('div');
      wrap.className = 'landing-file-picker';
      input.parentNode.insertBefore(wrap, input);
      wrap.appendChild(input);

      var label = document.createElement('label');
      label.className = 'landing-file-button';
      label.htmlFor = input.id;
      label.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4"></path><path d="m7 9 5-5 5 5"></path><path d="M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"></path></svg><span>Pilih File</span>';
      wrap.appendChild(label);

      var name = document.createElement('span');
      name.className = 'landing-file-name';
      name.textContent = input.files && input.files[0] ? input.files[0].name : 'Belum ada file dipilih';
      wrap.appendChild(name);

      input.addEventListener('change', function () {
        name.textContent = input.files && input.files[0] ? input.files[0].name : 'Belum ada file dipilih';
      });
    });
  }

  function injectStyle() {
    if (document.getElementById('landing-file-picker-style')) return;
    var style = document.createElement('style');
    style.id = 'landing-file-picker-style';
    style.textContent = `
      .landing-file-picker{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:4px}
      .landing-file-picker input[type=file]{position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;overflow:hidden!important;pointer-events:none!important}
      .landing-file-picker .landing-file-button{display:inline-flex;align-items:center;gap:9px;border:1px solid var(--border-strong,#cbd5e1);background:var(--panel,#fff);color:var(--text,#17212b);border-radius:10px;padding:10px 15px;font:600 13px/1 var(--body,inherit);cursor:pointer;box-shadow:0 1px 2px rgba(15,23,42,.04);transition:.18s ease}
      .landing-file-picker .landing-file-button:hover{border-color:var(--gold,#c97a00);color:var(--gold,#c97a00);background:var(--gold-dim,rgba(201,122,0,.08));transform:translateY(-1px)}
      .landing-file-picker .landing-file-button:focus-visible{outline:3px solid rgba(201,122,0,.2);outline-offset:2px}
      .landing-file-picker .landing-file-button svg{width:17px;height:17px;flex:none}
      .landing-file-picker .landing-file-name{color:var(--text-muted,#64748b);font-size:12px;min-width:0;max-width:420px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    `;
    document.head.appendChild(style);
  }

  function boot() {
    injectStyle();
    initFilePickers();
    // admin-landing-editor menambahkan field logo secara dinamis setelah fetch.
    if (window.MutationObserver) {
      var observer = new MutationObserver(initFilePickers);
      observer.observe(document.body, { childList: true, subtree: true });
      window.setTimeout(function () { observer.disconnect(); }, 10000);
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
