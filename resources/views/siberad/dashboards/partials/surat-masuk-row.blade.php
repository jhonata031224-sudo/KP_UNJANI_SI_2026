@php
    // Tentukan apakah satuan ini adalah penerima utama atau tembusan/view-only
    $isTujuanUtama = (int) $s->tujuan_satuan_id === (int) $satuan->id;
    $kodeSatuan    = strtoupper((string) $satuan->kode);
    $isWadan       = $kodeSatuan === 'WADAN';
    $isDanpus      = $kodeSatuan === 'DANPUS';
    $kodePengirim  = strtoupper((string) ($s->satuan->kode ?? ''));

    // Kolom "Tujuan" di modal detail cuma nampilin nama satuan sendiri
    // (redundan), jadi khusus buat Wadan pas nerima surat dari Danpus,
    // kolom itu disembunyikan aja. Skenario yang sama juga dipakai buat
    // nyalain mode modal simpel (cuma tombol "Teruskan Surat" + form
    // disposisi/tindakan inline, tanpa langkah konfirmasi terpisah).
    $hideTujuan = $isWadan && $isTujuanUtama && $kodePengirim === 'DANPUS';
    $wadanModeSimpel = $hideTujuan;

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

    // Boleh konfirmasi utama? (non-Wadan: konfirmasi biasa, surat pindah ke arsip)
    $canConfirm = $isTujuanUtama && ! $sudahDikonfirmasi && ! $isWadan;

    // Wadan: tombol konfirmasi TERPISAH -- hanya muncul saat belum dikonfirmasi,
    // tapi setelah konfirmasi surat TETAP di Surat Masuk (belum masuk arsip).
    $canKonfirmasiWadan = $isWadan && $isTujuanUtama && ! $sudahDikonfirmasi && ! $s->isSelesai();

    // Boleh konfirmasi tembusan?
    $canConfirmTembusan = $isTembusan && ! $sudahKonfirmasiTembusan;

    // Boleh teruskan? Wadan bisa teruskan baik sebelum maupun setelah konfirmasi
    // (jika belum konfirmasi, controller akan auto-konfirmasi dulu)
    $riwayatCount = $s->riwayats->count();
    $adaLaporanSatrap = $s->riwayats->contains(function ($r) use ($satuan) {
        return (int) $r->pengirim_satuan_id !== (int) $satuan->id && $r->aksi === \App\Models\LaporanSuratRiwayat::AKSI_SURAT_KELUAR;
    });

    // Wadan meneruskan ke Satrap: selama belum selesai dan belum ada laporan balik
    $canTeruskan = $isWadan && $isTujuanUtama && ! $s->isSelesai() && ! $adaLaporanSatrap;

    // Wadan meneruskan ke Danpus: jika sudah ada laporan balik dari Satrap / alur sudah berjalan
    $canKeDanpus = $isWadan && $isTujuanUtama && $sudahDikonfirmasi && ! $s->isSelesai() && ($adaLaporanSatrap || $riwayatCount >= 2);

    // Danpus: hanya jika surat masuk kembali ke Danpus setelah alur berjalan (riwayat >= 2)
    $canSelesai        = $isDanpus && $isTujuanUtama && $sudahDikonfirmasi && ! $s->isSelesai() && $riwayatCount >= 2;
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
    // Sembunyikan isi surat (catatan langkah Buat Surat / Surat Keluar berisi
    // deskripsi lengkap) dari satuan lain kalau prioritas Rahasia -- sama
    // aturannya dengan LaporanSurat::ringkasanUntuk().
    $sembunyikanIsiRahasia = $s->isRahasia() && (int) ($satuan->id ?? 0) !== (int) $s->satuan_id;
    $aksiBerisiIsiSurat    = [\App\Models\LaporanSuratRiwayat::AKSI_BUAT_SURAT, \App\Models\LaporanSuratRiwayat::AKSI_SURAT_KELUAR];
    $riwayats     = $s->riwayats;

    // Kelompokkan tembusan per riwayat_id agar bisa ditempel ke step TERUSKAN
    $tembusanPerRiwayat = $s->tembusans->groupBy('laporan_surat_riwayat_id');

    $riwayatJson  = $riwayats->map(function ($r) use ($sembunyikanIsiRahasia, $aksiBerisiIsiSurat, $tembusanPerRiwayat) {
        // Cabang paralel: satuan yang ikut menerima di waktu yang sama (view_only / tembusan)
        // tapi bukan penerima utama -- ditampilkan sebagai "parallel" di timeline
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
            'aksi'            => $r->labelAksi(),
            'aksi_kode'       => $r->aksi,
            'pengirim'        => $r->pengirimSatuan->nama ?? '-',
            'penerima'        => $r->penerimaSatuan->nama ?? null,
            'catatan'         => ($sembunyikanIsiRahasia && in_array($r->aksi, $aksiBerisiIsiSurat, true)) ? '' : $r->catatan,
            'disposisi'       => $r->disposisi,
            'tindakan'        => $r->tindakan,
            'lampiran_url'    => $r->lampiran_path ? asset('storage/' . $r->lampiran_path) : null,
            'lampiran_nama'   => $r->lampiran_nama_asli,
            'tanggal'         => $r->created_at->translatedFormat('d M Y H:i'),
            'siklus'          => $r->siklus,
            'paralel'         => $paralel, // penerima paralel/bersamaan (Urdal view-only, dst)
        ];
    })->toJson();

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
        data-context="masuk"
        data-perihal="{{ e($s->perihal) }}"
        data-tujuan="{{ e($satuan->nama ?? '-') }}"
        data-tujuan-kode="{{ e($satuan->kode ?? '') }}"
        data-kategori="{{ e($s->kategori ?: 'Umum') }}"
        data-prioritas="{{ e($s->prioritas) }}"
        data-status="{{ $s->labelStatus() }}"
        data-deskripsi="{{ e($s->ringkasanUntuk($satuan->id ?? null)) }}"
        data-rahasia="{{ $s->isRahasia() ? '1' : '0' }}"
        data-dari="{{ e($s->satuan->nama ?? '-') }}"
        data-dari-kode="{{ e($kodePengirim) }}"
        data-hide-tujuan="{{ $hideTujuan ? '1' : '0' }}"
        data-wadan-simple="{{ $wadanModeSimpel ? '1' : '0' }}"
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
        data-can-konfirmasi-wadan="{{ $canKonfirmasiWadan ? '1' : '0' }}"
        data-sudah-dikonfirmasi-wadan="{{ ($isWadan && $sudahDikonfirmasi) ? '1' : '0' }}"
        data-can-teruskan="{{ $canTeruskan ? '1' : '0' }}"
        data-can-ke-danpus="{{ $canKeDanpus ? '1' : '0' }}"
        data-can-selesai="{{ $canSelesai ? '1' : '0' }}"
        data-can-disposisi-ulang="{{ $canDisposisiUlang ? '1' : '0' }}"
        @if($canConfirm)
        data-confirm-action="{{ route('laporan-surat.konfirmasi', $s) }}"
        data-confirm-token="{{ csrf_token() }}"
        @endif
        @if($canKonfirmasiWadan)
        data-konfirmasi-wadan-action="{{ route('laporan-surat.konfirmasi', $s) }}"
        @endif
        @if($canConfirmTembusan)
        data-confirm-tembusan-action="{{ route('laporan-surat.konfirmasi-tembusan', $s) }}"
        @endif
        @if($canTeruskan || $canDisposisiUlang || $wadanModeSimpel)
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
