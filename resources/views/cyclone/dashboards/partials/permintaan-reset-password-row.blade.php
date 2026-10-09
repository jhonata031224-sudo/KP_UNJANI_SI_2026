<tr data-reset-id="{{ $r->id }}" data-created="{{ $r->created_at->timestamp }}" data-search-value="{{ strtolower(($r->user->name ?? '').' '.($r->user->satuan->nama ?? '').' '.($r->user->satuan->kode ?? '')) }}">
  <td><div class="subject">{{ $r->user->name ?? '-' }}</div></td>
  <td style="color:var(--text-muted);">{{ trim((string) $r->catatan) !== '' ? $r->catatan : '-' }}</td>
  <td><div class="request-deadline"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>{{ $r->created_at->translatedFormat('d M Y H:i') }}</div></td>
  <td>
    @if($r->status === \App\Models\PermintaanResetPassword::STATUS_MENUNGGU)
    <div class="btn-row">
      <button class="table-action-btn edit" type="button" onclick="bukaSetujuiResetPassword(this)" data-id="{{ $r->id }}" data-nama="{{ e($r->user->name ?? '-') }}">Setujui</button>
      <button class="table-action-btn danger" type="button" onclick="bukaTolakResetPassword(this)" data-id="{{ $r->id }}" data-nama="{{ e($r->user->name ?? '-') }}">Tolak</button>
    </div>
    @else
      <span style="font-size:11.5px;font-weight:700;color:{{ $r->status === \App\Models\PermintaanResetPassword::STATUS_DISETUJUI ? 'var(--success-bright)' : 'var(--red)' }};">{{ $r->status }}</span>
      <div style="font-size:10.5px;color:var(--text-dim);margin-top:2px;">oleh {{ $r->diprosesOleh->name ?? '-' }}</div>
    @endif
  </td>
</tr>
