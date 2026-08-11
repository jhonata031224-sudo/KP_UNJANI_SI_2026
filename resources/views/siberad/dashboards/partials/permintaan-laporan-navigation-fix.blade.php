@php
    $kodePermintaanNav = strtoupper((string) ($satuan->kode ?? ''));
    $permintaanNavRoles = ['DANPUS', 'WADAN', 'SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK', 'SATLAKDUKTEK', 'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT'];
@endphp

@if(in_array($kodePermintaanNav, $permintaanNavRoles, true))
<script>
(function () {
    /*
     * Pelaporan memakai satu shell dashboard, tetapi setiap submenu harus
     * berperilaku seperti halaman tersendiri. Jangan lagi memakai hash untuk
     * berpindah antar section karena listener lama dapat salah membuka Riwayat.
     */
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
        var requestSection = document.getElementById('permintaan-laporan');
        var historySection = document.getElementById('riwayat');
        var sendSection = document.getElementById('kirim');
        var incomingSection = document.getElementById('masuk');

        var requestLinks = findLinks('permintaan laporan');
        var historyLinks = findLinks('riwayat laporan');
        var sendLinks = findLinks('kirim laporan');
        var incomingLinks = findLinks('laporan masuk');

        /* Hilangkan duplikat Permintaan Laporan yang pernah dibuat oleh
         * beberapa enhancement lama. Sisakan satu link saja. */
        if (requestLinks.length > 1) {
            requestLinks.slice(1).forEach(function (link) { link.remove(); });
            requestLinks = [requestLinks[0]];
        }

        function bind(link, tab, section) {
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

        bind(requestLinks[0], 'permintaan-laporan', requestSection);
        bind(historyLinks[0], 'riwayat', historySection);
        bind(sendLinks[0], 'kirim', sendSection);
        bind(incomingLinks[0], 'masuk', incomingSection);

        /* Capture terakhir untuk memutus listener navigasi lama yang mungkin
         * masih terpasang dari view/partial sebelumnya. */
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

        var sectionIds = ['kirim', 'riwayat', 'permintaan-laporan', 'masuk', 'monitoring'];
        var targetId = tab;
        if (tab === 'permintaan-laporan') targetId = 'permintaan-laporan';

        var target = document.getElementById(targetId);
        if (!target) return;

        /* Sembunyikan konten pelaporan lain sehingga submenu benar-benar
         * terasa sebagai halaman terpisah, bukan dua halaman yang tercampur. */
        sectionIds.forEach(function (id) {
            var section = document.getElementById(id);
            if (!section) return;
            section.hidden = id !== targetId;
            section.style.display = id === targetId ? '' : 'none';
        });

        var requestLink = findLinks('permintaan laporan')[0];
        var historyLink = findLinks('riwayat laporan')[0];
        var sendLink = findLinks('kirim laporan')[0];
        var incomingLink = findLinks('laporan masuk')[0];
        var activeLink = tab === 'permintaan-laporan' ? requestLink
            : tab === 'riwayat' ? historyLink
            : tab === 'kirim' ? sendLink
            : tab === 'masuk' ? incomingLink : null;
        setActive(activeLink);
    }

    function init(attempt) {
        var requestSection = document.getElementById('permintaan-laporan');
        var historySection = document.getElementById('riwayat');
        var anySection = requestSection || historySection || document.getElementById('kirim') || document.getElementById('masuk');
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
