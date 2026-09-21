@php
    use App\Models\LaporanSurat;
    use App\Models\Satuan;

    // ALUR NAIK: modal "Kirim Surat" -- balasan satuan pelaksana (mis. Duktek)
    // ke Wadan atas surat yang turun dari Danpus. Tombol pemicunya ada di
    // kartu Surat Masuk (surat-masuk-row.blade.php, .surat-file-card-kirim).
    // Hanya dipasang untuk satuan yang memang boleh membalas (bukan
    // Danpus/Wadan/Urdal, lihat LaporanSurat::KODE_TANPA_BALASAN_NAIK).
    $bolehBalasNaik = LaporanSurat::satuanBolehKirimBalasanNaik($satuan->kode ?? null);

    $tembusanBalasan = collect();
    if ($bolehBalasNaik) {
        // Tembusan opsional: satuan lain (Satlak/Sdir/Kasansi/Pok Analis, dst),
        // KECUALI Danpus/Wadan/Urdal (otomatis/terikat struktural) & satuan
        // sendiri -- aturan yang sama divalidasi ulang di controller.
        $tembusanBalasan = collect($satuanSuratTujuanPilihan ?? []);
        if ($tembusanBalasan->isEmpty()) {
            $tembusanBalasan = Satuan::where('kode', '!=', 'ADMIN')->orderBy('nama')->get();
        }
        $tembusanBalasan = $tembusanBalasan
            ->reject(fn ($st) => in_array(strtoupper((string) $st->kode), LaporanSurat::KODE_TANPA_BALASAN_NAIK, true)
                || (int) $st->id === (int) ($satuan->id ?? 0))
            ->values();
    }
