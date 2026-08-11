@php
    $kodePermintaanNav = strtoupper((string) ($satuan->kode ?? ''));
    $permintaanNavRoles = ['DANPUS', 'WADAN', 'SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK', 'SATLAKDUKTEK', 'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT'];
@endphp

@if(in_array($kodePermintaanNav, $permintaanNavRoles, true))
<script>
(function () {
    function initPermintaanNavigation(attempt) {
        var section = document.getElementById('permintaan-laporan');
        var group = document.getElementById('{{ in_array($kodePermintaanNav, ['DANPUS', 'WADAN'], true) ? 'reportGroup' : 'laporanGroup' }}');
        var nav = group ? group.querySelector('.side-subnav > div') : null;

        if (!section || !nav) {
            if ((attempt || 0) < 12) window.requestAnimationFrame(function () { initPermintaanNavigation((attempt || 0) + 1); });
            return;
        }

        section.style.scrollMarginTop = '100px';

        var link = nav.querySelector('a[href="#permintaan-laporan"]');
        if (!link) {
            link = document.createElement('a');
            link.href = '#permintaan-laporan';
            link.className = 'side-sub-link';
            link.innerHTML = '<span class="sub-dot"></span>Permintaan Laporan';
            nav.appendChild(link);
        }

        if (link.dataset.permintaanNavigationBound !== '1') {
            link.dataset.permintaanNavigationBound = '1';
            link.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                try { history.replaceState(null, '', '#permintaan-laporan'); } catch (e) {}
            }, true);
        }

        if (window.location.hash === '#permintaan-laporan') {
            window.requestAnimationFrame(function () {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initPermintaanNavigation(0); }, { once: true });
    } else {
        initPermintaanNavigation(0);
    }
})();
</script>
@endif
