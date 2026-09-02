@php
    use App\Helpers\UserAgentParser;
    $uap = new UserAgentParser($s->user_agent ?? '');
@endphp
<tr data-session-id="{{ $s->id }}">
  <td>
    {{ $s->user_name ?? 'Tamu (belum login)' }}
    @if($s->id === $sesiSayaId)
      <span class="badge">Sesi Anda</span>
    @endif
  </td>
  <td>{{ $s->ip_address ?? '-' }}</td>
  <td style="max-width:300px;">
    @if($s->user_agent)
      <span style="display:block;font-size:13px;color:var(--text-body);">
        {{ $uap->icon() }} <strong>{{ $uap->device() }}</strong>
      </span>
      <span style="display:block;font-size:12px;color:var(--text-muted);margin-top:2px;">
        {{ $uap->os() }} &middot; {{ $uap->browser() }}
      </span>
    @else
      <span style="color:var(--text-dim);">-</span>
    @endif
  </td>
  <td class="js-terakhir-aktif">{{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->diffForHumans() }}</td>
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
