@php
    // Tentukan apakah satuan ini adalah penerima utama atau tembusan/view-only
    $isTujuanUtama = (int) $s->tujuan_satuan_id === (int) $satuan->id;
    $kodeSatuan    = strtoupper((string) $satuan->kode);
    $isWadan       = $kodeSatuan === 'WADAN';
    $isDanpus      = $kodeSatuan === 'DANPUS';

    // Tembusan row jika satuan ini bukan penerima utama
    $tembusanRow = null;
    if (! $isTujuanUtama) {
        $tembusanRow = $s->tembusans->where('satuan_id', $satuan->id)->first();
    }

    $isTembusan = $tembusanRow !== null;
    $jenisTembusan = $tembusanRow ? $tembusanRow->jenis : null;
    $sudahKonfirmasiTembusan = $tembusanRow && $tembusanRow->isDikonfirmasi();

    // Badge tipe penerima
    $tipeLabel = null;
    $tipeCls   = '';
    if ($jenisTembusan === \App\Models\LaporanSuratTembusan::JENIS_VIEW_ONLY) {
        $tipeLabel = 'View Only';
        $tipeCls   = 'status-sedang';
    } elseif ($jenisTembusan === \App\Models\LaporanSuratTembusan::JENIS_TEMBUSAN) {
        $tipeLabel = 'Tembusan';
        $tipeCls   = 'status-info';
    } elseif ($jenisTembusan === \App\Models\LaporanSuratTembusan::JENIS_HASIL_RC) {
        $tipeLabel = 'Hasil / RC';
        $tipeCls   = 'status-disetujui';
    }

    // Status sudah dikonfirmasi (penerima utama)
    $sudahDikonfirmasi = $isTujuanUtama && $s->isDikonfirmasi();

    // Boleh konfirmasi utama?
    $canConfirm = $isTujuanUtama && ! $sudahDikonfirmasi;
    // Boleh konfirmasi tembusan?
    $canConfirmTembusan = $isTembusan && ! $sudahKonfirmasiTembusan;

    // Boleh teruskan? (Wadan sudah konfirmasi, surat berstatus dikonfirmasi, belum selesai)
    $canTeruskan = $isWadan && $isTujuanUtama && $sudahDikonfirmasi && ! $s->isSelesai();

    // Boleh kembalikan ke Danpus? (Wadan setelah menerima laporan dari Satrap)
    // Syarat: Wadan adalah tujuan, sudah dikonfirmasi
    $canKeDanpus = $isWadan && $isTujuanUtama && $sudahDikonfirmasi && ! $s->isSelesai();

    // Boleh selesaikan / disposisi ulang? (Danpus sudah konfirmasi, ada riwayat penerusan)
    $canSelesai        = $isDanpus && $isTujuanUtama && $sudahDikonfirmasi && ! $s->isSelesai() && $s->riwayats->count() > 1;
    $canDisposisiUlang = $canSelesai;

    // Badge status kartu
    if ($isTembusan) {
        $badgeLabel = $sudahKonfirmasiTembusan ? 'Dikonfirmasi' : ($tipeLabel ?? 'Tembusan');
        $badgeCls   = $sudahKonfirmasiTembusan ? 'status-dikonfirmasi' : $tipeCls;
    } else {
        $badgeLabel = $s->isDikonfirmasi() ? 'Dikonfirmasi' : 'Menunggu';
        $badgeCls   = $s->badgeClass();
    }

    // Riwayat untuk timeline
    $riwayats     = $s->riwayats;
    $riwayatJson  = $riwayats->map(fn ($r) => [
        'aksi'            => $r->labelAksi(),
        'pengirim'        => $r->pengirimSatuan->nama ?? '-',
        'penerima'        => $r->penerimaSatuan->nama ?? null,
        'catatan'         => $r->catatan,
        'disposisi'       => $r->disposisi,
        'tindakan'        => $r->tindakan,
        'lampiran_url'    => $r->lampiran_path ? asset('storage/' . $r->lampiran_path) : null,
        'lampiran_nama'   => $r->lampiran_nama_asli,
        'tanggal'         => $r->created_at->translatedFormat('d M Y H:i'),
        'siklus'          => $r->siklus,
    ])->toJson();

    // Disposisi & Tindakan dari surat (untuk tampil di detail)
    $disposisiAktif = $s->disposisi_terakhir ?? $s->disposisi;
    $tindakanAktif  = $s->tindakan_terakhir ?? $s->tindakan;
