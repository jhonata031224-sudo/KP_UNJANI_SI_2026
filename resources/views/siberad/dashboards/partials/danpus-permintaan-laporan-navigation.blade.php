<script>
(function () {
    const requestUrl = @json(route('permintaan-laporan.index'));
    const dashboardUrl = @json(route('dashboard'));

    function cleanText(el) {
        return (el?.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function fixReportNavigation() {
        const links = Array.from(document.querySelectorAll('.sidebar a, .side-sub-link, nav a'));
        const historyLink = links.find(link => cleanText(link) === 'riwayat laporan');
        const reportGroup = historyLink?.closest('.side-nav-group');

        // Riwayat Laporan tetap menuju bagian riwayat pada dashboard.
        if (historyLink) {
            historyLink.href = dashboardUrl + '#riwayat';
        }

        // Permintaan Laporan adalah halaman mandiri, bukan bagian dari Riwayat.
        let requestLink = links.find(link => cleanText(link) === 'permintaan laporan');
        if (requestLink) {
            requestLink.href = requestUrl;
            requestLink.classList.add('active');
        } else if (reportGroup) {
            const subnav = reportGroup.querySelector('.side-subnav > div') || reportGroup.querySelector('.side-subnav');
            if (subnav) {
                requestLink = document.createElement('a');
                requestLink.className = 'side-sub-link';
                requestLink.href = requestUrl;
                requestLink.innerHTML = '<span class="sub-dot"></span><span>Permintaan Laporan</span>';
                subnav.appendChild(requestLink);
            }
        }
    }

    function hideRequestSectionOnDashboard() {
        // Halaman dashboard hanya menampilkan Riwayat Laporan.
        // Permintaan Laporan dibuka melalui menu halaman mandiri.
        const content = document.querySelector('.content, main');
        if (!content) return;

        const headings = Array.from(content.querySelectorAll('h1,h2,h3,h4'));
        headings.filter(h => cleanText(h) === 'permintaan laporan').forEach(heading => {
            let section = heading.closest('.section-block, .section-card, .card, section');
            if (!section) {
                section = heading.parentElement?.parentElement;
            }
            if (section) section.style.display = 'none';
        });
    }

    function init() {
        fixReportNavigation();
        hideRequestSectionOnDashboard();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
</script>
