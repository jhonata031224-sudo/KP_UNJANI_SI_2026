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

    function text(el) {
        return (el?.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function links(label) {
        return [...document.querySelectorAll('.side-sub-link')].filter(el => text(el) === label);
    }

    function reveal(el) {
        if (!el) return;
        el.hidden = false;
        el.style.removeProperty('display');
        el.style.removeProperty('visibility');
        el.style.removeProperty('opacity');
        el.setAttribute('aria-hidden', 'false');
    }

    function conceal(el) {
        if (!el) return;
        el.hidden = true;
        el.style.display = 'none';
        el.setAttribute('aria-hidden', 'true');
    }

    function showTab(tab, updateUrl = true) {
        const target = document.getElementById(tab);
        if (!target) return false;

        // Jangan lagi menambahkan class/CSS global yang dapat menyembunyikan
        // seluruh halaman dashboard. Cukup tukar section Pelaporan yang ada.
        tabs.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            if (id === tab) reveal(el);
            else conceal(el);
        });

        // Beberapa view meletakkan section di dalam wrapper yang ikut diset
        // hidden oleh navigasi lama. Pastikan ancestor langsung yang memang
        // berupa container section kembali terlihat, tanpa membuka overlay.
        let parent = target.parentElement;
        let depth = 0;
        while (parent && depth < 4) {
            if (parent.hidden) parent.hidden = false;
            if (parent.style.display === 'none') parent.style.removeProperty('display');
            parent = parent.parentElement;
            depth++;
        }

        document.querySelectorAll('.side-sub-link').forEach(el => el.classList.remove('active'));
        const label = Object.keys(labels).find(key => labels[key] === tab);
        if (label) {
            const link = links(label)[0];
            if (link) link.classList.add('active');
        }

        if (updateUrl) {
            const url = new URL(window.location.href);
            url.hash = tab;
            window.history.pushState({ reportTab: tab }, '', url);
        }

        // Tetap di posisi halaman saat ini; tidak ada reload dan tidak ada
        // loading. Scroll hanya jika target berada di luar viewport.
        const rect = target.getBoundingClientRect();
        if (rect.top < 0 || rect.top > window.innerHeight) {
            target.scrollIntoView({ behavior: 'auto', block: 'start' });
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
            link.href = '#' + tab;
            link.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (event.stopImmediatePropagation) event.stopImmediatePropagation();
                showTab(tab, true);
            }, true);
        });

        const hash = window.location.hash.replace('#', '');
        const queryTab = new URLSearchParams(window.location.search).get('tab');
        const tab = tabs.includes(hash) ? hash : queryTab;
        if (tab) showTab(tab, false);
    }

    window.addEventListener('popstate', function () {
        const hash = window.location.hash.replace('#', '');
        const tab = tabs.includes(hash) ? hash : new URLSearchParams(window.location.search).get('tab');
        if (tab && tabs.includes(tab)) showTab(tab, false);
    });

    window.addEventListener('hashchange', function () {
        const tab = window.location.hash.replace('#', '');
        if (tabs.includes(tab)) showTab(tab, false);
    });

    function init(n = 0) {
        if (document.getElementById('kirim') || document.getElementById('riwayat') || document.getElementById('permintaan-laporan')) {
            bind();
        } else if (n < 30) {
            setTimeout(() => init(n + 1), 50);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => init(), { once: true });
    } else {
        init();
    }
})();
</script>
@endif
