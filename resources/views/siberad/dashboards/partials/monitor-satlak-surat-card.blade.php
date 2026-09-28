{{--
  Kartu Surat untuk section Monitoring per-Satlak (mode Duktek) --
  view-only 100% (tidak ada aksi konfirmasi/disposisi/teruskan/selesai
  apa pun, semua tombol aksi di modal Detail Surat otomatis tidak
  muncul karena semua data-can-*="0").

  Params wajib:
    $s          : LaporanSurat (sudah punya properti tambahan
                  arahUntukSatlak = 'masuk'|'keluar', ditempel di
                  DashboardController::pelaporan())
    $satlakId   : id Satuan Satlak yang sedang dipantau (dipakai buat
                  sudut pandang labelStatus()/badgeClass()/ringkasanUntuk()
                  serta kunci Rahasia -- BUKAN id Duktek yang login)
    $namaSatlak : nama Satlak (buat teks aria/label)
--}}
@php
    $arah = $s->arahUntukSatlak ?? (((int) $s->satuan_id === (int) $satlakId) ? 'keluar' : 'masuk');
    $isKeluar = $arah === 'keluar';

    // Lawan bicara (buat filter Satuan di toolbar & label ringkas) --
    // satuan di seberang Satlak yang sedang dipantau. Asal/Tujuan
    // sebenarnya (pengirim asli & pemegang surat saat ini) TETAP
    // ditampilkan apa adanya di badan kartu di bawah, terlepas dari
    // $lawan ini.
    $lawan = $isKeluar ? $s->tujuanSatuan : $s->satuan;

    // Kunci Rahasia: sama persis aturan LaporanSurat::ringkasanUntuk() --
    // isi surat Rahasia cuma boleh dibaca satuan pengirim ASLI. Sudut
    // pandangnya sengaja pakai $satlakId (Satlak yang dipantau), BUKAN
    // satuan Duktek yang login, supaya konsisten dengan apa yang boleh
    // dilihat Satlak itu sendiri.
    $sembunyikanIsiRahasia = $s->isRahasia() && (int) ($satlakId ?? 0) !== (int) $s->satuan_id;
    $rahasiaTerkunci = $sembunyikanIsiRahasia;
    $aksiBerisiIsiSurat = [\App\Models\LaporanSuratRiwayat::AKSI_BUAT_SURAT, \App\Models\LaporanSuratRiwayat::AKSI_SURAT_KELUAR];

    $tembusanPerRiwayat = $s->tembusans->groupBy('laporan_surat_riwayat_id');
    $riwayatJson = $s->riwayats->map(function ($r) use ($sembunyikanIsiRahasia, $aksiBerisiIsiSurat, $tembusanPerRiwayat) {
        $tembusanStep = $tembusanPerRiwayat->get($r->id, collect());
        $paralel = $tembusanStep->map(fn ($t) => [
            'satuan'           => $t->satuan->nama ?? '-',
            'satuan_kode'      => $t->satuan->kode ?? '-',
            'jenis'            => $t->jenis,
            'label_jenis'      => $t->labelJenis(),
            'sudah_konfirmasi' => $t->dikonfirmasi_at !== null,
            'dikonfirmasi_at'  => $t->dikonfirmasi_at ? $t->dikonfirmasi_at->translatedFormat('d M Y H:i') : null,
        ])->values()->toArray();

        return [
            'aksi'          => $r->labelAksi(),
            'aksi_kode'     => $r->aksi,
            'pengirim'      => $r->pengirimSatuan->nama ?? '-',
            'penerima'      => $r->penerimaSatuan->nama ?? null,
            'catatan'       => ($sembunyikanIsiRahasia && in_array($r->aksi, $aksiBerisiIsiSurat, true)) ? '' : $r->catatan,
            'disposisi'     => $r->disposisi,
            'tindakan'      => $r->tindakan,
            'lampiran_url'  => $r->lampiran_path ? asset('storage/' . $r->lampiran_path) : null,
            'lampiran_nama' => $r->lampiran_nama_asli,
            'tanggal'       => $r->created_at->translatedFormat('d M Y H:i'),
            'siklus'        => $r->siklus,
            'paralel'       => $paralel,
        ];
    })->toJson();

    // Sistem ini belum punya kolom "Nomor Surat" tersendiri (LaporanSurat
    // cuma punya perihal, tanpa penomoran resmi) -- dipakai nomor urut
    // referensi dari id asli surat (BUKAN data dummy/karangan) supaya
    // tetap ada identitas ringkas per kartu.
    $nomorSurat = 'SR-' . str_pad((string) $s->id, 6, '0', STR_PAD_LEFT);
