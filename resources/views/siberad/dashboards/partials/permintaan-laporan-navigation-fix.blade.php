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

        // Jangan bergantung pada urutan menu atau listener navigasi lama.
        // Menu Permintaan Laporan harus selalu punya target #permintaan-laporan.
        var allLinks = Array.prototype.slice.call(document.querySelectorAll('.side-sub-link'));
        var link = allLinks.find(function (item) {
            return (item.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase() === 'permintaan laporan';
        });

        if (!section || !link) {
            if ((attempt || 0) < 20) {
                window.setTimeout(function () { initPermintaanNavigation((attempt || 0) + 1); }, 50);
            }
            return;
        }

        section.style.scrollMarginTop = '100px';
        link.setAttribute('href', '#permintaan-laporan');
        link.dataset.permintaanNavigation = '1';

        function openPermintaan(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
                if (typeof event.stopImmediatePropagation === 'function') event.stopImmediatePropagation();
            }

            // Pastikan section tidak tertutup oleh wrapper feature.
            var wrapper = document.getElementById('permintaanLaporanFeature');
            if (wrapper && wrapper.contains(section)) {
                var target = document.querySelector('.pimp-page') || document.querySelector('.content');
                if (target) target.insertBefore(section, target.querySelector('#monitoring, #riwayat') || null);
                wrapper.remove();
            }

            section.hidden = false;
            section.style.display = '';
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            document.querySelectorAll('.side-sub-link').forEach(function (item) {
                item.classList.toggle('active', item === link);
            });
            try { history.replaceState(null, '', '#permintaan-laporan'); } catch (e) {}
        }

        // Listener capture di document dipasang paling awal saat klik terjadi.
        // Ini mencegah listener navigasi lama mengarahkan Permintaan Laporan
        // ke #riwayat atau section lain.
        if (!document.documentElement.dataset.permintaanCaptureBound) {
            document.documentElement.dataset.permintaanCaptureBound = '1';
            document.addEventListener('click', function (event) {
                var target = event.target && event.target.closest ? event.target.closest('a.side-sub-link') : null;
                if (!target) return;
                if ((target.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase() !== 'permintaan laporan') return;
                var currentSection = document.getElementById('permintaan-laporan');
                if (!currentSection) return;
                event.preventDefault();
                event.stopPropagation();
                if (typeof event.stopImmediatePropagation === 'function') event.stopImmediatePropagation();
                currentSection.style.scrollMarginTop = '100px';
                currentSection.hidden = false;
                currentSection.style.display = '';
                currentSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.querySelectorAll('.side-sub-link').forEach(function (item) {
                    item.classList.toggle('active', item === target);
                });
                try { history.replaceState(null, '', '#permintaan-laporan'); } catch (e) {}
            }, true);
        }

        if (link.dataset.permintaanDirectBound !== '1') {
            link.dataset.permintaanDirectBound = '1';
            link.addEventListener('click', openPermintaan, true);
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