@endphp
@if($bolehBalasNaik)
<div class="report-modal" id="suratBalasanModal">
    {{-- .report-modal-card jadi non-scroll (overflow:hidden, lihat surat-card-styles.blade.php)
         supaya sudut kanan-atas/bawah tidak kepotong scrollbar bawaan browser -- scroll asli
         dipindah ke .surat-balasan-scroll di dalamnya (pola sama seperti #suratDetailModal,
         lihat surat-detail-modal.blade.php). --}}
    <div class="report-modal-card" style="max-width:560px">
    <div class="surat-balasan-scroll">
        <div class="report-modal-head" style="padding:20px 24px 0">
            <div style="min-width:0">
                <h3 style="margin:0 0 4px">Kirim Surat</h3>
                <p id="suratBalasanSub" style="margin:0;font-size:12px;color:var(--text-muted);word-break:break-word">Kirim hasil pelaksanaan kembali ke Wadan.</p>
            </div>
            <button type="button" class="btn-icon-close" id="suratBalasanClose" aria-label="Tutup" style="margin-left:auto">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="suratBalasanForm" method="POST" action="{{ route('laporan-surat.store') }}" enctype="multipart/form-data" style="padding:16px 24px 0">
            @csrf
            <input type="hidden" name="induk_surat_id" id="suratBalasanIndukId" value="">

            {{-- Tujuan: selalu Wadan (Urdal tidak jadi gerbang) --}}
            <div class="form-group">
                <label class="form-label">Tujuan</label>
                <div style="padding:9px 12px;border:1px solid var(--border);border-radius:7px;background:var(--panel-alt);font-size:12.5px;font-weight:600">
                    Wadan <span style="font-weight:400;color:var(--text-muted);font-size:10.5px">(otomatis &mdash; perihal, kategori, dan disposisi surat ikut terbawa)</span>
                </div>
            </div>

            {{-- Lampiran (wajib) -- pakai style dropzone yang sama dengan modal
                 "Buat Surat Baru" (.surat-lampiran-zone, lihat CSS & JS-nya di
                 laporan-role.blade.php), bukan input file polos. --}}
            <div class="form-group">
                <label class="form-label" for="suratBalasanLampiran">Lampiran <span style="color:var(--red)">*</span> <span style="font-size:11px;color:var(--text-muted);font-weight:400">(maks. 10MB)</span></label>
                <div class="surat-lampiran-zone" id="suratBalasanLampiranZone">
                    <input type="file" name="lampiran" id="suratBalasanLampiran" class="surat-lampiran-zone-input" required>
                    <div class="surat-lampiran-zone-prompt">
                        <span class="surat-lampiran-zone-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 17l4-4 4 4"></path><path d="M12 13v9"></path><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path></svg>
                        </span>
                        <span class="surat-lampiran-zone-text">
                            <span class="surat-lampiran-zone-text-main">Klik untuk pilih file atau drag &amp; drop</span>
                            <span class="surat-lampiran-zone-text-sub">Maksimal 10 MB</span>
                        </span>
                    </div>
                </div>
                <div class="lampiran-file-list" id="suratBalasanLampiranFileList"></div>
                <span id="suratBalasanLampiranError" style="display:none;color:var(--red);font-size:10.5px;margin-top:4px"></span>
            </div>

            {{-- Catatan (opsional) --}}
            <div class="form-group">
                <label class="form-label" for="suratBalasanCatatan">Catatan <span style="font-size:11px;color:var(--text-muted);font-weight:400">(opsional)</span></label>
                <textarea name="deskripsi" id="suratBalasanCatatan" class="form-textarea" rows="3" maxlength="10000"
                    placeholder="Tambahkan catatan singkat hasil pelaksanaan..." style="resize:none"></textarea>
            </div>

            {{-- Tembusan (opsional) --}}
            @if($tembusanBalasan->isNotEmpty())
            <div class="form-group">
                <label class="form-label">Tembusan <span style="font-size:11px;color:var(--text-muted);font-weight:400">(opsional)</span></label>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:6px;max-height:130px;overflow-y:auto;padding:8px;background:var(--panel-alt);border:1px solid var(--border);border-radius:7px">
                    @foreach($tembusanBalasan as $st)
                        <label style="display:flex;align-items:center;gap:6px;font-size:11.5px;cursor:pointer">
                            <input type="checkbox" name="tembusan[]" value="{{ $st->id }}" style="width:auto">
                            <span>{{ $st->nama_singkat ?: $st->nama }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif
        </form>
    </div>

        <div class="modal-actions" style="padding:16px 24px 20px;gap:10px">
            <button type="button" class="btn" id="suratBalasanBatal">Batal</button>
            <button type="button" class="btn btn-primary" id="suratBalasanSubmit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;margin-right:6px"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <span id="suratBalasanBtnLabel">Kirim Surat</span>
            </button>
        </div>
    </div>
</div>

<script>
(function(){
    var modal = document.getElementById('suratBalasanModal');
    if (!modal) return;

    var form      = document.getElementById('suratBalasanForm');
    var zone      = document.getElementById('suratBalasanLampiranZone');
    var fileInput = document.getElementById('suratBalasanLampiran');
    var fileList  = document.getElementById('suratBalasanLampiranFileList');
    var fileError = document.getElementById('suratBalasanLampiranError');
    var submitBtn = document.getElementById('suratBalasanSubmit');
    var btnLabel  = document.getElementById('suratBalasanBtnLabel');
    var MAKS_BYTE = 10 * 1024 * 1024;
    var sedangKirim = false;

    function close(){ modal.classList.remove('open'); }

    function tampilError(pesan){
        zone.classList.toggle('field-invalid', !!pesan);
        fileError.textContent = pesan || '';
        fileError.style.display = pesan ? 'block' : 'none';
    }

    function formatSize(bytes){
        if (bytes < 1024 * 1024) return Math.max(1, Math.round(bytes / 1024)) + ' KB';
        return (bytes / 1024 / 1024).toFixed(1) + ' MB';
    }

    // Render kartu file terpilih di bawah dropzone (nama, ukuran, tombol
    // hapus) -- pola sama dengan .lampiran-file-row di modal Buat Surat/
    // Kirim Laporan/Kirim Kendala, lihat CSS-nya di
    // permintaan-laporan-deadline-styles.blade.php.
    function render(){
        fileList.innerHTML = '';
        var file = fileInput.files && fileInput.files[0];
        if (!file) return;

        var badge = (window.siberadLampiranBadge && window.siberadLampiranBadge(file.name)) || {text: 'FILE', cls: 'lfx-other'};
        var row = document.createElement('div');
        row.className = 'lampiran-file-row';

        var icon = document.createElement('span');
        icon.className = 'lampiran-file-row-icon ' + badge.cls;
        icon.textContent = badge.text;

        var info = document.createElement('span');
        info.className = 'lampiran-file-row-info';
        var name = document.createElement('span');
        name.className = 'lampiran-file-row-name';
        name.textContent = file.name;
        var size = document.createElement('span');
        size.className = 'lampiran-file-row-size';
        size.textContent = formatSize(file.size);
        info.appendChild(name);
        info.appendChild(size);

        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'lampiran-file-row-remove';
        removeBtn.setAttribute('aria-label', 'Hapus file');
        removeBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>';
        removeBtn.addEventListener('click', function(){
            fileInput.value = '';
            render();
        });

        row.appendChild(icon);
        row.appendChild(info);
        row.appendChild(removeBtn);
        fileList.appendChild(row);
    }

    // Dipanggil dari tombol "Kirim Surat" di kartu Surat Masuk
    // (surat-masuk-row.blade.php). Kartu bisa dirender ulang oleh polling
    // realtime, makanya handler ini global & baca data dari tombol yang diklik.
    window.bukaKirimBalasanSurat = function(btn){
        form.reset();
        tampilError('');
        render();
        sedangKirim = false;
        submitBtn.disabled = false;
        btnLabel.textContent = 'Kirim Surat';

        form.action = btn.dataset.action || form.action;
        document.getElementById('suratBalasanIndukId').value = btn.dataset.indukId || '';
        document.getElementById('suratBalasanSub').textContent = 'Balasan untuk surat: ' + (btn.dataset.perihal || '-');

        modal.classList.add('open');
    };

    document.getElementById('suratBalasanClose').addEventListener('click', close);
    document.getElementById('suratBalasanBatal').addEventListener('click', close);
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && modal.classList.contains('open')) close();
    });

    fileInput.addEventListener('change', function(){
        var f = fileInput.files && fileInput.files[0];
        if (f && f.size > MAKS_BYTE) {
            fileInput.value = '';
            tampilError('Ukuran lampiran maksimal 10 MB.');
            render();
            return;
        }
        if (f) tampilError('');
        render();
    });

    ['dragenter', 'dragover'].forEach(function(evt){
        zone.addEventListener(evt, function(e){ e.preventDefault(); e.stopPropagation(); zone.classList.add('is-dragover'); });
    });
    ['dragleave', 'drop'].forEach(function(evt){
        zone.addEventListener(evt, function(){ zone.classList.remove('is-dragover'); });
    });

    submitBtn.addEventListener('click', function(){
        if (sedangKirim) return;
        if (!fileInput.files || !fileInput.files.length) {
            tampilError('Lampiran wajib diisi untuk mengirim surat.');
            zone.scrollIntoView({block: 'center', behavior: 'smooth'});
            return;
        }
        // Cegah kirim ganda (klik dobel): satu kali submit, tombol dikunci.
        sedangKirim = true;
        submitBtn.disabled = true;
        btnLabel.textContent = 'Mengirim...';
        form.submit();
    });
})();
</script>
@endif
