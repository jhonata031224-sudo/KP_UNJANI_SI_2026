@php
    use App\Models\Satuan;
    use App\Models\LaporanSurat;

    // Ambil semua satuan yang bisa menjadi tujuan penerusan (kecuali ADMIN & URDAL)
    $semuaSatuan = Satuan::orderBy('nama')->get()->filter(function ($st) {
        return ! in_array(strtoupper($st->kode), ['ADMIN'], true);
    });

    // Kode satuan yang boleh jadi tujuan penerusan Wadan:
    // hanya Pok Analis, 4 Satlak, dan 4 Sdir (bukan Sansidam/Kotama).
    $kodeWadanTujuan = array_merge(
        Satuan::KODE_UNSUR_PEMBANTU_PIMPINAN, // POKANALIS
        Satuan::KODE_SATLAK,                   // SATLAKKAL, SATLAKSISOS, SATLAKDAK, SATLAKDUKTEK
        Satuan::KODE_PEMBINAAN,                // BINFUNG, BINUM, DIKLAT, BINMAT
    );
    $wadanTujuanIds = $semuaSatuan
        ->filter(fn ($st) => in_array(strtoupper($st->kode), $kodeWadanTujuan, true))
        ->pluck('id')
        ->values()
        ->all();

    $disposisiOptions = LaporanSurat::DISPOSISI_WADAN_OPTIONS;
    $tindakanOptions  = LaporanSurat::TINDAKAN_WADAN_OPTIONS;
@endphp

{{-- ═══════════════════════════════════════════════════
     MODAL: DISPOSISI & TERUSKAN SURAT
     Dipakai oleh: Wadan (teruskan ke Satrap), Danpus (disposisi ulang)
     ═══════════════════════════════════════════════════ --}}
