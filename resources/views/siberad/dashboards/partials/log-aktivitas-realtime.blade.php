<script>
(function () {
    const endpoint = '{{ route('laporan.log-aktivitas.realtime') }}';
    let busy = false;
    let timer = null;
    let sinceId = 0;
    const text = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value; };
    function updateStats(stats){ Object.entries(stats||{}).forEach(([id,s])=>{ text(`satlakTotalOverview-${id}`,s.total); text(`satlakTotalMonitoring-${id}`,s.total); text(`satlakDiterima-${id}`,s.diterima); text(`satlakDitolak-${id}`,s.ditolak); text(`satlakMenunggu-${id}`,s.menunggu); }); }
    function poll(){
        if(busy)return; busy=true;
        fetch(endpoint+'?since='+sinceId+'&realtime=1&_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}})
        .then(r=>r.ok?r.json():null).then(data=>{ if(!data)return; if(typeof data.latest_id==='number'&&data.latest_id>sinceId)sinceId=data.latest_id; updateStats(data.stats); text('kpiTotalLaporan',data.total_laporan); text('kpiDisetujuiLaporan',data.total_disetujui); text('kpiDitolakLaporan',data.total_ditolak); })
        .catch(()=>{}).finally(()=>{busy=false;});
    }
    function schedule(){ clearTimeout(timer); timer=setTimeout(()=>{poll();schedule();},2000); }
    function start(){ setTimeout(()=>{poll();schedule();},150); document.addEventListener('visibilitychange',()=>{if(!document.hidden){poll();schedule();}}); window.addEventListener('focus',()=>{poll();schedule();}); }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',start,{once:true}); else start();
})();
</script>
