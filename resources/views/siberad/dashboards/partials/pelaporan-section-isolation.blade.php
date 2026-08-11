@php
    $pelaporanIsolationRoles = ['DANPUS', 'WADAN', 'SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK', 'SATLAKDUKTEK', 'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT'];
@endphp
@if(in_array(strtoupper((string) ($satuan->kode ?? '')), $pelaporanIsolationRoles, true))
<style>
    /* Riwayat dan Permintaan adalah dua tampilan terpisah. */
    #permintaan-laporan.pelaporan-section-hidden,
    #riwayat.pelaporan-section-hidden,
    #kirim.pelaporan-section-hidden { display:none !important; }
</style>
<script>
(function () {
    function label(el) {
        return (el && el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function setActive(target) {
        document.querySelectorAll('.side-sub-link').forEach(function (el) {
            el.classList.remove('active');
        });
        var links = document.querySelectorAll('.side-sub-link');
        links.forEach(function (el) {
            var t = label(el);
            if ((target === 'permintaan-laporan' && t === 'permintaan laporan') ||
                (target === 'riwayat' && t === 'riwayat laporan') ||
                (target === 'kirim' && t === 'kirim laporan')) {
                el.classList.add('active');
            }
        });
        var dashboard = document.querySelector('.side-link[href="#dashboard"]');
        if (dashboard) dashboard.classList.remove('active');
    }

    function showOnly(target) {
        var sections = ['kirim', 'riwayat', 'permintaan-laporan'];
        var found = false;
        sections.forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            if (id === target) {
                el.classList.remove('pelaporan-section-hidden');
                el.hidden = false;
                el.style.removeProperty('display');
                found = true;
            } else {
                el.classList.add('pelaporan-section-hidden');
                el.hidden = true;
            }
        });
        if (found) setActive(target);
    }

    function currentTarget() {
        var hash = (window.location.hash || '').replace('#', '');
        if (hash === 'permintaan-laporan' || hash === 'riwayat' || hash === 'kirim') return hash;
        return null;
    }

    function bind() {
        var requestLinks = Array.prototype.slice.call(document.querySelectorAll('.side-sub-link'));
        requestLinks.forEach(function (link) {
            var text = label(link);
            if (text !== 'permintaan laporan' && text !== 'riwayat laporan' && text !== 'kirim laporan') return;
            if (link.dataset.sectionIsolationBound === '1') return;
            link.dataset.sectionIsolationBound = '1';
            var target = text === 'permintaan laporan' ? 'permintaan-laporan' : (text === 'riwayat laporan' ? 'riwayat' : 'kirim');
            link.setAttribute('href', '#' + target);
            link.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (event.stopImmediatePropagation) event.stopImmediatePropagation();
                history.pushState({ pelaporanTab: target }, '', '#' + target);
                showOnly(target);
            }, true);
        });

        window.addEventListener('hashchange', function () {
            var target = currentTarget();
            if (target) showOnly(target);
        });

        var target = currentTarget();
        if (target) showOnly(target);
        else {
            /* Default role view: jangan biarkan Permintaan menempel di atas Riwayat. */
            var first = document.getElementById('kirim') ? 'kirim' : 'riwayat';
            showOnly(first);
        }
    }

    function init(attempt) {
        if (!document.getElementById('riwayat') && !document.getElementById('kirim')) {
            if ((attempt || 0) < 30) setTimeout(function () { init((attempt || 0) + 1); }, 50);
            return;
        }
        bind();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { init(0); }, { once: true });
    else init(0);
})();
</script>
@endif