<div class="report-modal" id="suratTeruskanModal" data-wadan-tujuan-ids="{{ json_encode($wadanTujuanIds) }}">
    <div class="report-modal-card" style="max-width:600px">
        <div class="report-modal-head">
            <div style="min-width:0">
                <h3 style="margin:0 0 4px" id="suratTeruskanModalTitle">Disposisi &amp; Teruskan Surat</h3>
                <p id="suratTeruskanModalSub" style="margin:0;font-size:12px;color:var(--text-muted)">Isi disposisi dan pilih tindakan sebelum meneruskan.</p>
            </div>
            <button type="button" class="btn-icon-close" id="suratTeruskanClose" aria-label="Tutup" style="margin-left:auto">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="suratTeruskanForm" method="POST" enctype="multipart/form-data" style="padding:20px 24px 0">
            @csrf
            <input type="hidden" name="_method" id="suratTeruskanMethod" value="POST">

            {{-- Tujuan Satuan --}}
            {{-- Dua select: #suratTeruskanTujuanWadan hanya untuk Wadan (Pok Analis + Satlak + Sdir), --}}
            {{-- #suratTeruskanTujuanDanpus untuk Danpus (semua). JS swap aktif/non-aktif saat buka. --}}
            <div class="form-group" id="suratTeruskanTujuanWrap">
                <label class="form-label" for="suratTeruskanTujuanWadan">Tujuan Penerusan <span style="color:var(--red)">*</span></label>

                {{-- SELECT WADAN: hanya Pok Analis, 4 Satlak, 4 Sdir --}}
                @php
                    $satuanWadan = $semuaSatuan->filter(fn ($st) => in_array(strtoupper($st->kode), $kodeWadanTujuan, true));
                @endphp
                <select name="tujuan_satuan_id" id="suratTeruskanTujuanWadan" class="form-select" required>
                    <option value="">— Pilih Satuan Tujuan —</option>
                    @foreach($satuanWadan as $st)
                        <option value="{{ $st->id }}">{{ $st->nama }} ({{ $st->kode }})</option>
                    @endforeach
                </select>

                {{-- SELECT DANPUS: semua satuan (termasuk Sansidam) --}}
                <select name="tujuan_satuan_id" id="suratTeruskanTujuanDanpus" class="form-select" required style="display:none">
                    <option value="">— Pilih Satuan Tujuan —</option>
                    @foreach($semuaSatuan as $st)
                        <option value="{{ $st->id }}">{{ $st->nama }} ({{ $st->kode }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Disposisi --}}
            <div class="form-group">
                <label class="form-label" for="suratTeruskanDisposisi">Disposisi <span style="color:var(--red)">*</span></label>
                <select name="disposisi" id="suratTeruskanDisposisi" class="form-select" required>
                    <option value="">— Pilih Disposisi —</option>
                    @foreach($disposisiOptions as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tindakan (checkboxes) --}}
            <div class="form-group">
                <label class="form-label">Tindakan <span style="color:var(--red)">*</span> <span style="font-size:11px;color:var(--text-muted);font-weight:400">(Pilih minimal satu)</span></label>
                <div id="suratTeruskanTindakanGrid" style="display:grid;grid-template-columns:1fr 1fr;gap:6px 16px;margin-top:6px;max-height:220px;overflow-y:auto;padding:2px 0;border:1px solid transparent;border-radius:10px">
                    @foreach($tindakanOptions as $opt)
                        <label class="surat-tindakan-check-label" style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;padding:4px 6px;border-radius:6px;transition:background .15s">
                            <input type="checkbox" name="tindakan[]" value="{{ $opt }}"
                                class="surat-tindakan-check"
                                style="width:15px;height:15px;accent-color:var(--primary);cursor:pointer;flex-shrink:0">
                            <span>{{ $opt }}</span>
                        </label>
                    @endforeach
                </div>
                <span id="suratTeruskanTindakanError" style="display:none;align-items:center;gap:6px;color:var(--red);font-size:10.5px;margin-top:4px">Tindakan wajib dipilih (minimal satu).</span>
            </div>

            {{-- Catatan Arahan (opsional) --}}
            <div class="form-group">
                <label class="form-label" for="suratTeruskanCatatan">Catatan Arahan <span style="font-size:11px;color:var(--text-muted);font-weight:400">(opsional)</span></label>
                <textarea name="catatan" id="suratTeruskanCatatan" class="form-textarea"
                    placeholder="Tambahkan catatan atau arahan khusus untuk penerima..."
                    rows="3" style="resize:vertical"></textarea>
            </div>

            {{-- Lampiran Tambahan (opsional) --}}
            <div class="form-group">
                <label class="form-label" for="suratTeruskanLampiran">Lampiran Tambahan <span style="font-size:11px;color:var(--text-muted);font-weight:400">(opsional, maks. 10MB)</span></label>
                <input type="file" name="lampiran" id="suratTeruskanLampiran"
                    class="form-input"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                    style="padding:8px 12px">
                <div id="suratTeruskanLampiranInfo" style="font-size:11px;color:var(--text-muted);margin-top:4px">Kosongkan jika tidak ada lampiran baru (lampiran sebelumnya tetap digunakan).</div>
            </div>
        </form>

        <div class="modal-actions" style="padding:16px 24px 20px;gap:10px">
            <button type="button" class="btn" id="suratTeruskanBatal">Batal</button>
            <button type="button" class="btn btn-primary" id="suratTeruskanSubmit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;margin-right:6px"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <span id="suratTeruskanBtnLabel">Teruskan Surat</span>
            </button>
        </div>
    </div>
</div>

<script>
(function(){
    var modal = document.getElementById('suratTeruskanModal');
    if (!modal) return;

    function close(){
        modal.classList.remove('open');
    }

    document.getElementById('suratTeruskanClose').addEventListener('click', close);
    document.getElementById('suratTeruskanBatal').addEventListener('click', close);
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && modal.classList.contains('open')) close();
    });

    // Buka modal Teruskan dari luar (dipanggil oleh surat-detail-modal)
    // opts.isWadan = true  → pakai select Wadan (Pok Analis + Satlak + Sdir saja)
    // opts.isWadan = false → pakai select Danpus (semua satuan)
    window.bukaSuratTeruskanModal = function(opts){
        var form = document.getElementById('suratTeruskanForm');
        form.action = opts.action;
        document.getElementById('suratTeruskanMethod').value = opts.method || 'POST';
        document.getElementById('suratTeruskanModalTitle').textContent = opts.title || 'Disposisi & Teruskan';
        document.getElementById('suratTeruskanModalSub').textContent = opts.sub || '';
        document.getElementById('suratTeruskanBtnLabel').textContent = opts.btnLabel || 'Teruskan Surat';

        // Reset form
        form.reset();
        document.getElementById('suratTeruskanTindakanError').style.display = 'none';
        document.getElementById('suratTeruskanTindakanGrid').style.borderColor = 'transparent';
        document.getElementById('suratTeruskanTindakanGrid').style.boxShadow = 'none';

        var tujuanWrap   = document.getElementById('suratTeruskanTujuanWrap');
        var selWadan     = document.getElementById('suratTeruskanTujuanWadan');
        var selDanpus    = document.getElementById('suratTeruskanTujuanDanpus');

        if (opts.hideTujuan) {
            // Sembunyikan seluruh field tujuan (mis. kembalikan ke Danpus)
            tujuanWrap.style.display = 'none';
            selWadan.disabled  = true;
            selDanpus.disabled = true;
        } else {
            tujuanWrap.style.display = '';
            if (opts.isWadan) {
                // Tampilkan select Wadan (terbatas), sembunyikan select Danpus
                selWadan.style.display  = '';
                selWadan.disabled       = false;
                selWadan.name           = 'tujuan_satuan_id';
                selDanpus.style.display = 'none';
                selDanpus.disabled      = true;
                selDanpus.name          = '';
            } else {
                // Tampilkan select Danpus (semua), sembunyikan select Wadan
                selDanpus.style.display = '';
                selDanpus.disabled      = false;
                selDanpus.name          = 'tujuan_satuan_id';
                selWadan.style.display  = 'none';
                selWadan.disabled       = true;
                selWadan.name           = '';
            }
        }

        modal.classList.add('open');
    };

    function tindakanValid(){
        var grid = document.getElementById('suratTeruskanTindakanGrid');
        var errEl = document.getElementById('suratTeruskanTindakanError');
        var checks = document.querySelectorAll('.surat-tindakan-check:checked');
        if (checks.length === 0) {
            grid.style.borderColor = 'var(--red)';
            grid.style.boxShadow = '0 0 0 3px color-mix(in srgb,var(--red) 15%,transparent)';
            errEl.style.display = 'flex';
            return false;
        }
        grid.style.borderColor = 'transparent';
        grid.style.boxShadow = 'none';
        errEl.style.display = 'none';
        return true;
    }

    document.getElementById('suratTeruskanTindakanGrid').addEventListener('change', function(){
        if (document.querySelectorAll('.surat-tindakan-check:checked').length > 0) tindakanValid();
    });

    document.getElementById('suratTeruskanForm').addEventListener('submit', function(e){
        if (!tindakanValid()) e.preventDefault();
    });

    document.getElementById('suratTeruskanSubmit').addEventListener('click', function(){
        if (!tindakanValid()) return;
        document.getElementById('suratTeruskanForm').submit();
    });
})();
</script>
