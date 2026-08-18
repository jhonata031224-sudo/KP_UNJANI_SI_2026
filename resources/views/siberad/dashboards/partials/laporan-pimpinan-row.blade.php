<tr
    data-permintaan-created="{{ $l->permintaanLaporan?->created_at?->translatedFormat('d M Y H:i') }}"
    data-permintaan-ditinjau="{{ $l->permintaanLaporan?->dikerjakan_at?->translatedFormat('d M Y H:i') }}"
    data-permintaan-status="{{ $l->permintaanLaporan?->status }}"
    data-permintaan-dibatalkan="{{ $l->permintaanLaporan?->dibatalkan_at?->translatedFormat('d M Y H:i') }}"
    data-permintaan-terlambat="{{ $l->permintaanLaporan?->isTerlambat() ? '1' : '' }}"
    data-progres="{{ $l->progres }}"
    data-updated="{{ $l->updated_at?->format('Y-m-d H:i:s.uP') }}"
    data-kendala="{{ e($l->kendala ?? '') }}"
    data-permintaan-id="{{ $l->permintaan_laporan_id }}"
    data-laporan-id="{{ $l->id }}"
    data-laporan-status="{{ e($l->status) }}"
    data-satuan-nama="{{ e($l->satuan->nama ?? '-') }}"
    data-perihal="{{ e($l->perihal) }}"
>
    <td>
        <div class="subject">{{ $l->perihal }}</div>
        <div class="muted">{{ $l->proyek ?? 'Laporan kegiatan' }}</div>
    </td>
    <td>{{ $l->tujuanSatuan->nama ?? '-' }}</td>
    <td>{{ $l->prioritas }}</td>
    <td>
        <span class="status-pill {{ $l->status === \App\Models\Laporan::STATUS_PROGRES ? 'blue' : (str_contains(strtolower($l->status), 'tolak') ? 'bad' : ((str_contains(strtolower($l->status), 'setuj') || str_contains(strtolower($l->status), 'diterima')) ? 'ok' : ((str_contains(strtolower($l->status), 'revisi')) ? 'revisi' : 'wait'))) }}">
            {{ $l->status === \App\Models\Laporan::STATUS_PROGRES ? 'Progres · '.$l->progres.'%' : $l->status }}
        </span>
    </td>
    <td>{{ $l->created_at?->translatedFormat('d M Y H:i') }}</td>
    <td>
        <button
            type="button"
            class="detail-btn"
            onclick="openReportDetail(this)"
            data-pengirim="{{ e($l->satuan->nama ?? '-') }}"
            data-tujuan="{{ e($l->tujuanSatuan->nama ?? '-') }}"
            data-perihal="{{ e($l->perihal) }}"
            data-prioritas="{{ e($l->prioritas) }}"
            data-progres="{{ $l->progres }}"
            data-kendala="{{ e($l->kendala ?? '') }}"
            data-proyek="{{ e($l->proyek ?? '-') }}"
            data-tanggal="{{ e($l->created_at?->translatedFormat('d M Y H:i')) }}"
            data-deskripsi="{{ e($l->deskripsi) }}"
            data-lampiran="{{ $l->lampiran_path ? e(asset('storage/'.$l->lampiran_path)) : '' }}"
            data-readonly="1"
        >Detail</button>
    </td>
</tr>
