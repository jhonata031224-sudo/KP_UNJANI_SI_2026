@php
    // Arsip Surat gabungan dua arah -- kartu ini bisa mewakili surat yang
    // KIRIM sendiri (satuan_id === $satuan->id) atau surat yang MASUK ke
    // satuan ini dan sudah DIA SENDIRI konfirmasi
    // (tujuan_satuan_id === $satuan->id). $lawan = pihak SEBERANG (siapa
    // yang bukan $satuan) buat field "Dari/Ke" & data detail modal.
    $isSent = (int) $s->satuan_id === (int) $satuan->id;

    // Tujuan yang DIPILIH sisi yang sedang melihat kartu -- BUKAN pemegang surat
    // saat ini ($s->tujuanSatuan, yang terus berpindah tiap diteruskan).
    // Contoh alur Danpus > Wadan > Satlak Dukteksi:
    //   - Danpus  -> Ke: Wadan          (tujuan yang Danpus pilih saat membuat surat)
    //   - Wadan   -> Ke: Satlak Duktek  (satuan yang Wadan pilih saat disposisi)
    //   - Satlak  -> Dari: Danpus       (penerima akhir, tidak meneruskan lagi)
    // = penerima dari langkah kirim PERTAMA oleh satuan ini di siklus berjalan.
    $aksiKirim = [
        \App\Models\LaporanSuratRiwayat::AKSI_BUAT_SURAT,
        \App\Models\LaporanSuratRiwayat::AKSI_SURAT_KELUAR,
        \App\Models\LaporanSuratRiwayat::AKSI_TERUSKAN,
    ];
    $langkahKirimSaya = $s->riwayats
        ->where('siklus', $s->siklus)
        ->whereIn('aksi', $aksiKirim)
        ->where('pengirim_satuan_id', $satuan->id)
        ->whereNotNull('penerima_satuan_id')
        ->sortBy('id')
        ->first();
    $pilihanSaya = $langkahKirimSaya?->penerimaSatuan;
    $tampilKe    = $pilihanSaya !== null;

    $lawan = $tampilKe ? $pilihanSaya : ($isSent ? $s->tujuanSatuan : $s->satuan);
    $labelLawan = ($tampilKe || $isSent) ? 'Ke' : 'Dari';
    // Pengirim ASLI surat (tetap sama untuk semua sisi) -- dipakai untuk data "Dari" di modal.
    $dariSatuan = $s->satuan;

    // Kolom "Tujuan" di modal detail cuma nampilin nama satuan SENDIRI
    // (redundan) tiap kali yang buka adalah penerima akhir surat ini --
    // bukan pengirim ($isSent) dan bukan yang meneruskan lagi ($tampilKe) --
    // lihat data-tujuan di bawah: ($tampilKe || $isSent) ? nama lawan : nama
    // sendiri. Berlaku untuk semua satuan (Wadan, Satlak, dst), bukan cuma
    // Wadan-dari-Danpus seperti sebelumnya, karena kasusnya sama persis:
    // satuan itu sendiri sudah tahu suratnya ditujukan ke dia.
    $isWadan     = strtoupper($satuan->kode ?? '') === 'WADAN';
    $lawanKode   = strtoupper($lawan->kode ?? '');
    $hideTujuan  = ! $isSent && ! $tampilKe;
    $hideDisposisi = $isWadan && ! $isSent && $lawanKode === 'DANPUS';

    // Riwayat untuk timeline
    // Sembunyikan isi surat (catatan langkah Buat Surat / Surat Keluar berisi
    // deskripsi lengkap) dari satuan lain kalau prioritas Rahasia -- sama
    // aturannya dengan LaporanSurat::ringkasanUntuk(). Satuan yang gak
    // berhak tahu isi surat Rahasia ini JUGA gak bisa buka kartunya sama
    // sekali di Arsip (lihat $rahasiaTerkunci di bawah -- kartu ditampilin
    // beda/gembok, tombol Lihat Detail dinonaktifkan) -- TAPI tetap
    // kelihatan & bisa DIFILTER sebagai "Rahasia" di dropdown Jenis Surat,
    // cuma isinya yang dikunci, bukan keberadaannya yang disembunyikan.
    $sembunyikanIsiRahasia = $s->isRahasia() && (int) ($satuan->id ?? 0) !== (int) $s->satuan_id;
    $rahasiaTerkunci       = $sembunyikanIsiRahasia;
    $aksiBerisiIsiSurat    = [\App\Models\LaporanSuratRiwayat::AKSI_BUAT_SURAT, \App\Models\LaporanSuratRiwayat::AKSI_SURAT_KELUAR];
    $riwayats    = $s->riwayats;

    // Kelompokkan tembusan per riwayat_id agar bisa ditempel ke step TERUSKAN
    $tembusanPerRiwayat = $s->tembusans->groupBy('laporan_surat_riwayat_id');

    $riwayatJson = $riwayats->map(function ($r) use ($sembunyikanIsiRahasia, $aksiBerisiIsiSurat, $tembusanPerRiwayat) {
        // Cabang paralel: satuan yang ikut menerima di waktu yang sama (view_only / tembusan)
        $tembusanStep = $tembusanPerRiwayat->get($r->id, collect());
        $paralel = $tembusanStep->map(fn ($t) => [
            'satuan'          => $t->satuan->nama ?? '-',
            'satuan_kode'     => $t->satuan->kode ?? '-',
            'jenis'           => $t->jenis,
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
<div class="surat-file-card{{ $rahasiaTerkunci ? ' surat-file-card-locked' : '' }}" data-surat-id="{{ $s->id }}" data-created-at="{{ $s->created_at->timestamp }}" data-search="{{ strtolower($s->perihal.' '.($lawan->nama ?? '').' '.($lawan->kode ?? '')) }}" data-prioritas="{{ $s->prioritas }}" data-jenis-filter="{{ $s->prioritas }}" data-satuan-kode="{{ $lawan->kode ?? '' }}" data-satuan-nama="{{ $lawan->nama ?? ($lawan->kode ?? '-') }}" data-status="{{ $s->labelStatus() }}">
    @if($rahasiaTerkunci)
    <div class="surat-file-card-icon surat-file-card-icon-locked"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2.2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg></div>
    @else
    <div class="surat-file-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></div>
    @endif
    <span class="status-badge {{ $s->badgeClass() }} surat-file-card-badge">{{ $s->labelStatus() }}</span>
    <div class="surat-file-card-title">{{ $s->perihal }}</div>
    <div><div class="surat-file-card-dari-label">{{ $labelLawan }}</div><div class="surat-file-card-dari-value"><span>{{ $lawan->nama ?? '-' }}</span><span class="satuan-pill">{{ $lawan->kode ?? $lawan->nama ?? '-' }}</span></div></div>
    <div class="surat-file-card-divider"></div>
    <div class="surat-file-card-meta"><span class="surat-file-card-meta-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span><div><div class="surat-file-card-meta-label">Tanggal Dibuat</div><div class="surat-file-card-meta-value">{{ $s->created_at->translatedFormat('d M Y H:i') }}</div></div></div>
    <div class="surat-file-card-divider"></div>
    @if($rahasiaTerkunci)
    {{-- Surat Rahasia yang bukan punya satuan ini -- kartu terkunci total,
         gak ada onclick/openSuratDetail sama sekali (bukan cuma disabled
         secara visual) & gak dibawain data-* isi surat (deskripsi,
         riwayat, dkk) ke DOM, biar isinya emang beneran gak nyampe ke
         browser satuan yang gak berhak. --}}
    <button type="button" class="surat-file-card-btn surat-file-card-btn-locked" disabled aria-label="Surat rahasia -- tidak dapat dibuka oleh satuan ini"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0"><rect x="5" y="11" width="14" height="9" rx="2.2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>Rahasia</button>
    @else
    <button type="button" class="surat-file-card-btn" onclick="openSuratDetail(this)"
        data-context="arsip"
        data-perihal="{{ e($s->perihal) }}"
        data-tujuan="{{ e(($tampilKe || $isSent) ? ($lawan->nama ?? '-') : ($satuan->nama ?? '-')) }}"
        data-tujuan-kode="{{ e(($tampilKe || $isSent) ? ($lawan->kode ?? '') : ($satuan->kode ?? '')) }}"
        data-kategori="{{ e($s->kategori ?: 'Umum') }}"
        data-prioritas="{{ e($s->prioritas) }}"
        data-status="{{ $s->labelStatus() }}"
        data-deskripsi="{{ e($s->ringkasanUntuk($satuan->id ?? null)) }}"
        data-rahasia="{{ $s->isRahasia() ? '1' : '0' }}"
        data-dari="{{ e($dariSatuan->nama ?? '-') }}"
        data-dari-kode="{{ e($isSent ? ($satuan->kode ?? '') : strtoupper($dariSatuan->kode ?? '')) }}"
        data-hide-tujuan="{{ $hideTujuan ? '1' : '0' }}"
        data-hide-disposisi="{{ $hideDisposisi ? '1' : '0' }}"
        data-dibuat-oleh="{{ e($dariSatuan->nama ?? '-') }}"
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
        data-deadline="{{ $s->deadline_at ? $s->deadline_at->translatedFormat('d M Y H:i') : '' }}"
    >Lihat Detail</button>
    @endif
</div>
