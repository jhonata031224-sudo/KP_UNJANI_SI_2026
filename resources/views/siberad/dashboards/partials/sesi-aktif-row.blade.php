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
      @if($uap->isUaReduced())
        <span style="display:block;font-size:11px;color:var(--text-dim);margin-top:2px;"
              title="Chrome versi baru menyembunyikan info device & versi Android demi privasi (UA Reduction)">
          ⚠️ Info terbatas (Chrome baru)
        </span>
      @endif
    @else
      <span style="color:var(--text-dim);">-</span>
    @endif
  </td>
  <td class="js-terakhir-aktif">{{ \Carbon\Carbon::parse($s->login_at ?? \Carbon\Carbon::createFromTimestamp($s->last_activity))->diffForHumans() }}</td>
  <td>
    @if($s->id !== $sesiSayaId)
    <button class="btn btn-ghost-red btn-sm" type="button" onclick="bukaPaksaLogout(this)"
      data-action="{{ route('admin.sessions.destroy', $s->id) }}"
      data-nama="{{ $s->user_name ?? 'Tamu (belum login)' }}">Paksa Logout</button>
    @else
      <form method="POST" action="{{ route('logout') }}" class="logout-form" style="display:inline">
        @csrf
        <button type="submit" class="btn btn-ghost btn-sm" title="Logout dari sesi Anda saat ini">Logout</button>
      </form>
    @endif
  </td>
</tr>
