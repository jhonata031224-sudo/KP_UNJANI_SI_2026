@php
    $kodePermintaanNav = strtoupper((string) ($satuan->kode ?? ''));
    $permintaanNavRoles = ['DANPUS', 'WADAN', 'SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK', 'SATLAKDUKTEK', 'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT'];
@endphp

@if(in_array($kodePermintaanNav, $permintaanNavRoles, true))
<script>
(function () {
    const tabs = ['kirim', 'riwayat', 'permintaan-laporan', 'masuk', 'monitoring'];
    const labels = {
        'permintaan laporan': 'permintaan-laporan',
        'riwayat laporan': 'riwayat',
        'kirim laporan': 'kirim',
        'laporan masuk': 'masuk'
    };

    const normalize = el => (el?.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
    const getLinks = label => [...document.querySelectorAll('.side-sub-link')].filter(el => normalize(el) === label);

    function getSection(tab) {
        return document.getElementById(tab)
            || document.querySelector('[data-report-section="' + tab + '"]');
    }

    function setVisible(el, visible) {
        if (!el) return;
        el.hidden = !visible;
        if (visible) {
            el.style.removeProperty('display');
            el.style.removeProperty('visibility');
        } else {
            el.style.display = 'none';
        }
    }

    function showTab(tab, push = true) {
        const target = getSection(tab);
        if (!target) return false;

        // Sembunyikan dashboard dan hanya tampilkan SATU halaman Pelaporan.
        const dashboard = document.getElementById('dashboard');
        if (dashboard && dashboard !== target) setVisible(dashboard, false);

        tabs.forEach(id => {
            const section = getSection(id);
            if (section && section !== target) setVisible(section, false);
        });
        setVisible(target, true);

        // Jika target berada di wrapper yang sebelumnya ikut disembunyikan,
        // buka hanya ancestor sampai shell utama, bukan seluruh dashboard.
        let parent = target.parentElement;
        let depth = 0;
        while (parent && depth < 3) {
            if (parent.hidden) parent.hidden = false;
            if (parent.style.display === 'none') parent.style.removeProperty('display');
            parent = parent.parentElement;
            depth++;
        }

        document.querySelectorAll('.side-sub-link').forEach(el => el.classList.remove('active'));
        const activeLabel = Object.keys(labels).find(key => labels[key] === tab);
        if (activeLabel) {
            const activeLink = getLinks(activeLabel)[0];
            if (activeLink) activeLink.classList.add('active');
        }

        if (push) {
            const url = new URL(window.location.href);
            url.hash = tab;
            history.pushState({ reportTab: tab }, '', url);
        }
        return true;
    }

    function bind() {
        const requestLinks = getLinks('permintaan laporan');
        if (requestLinks.length > 1) requestLinks.slice(1).forEach(el => el.remove());

        Object.entries(labels).forEach(([label, tab]) => {
            const link = getLinks(label)[0];
            if (!link || link.dataset.instantReportTab === tab) return;
            link.dataset.instantReportTab = tab;
            link.href = '#' + tab;
            link.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                showTab(tab, true);
            }, true);
        });

        const hash = window.location.hash.substring(1);
        const queryTab = new URLSearchParams(window.location.search).get('tab');
        if (tabs.includes(hash)) showTab(hash, false);
        else if (tabs.includes(queryTab)) showTab(queryTab, false);
    }

    window.addEventListener('popstate', () => {
        const tab = window.location.hash.substring(1);
        if (tabs.includes(tab)) showTab(tab, false);
    });

    window.addEventListener('hashchange', () => {
        const tab = window.location.hash.substring(1);
        if (tabs.includes(tab)) showTab(tab, false);
    });

    function init(attempt = 0) {
        const ready = tabs.some(tab => getSection(tab));
        if (ready) bind();
        else if (attempt < 40) setTimeout(() => init(attempt + 1), 50);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => init(), { once: true });
    } else {
        init();
    }
})();
</script>
@endif
