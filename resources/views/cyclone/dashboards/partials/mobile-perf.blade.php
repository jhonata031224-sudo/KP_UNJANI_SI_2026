{{-- Optimasi performa untuk HP/layar sentuh. Disuntik paling awal di <head>
     oleh InjectMobilePerf supaya pembungkus setInterval sudah aktif sebelum
     script dashboard lain mendaftarkan timer polling-nya. Di desktop
     (layar lebar + mouse) blok ini tidak melakukan apa-apa. --}}
<style id="cyclone-mobile-perf">
@media (max-width:900px),(pointer:coarse){
  /* blur latar (backdrop-filter) sangat berat di GPU HP kelas menengah/bawah */
  *,*::before,*::after{-webkit-backdrop-filter:none!important;backdrop-filter:none!important}
  /* dekorasi hero yang berputar/berdenyut terus-menerus bikin repaint tiap frame */
  .dash-hero-lambang,.dash-hero-badge-ring,.dash-hero-badge-ring2{animation:none!important}
}
</style>
<script id="cyclone-mobile-perf-js">
(function () {
  'use strict';
  try {
    var mq = window.matchMedia && window.matchMedia('(max-width: 900px), (pointer: coarse)');
    if (!mq || !mq.matches) return;

    var MIN_POLL_MS = 6000;      // polling 2-5 detik di HP dilonggarkan jadi >= 6 detik
    var nativeSetInterval = window.setInterval.bind(window);
    var tracked = [];            // timer polling yang terlewat saat tab/app di background

    window.setInterval = function (fn, ms) {
      if (typeof fn !== 'function') return nativeSetInterval.apply(null, arguments);
      var extra = Array.prototype.slice.call(arguments, 2);
      var isPoll = typeof ms === 'number' && ms >= 2000;
      var delay = isPoll ? Math.max(ms, MIN_POLL_MS) : ms;
      var entry = { fn: fn, args: extra, missed: false };
      if (isPoll) tracked.push(entry);
      return nativeSetInterval(function () {
        if (document.hidden) { entry.missed = true; return; }
        entry.missed = false;
        fn.apply(window, extra);
      }, delay);
    };

    // Begitu app dibuka lagi, jalankan polling yang terlewat satu per satu
    // (berjeda) supaya data langsung segar tanpa menembak belasan request sekaligus.
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) return;
      var n = 0;
      tracked.forEach(function (e) {
        if (!e.missed) return;
        e.missed = false;
        setTimeout(function () { try { e.fn.apply(window, e.args); } catch (_) {} }, 250 * n++);
      });
    });
  } catch (_) {}
})();
</script>
