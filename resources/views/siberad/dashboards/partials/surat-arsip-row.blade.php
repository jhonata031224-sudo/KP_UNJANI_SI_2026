<tr data-surat-id="{{ $s->id }}" data-search="{{ strtolower($s->perihal.' '.($s->tujuanSatuan->nama ?? '')) }}" data-prioritas="{{ $s->prioritas }}">
    <td>{{ $s->perihal }}</td>
    <td style="text-align:center"><span class="satuan-pill">{{ $s->tujuanSatuan->kode ?? $s->tujuanSatuan->nama ?? '-' }}</span></td>
    <td><span class="priority-tag prio-{{ strtolower($s->prioritas) }}">{{ $s->prioritas }}</span></td>
    <td style="text-align:center"><span class="status-badge status-dikonfirmasi">Dikonfirmasi</span></td>
    <td style="text-align:center">{{ $s->created_at->translatedFormat('d M Y H:i') }}</td>
    <td>
        <div class="review-actions" style="justify-content:center;flex-wrap:wrap">
            <button type="button" class="detail-btn"
                onclick="openReportDetail(this)"
                data-pengirim="{{ e($satuan->nama ?? $s->satuan->nama ?? '-') }}"
                data-tujuan="{{ e($s->tujuanSatuan->nama ?? '-') }}"
                data-perihal="{{ e($s->perihal) }}"
                data-prioritas="{{ e($s->prioritas) }}"
                data-proyek="{{ e($s->kategori ?? '-') }}"
                data-tanggal="{{ e($s->created_at->translatedFormat('d M Y H:i')) }}"
                data-deskripsi="{{ e($s->deskripsi) }}"
                data-deskripsi-label="Isi Surat"
                data-lampiran="{{ $s->lampiran_path ? collect([['url' => asset('storage/'.$s->lampiran_path), 'nama' => $s->lampiran_nama_asli ?: basename($s->lampiran_path)]])->toJson() : '[]' }}"
                data-kendala-report="1"
                data-readonly="1"
                data-readonly-text="Surat ini sudah dikonfirmasi oleh penerima ({{ e($s->tujuanSatuan->nama ?? '-') }}) pada {{ e($s->dikonfirmasi_at?->translatedFormat('d M Y H:i') ?? '-') }}.">Detail</button>
            <form method="POST" action="{{ route('laporan-surat.destroy', $s) }}"
                onsubmit="return confirm('Hapus surat ini dari Arsip Surat? Tindakan ini tidak dapat dibatalkan.')">
                @csrf @method('DELETE')
                <button type="submit" class="reject">Hapus</button>
            </form>
        </div>
    </td>
</tr>
