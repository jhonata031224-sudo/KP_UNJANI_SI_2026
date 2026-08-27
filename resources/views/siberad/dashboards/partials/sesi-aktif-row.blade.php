<tr data-session-id="{{ $s->id }}">
  <td>
    {{ $s->user_name ?? 'Tamu (belum login)' }}
    @if($s->id === $sesiSayaId)
      <span class="badge">Sesi Anda</span>
    @endif
  </td>
  <td>{{ $s->ip_address ?? '-' }}</td>
  <td style="color:var(--text-muted);max-width:260px;">{{ \Illuminate\Support\Str::limit($s->user_agent, 60) }}</td>
  <td>{{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</td>
  <td>
    @if($s->id !== $sesiSayaId)
    <button class="btn btn-ghost-red btn-sm" type="button" onclick="bukaPaksaLogout(this)"
      data-action="{{ route('admin.sessions.destroy', $s->id) }}"
      data-nama="{{ $s->user_name ?? 'Tamu (belum login)' }}">Paksa Logout</button>
    @else
      <span style="font-size:11.5px;color:var(--text-dim);">—</span>
    @endif
  </td>
</tr>
