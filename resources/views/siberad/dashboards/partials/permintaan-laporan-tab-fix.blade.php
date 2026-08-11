@php
    $permintaanTabRoles = ['DANPUS','WADAN','SATLAKKAL','SATLAKSISOS','SATLAKDAK','SATLAKDUKTEK','BINFUNG','BINUM','DIKLAT','BINMAT'];
@endphp
@if(in_array(strtoupper((string)($satuan->kode ?? '')), $permintaanTabRoles, true))
<script>
(function () {
    function initPermintaanTabFix() {
        var section = document.getElementById('permintaan-laporan');
        if (!section) return;

        var group = document.getElementById('laporanGroup') || document.getElementById('reportGroup');
        if (!group) return;
        var nav = group.querySelector('.side-subnav > div');
        if (!nav) return;

        var link = Array.from(nav.querySelectorAll('a.side-sub-link')).find(function (a) {
            return a.textContent.replace(/\s+/g, ' ').trim().toLowerCase() === 'permintaan laporan';
        });

        if (!link) {
            link = document.createElement('a');
            link.href = '#permintaan-laporan';
            link.className = 'side-sub-link';
            link.innerHTML = '<span class="sub-dot"></span>Permintaan Laporan';
            nav.appendChild(link);
        }

        function activateRequest() {
            section.style.display = '';
            section.hidden = false;
            document.querySelectorAll('.side-sub-link').forEach(function (item) { item.classList.remove('active'); });
            link.classList.add('active');
            var dashboard = document.querySelector('.side-link[href="#dashboard"]');
            if (dashboard) dashboard.classList.remove('active');
        }

        function hideRequestUnlessSelected() {
            if (window.location.hash !== '#permintaan-laporan') {
                section.hidden = true;
                section.style.display = 'none';
                link.classList.remove('active');
            }
        }

        if (link.dataset.permintaanTabFix !== '1') {
            link.dataset.permintaanTabFix = '1';
            link.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (event.stopImmediatePropagation) event.stopImmediatePropagation();
                activateRequest();
                if (window.location.hash !== '#permintaan-laporan') {
                    history.pushState({ tab: 'permintaan-laporan' }, '', '#permintaan-laporan');
                }
            }, true);
        }

        if (!window.__permintaanTabFixBound) {
            window.__permintaanTabFixBound = true;
            window.addEventListener('hashchange', function () {
                if (window.location.hash === '#permintaan-laporan') activateRequest();
                else hideRequestUnlessSelected();
            });
        }

        if (window.location.hash === '#permintaan-laporan') activateRequest();
        else hideRequestUnlessSelected();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { setTimeout(initPermintaanTabFix, 0); }, { once: true });
    } else {
        setTimeout(initPermintaanTabFix, 0);
    }
})();
</script>
@endif
