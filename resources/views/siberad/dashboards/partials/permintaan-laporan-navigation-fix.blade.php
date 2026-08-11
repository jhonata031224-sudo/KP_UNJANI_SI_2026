@php
    $kodePermintaanNav = strtoupper((string) ($satuan->kode ?? ''));
    $permintaanNavRoles = ['DANPUS', 'WADAN', 'SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK', 'SATLAKDUKTEK', 'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT'];
@endphp

@if(in_array($kodePermintaanNav, $permintaanNavRoles, true))
<style>
body.report-tab-mode .pimp-page > * { display:none !important; }
body.report-tab-mode .pimp-page > #kirim,
body.report-tab-mode .pimp-page > #riwayat,
body.report-tab-mode .pimp-page > #permintaan-laporan,
body.report-tab-mode .pimp-page > #masuk,
body.report-tab-mode .pimp-page > #monitoring { display:block !important; }
</style>
<script>
(function () {
    const tabs = ['kirim','riwayat','permintaan-laporan','masuk','monitoring'];
    const labels = {
        'permintaan laporan':'permintaan-laporan',
        'riwayat laporan':'riwayat',
        'kirim laporan':'kirim',
        'laporan masuk':'masuk'
    };

    function text(el){ return (el?.textContent || '').replace(/\s+/g,' ').trim().toLowerCase(); }
    function links(label){ return [...document.querySelectorAll('.side-sub-link')].filter(el => text(el) === label); }

    function showTab(tab, updateUrl = true) {
        const target = document.getElementById(tab);
        if (!target) return false;

        document.body.classList.add('report-tab-mode');

        tabs.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const active = id === tab;
            el.hidden = !active;
            el.style.display = active ? '' : 'none';
        });

        document.querySelectorAll('.side-sub-link').forEach(el => el.classList.remove('active'));
        const label = Object.keys(labels).find(key => labels[key] === tab);
        if (label) {
            const link = links(label)[0];
            if (link) link.classList.add('active');
        }

        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.pushState({reportTab: tab}, '', url);
        }
        return true;
    }

    function bind() {
        const requestLinks = links('permintaan laporan');
        if (requestLinks.length > 1) requestLinks.slice(1).forEach(el => el.remove());

        Object.entries(labels).forEach(([label, tab]) => {
            const link = links(label)[0];
            if (!link || link.dataset.instantReportTab === tab) return;
            link.dataset.instantReportTab = tab;
            link.href = '?tab=' + encodeURIComponent(tab);
            link.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();
                showTab(tab, true);
            }, true);
        });

        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        if (tab && tabs.includes(tab)) showTab(tab, false);
    }

    window.addEventListener('popstate', function () {
        const tab = new URLSearchParams(window.location.search).get('tab');
        if (tab && tabs.includes(tab)) showTab(tab, false);
    });

    function init(n=0){
        if (document.getElementById('kirim') || document.getElementById('riwayat') || document.getElementById('permintaan-laporan')) bind();
        else if (n < 30) setTimeout(() => init(n+1), 50);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => init(), {once:true});
    else init();
})();
</script>
@endif