@endphp
<div class="surat-file-card" data-surat-id="{{ $s->id }}" data-created-at="{{ $s->created_at->timestamp }}" data-search="{{ strtolower($s->perihal.' '.($s->satuan->nama ?? '').' '.($s->satuan->kode ?? '')) }}" data-prioritas="{{ $s->prioritas }}">
    <div class="surat-file-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></div>
    <span class="status-badge {{ $badgeCls }} surat-file-card-badge">{{ $badgeLabel }}</span>
    @if($tipeLabel && ! $sudahKonfirmasiTembusan)
        <span class="status-badge {{ $tipeCls }}" style="font-size:10px;padding:2px 8px;margin-left:4px;">{{ $tipeLabel }}</span>
    @endif
    <div class="surat-file-card-title">{{ $s->perihal }}</div>
    <div><div class="surat-file-card-dari-label">Dari</div><div class="surat-file-card-dari-value"><span>{{ $s->satuan->nama ?? '-' }}</span><span class="satuan-pill">{{ $s->satuan->kode ?? $s->satuan->nama ?? '-' }}</span></div></div>
    <div class="surat-file-card-divider"></div>
    <div class="surat-file-card-meta"><span class="surat-file-card-meta-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span><div><div class="surat-file-card-meta-label">Tanggal Dibuat</div><div class="surat-file-card-meta-value">{{ $s->created_at->translatedFormat('d M Y H:i') }}</div></div></div>
    <div class="surat-file-card-divider"></div>
    <button type="button" class="surat-file-card-btn" onclick="openSuratDetail(this)"
        data-perihal="{{ e($s->perihal) }}"
        data-tujuan="{{ e($satuan->nama ?? '-') }}"
        data-tujuan-kode="{{ e($satuan->kode ?? '') }}"
        data-kategori="{{ e($s->kategori ?: 'Umum') }}"
        data-prioritas="{{ e($s->prioritas) }}"
        data-status="{{ $s->labelStatus() }}"
        data-deskripsi="{{ e($s->ringkasanUntuk($satuan->id ?? null)) }}"
        data-rahasia="{{ $s->isRahasia() ? '1' : '0' }}"
        data-dari="{{ e($s->satuan->nama ?? '-') }}"
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
        data-disposisi="{{ e($disposisiAktif ?? '') }}"
        data-tindakan="{{ e(json_encode($tindakanAktif ?? [])) }}"
        data-riwayat="{{ e($riwayatJson) }}"
        data-jenis-tembusan="{{ e($jenisTembusan ?? '') }}"
        data-can-confirm="{{ $canConfirm ? '1' : '0' }}"
        data-can-confirm-tembusan="{{ $canConfirmTembusan ? '1' : '0' }}"
        data-can-teruskan="{{ $canTeruskan ? '1' : '0' }}"
        data-can-ke-danpus="{{ $canKeDanpus ? '1' : '0' }}"
        data-can-selesai="{{ $canSelesai ? '1' : '0' }}"
        data-can-disposisi-ulang="{{ $canDisposisiUlang ? '1' : '0' }}"
        @if($canConfirm)
        data-confirm-action="{{ route('laporan-surat.konfirmasi', $s) }}"
        data-confirm-token="{{ csrf_token() }}"
        @endif
        @if($canConfirmTembusan)
        data-confirm-tembusan-action="{{ route('laporan-surat.konfirmasi-tembusan', $s) }}"
        @endif
        @if($canTeruskan || $canDisposisiUlang)
        data-teruskan-action="{{ route('laporan-surat.teruskan', $s) }}"
        data-disposisi-ulang-action="{{ route('laporan-surat.disposisi-ulang', $s) }}"
        @endif
        @if($canKeDanpus)
        data-ke-danpus-action="{{ route('laporan-surat.ke-danpus', $s) }}"
        @endif
        @if($canSelesai)
        data-selesai-action="{{ route('laporan-surat.selesai', $s) }}"
        @endif
        data-csrf="{{ csrf_token() }}"
    >Lihat Detail</button>
</div>
