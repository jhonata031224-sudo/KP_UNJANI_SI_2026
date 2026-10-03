@php
    use App\Helpers\UserAgentParser;
    $uap = new UserAgentParser($s->user_agent ?? '');

    // Kolom geo_*
    $geoKota   = $s->geo_kota   ?? null;
    $geoRegion = $s->geo_region ?? null;
    $geoNegara = $s->geo_negara ?? null;
    $geoIsp    = $s->geo_isp    ?? null;
    $geoLat    = isset($s->geo_lat) ? (float) $s->geo_lat : null;
    $geoLon    = isset($s->geo_lon) ? (float) $s->geo_lon : null;
    $geoSumber = $s->geo_sumber ?? null; // 'gps' | 'ip' | 'lokal' | null

    // Baris pertama: Kota + Region (kalau beda dengan kota)
    $lokasiParts  = array_filter([$geoKota, ($geoRegion && $geoRegion !== $geoKota) ? $geoRegion : null]);
    $lokasiBaris1 = implode(', ', $lokasiParts);

    // URL Google Maps — pakai koordinat kalau ada, fallback ke nama kota
    $mapsUrl = null;
    if ($geoLat !== null && $geoLon !== null) {
        // Pin presisi berdasarkan koordinat GPS/IP
        $mapsUrl = "https://maps.google.com/?q={$geoLat},{$geoLon}";
    } elseif ($lokasiBaris1) {
        // Fallback: search nama kota di Maps
        $mapsUrl = 'https://maps.google.com/?q=' . urlencode($lokasiBaris1 . ($geoNegara ? ", {$geoNegara}" : ''));
    }

    $isLokal = ($geoKota === 'Jaringan Lokal');
    // Tetap dianggap "ada geo" walau nama kota belum sempat ke-reverse-
    // geocode (mis. GPS masuk tapi Nominatim gagal/timeout) -- koordinat
    // presisinya sendiri sudah cukup untuk pin Maps.
    $adaGeo  = $geoKota !== null || $geoRegion !== null || $geoNegara !== null || ($geoLat !== null && $geoLon !== null);
@endphp
<tr data-session-id="{{ $s->id }}">
  <td>
    {{ $s->user_name ?? 'Tamu (belum login)' }}
    @if($s->id === $sesiSayaId)
      <span class="badge">Sesi Anda</span>
    @endif
    @if($s->anomali_multi_lokasi ?? false)
      <span class="badge red badge-plain"
            title="Akun ini aktif login secara BERSAMAAN dari lokasi yang berjarak sekitar {{ number_format($s->anomali_jarak_km, 0) }} km ({{ $s->anomali_sesi_lain }}). Kemungkinan akun dipakai lebih dari satu orang/perangkat -- segera periksa.">
        🚨 2 Lokasi Sekaligus
      </span>
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

  {{-- ===== KOLOM TITIK LOKASI ===== --}}
  <td>
    @if($isLokal)
      {{-- IP Private / LAN instansi — tidak ada koordinat --}}
      <span style="display:flex;align-items:center;gap:5px;font-size:13px;color:var(--text-muted);">
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
        Jaringan Lokal
      </span>
    @elseif($adaGeo)
      {{-- Ada data geo — tampilkan sebagai link ke Google Maps --}}
      <a href="{{ $mapsUrl }}"
         target="_blank"
         rel="noopener noreferrer"
         title="{{ $geoLat !== null ? "Buka pin koordinat ({$geoLat}, {$geoLon}) di Google Maps" : 'Buka lokasi di Google Maps' }}"
         class="geo-maps-link"
      >
        {{-- Pin merah --}}
        <svg style="flex-shrink:0;margin-top:2px;color:var(--red);"
             viewBox="0 0 24 24" width="13" height="13" fill="none"
             stroke="currentColor" stroke-width="2.2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2a7 7 0 0 1 7 7c0 5.25-7 13-7 13S5 14.25 5 9a7 7 0 0 1 7-7z"/>
          <circle cx="12" cy="9" r="2.5"/>
        </svg>

        <span style="min-width:0;">
          @if($lokasiBaris1)
            <span style="display:block;font-size:13px;color:var(--text-body);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              {{ $lokasiBaris1 }}
              @if($geoSumber === 'gps')
                <span style="display:inline-block;margin-left:4px;padding:1px 6px;border-radius:999px;font-size:9.5px;font-weight:700;letter-spacing:.03em;background:rgba(34,197,94,.15);color:#22c55e;vertical-align:middle;"
                      title="Titik lokasi presisi dari GPS perangkat">GPS</span>
              @elseif($geoSumber === 'ip')
                <span style="display:inline-block;margin-left:4px;padding:1px 6px;border-radius:999px;font-size:9.5px;font-weight:700;letter-spacing:.03em;background:var(--gold-dim);color:var(--gold-bright);vertical-align:middle;"
                      title="Perkiraan lokasi dari alamat IP (GPS tidak tersedia)">IP</span>
              @endif
            </span>
          @elseif($geoLat !== null && $geoLon !== null)
            {{-- Koordinat ada tapi nama kota belum sempat ke-reverse-geocode --}}
            <span style="display:block;font-size:13px;color:var(--text-body);font-weight:500;">
              {{ number_format($geoLat, 5) }}, {{ number_format($geoLon, 5) }}
              @if($geoSumber === 'gps')
                <span style="display:inline-block;margin-left:4px;padding:1px 6px;border-radius:999px;font-size:9.5px;font-weight:700;letter-spacing:.03em;background:rgba(34,197,94,.15);color:#22c55e;vertical-align:middle;"
                      title="Titik lokasi presisi dari GPS perangkat">GPS</span>
              @endif
            </span>
          @endif
          @if($geoNegara)
            <span style="display:block;font-size:12px;color:var(--text-muted);margin-top:1px;">
              {{ $geoNegara }}
            </span>
          @endif
          @if($geoIsp)
            <span style="display:block;font-size:11px;color:var(--text-dim);margin-top:2px;"
                  title="ISP / Penyedia Jaringan">
              {{ $geoIsp }}
            </span>
          @endif
          {{-- Label "Buka Maps" muncul hanya waktu hover via CSS class --}}
          <span class="geo-maps-hint"
                style="display:block;font-size:11px;color:var(--gold-bright);margin-top:3px;opacity:0;transition:opacity .15s;">
            🗺 Lihat di Google Maps ↗
          </span>
        </span>
      </a>
    @else
      <span style="color:var(--text-dim);font-size:13px;">–</span>
    @endif
  </td>
  {{-- ===== END KOLOM TITIK LOKASI ===== --}}

  <td class="js-terakhir-aktif">{{ \Carbon\Carbon::parse($s->login_at ?? \Carbon\Carbon::createFromTimestamp($s->last_activity))->diffForHumans() }}</td>
  <td style="text-align:center;">
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
