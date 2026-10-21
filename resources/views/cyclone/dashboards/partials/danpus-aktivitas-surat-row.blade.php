{{--
  Baris Surat pada Riwayat Aktivitas DANPUS (tab "Surat" per satuan) --
  daftar ringkas yang bisa dibuka untuk melihat ALUR surat langkah demi langkah
  (dari riwayat surat: dibuat -> konfirmasi -> diteruskan -> balasan -> selesai).
  View-only; modal Detail Surat tetap dipakai untuk isi lengkap (semua
  data-can-* = 0, tidak ada tombol aksi).

  Params:
    $s        : LaporanSurat + properti AktivitasSatuanDanpus (arahUntukSatuan,
                balasanDikirim, hanyaTembusan, diarsipkan, menungguDanpus)
    $sid      : id satuan yang dipantau (sudut pandang label status & sorotan alur)
    $danpusId : id Danpus (sudut pandang isi ringkasan / aturan Rahasia)

  Nomor surat: sistem belum punya kolom nomor resmi, dipakai referensi dari id
  (SR-000123), sama dengan kartu pemantauan Duktek.
--}}
@php
    $isKeluar = ($s->arahUntukSatuan ?? 'masuk') === 'keluar';
    $nomorSurat = 'SR-' . str_pad((string) $s->id, 6, '0', STR_PAD_LEFT);
    $labelStatus = $s->labelStatus($sid);
    $kategori = $s->kategori ?: 'Umum';
    $tone = match ($labelStatus) {
        'Dikonfirmasi', 'Selesai' => 'ok',
        'Diteruskan'              => 'info',
        default                   => 'wait',
    };

    // Aturan Rahasia = LaporanSurat::ringkasanUntuk(): isi hanya untuk pengirim asli.
    $sembunyikanIsi = $s->isRahasia() && (int) $danpusId !== (int) $s->satuan_id;
    $aksiBerisiIsi = [\App\Models\LaporanSuratRiwayat::AKSI_BUAT_SURAT, \App\Models\LaporanSuratRiwayat::AKSI_SURAT_KELUAR];
    $tembusanPerRiwayat = $s->tembusans->groupBy('laporan_surat_riwayat_id');

    // ---- Alur: langkah dari riwayat nyata + (jika ada) langkah posisi sekarang ----
    $langkah = $s->riwayats->map(function ($r) use ($sid, $sembunyikanIsi, $aksiBerisiIsi, $tembusanPerRiwayat) {
        return [
            'judul'     => $r->labelAksi(),
            'dari'      => $r->pengirimSatuan->nama ?? null,
            'ke'        => $r->penerimaSatuan->nama ?? null,
            'tanggal'   => $r->created_at->translatedFormat('d M Y H:i'),
            'catatan'   => ($sembunyikanIsi && in_array($r->aksi, $aksiBerisiIsi, true)) ? '' : trim((string) $r->catatan),
            'disposisi' => trim((string) $r->disposisi),
            'tindakan'  => array_filter((array) $r->tindakan),
            'tembusan'  => $tembusanPerRiwayat->get($r->id, collect())
                              ->map(fn ($t) => ($t->satuan->nama ?? '-') . ' (' . $t->labelJenis() . ')')->implode(', '),
            'terkait'   => (int) $r->pengirim_satuan_id === (int) $sid || (int) $r->penerima_satuan_id === (int) $sid,
            'state'     => 'done',
        ];
    })->values()->all();

    // Surat lama tanpa riwayat: tetap tampilkan langkah awal dari data surat itu sendiri.
    if (empty($langkah)) {
        $langkah[] = [
            'judul' => 'Surat Dibuat', 'dari' => $s->satuan->nama ?? null, 'ke' => $s->tujuanSatuan->nama ?? null,
            'tanggal' => $s->created_at->translatedFormat('d M Y H:i'), 'catatan' => '', 'disposisi' => '',
            'tindakan' => [], 'tembusan' => '', 'terkait' => (int) $s->satuan_id === (int) $sid || (int) $s->tujuan_satuan_id === (int) $sid,
            'state' => 'done',
        ];
    }

    // Posisi sekarang. Riwayat SELESAI sudah ada sendiri bila surat diselesaikan manual,
    // jadi langkah penutup hanya ditambah bila belum tercatat.
    $adaLangkahSelesai = $s->riwayats->contains(fn ($r) => $r->aksi === \App\Models\LaporanSuratRiwayat::AKSI_SELESAI);
    if (! $s->isSelesai() && $s->status === \App\Models\LaporanSurat::STATUS_MENUNGGU) {
        $langkah[] = [
            'judul' => 'Menunggu konfirmasi ' . ($s->tujuanSatuan->nama ?? '-'), 'dari' => null, 'ke' => null,
            'tanggal' => '', 'catatan' => '', 'disposisi' => '', 'tindakan' => [], 'tembusan' => '',
            'terkait' => (int) $s->tujuan_satuan_id === (int) $sid, 'state' => 'current',
        ];
    } elseif ($s->diarsipkan && ! $adaLangkahSelesai) {
        $langkah[] = [
            'judul' => 'Alur tuntas, masuk arsip', 'dari' => null, 'ke' => null,
            'tanggal' => $s->selesai_at ? $s->selesai_at->translatedFormat('d M Y H:i') : '', 'catatan' => '', 'disposisi' => '',
            'tindakan' => [], 'tembusan' => '', 'terkait' => false, 'state' => 'done',
        ];
    }

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
<details class="akt-srow"
     data-surat-id="{{ $s->id }}"
     data-created-at="{{ $s->created_at->timestamp }}"
     data-search="{{ strtolower($nomorSurat.' '.$s->perihal.' '.$kategori.' '.($s->satuan->nama ?? '').' '.($s->tujuanSatuan->nama ?? '')) }}"
     data-arah="{{ $isKeluar ? 'keluar' : 'masuk' }}"
     data-status="{{ $labelStatus }}"
     data-arsip="{{ $s->diarsipkan ? '1' : '0' }}"
     data-perlu="{{ $s->menungguDanpus ? '1' : '0' }}">
  <summary class="akt-srow-sum">
    <span class="akt-srow-chev" aria-hidden="true"></span>
    <span class="akt-srow-main">
      <span class="akt-srow-title">{{ $s->perihal }}</span>
      <span class="akt-srow-meta">{{ $nomorSurat }} &middot; {{ $kategori }} &middot; {{ $s->created_at->translatedFormat('d M Y') }} &middot; {{ $s->satuan->kode ?? '-' }} &rarr; {{ $s->tujuanSatuan->kode ?? '-' }}</span>
    </span>
    <span class="akt-srow-tags">
      <span class="akt-st akt-st-{{ $tone }}">{{ $labelStatus }}</span>
      <span class="akt-tag">{{ $isKeluar ? '↑ Keluar' : '↓ Masuk' }}</span>
      @if($s->diarsipkan)<span class="akt-tag akt-tag-arsip">Arsip</span>@endif
      @if($s->menungguDanpus)<span class="akt-tag akt-tag-perlu">Menunggu Danpus</span>@endif
      @if($s->balasanDikirim)<span class="akt-tag">Balasan dikirim</span>@endif
      @if($s->hanyaTembusan)<span class="akt-tag">Tembusan</span>@endif
    </span>
  </summary>

  <div class="akt-srow-body">
    <ol class="akt-flow" aria-label="Alur surat {{ $nomorSurat }}">
      @foreach($langkah as $l)
      <li class="akt-flow-step is-{{ $l['state'] }}{{ $l['terkait'] ? ' is-terkait' : '' }}">
        <span class="akt-flow-dot" aria-hidden="true"></span>
        <div class="akt-flow-content">
          <div class="akt-flow-head">
            <span class="akt-flow-title">{{ $l['judul'] }}</span>
            @if($l['tanggal'])<span class="akt-flow-date">{{ $l['tanggal'] }}</span>@endif
          </div>
          @if($l['dari'] || $l['ke'])
          <div class="akt-flow-route">{{ $l['dari'] ?? '-' }} @if($l['ke'])&rarr; {{ $l['ke'] }}@endif</div>
          @endif
          @if($l['disposisi'] !== '' || ! empty($l['tindakan']))
          <div class="akt-flow-note">@if($l['disposisi'] !== '')Disposisi: {{ $l['disposisi'] }}@endif @if(! empty($l['tindakan']))@if($l['disposisi'] !== '') &middot; @endif Tindakan: {{ implode(', ', $l['tindakan']) }}@endif</div>
          @endif
          @if($l['tembusan'] !== '')<div class="akt-flow-note">Tembusan: {{ $l['tembusan'] }}</div>@endif
          @if($l['catatan'] !== '')<div class="akt-flow-note">{{ \Illuminate\Support\Str::limit($l['catatan'], 160) }}</div>@endif
          @if($l['terkait'])<span class="akt-flow-self">melibatkan {{ $m['nama'] ?? 'satuan ini' }}</span>@endif
        </div>
      </li>
      @endforeach
    </ol>

    <div class="akt-srow-actions">
      <button type="button" class="akt-btn" onclick="openSuratDetail(this)"
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
      @if($s->menungguDanpus)<a href="#surat-masuk" class="akt-link-tindak">Tindak lanjuti &rarr;</a>@endif
    </div>
  </div>
</details>