@endphp
<div class="surat-file-card{{ $rahasiaTerkunci ? ' surat-file-card-locked' : '' }}"
     data-surat-id="{{ $s->id }}"
     data-created-at="{{ $s->created_at->timestamp }}"
     data-search="{{ strtolower($nomorSurat.' '.$s->perihal.' '.($s->satuan->nama ?? '').' '.($s->tujuanSatuan->nama ?? '')) }}"
     data-prioritas="{{ $s->prioritas }}"
     data-arah="{{ $arah }}"
     data-jenis-filter="{{ $s->prioritas }}"
     data-satuan-kode="{{ $lawan->kode ?? '' }}"
     data-satuan-nama="{{ $lawan->nama ?? ($lawan->kode ?? '-') }}"
     data-status="{{ $s->labelStatus($satlakId) }}">
    @if($rahasiaTerkunci)
    <div class="surat-file-card-icon surat-file-card-icon-locked"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2.2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></div>
    @else
    <div class="surat-file-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></div>
    @endif

    <div class="surat-card-tags">
        <span class="status-badge {{ $s->badgeClass($satlakId) }}">{{ $s->labelStatus($satlakId) }}</span>
        <span class="surat-arah surat-arah-{{ $isKeluar ? 'keluar' : 'masuk' }}">{{ $isKeluar ? '↑ Surat Keluar' : '↓ Surat Masuk' }}</span>
    </div>

    <div class="surat-file-card-title" style="margin-bottom:6px">{{ $s->perihal }}</div>
    <div style="font-size:11px;color:var(--text-muted);margin-bottom:14px">No. {{ $nomorSurat }}</div>

    <div><div class="surat-file-card-dari-label">Asal / Pengirim</div><div class="surat-file-card-dari-value"><span>{{ $s->satuan->nama ?? '-' }}</span><span class="satuan-pill">{{ $s->satuan->kode ?? '-' }}</span></div></div>
    <div style="margin-top:12px"><div class="surat-file-card-dari-label">Tujuan</div><div class="surat-file-card-dari-value"><span>{{ $s->tujuanSatuan->nama ?? '-' }}</span><span class="satuan-pill">{{ $s->tujuanSatuan->kode ?? '-' }}</span></div></div>

    <div class="surat-file-card-divider"></div>
    <div class="surat-file-card-meta">
        <span class="surat-file-card-meta-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span>
        <div><div class="surat-file-card-meta-label">Tanggal</div><div class="surat-file-card-meta-value">{{ $s->created_at->translatedFormat('d M Y H:i') }}</div></div>
    </div>
    @if($s->prioritas)
    <div style="margin-top:12px"><span class="priority-tag prio-{{ strtolower($s->prioritas) }}">{{ $s->prioritas }}</span></div>
    @endif
    <div class="surat-file-card-divider"></div>

    @if($rahasiaTerkunci)
    <button type="button" class="surat-file-card-btn surat-file-card-btn-locked" disabled aria-label="Surat rahasia -- tidak dapat dibuka untuk {{ $namaSatlak }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0"><rect x="5" y="11" width="14" height="9" rx="2.2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>Rahasia</button>
    @else
    <button type="button" class="surat-file-card-btn" onclick="openSuratDetail(this)"
        data-context="arsip"
        data-perihal="{{ e($s->perihal) }}"
        data-tujuan="{{ e($s->tujuanSatuan->nama ?? '-') }}"
        data-tujuan-kode="{{ e($s->tujuanSatuan->kode ?? '') }}"
        data-kategori="{{ e($s->kategori ?: 'Umum') }}"
        data-prioritas="{{ e($s->prioritas) }}"
        data-status="{{ $s->labelStatus($satlakId) }}"
        data-deskripsi="{{ e($s->ringkasanUntuk($satlakId)) }}"
        data-rahasia="{{ $s->isRahasia() ? '1' : '0' }}"
        data-dari="{{ e($s->satuan->nama ?? '-') }}"
        data-dari-kode="{{ e($s->satuan->kode ?? '') }}"
        data-hide-tujuan="0"
        data-hide-disposisi="0"
        data-dibuat-oleh="{{ e($s->satuan->nama ?? '-') }}"
        data-dibuat-tanggal="{{ e($s->created_at->translatedFormat('d M Y H:i')) }}"
        data-dikonfirmasi-oleh="{{ e($s->dikonfirmasiOleh->name ?? '') }}"
        data-dikonfirmasi-tanggal="{{ $s->dikonfirmasi_at ? e($s->dikonfirmasi_at->translatedFormat('d M Y H:i')) : '' }}"
        data-lampiran-url="{{ $s->lampiran_path ? asset('storage/'.$s->lampiran_path) : '' }}"
        data-lampiran-nama="{{ $s->lampiran_path ? ($s->lampiran_nama_asli ?: basename($s->lampiran_path)) : '' }}"
        data-lampiran-size="{{ $s->lampiran_size ?? '' }}"
        data-surat-id="{{ $s->id }}"
        data-is-selesai="{{ $s->isSelesai() ? '1' : '0' }}"
        data-siklus="{{ $s->siklus }}"
        data-disposisi="{{ e($s->disposisi_terakhir ?? $s->disposisi ?? '') }}"
        data-tindakan="{{ json_encode($s->tindakan_terakhir ?? $s->tindakan ?? []) }}"
        data-riwayat="{{ $riwayatJson }}"
        data-can-confirm="0"
        data-can-confirm-tembusan="0"
        data-can-teruskan="0"
        data-can-ke-danpus="0"
        data-can-selesai="0"
        data-can-disposisi-ulang="0"
        data-readonly="1"
        data-readonly-text="Mode pemantauan Duktek -- hanya untuk dilihat, tidak ada tindakan yang bisa dilakukan dari sini."
        data-deadline="{{ $s->deadline_at ? $s->deadline_at->translatedFormat('d M Y H:i') : '' }}"
    >Lihat Detail</button>
    @endif
</div>
