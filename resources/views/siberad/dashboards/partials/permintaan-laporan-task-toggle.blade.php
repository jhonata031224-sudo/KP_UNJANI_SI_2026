<script>
(function(){
    // Checklist task per satuan (lihat permintaan-laporan-item.blade.php) --
    // centang/uncentang langsung PATCH ke permintaan-laporan.task.toggle
    // tanpa reload; badge persen & style "selesai" di-update optimis dari
    // response, revert kalau request gagal. Kartu penuh tetap ikut ter-
    // refresh tiap beberapa detik lewat laporan-role-realtime-sync.blade.php
    // yang sudah ada (full re-render dari partial yang sama).
    document.addEventListener('change', function (e) {
        var cb = e.target.closest('.permintaan-task-checkbox');
        if (!cb) return;
        var url = cb.dataset.toggleUrl;
        if (!url) return;
        var token = document.querySelector('input[name="_token"]')?.value
            || document.querySelector('meta[name="csrf-token"]')?.content || '';
        var prevChecked = !cb.checked;
        cb.disabled = true;
        fetch(url, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
        }).then(function (r) {
            if (!r.ok) throw new Error('toggle-failed');
            return r.json();
        }).then(function (data) {
            var card = cb.closest('[data-realtime-permintaan-id]');
            var badge = card?.querySelector('.deadline-progress-badge');
            if (badge) badge.textContent = data.progres + '%';
            cb.closest('.deadline-task-item')?.classList.toggle('done', !!data.selesai);
        }).catch(function () {
            cb.checked = prevChecked;
        }).finally(function () {
            cb.disabled = false;
        });
    });
})();
</script>
