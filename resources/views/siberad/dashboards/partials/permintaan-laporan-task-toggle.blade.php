<script>
(function(){
    // Step chevron task per satuan (lihat permintaan-laporan-item.blade.php) --
    // klik satu step langsung PATCH ke permintaan-laporan.task.toggle tanpa
    // reload; badge persen & state done/active/pending SELURUH track dihitung
    // ulang dari response, revert kalau request gagal. Kartu penuh tetap ikut
    // ter-refresh tiap beberapa detik lewat laporan-role-realtime-sync.blade.php
    // yang sudah ada (full re-render dari partial yang sama), jadi ini cuma
    // buat feedback instan sebelum poll berikutnya nyusul.
    function recomputeSteps(track){
        if (!track) return;
        var active = false;
        Array.from(track.querySelectorAll('.deadline-task-step')).forEach(function (step) {
            var done = step.dataset.selesai === '1';
            var num = step.querySelector('.deadline-task-num');
            step.classList.remove('done', 'active', 'pending');
            if (done) {
                step.classList.add('done');
                if (num) num.textContent = '✓';
            } else if (!active) {
                step.classList.add('active');
                active = true;
                if (num) num.textContent = step.dataset.stepNumber || '';
            } else {
                step.classList.add('pending');
                if (num) num.textContent = step.dataset.stepNumber || '';
            }
        });
    }

    document.addEventListener('click', function (e) {
        var step = e.target.closest('.deadline-task-step');
        if (!step) return;
        var url = step.dataset.toggleUrl;
        if (!url) return;
        var track = step.closest('[data-permintaan-task-track]');
        var token = document.querySelector('input[name="_token"]')?.value
            || document.querySelector('meta[name="csrf-token"]')?.content || '';
        var prevSelesai = step.dataset.selesai;
        step.disabled = true;
        fetch(url, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
        }).then(function (r) {
            if (!r.ok) throw new Error('toggle-failed');
            return r.json();
        }).then(function (data) {
            var card = step.closest('[data-realtime-permintaan-id]');
            var badge = card?.querySelector('.deadline-progress-badge');
            if (badge) badge.textContent = data.progres + '%';
            step.dataset.selesai = data.selesai ? '1' : '0';
            recomputeSteps(track);
        }).catch(function () {
            step.dataset.selesai = prevSelesai;
            recomputeSteps(track);
        }).finally(function () {
            step.disabled = false;
        });
    });
})();
</script>
