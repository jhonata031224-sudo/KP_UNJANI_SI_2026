@php
    $kodePermintaanNav = strtoupper((string) ($satuan->kode ?? ''));
    $permintaanNavRoles = ['DANPUS', 'WADAN', 'SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK', 'SATLAKDUKTEK', 'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT'];
@endphp

@if(in_array($kodePermintaanNav, $permintaanNavRoles, true))
<style>
/* Saat submenu Pelaporan dipilih, shell tetap sama tetapi isi bertindak
   seperti satu halaman mandiri. */
body.report-tab-mode .pimp-page > * { display:none !important; }
body.report-tab-mode .pimp-page > #kirim,
body.report-tab-mode .pimp-page > #riwayat,
body.report-tab-mode .pimp-page > #permintaan-laporan,
body.report-tab-mode .pimp-page > #masuk,
body.report-tab-mode .pimp-page > #monitoring { display:block !important; }
</style>
<script>
(function () {
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

    function goTab(tab) {
        var url = new URL('{{ route('dashboard') }}', window.location.origin);
        url.searchParams.set('tab', tab);
        window.location.assign(url.toString());
    }

    function installNavigation() {
        var requestLinks = findLinks('permintaan laporan');
        var historyLinks = findLinks('riwayat laporan');
        var sendLinks = findLinks('kirim laporan');
        var incomingLinks = findLinks('laporan masuk');

        /* Beberapa enhancement lama sempat membuat dua link Permintaan.
           Selalu sisakan satu agar tidak ada menu aktif + menu mati bersamaan. */
        if (requestLinks.length > 1) {
            requestLinks.slice(1).forEach(function (link) { link.remove(); });
            requestLinks = [requestLinks[0]];
        }

        function bind(link, tab) {
            if (!link || link.dataset.reportPageBound === tab) return;
            link.dataset.reportPageBound = tab;
            link.href = '{{ route('dashboard') }}?tab=' + encodeURIComponent(tab);
            link.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (event.stopImmediatePropagation) event.stopImmediatePropagation();
                goTab(tab);
            }, true);
        }

        bind(requestLinks[0], 'permintaan-laporan');
        bind(historyLinks[0], 'riwayat');
        bind(sendLinks[0], 'kirim');
        bind(incomingLinks[0], 'masuk');

        /* Putus listener navigasi lama yang masih memakai hash dan dapat
           mengarahkan Permintaan ke Riwayat. */
        if (!document.documentElement.dataset.reportPageCaptureBound) {
            document.documentElement.dataset.reportPageCaptureBound = '1';
            document.addEventListener('click', function (event) {
                var link = event.target && event.target.closest ? event.target.closest('.side-sub-link, .side-link') : null;
                if (!link) return;
                var label = textOf(link);
                var tab = null;
                if (label === 'permintaan laporan') tab = 'permintaan-laporan';
                else if (label === 'riwayat laporan') tab = 'riwayat';
                else if (label === 'kirim laporan') tab = 'kirim';
                else if (label === 'laporan masuk') tab = 'masuk';
                if (!tab) return;

                event.preventDefault();
                event.stopPropagation();
                if (event.stopImmediatePropagation) event.stopImmediatePropagation();
                goTab(tab);
            }, true);
        }

        applyActiveTab();
    }

    function applyActiveTab() {
        var params = new URLSearchParams(window.location.search);
        var tab = params.get('tab');
        if (!tab) return;

        var targetId = tab;
        if (tab === 'permintaan-laporan') targetId = 'permintaan-laporan';
        var target = document.getElementById(targetId);
        if (!target) return;

        document.body.classList.add('report-tab-mode');

        var sectionIds = ['kirim', 'riwayat', 'permintaan-laporan', 'masuk', 'monitoring'];
        sectionIds.forEach(function (id) {
            var section = document.getElementById(id);
            if (!section) return;
            var active = id === targetId;
            section.hidden = !active;
            section.style.display = active ? '' : 'none';
        });

        var activeLink = tab === 'permintaan-laporan' ? findLinks('permintaan laporan')[0]
            : tab === 'riwayat' ? findLinks('riwayat laporan')[0]
            : tab === 'kirim' ? findLinks('kirim laporan')[0]
            : tab === 'masuk' ? findLinks('laporan masuk')[0] : null;
        setActive(activeLink);
    }

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
