@php
    $kodePermintaanNav = strtoupper((string) ($satuan->kode ?? ''));
    // Hanya Danpus/Wadan yang memakai struktur .pimp-page yang jadi sasaran perbaikan ini.
    // Role satuan (Satlak, Bin*, Diklat) memakai shell laporan-role yang sudah punya
    // switching tab sendiri; ikut memasukkannya di sini malah membuat klik submenu
    // "Kirim/Riwayat/Laporan Masuk" ter-intercept tanpa ada yang tampil.
    $permintaanNavRoles = ['DANPUS', 'WADAN'];
@endphp

@if(in_array($kodePermintaanNav, $permintaanNavRoles, true))
<style>
body.report-tab-mode .pimp-page > * { display:none !important; }
body.report-tab-mode .pimp-page > #kirim,
body.report-tab-mode .pimp-page > #riwayat,
body.report-tab-mode .pimp-page > #permintaan-laporan,
body.report-tab-mode .pimp-page > #masuk,
body.report-tab-mode .pimp-page > #monitoring { display:block !important; }

/* Perpindahan antar submenu tidak memakai loading/animasi halaman. */
.report-tab-switching { opacity:1 !important; }
</style>
<script>
(function () {
    var TAB_IDS = ['kirim', 'riwayat', 'permintaan-laporan', 'masuk', 'monitoring'];

    function textOf(el) {
        return (el && el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function findLinks(label) {
        return Array.prototype.slice.call(document.querySelectorAll('.side-sub-link'))
            .filter(function (item) { return textOf(item) === label; });
    }

    function setActive(link) {
        document.querySelectorAll('.side-sub-link').forEach(function (item) {
            item.classList.remove('active');
        });
        if (link) link.classList.add('active');
    }

    function getTabFromUrl() {
        return new URLSearchParams(window.location.search).get('tab');
    }

    function setUrl(tab, replace) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        url.hash = '';
        if (replace) history.replaceState({ reportTab: tab }, '', url.toString());
        else history.pushState({ reportTab: tab }, '', url.toString());
    }

    function applyTab(tab, updateUrl) {
        if (TAB_IDS.indexOf(tab) === -1) tab = 'kirim';

        var target = document.getElementById(tab);
        if (!target) return false;

        /* Semua section sudah berada di DOM. Jadi cukup tukar visibility;
           tidak ada request HTTP, reload, spinner, atau perpindahan halaman. */
        document.body.classList.add('report-tab-mode');
        TAB_IDS.forEach(function (id) {
            var section = document.getElementById(id);
            if (!section) return;
            var active = id === tab;
            section.hidden = !active;
            section.style.display = active ? '' : 'none';
            section.classList.toggle('report-tab-switching', active);
        });

        var activeLink = tab === 'permintaan-laporan' ? findLinks('permintaan laporan')[0]
            : tab === 'riwayat' ? findLinks('riwayat laporan')[0]
            : tab === 'kirim' ? findLinks('kirim laporan')[0]
            : tab === 'masuk' ? findLinks('laporan masuk')[0] : null;
        setActive(activeLink);

        if (updateUrl) setUrl(tab, false);
        return true;
    }

    function installNavigation() {
        var requestLinks = findLinks('permintaan laporan');
        var historyLinks = findLinks('riwayat laporan');
        var sendLinks = findLinks('kirim laporan');
        var incomingLinks = findLinks('laporan masuk');

        /* Sisakan satu Permintaan Laporan jika enhancement lama pernah membuat
           duplikat. */
        if (requestLinks.length > 1) {
            requestLinks.slice(1).forEach(function (link) { link.remove(); });
            requestLinks = [requestLinks[0]];
        }

        function bind(link, tab) {
            if (!link || link.dataset.reportPageBound === tab) return;
            link.dataset.reportPageBound = tab;
            link.href = '?tab=' + encodeURIComponent(tab);
            link.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (event.stopImmediatePropagation) event.stopImmediatePropagation();
                applyTab(tab, true);
            }, true);
        }

        bind(requestLinks[0], 'permintaan-laporan');
        bind(historyLinks[0], 'riwayat');
        bind(sendLinks[0], 'kirim');
        bind(incomingLinks[0], 'masuk');

        /* Menangkap listener lama yang masih mencoba melakukan navigasi/reload. */
        if (!document.documentElement.dataset.reportPageCaptureBound) {
            document.documentElement.dataset.reportPageCaptureBound = '1';
            document.addEventListener('click', function (event) {
                var link = event.target && event.target.closest ? event.target.closest('.side-sub-link, .side-link') : null;
                if (!link) return;
                var label = textOf(link);
                var tab = label === 'permintaan laporan' ? 'permintaan-laporan'
                    : label === 'riwayat laporan' ? 'riwayat'
                    : label === 'kirim laporan' ? 'kirim'
                    : label === 'laporan masuk' ? 'masuk' : null;
                if (!tab) return;

                event.preventDefault();
                event.stopPropagation();
                if (event.stopImmediatePropagation) event.stopImmediatePropagation();
                applyTab(tab, true);
            }, true);
        }

        var initialTab = getTabFromUrl();
        if (initialTab) applyTab(initialTab, false);
    }

    window.addEventListener('popstate', function () {
        var tab = getTabFromUrl();
        if (tab) applyTab(tab, false);
    });

    function init(attempt) {
        var anySection = document.getElementById('permintaan-laporan')
            || document.getElementById('riwayat')
            || document.getElementById('kirim')
            || document.getElementById('masuk');
        if (!anySection) {
            if ((attempt || 0) < 30) window.setTimeout(function () { init((attempt || 0) + 1); }, 50);
            return;
        }
        installNavigation();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(0); }, { once: true });
    } else {
        init(0);
    }
})();
</script>
@endif
