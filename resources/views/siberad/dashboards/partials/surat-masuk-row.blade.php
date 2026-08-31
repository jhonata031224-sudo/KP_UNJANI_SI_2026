<tr data-surat-id="{{ $s->id }}" data-search="{{ strtolower(($s->satuan->nama ?? '').' '.$s->perihal) }}" data-prioritas="{{ $s->prioritas }}">
    <td><div class="sender">{{ $s->satuan->nama ?? '-' }}</div></td>
    <td>{{ $s->perihal }}</td>
    <td><span class="priority-tag prio-{{ strtolower($s->prioritas) }}">{{ $s->prioritas }}</span></td>
    <td style="text-align:center">
        <span class="status-badge {{ $s->badgeClass() }}">{{ $s->labelStatus() }}</span>
    </td>
    <td style="text-align:center">{{ $s->created_at->translatedFormat('d M Y H:i') }}</td>
    <td style="text-align:center">
        <div class="review-actions" style="justify-content:center;flex-wrap:wrap;gap:4px">
            <button type="button" class="detail-btn"
                onclick="openReportDetail(this)"
                data-pengirim="{{ e($s->satuan->nama ?? '-') }}"
                data-tujuan="{{ e($s->tujuanSatuan->nama ?? '-') }}"
                data-perihal="{{ e($s->perihal) }}"
                data-prioritas="{{ e($s->prioritas) }}"
                data-proyek="{{ e($s->kategori ?? '-') }}"
                data-tanggal="{{ e($s->created_at->translatedFormat('d M Y H:i')) }}"
                data-deskripsi="{{ e($s->deskripsi) }}"
                data-deskripsi-label="Isi Surat"
                data-lampiran="{{ $s->lampiran_path ? collect([['url' => asset('storage/'.$s->lampiran_path), 'nama' => basename($s->lampiran_path)]])->toJson() : '[]' }}"
                data-kendala-report="1"
                data-readonly="1"
                data-readonly-text="Surat dari {{ e($s->satuan->nama ?? '-') }}{{ $s->isDikonfirmasi() ? ' -- sudah Anda konfirmasi pada '.$s->dikonfirmasi_at?->translatedFormat('d M Y H:i').'.' : ' -- belum dikonfirmasi.' }}">Detail</button>
            @if(! $s->isDikonfirmasi())
                <form method="POST" action="{{ route('laporan-surat.konfirmasi', $s) }}"
                    onsubmit="return confirm('Konfirmasi surat ini dari {{ addslashes($s->satuan->nama ?? '') }}? Pengirim akan mengetahui bahwa surat sudah diterima.')">
                    @csrf @method('PATCH')
                    <button type="submit" class="detail-btn" style="background:var(--clr-accent,#1a7a4a);color:#fff;border-color:var(--clr-accent,#1a7a4a)">Konfirmasi</button>
                </form>
            @endif
        </div>
    </td>
</tr>
