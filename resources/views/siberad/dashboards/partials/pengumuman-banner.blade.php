@if(($pengumumanAktif ?? collect())->isNotEmpty())
  <div class="pengumuman-banner-wrap" id="pengumumanBannerWrap">
    @foreach($pengumumanAktif as $p)
      <div class="pengumuman-banner" data-pengumuman-id="{{ $p->id }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 3v6c0 5-3.4 8.4-8 11-4.6-2.6-8-6-8-11V5l8-3z"/><path d="M12 8v5"/><path d="M12 16.5h.01"/></svg>
        <div class="pengumuman-banner-body">
          <b>{{ $p->judul }}</b>
          <span>{{ $p->isi }}</span>
        </div>
        <button type="button" class="pengumuman-banner-close" aria-label="Tutup pengumuman" onclick="this.closest('.pengumuman-banner').remove()">
          <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>
        </button>
      </div>
    @endforeach
  </div>

  <style>
    .pengumuman-banner-wrap{display:flex;flex-direction:column;gap:10px;margin-bottom:20px;}
    .pengumuman-banner{display:flex;align-items:flex-start;gap:12px;background:var(--gold-dim);border:1px solid var(--border);border-radius:10px;padding:13px 16px;}
    .pengumuman-banner svg{width:19px;height:19px;color:var(--gold-bright);flex-shrink:0;margin-top:2px;}
    .pengumuman-banner-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:2px;}
    .pengumuman-banner-body b{font-family:var(--display);font-size:13.5px;color:var(--text);}
    .pengumuman-banner-body span{font-size:12.5px;color:var(--text-muted);line-height:1.55;}
    .pengumuman-banner-close{background:none;border:none;cursor:pointer;color:var(--text-dim);flex-shrink:0;padding:2px;line-height:0;}
    .pengumuman-banner-close:hover{color:var(--gold-bright);}
    .pengumuman-banner-close svg{width:15px;height:15px;margin:0;}
  </style>
@endif

@include('siberad.dashboards.partials.profile-enhancements')
@include('siberad.dashboards.partials.notification-controls')
