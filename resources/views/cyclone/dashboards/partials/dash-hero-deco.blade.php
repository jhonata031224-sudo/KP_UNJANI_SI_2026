{{--
    "Command HUD Badge" -- dekorasi pudar di sisi kanan panel .dash-hero,
    konsep sci-fi/game HUD (bracket target-lock, jejak sirkuit bercabang,
    dual-ring radar kontra-rotasi, hex aksen) TAPI tetap profesional:
    monokrom warna emas ikut tema (currentColor = --gold-bright).

    Lambang di tengah hexagon beda per ROLE (parameter $icon), representasi
    posisi/fungsi orangnya, BUKAN logo instansi:
      - gear  : Admin   -- pengendali/pengaturan sistem
      - star  : Danpus  -- komandan (bintang, otoritas komando puncak)
      - rose  : Wadan   -- wakil komandan (3 bunga melati berjejer, niru
                           insignia pangkat Kolonel TNI, SENGAJA bukan
                           bintang; layout sama kayak 'users' di bawah)
      - users : Satuan  -- unit pelaksana (3 ikon user berjejer,
                           representasi banyak personel dalam 1 unit)

    Semua koordinat hexagon & ikon hasil hitung manual (bukan kira-kira):
    flat-top hex r=46 (luar) & r=34 (dalam) pusat (60,60); tiap ikon
    dijaga muat di dalam inradius hex-dalam (29.44) biar gak nembus.
    jejak sirkuit viewBox 320x131 disamakan persis sama rasio nyata
    kontainer .dash-hero-deco (diukur via getBoundingClientRect) supaya
    preserveAspectRatio="slice" gak motong kontennya.

    Murni dekorasi (aria-hidden, pointer-events:none), gak ganggu layout
    teks kiri karena posisinya absolute. Sembunyi total di bawah 820px
    (mobile/tablet sempit) karena ruangnya kurang muat.
--}}
<style>
.dash-hero{overflow:hidden;}
.dash-hero-deco{position:absolute;top:0;right:0;bottom:0;width:min(46%,320px);pointer-events:none;display:flex;align-items:center;justify-content:flex-end;color:var(--gold-bright);background:radial-gradient(circle at 80% 50%,rgba(255,152,0,.16),transparent 62%);}
.dash-hero-batik{position:absolute;inset:0;width:100%;height:100%;opacity:.5;}
.dash-hero-lambang{position:relative;width:128px;height:128px;opacity:.68;filter:drop-shadow(0 0 4px rgba(255,152,0,.2));margin-right:16px;animation:dashHeroPulse 3.2s ease-in-out infinite;}
.dash-hero-badge-ring{transform-box:fill-box;transform-origin:50% 50%;animation:dashHeroSpin 18s linear infinite;}
.dash-hero-badge-ring2{transform-box:fill-box;transform-origin:50% 50%;animation:dashHeroSpinRev 26s linear infinite;}
@keyframes dashHeroPulse{0%,100%{filter:drop-shadow(0 0 3px rgba(255,152,0,.15));}50%{filter:drop-shadow(0 0 11px rgba(255,152,0,.5));}}
@keyframes dashHeroSpin{to{transform:rotate(360deg);}}
@keyframes dashHeroSpinRev{to{transform:rotate(-360deg);}}
@media(prefers-reduced-motion:reduce){.dash-hero-lambang,.dash-hero-badge-ring,.dash-hero-badge-ring2{animation:none;}}
@media(max-width:820px){.dash-hero-deco{display:none;}}
</style>
<div class="dash-hero-deco" aria-hidden="true">
  <svg class="dash-hero-batik" viewBox="0 0 320 131" preserveAspectRatio="xMaxYMid slice" fill="none" stroke="currentColor">
    <g stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" opacity=".55">
      <path d="M0,65 L50,65"></path>
      <path d="M50,65 L63,52 L110,52 L123,39 L168,39"></path>
      <path d="M50,65 L191,65.4"></path>
      <path d="M50,65 L63,78 L115,78 L128,91 L175,91"></path>
      <path d="M0,20 L35,20 L48,33 L95,33"></path>
      <path d="M0,113 L45,113 L58,100 L100,100 L113,87"></path>
    </g>
    {{-- Titik node di hub & ujung cabang atas/bawah -- cabang tengah
         (garis lurus ke lambang) sengaja polos tanpa titik. --}}
    <g fill="currentColor" stroke="none" opacity=".6">
      <circle cx="50" cy="65" r="3"></circle>
      <circle cx="168" cy="39" r="3"></circle>
      <circle cx="175" cy="91" r="3.5"></circle>
      <circle cx="95" cy="33" r="3"></circle>
      <circle cx="113" cy="87" r="3"></circle>
    </g>
    <g stroke-width="1.1" opacity=".3">
      <path d="M31,44 L26.5,51.79 L17.5,51.79 L13,44 L17.5,36.21 L26.5,36.21 Z"></path>
      <path d="M47,93 L41,103.39 L29,103.39 L23,93 L29,82.61 L41,82.61 Z"></path>
    </g>
  </svg>
  <svg class="dash-hero-lambang" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-linecap="round">
    @if(($icon ?? 'gear') === 'gear')
      <defs>
        <rect id="dashHeroGearTooth" x="-2.5" y="-18.5" width="5" height="7.5" rx="1.4"></rect>
      </defs>
    @elseif(($icon ?? 'gear') === 'rose')
      <defs>
        <ellipse id="dashHeroMelatiPetal" cx="0" cy="-4" rx="2.3" ry="3.8"></ellipse>
        <g id="dashHeroMelati">
          <use href="#dashHeroMelatiPetal"></use>
          <use href="#dashHeroMelatiPetal" transform="rotate(60)"></use>
          <use href="#dashHeroMelatiPetal" transform="rotate(120)"></use>
          <use href="#dashHeroMelatiPetal" transform="rotate(180)"></use>
          <use href="#dashHeroMelatiPetal" transform="rotate(240)"></use>
          <use href="#dashHeroMelatiPetal" transform="rotate(300)"></use>
          <circle r="1.8"></circle>
        </g>
      </defs>
    @elseif(($icon ?? 'gear') === 'users')
      <defs>
        <g id="dashHeroPerson">
          <circle cx="0" cy="-7" r="4"></circle>
          <path d="M-6,9 Q-6,-1 0,-1 Q6,-1 6,9 Z"></path>
        </g>
      </defs>
    @endif
    <g class="dash-hero-badge-ring">
      <circle cx="60" cy="60" r="54" stroke-width="1.4" stroke-dasharray="3 7" opacity=".55"></circle>
    </g>
    <g class="dash-hero-badge-ring2">
      <circle cx="60" cy="60" r="48" stroke-width="1.1" stroke-dasharray="1.5 5" opacity=".4"></circle>
    </g>
    <g stroke-width="2.2" opacity=".85">
      <path d="M8,26 L8,14 L20,14"></path>
      <path d="M112,26 L112,14 L100,14"></path>
      <path d="M8,94 L8,106 L20,106"></path>
      <path d="M112,94 L112,106 L100,106"></path>
    </g>
    <path d="M106,60 L83,20.16 L37,20.16 L14,60 L37,99.84 L83,99.84 Z" fill="currentColor" fill-opacity=".07" stroke-width="3"></path>
    <path d="M94,60 L77,30.56 L43,30.56 L26,60 L43,89.44 L77,89.44 Z" stroke-width="1.6" opacity=".75"></path>
    @switch($icon ?? 'gear')
      @case('star')
        {{-- Danpus: bintang solid 5 sudut, outer radius 24 < inradius
             hex-dalam 29.44 di segala arah biar gak nembus. --}}
        <path d="M60,36 L65.41,52.56 L82.83,52.58 L68.75,62.84 L74.11,79.42 L60,69.2 L45.89,79.42 L51.25,62.84 L37.17,52.58 L54.59,52.56 Z" fill="currentColor" stroke="none"></path>
        @break
      @case('rose')
        {{-- Wadan: 3 bunga melati berjejer -- niru insignia pangkat
             Kolonel TNI (3 melati), BEDA TOTAL dari bintang Danpus
             (bintang = pangkat jenderal). Layout sengaja SAMA kayak 3
             user Satuan (x=42/60/78, y=60) biar konsisten polanya. Tiap
             kembang r~7.8 dari pusatnya; bunga di ujung kiri/kanan
             jangkauan terjauh 25.8 dari pusat hex < inradius hex-dalam
             29.44. --}}
        <g fill="currentColor" stroke="none">
          <use href="#dashHeroMelati" x="42" y="60"></use>
          <use href="#dashHeroMelati" x="60" y="60"></use>
          <use href="#dashHeroMelati" x="78" y="60"></use>
        </g>
        @break
      @case('users')
        {{-- Satuan: 3 ikon user berjejer -- representasi "banyak
             personel" dalam 1 unit, BEDA TOTAL dari bintang/mawar (bukan
             insignia pangkat/individu). Sengaja gak numpuk/overlap
             (biar gak jadi siluet nyatu) -- titik terjauh (36,69) dari
             pusat (60,60) jangkauan 25.6 < inradius hex-dalam 29.44. --}}
        <g fill="currentColor" stroke="none">
          <use href="#dashHeroPerson" x="42" y="60"></use>
          <use href="#dashHeroPerson" x="60" y="60"></use>
          <use href="#dashHeroPerson" x="78" y="60"></use>
        </g>
        @break
      @default
        {{-- Admin: roda gigi (gear/cog) -- pengendali/pengaturan sistem,
             8 gigi numpang <use> biar gak ada duplikat sudut. --}}
        <g transform="translate(60,60)" stroke-width="2.5">
          <use href="#dashHeroGearTooth" fill="currentColor" stroke="none"></use>
          <use href="#dashHeroGearTooth" fill="currentColor" stroke="none" transform="rotate(45)"></use>
          <use href="#dashHeroGearTooth" fill="currentColor" stroke="none" transform="rotate(90)"></use>
          <use href="#dashHeroGearTooth" fill="currentColor" stroke="none" transform="rotate(135)"></use>
          <use href="#dashHeroGearTooth" fill="currentColor" stroke="none" transform="rotate(180)"></use>
          <use href="#dashHeroGearTooth" fill="currentColor" stroke="none" transform="rotate(225)"></use>
          <use href="#dashHeroGearTooth" fill="currentColor" stroke="none" transform="rotate(270)"></use>
          <use href="#dashHeroGearTooth" fill="currentColor" stroke="none" transform="rotate(315)"></use>
          <circle r="11.6"></circle>
          <circle r="4.1" stroke-width="2"></circle>
        </g>
    @endswitch
  </svg>
</div>
