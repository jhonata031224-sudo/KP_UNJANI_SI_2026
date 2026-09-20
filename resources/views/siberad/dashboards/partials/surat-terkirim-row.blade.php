@php
    // Status surat keluar: Diteruskan, Menunggu Konfirmasi, dsb -- dilihat dari
    // sudut pandang PENGIRIM ASLI ($satuan), supaya "Dikonfirmasi" tidak
    // menyesatkan seolah suratnya sudah tuntas padahal cuma penerima
    // berikutnya (mis. Wadan) yang baru konfirmasi terima satu langkah.
    $viewerSatuanId = $satuan->id ?? $s->satuan_id;
    $badgeCls   = $s->badgeClass($viewerSatuanId);
    $badgeLabel = $s->labelStatus($viewerSatuanId);

    // Riwayat untuk timeline
    // Sembunyikan isi surat (catatan langkah Buat Surat / Surat Keluar berisi
    // deskripsi lengkap) dari satuan lain kalau prioritas Rahasia -- sama
    // aturannya dengan LaporanSurat::ringkasanUntuk().
    $sembunyikanIsiRahasia = $s->isRahasia() && (int) ($satuan->id ?? $s->satuan_id) !== (int) $s->satuan_id;
    $aksiBerisiIsiSurat    = [\App\Models\LaporanSuratRiwayat::AKSI_BUAT_SURAT, \App\Models\LaporanSuratRiwayat::AKSI_SURAT_KELUAR];
    $riwayats    = $s->riwayats;

    // Kelompokkan tembusan per riwayat_id agar cabang paralel (mis. Urdal
    // View Only saat Wadan disposisi ke Satuan) ikut tampil di modal Surat
    // Terkirim juga -- konsisten dengan Surat Masuk & Arsip.
    $tembusanPerRiwayat = $s->tembusans->groupBy('laporan_surat_riwayat_id');

    // Alur Tujuan: dari sudut pandang pengirim asli, "Tujuan" tidak boleh cuma
    // satu satuan (tujuan_satuan_id sekarang, yang berpindah tiap diteruskan)
    // -- itu bikin satuan perantara (mis. Wadan) seolah hilang begitu Wadan
    // meneruskan ke satuan lain. Bangun rantai penerima berurutan dalam
    // siklus berjalan (Buat/Surat Keluar -> Teruskan -> Teruskan -> ...)
    // supaya modal menampilkan alur "Wadan -> Satlak Dukteksi", bukan cuma
    // "Satlak Dukteksi". Disimpan sebagai array hop (nama+kode) supaya bisa
    // dirender sebagai chip alur workflow, bukan cuma teks digabung "›".
    $aksiTujuanChain = [
        \App\Models\LaporanSuratRiwayat::AKSI_BUAT_SURAT,
        \App\Models\LaporanSuratRiwayat::AKSI_SURAT_KELUAR,
        \App\Models\LaporanSuratRiwayat::AKSI_TERUSKAN,
    ];
    $tujuanHops = $riwayats
        ->where('siklus', $s->siklus)
        ->whereIn('aksi', $aksiTujuanChain)
        ->sortBy('created_at')
        ->map(fn ($r) => $r->penerimaSatuan ? ['nama' => $r->penerimaSatuan->nama, 'kode' => $r->penerimaSatuan->kode] : null)
        ->filter()
        ->values();
    if ($tujuanHops->isEmpty() && ($s->tujuanSatuan ?? null)) {
        $tujuanHops = collect([['nama' => $s->tujuanSatuan->nama, 'kode' => $s->tujuanSatuan->kode]]);
    }
    $tujuanChainDisplay  = $tujuanHops->isNotEmpty() ? $tujuanHops->pluck('nama')->implode(' › ') : '-';
    $tujuanChainAkhirKode = $tujuanHops->count() > 1 ? null : ($tujuanHops->first()['kode'] ?? '');
    $tujuanHopsJson = $tujuanHops->toJson();

    $riwayatJson = $riwayats->map(function ($r) use ($sembunyikanIsiRahasia, $aksiBerisiIsiSurat, $tembusanPerRiwayat) {
        $tembusanStep = $tembusanPerRiwayat->get($r->id, collect());
        $paralel = $tembusanStep->map(fn ($t) => [
            'satuan'          => $t->satuan->nama ?? '-',
            'satuan_kode'     => $t->satuan->kode ?? '-',
            'jenis'           => $t->jenis, // view_only | tembusan | hasil_rc
            'label_jenis'     => $t->labelJenis(),
            'sudah_konfirmasi'=> $t->dikonfirmasi_at !== null,
            'dikonfirmasi_at' => $t->dikonfirmasi_at ? $t->dikonfirmasi_at->translatedFormat('d M Y H:i') : null,
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
            'paralel'       => $paralel, // penerima paralel/bersamaan (Urdal view-only, dst)
        ];
    })->toJson();
@endphp
<div class="surat-file-card" data-surat-id="{{ $s->id }}" data-created-at="{{ $s->created_at->timestamp }}" data-search="{{ strtolower($s->perihal.' '.$tujuanChainDisplay.' '.($s->tujuanSatuan->kode ?? '')) }}" data-prioritas="{{ $s->prioritas }}">
    <div class="surat-file-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></div>
    <span class="status-badge {{ $badgeCls }} surat-file-card-badge">{{ $badgeLabel }}</span>
    <div class="surat-file-card-title">{{ $s->perihal }}</div>
    {{-- Cover kartu Danpus: keterangan "Kepada" disembunyikan (tujuan tetap tampil di dalam modal Detail). --}}
    @if(strtoupper($satuan->kode ?? '') !== 'DANPUS')
    <div><div class="surat-file-card-dari-label">Kepada</div><div class="surat-file-card-dari-value surat-tujuan-flow surat-tujuan-flow-sm">@foreach($tujuanHops as $hop)@if(!$loop->first)<span class="surat-tujuan-flow-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>@endif<span class="surat-tujuan-flow-step{{ $loop->last ? ' is-final' : '' }}">{{ $hop['nama'] }}</span>@endforeach</div></div>
    @endif
    <div class="surat-file-card-divider"></div>
    <div class="surat-file-card-meta"><span class="surat-file-card-meta-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span><div><div class="surat-file-card-meta-label">Tanggal Dibuat</div><div class="surat-file-card-meta-value">{{ $s->created_at->translatedFormat('d M Y H:i') }}</div></div></div>
    <div class="surat-file-card-divider"></div>
    <button type="button" class="surat-file-card-btn" onclick="openSuratDetail(this)"
        data-context="keluar"
        data-perihal="{{ e($s->perihal) }}"
        data-tujuan="{{ e($tujuanChainDisplay) }}"
        data-tujuan-kode="{{ e($tujuanChainAkhirKode ?? '') }}"
        data-tujuan-hops="{{ $tujuanHopsJson }}"
        data-kategori="{{ e($s->kategori ?: 'Umum') }}"
        data-prioritas="{{ e($s->prioritas) }}"
        data-status="{{ $badgeLabel }}"
        data-deskripsi="{{ e($s->ringkasanUntuk($satuan->id ?? $s->satuan_id)) }}"
        data-rahasia="{{ $s->isRahasia() ? '1' : '0' }}"
        data-dari="{{ e($satuan->nama ?? $s->satuan->nama ?? '-') }}"
        data-dibuat-oleh="{{ e($satuan->nama ?? $s->satuan->nama ?? '-') }}"
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
        data-hide-disposisi="{{ strtoupper($satuan->kode ?? '') === 'DANPUS' ? '1' : '0' }}"
        data-tindakan="{{ json_encode($s->tindakan_terakhir ?? $s->tindakan ?? []) }}"
        data-riwayat="{{ $riwayatJson }}"
        data-can-confirm="0"
        data-can-confirm-tembusan="0"
        data-can-teruskan="0"
        data-can-ke-danpus="0"
        data-can-selesai="0"
        data-can-disposisi-ulang="0"
    >Lihat Detail</button>
</div>
