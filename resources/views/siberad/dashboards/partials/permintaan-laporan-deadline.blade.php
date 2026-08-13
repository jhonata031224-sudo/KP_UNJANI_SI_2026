<style>
.deadline-section,.deadline-sender-section{position:relative;overflow:hidden}.deadline-primary,.deadline-secondary{border:1px solid transparent;border-radius:9px;padding:9px 14px;font-size:11px;font-weight:700;cursor:pointer;transition:.15s ease}.deadline-primary{background:var(--p-accent,var(--gold-bright));color:#fff}.deadline-primary:hover{filter:brightness(1.06);transform:translateY(-1px)}.deadline-secondary{background:var(--p-surface-2,var(--panel-alt));border-color:var(--p-border,var(--border));color:var(--p-text,var(--text))}.deadline-secondary:hover{border-color:var(--p-accent,var(--gold-bright))}.deadline-primary.small,.deadline-secondary.small{padding:7px 10px;font-size:10px}.kirim-laporan-btn{letter-spacing:.03em;box-shadow:0 8px 18px -8px rgba(212,175,55,.55)}.kirim-laporan-btn:hover{box-shadow:0 10px 22px -6px rgba(212,175,55,.65)}.deadline-form-wrap{margin:0 0 18px;padding:16px;border:1px solid var(--p-border,var(--border-soft));border-radius:12px;background:var(--p-surface-2,var(--panel-alt))}.deadline-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.deadline-field{display:flex;flex-direction:column;gap:6px}.deadline-field.full{grid-column:1/-1}.deadline-field label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--p-muted,var(--text-muted))}.deadline-field input,.deadline-field select,.deadline-field textarea{width:100%;box-sizing:border-box;border:1px solid var(--p-border,var(--border));border-radius:8px;background:var(--p-surface,var(--panel));color:var(--p-text,var(--text));padding:9px 10px;font:inherit;font-size:12px}.deadline-field textarea{min-height:100px;resize:vertical}.deadline-check-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}.deadline-check{display:grid;grid-template-columns:auto 1fr;column-gap:7px;align-items:center;padding:9px;border:1px solid var(--p-border,var(--border));border-radius:9px;background:var(--p-surface,var(--panel));cursor:pointer}.deadline-check input{width:auto}.deadline-check span{font-family:var(--mono);font-size:10px;font-weight:800;color:var(--p-accent,var(--gold-bright))}.deadline-check small{grid-column:2;font-size:9px;color:var(--p-muted,var(--text-muted));line-height:1.35}.deadline-form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}.deadline-table-wrap{overflow-x:auto}.deadline-table{width:100%;border-collapse:collapse;min-width:720px}.deadline-table th{font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:var(--p-muted,var(--text-muted));text-align:left;padding:10px;border-bottom:1px solid var(--p-border,var(--border))}.deadline-table td{padding:11px 10px;border-bottom:1px solid var(--p-border,var(--border));font-size:11px;vertical-align:middle}.deadline-table td strong{display:block}.deadline-table td small{display:block;color:var(--p-muted,var(--text-muted));margin-top:3px;max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.deadline-pill{display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:9px;font-weight:800;border:1px solid transparent;white-space:nowrap}.deadline-pill.wait{color:#a36d00;background:rgba(224,168,58,.12);border-color:rgba(224,168,58,.3)}.deadline-pill.ok{color:#16834b;background:rgba(63,194,125,.12);border-color:rgba(63,194,125,.28)}.deadline-pill.bad{color:#c83b3b;background:rgba(181,52,47,.08);border-color:rgba(198,40,40,.28)}.deadline-pill.blue{color:#2476ad;background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.25)}.deadline-empty{text-align:center!important;color:var(--p-muted,var(--text-muted));padding:25px!important}.deadline-empty.sender{padding:38px 20px!important;border:2px dotted var(--border-soft);border-radius:12px;background:var(--panel-alt)}.deadline-sender-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}.deadline-sender-head h3{margin:0 0 4px}.deadline-sender-head p{margin:0;font-size:11px;color:var(--text-muted);line-height:1.5}.deadline-count{font-family:var(--mono);font-size:10px;color:var(--gold-bright);white-space:nowrap}.deadline-sender-list{display:grid;gap:10px}.deadline-sender-item{display:flex;justify-content:space-between;gap:16px;padding:13px;border:1px solid var(--border-soft);border-radius:10px;background:var(--panel-alt)}.deadline-sender-item.near{border-color:rgba(224,168,58,.45)}.deadline-sender-item.bad{border-color:rgba(198,40,40,.35)}.deadline-sender-main{min-width:0}.deadline-sender-title{font-size:13px;font-weight:800}.deadline-sender-meta{font-size:10px;color:var(--text-muted);margin-top:4px}.deadline-sender-instruction{font-size:11px;line-height:1.55;color:var(--text-muted);margin-top:8px;white-space:pre-wrap}.deadline-sender-side{display:flex;flex-direction:column;align-items:flex-end;justify-content:space-between;gap:8px;flex-shrink:0}.deadline-actions{display:flex;gap:6px;align-items:center;justify-content:flex-end}.deadline-complete{font-size:10px;font-weight:700;color:var(--green)}
@media(max-width:850px){.deadline-check-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.deadline-form-grid{grid-template-columns:1fr}.deadline-field.full{grid-column:auto}.deadline-sender-item{flex-direction:column}.deadline-sender-side{align-items:flex-start}.deadline-actions{justify-content:flex-start}}@media(max-width:550px){.deadline-check-grid{grid-template-columns:1fr}.deadline-sender-head{display:block}.deadline-count{display:inline-block;margin-top:8px}}
</style>

<script>
(function(){
    // "Kirim Laporan" sekarang berupa modal (#kirimLaporanModal) yang dibuka
    // dari section #permintaan-laporan, bukan tab tersendiri lagi -- jadi
    // tombol "Buat Laporan" di sini tinggal membuka modal itu sambil
    // mengisi form-nya, tidak perlu pindah tab.
    function initUsePermintaanButtons(){
        document.querySelectorAll('.use-permintaan').forEach(function(btn){
            if(btn.dataset.useBound === '1') return;
            btn.dataset.useBound = '1';
            btn.addEventListener('click',function(){
                var form=document.querySelector('form[action$="/laporan"]');
                var modal=document.getElementById('kirimLaporanModal');
                if(!form || !modal) return;
                var hidden=form.querySelector('input[name="permintaan_laporan_id"]');
                if(!hidden){hidden=document.createElement('input');hidden.type='hidden';hidden.name='permintaan_laporan_id';form.appendChild(hidden)}
                hidden.value=btn.dataset.requestId||'';
                var tujuan=form.querySelector('[name="tujuan_satuan_id"]'); if(tujuan && btn.dataset.targetId) tujuan.value=btn.dataset.targetId;
                var perihal=form.querySelector('[name="perihal"]'); if(perihal && btn.dataset.perihal) perihal.value=btn.dataset.perihal;
                var kategori=form.querySelector('[name="proyek"]'); if(kategori && btn.dataset.kategori) kategori.value=btn.dataset.kategori;
                var prioritas=form.querySelector('[name="prioritas"]'); if(prioritas && btn.dataset.prioritas) prioritas.value=btn.dataset.prioritas;
                var deskripsi=form.querySelector('[name="deskripsi"]'); if(deskripsi && btn.dataset.instruksi && !deskripsi.value.trim()) deskripsi.value=btn.dataset.instruksi;
                modal.classList.add('open');
                perihal?.focus();
            });
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initUsePermintaanButtons); else initUsePermintaanButtons();

    // Pencarian daftar Permintaan Laporan -- reuse gaya .rpt-filter-* yang
    // sama dengan tabel lain (1 sistem), tapi logikanya custom karena isinya
    // kartu <article> bukan baris <tr>.
    function initPermintaanSearch(){
        var section=document.getElementById('permintaan-laporan');
        if(!section||section.dataset.searchReady==='1') return;
        var list=section.querySelector('.deadline-sender-list');
        if(!list) return;
        var items=Array.prototype.slice.call(list.querySelectorAll(':scope > article.deadline-sender-item'));
        if(!items.length) return;
        section.dataset.searchReady='1';

        var bar=document.createElement('div');
        bar.className='rpt-filter-bar';
        bar.innerHTML='<div class="rpt-filter-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input type="search" autocomplete="off" placeholder="Cari perihal..." aria-label="Cari perihal"></div><span class="rpt-filter-count"></span>';
        list.parentNode.insertBefore(bar,list);

        var emptyBox=document.createElement('div');
        emptyBox.className='rpt-filter-empty';
        emptyBox.style.display='none';
        emptyBox.innerHTML='<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 8px;display:block;opacity:.7"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>Tidak ada permintaan laporan yang sesuai dengan pencarian.';
        list.parentNode.insertBefore(emptyBox,list.nextSibling);

        var input=bar.querySelector('input');
        var count=bar.querySelector('.rpt-filter-count');

        function apply(){
            var q=(input.value||'').trim().toLowerCase();
            var visible=0;
            items.forEach(function(item){
                var match=!q||(item.dataset.search||'').indexOf(q)!==-1;
                item.style.display=match?'':'none';
                if(match)visible++;
            });
            count.textContent=visible+' dari '+items.length+' data';
            emptyBox.style.display=visible===0?'block':'none';
        }
        input.addEventListener('input',apply);
        apply();
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initPermintaanSearch); else initPermintaanSearch();
})();
</script>
