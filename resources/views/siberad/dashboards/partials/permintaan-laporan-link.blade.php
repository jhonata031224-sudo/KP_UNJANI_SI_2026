@php
    $permintaanLinkRoles = ['DANPUS', 'WADAN', 'SATLAKKAL', 'SATLAKSISOS', 'SATLAKDAK', 'SATLAKDUKTEK', 'BINFUNG', 'BINUM', 'DIKLAT', 'BINMAT'];
@endphp
@if(in_array(strtoupper((string) ($satuan->kode ?? '')), $permintaanLinkRoles, true))
<script>
(function () {
    function init() {
        const group = document.getElementById('laporanGroup');
        const container = group?.querySelector('.side-subnav > div');
        if (!container || container.querySelector('[data-permintaan-link]')) return;
        const link = document.createElement('a');
        link.href = @json(route('permintaan-laporan.index'));
        link.className = 'side-sub-link';
        link.dataset.permintaanLink = '1';
        link.title = 'Permintaan Laporan';
        link.innerHTML = '<span class="sub-dot"></span>Permintaan Laporan';
        container.appendChild(link);
        link.addEventListener('click', function () {
            document.querySelectorAll('.side-sub-link').forEach(el => el.classList.remove('active'));
            link.classList.add('active');
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
@endif
