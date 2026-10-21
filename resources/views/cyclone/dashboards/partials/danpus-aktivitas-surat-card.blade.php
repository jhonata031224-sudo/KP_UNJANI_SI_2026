{{--
  Kartu Surat pada Riwayat Aktivitas DANPUS (tab "Surat" per satuan) --
  view-only. Semua data-can-* = 0 sehingga modal Detail Surat (openSuratDetail)
  tidak menampilkan tombol aksi; tindakan surat tetap lewat menu Surat.

  Params:
    $s        : LaporanSurat dengan properti tambahan dari
                App\Support\AktivitasSatuanDanpus (arahUntukSatuan,
                balasanDikirim, hanyaTembusan, diarsipkan, menungguDanpus)
    $sid      : id satuan yang sedang dipantau (sudut pandang label status)
    $danpusId : id Danpus (sudut pandang isi ringkasan, lihat ringkasanUntuk)

  Sistem belum punya kolom nomor surat resmi; dipakai nomor referensi dari id
  surat (SR-000123), sama dengan kartu surat pemantauan Duktek.
--}}
@php
    $isKeluar = ($s->arahUntukSatuan ?? 'masuk') === 'keluar';
    $lawan = $isKeluar ? $s->tujuanSatuan : $s->satuan;
    $nomorSurat = 'SR-' . str_pad((string) $s->id, 6, '0', STR_PAD_LEFT);
    $labelStatus = $s->labelStatus($sid);
    $kategori = $s->kategori ?: 'Umum';

    // Aturan Rahasia sama dengan LaporanSurat::ringkasanUntuk(): isi hanya untuk
    // pengirim asli. Sudut pandangnya Danpus (yang sedang login), jadi surat
    // Rahasia buatan Danpus sendiri terbuka, selain itu ringkasan dikosongkan.
    $sembunyikanIsi = $s->isRahasia() && (int) $danpusId !== (int) $s->satuan_id;
    $aksiBerisiIsi = [\App\Models\LaporanSuratRiwayat::AKSI_BUAT_SURAT, \App\Models\LaporanSuratRiwayat::AKSI_SURAT_KELUAR];
    $tembusanPerRiwayat = $s->tembusans->groupBy('laporan_surat_riwayat_id');
    $riwayatJson = $s->riwayats->map(function ($r) use ($sembunyikanIsi, $aksiBerisiIsi, $tembusanPerRiwayat) {
        $paralel = $tembusanPerRiwayat->get($r->id, collect())->map(fn ($t) => [
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
            'catatan'       => ($sembunyikanIsi && in_array($r->aksi, $aksiBerisiIsi, true)) ? '' : $r->catatan,
            'disposisi'     => $r->disposisi,
            'tindakan'      => $r->tindakan,
            'lampiran_url'  => $r->lampiran_path ? asset('storage/' . $r->lampiran_path) : null,
            'lampiran_nama' => $r->lampiran_nama_asli,
            'tanggal'       => $r->created_at->translatedFormat('d M Y H:i'),
            'siklus'        => $r->siklus,
            'paralel'       => $paralel,
        ];
    })->toJson();
@endphp
<div class="surat-file-card"
     data-surat-id="{{ $s->id }}"
     data-created-at="{{ $s->created_at->timestamp }}"
     data-search="{{ strtolower($nomorSurat.' '.$s->perihal.' '.$kategori.' '.($s->satuan->nama ?? '').' '.($s->tujuanSatuan->nama ?? '')) }}"
     data-arah="{{ $isKeluar ? 'keluar' : 'masuk' }}"
     data-status="{{ $labelStatus }}"
     data-arsip="{{ $s->diarsipkan ? '1' : '0' }}"
     data-perlu="{{ $s->menungguDanpus ? '1' : '0' }}">
    <div class="surat-file-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></div>

    <div class="surat-card-tags">
        <span class="status-badge {{ $s->badgeClass($sid) }}">{{ $labelStatus }}</span>
        <span class="surat-arah surat-arah-{{ $isKeluar ? 'keluar' : 'masuk' }}">{{ $isKeluar ? '↑ Surat Keluar' : '↓ Surat Masuk' }}</span>
        @if($s->diarsipkan)<span class="akt-tag akt-tag-arsip">Arsip</span>@endif
        @if($s->menungguDanpus)<span class="akt-tag akt-tag-perlu">Menunggu Danpus</span>@endif
        @if($s->balasanDikirim)<span class="akt-tag">Balasan dikirim</span>@endif
        @if($s->hanyaTembusan)<span class="akt-tag">Tembusan</span>@endif
    </div>

    <div class="surat-file-card-title" style="margin-bottom:6px">{{ $s->perihal }}</div>
    <div style="font-size:11px;color:var(--p-muted);margin-bottom:14px">No. {{ $nomorSurat }} &middot; {{ $kategori }}</div>

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

    <div class="akt-card-actions">
    <button type="button" class="surat-file-card-btn" onclick="openSuratDetail(this)"
        data-context="arsip"
        data-perihal="{{ e($s->perihal) }}"
        data-tujuan="{{ e($s->tujuanSatuan->nama ?? '-') }}"
        data-tujuan-kode="{{ e($s->tujuanSatuan->kode ?? '') }}"
        data-kategori="{{ e($kategori) }}"
        data-prioritas="{{ e($s->prioritas) }}"
        data-status="{{ $labelStatus }}"
        data-deskripsi="{{ e($s->ringkasanUntuk($danpusId)) }}"
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
        data-readonly-text="Mode pemantauan -- hanya untuk dilihat. Tindakan surat dilakukan lewat menu Surat."
        data-deadline="{{ $s->deadline_at ? $s->deadline_at->translatedFormat('d M Y H:i') : '' }}"
    >Lihat Detail</button>
    @if($s->menungguDanpus)
    <a href="#surat-masuk" class="akt-link-tindak">Tindak lanjuti &rarr;</a>
    @endif
    </div>
</div>
