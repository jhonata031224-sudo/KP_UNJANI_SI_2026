<tr data-laporan-id="{{ $l->id }}" data-permintaan-id="{{ $l->permintaan_laporan_id }}" data-search="{{ strtolower(($l->satuan->nama ?? '').' '.$l->perihal) }}" data-prioritas="{{ $l->prioritas }}">
    <td style="text-align:center">{{ $l->satuan->nama ?? '-' }}</td>
    <td>{{ $l->perihal }}</td>
    <td>{{ $l->prioritas }}</td>
    <td style="text-align:center">{{ $l->created_at->translatedFormat('d M Y H:i') }}</td>
    <td style="text-align:center"><span class="status-dot {{ str_contains(strtolower($l->status),'setuj') || str_contains(strtolower($l->status),'diterima') ? 'green' : (str_contains(strtolower($l->status),'tolak') ? 'bad' : 'amber') }}">{{ $l->status }}</span></td>
    @if($canReview)
    <td><div class="review-actions" style="justify-content:center"><button type="button" class="detail-btn" onclick="openReportDetail(this)" data-pengirim="{{ e($l->satuan->nama ?? '-') }}" data-tujuan="{{ e($satuan->nama) }}" data-perihal="{{ e($l->perihal) }}" data-prioritas="{{ e($l->prioritas) }}" data-progres="{{ $l->progres }}" data-kendala="{{ e($l->kendala ?? '') }}" data-proyek="{{ e($l->proyek ?? '-') }}" data-tanggal="{{ e($l->created_at->translatedFormat('d M Y H:i')) }}" data-deskripsi="{{ e($l->deskripsi) }}" data-lampiran="{{ $l->lampiran_path ? e(asset('storage/'.$l->lampiran_path)) : '' }}">Lihat</button><form method="POST" action="{{ route('laporan.status', $l) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="Diterima"><button class="approve" type="submit">Terima</button></form><form method="POST" action="{{ route('laporan.status', $l) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="Revisi"><button type="submit">Revisi</button></form><form method="POST" action="{{ route('laporan.status', $l) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="Ditolak"><button class="reject" type="submit">Tolak</button></form></div></td>
    @endif
</tr>
