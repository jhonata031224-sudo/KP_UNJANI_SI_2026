<script>
(function () {
    const dashboardUrl = @json(route('dashboard'));
    const requestUrl = dashboardUrl + '?section=permintaan';
    const params = new URLSearchParams(window.location.search);
    const isRequestPage = params.get('section') === 'permintaan';

    function cleanText(el) {
        return (el?.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function fixReportNavigation() {
        const links = Array.from(document.querySelectorAll('.sidebar a, .side-sub-link, nav a'));
        const historyLink = links.find(link => cleanText(link) === 'riwayat laporan');
        const reportGroup = historyLink?.closest('.side-nav-group');

        if (historyLink) {
            historyLink.href = dashboardUrl + '#riwayat';
            historyLink.classList.toggle('active', !isRequestPage && window.location.hash === '#riwayat');
        }

        let requestLink = links.find(link => cleanText(link) === 'permintaan laporan');
        if (requestLink) {
            requestLink.href = requestUrl;
            requestLink.classList.toggle('active', isRequestPage);
        } else if (reportGroup) {
            const subnav = reportGroup.querySelector('.side-subnav > div') || reportGroup.querySelector('.side-subnav');
            if (subnav) {
                requestLink = document.createElement('a');
                requestLink.className = 'side-sub-link' + (isRequestPage ? ' active' : '');
                requestLink.href = requestUrl;
                requestLink.innerHTML = '<span class="sub-dot"></span><span>Permintaan Laporan</span>';
                subnav.appendChild(requestLink);
            }
        }
    }

    function hideRequestSectionOnNormalDashboard() {
        if (isRequestPage) return;
        const content = document.querySelector('.content, main');
        if (!content) return;

        Array.from(content.querySelectorAll('h1,h2,h3,h4'))
            .filter(h => cleanText(h) === 'permintaan laporan')
            .forEach(heading => {
                let section = heading.closest('.section-block, .section-card, .card, section');
                if (!section) section = heading.parentElement?.parentElement;
                if (section) section.style.display = 'none';
            });
    }

    function init() {
        fixReportNavigation();
        hideRequestSectionOnNormalDashboard();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
</script>
