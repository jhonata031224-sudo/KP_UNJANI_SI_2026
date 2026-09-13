# Terapkan patch v2 (banner statis, tanpa animasi) & push ke refactor-fokus-laporan
# Jalankan dari DALAM folder repo (yang ada folder .git)

$ErrorActionPreference = "Stop"

if (-not (Test-Path ".git")) { Write-Host "ERROR: jalankan dari root folder repo." -ForegroundColor Red; exit 1 }

$patchPath = Join-Path $PWD "modal-maintenance-static-v2.generated.patch"

$patchContent = @'
diff --git a/resources/views/siberad/dashboards/partials/notification-controls.blade.php b/resources/views/siberad/dashboards/partials/notification-controls.blade.php
index c9e22dbb..eb3335ce 100644
--- a/resources/views/siberad/dashboards/partials/notification-controls.blade.php
+++ b/resources/views/siberad/dashboards/partials/notification-controls.blade.php
@@ -99,50 +99,56 @@
            self-contained di sini (bukan pakai .report-modal punya
            laporan-role/laporan-pimpinan) karena partial ini juga dipasang di
            dashboard Admin, yang tidak punya CSS itu.
-           Kategori 'maintenance' dapat treatment khusus (banner hazard-stripe
-           + ikon kunci pas + status pulse) karena satu-satunya kategori yang
-           memang bisa diklik/dibuka modalnya (lihat render(), 'keterangan'
-           tidak pernah clickable) -- jadi modal ini de facto SELALU tentang
-           pemeliharaan sistem, dan pantas didesain sesuai itu, bukan generik. */
+           Kategori 'maintenance' dapat treatment khusus (banner blueprint-grid
+           statis + ikon kunci pas + daftar detail) karena satu-satunya
+           kategori yang memang bisa diklik/dibuka modalnya (lihat render(),
+           'keterangan' tidak pernah clickable) -- jadi modal ini de facto
+           SELALU tentang pemeliharaan sistem, dan pantas didesain sesuai itu,
+           bukan generik. Sengaja TANPA animasi apa pun (banner, ring, badge)
+           -- cukup grafis statis, konsisten dengan gaya HUD dashboard yang
+           sudah ada (gelap, aksen amber, label mono uppercase). */
         .siberad-pengumuman-overlay{position:fixed;inset:0;z-index:100210;background:rgba(2,4,6,.68);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease;box-sizing:border-box;}
         :root[data-theme="light"] .siberad-pengumuman-overlay{background:rgba(60,50,20,.4);}
         .siberad-pengumuman-overlay.open{opacity:1;visibility:visible;pointer-events:auto;}
         .siberad-pengumuman-card{width:min(440px,100%);background:var(--panel,var(--p-surface,#fff));border:1px solid var(--border-soft,var(--p-border,#e2e8f0));border-radius:18px;box-shadow:0 25px 70px rgba(0,0,0,.4);box-sizing:border-box;overflow:hidden;transform:translateY(14px) scale(.97);transition:transform .2s ease;}
         .siberad-pengumuman-overlay.open .siberad-pengumuman-card{transform:translateY(0) scale(1);}
-        .siberad-pengumuman-close{position:absolute;top:14px;right:14px;width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.35);background:rgba(6,9,12,.25);backdrop-filter:blur(2px);color:#fff;cursor:pointer;transition:border-color .2s ease,color .2s ease,transform .2s ease,background .2s ease;z-index:2;}
+        .siberad-pengumuman-close{position:absolute;top:14px;right:14px;width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.35);background:rgba(6,9,12,.25);backdrop-filter:blur(2px);color:#fff;cursor:pointer;transition:border-color .2s ease,color .2s ease,transform .2s ease,background .2s ease;z-index:3;}
         .siberad-pengumuman-close:hover{border-color:var(--red,#c83b3b);color:var(--red,#c83b3b);background:rgba(6,9,12,.5);transform:rotate(90deg);}
         .siberad-pengumuman-close svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;display:block;}
-        /* Banner hazard-stripe -- amber/gelap berjalan pelan, motif dasar
-           "sedang ada pekerjaan berlangsung" yang langsung dikenali tanpa
-           perlu baca teks dulu. */
-        .siberad-pengumuman-banner{position:relative;height:92px;background:#0c1116;overflow:hidden;flex-shrink:0;}
-        .siberad-pengumuman-banner::before{content:'';position:absolute;inset:-4px;background:repeating-linear-gradient(-45deg,var(--gold-bright,#ff9800) 0 18px,#0c1116 18px 36px);opacity:.85;animation:siberadHazardScroll 6s linear infinite;}
-        .siberad-pengumuman-banner::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(6,9,12,.35),rgba(6,9,12,.88) 88%);}
-        @keyframes siberadHazardScroll{from{transform:translate3d(0,0,0);}to{transform:translate3d(51px,0,0);}}
-        .siberad-pengumuman-icon-wrap{position:absolute;left:50%;bottom:-30px;transform:translateX(-50%);width:64px;height:64px;z-index:2;}
-        .siberad-pengumuman-icon-ring{position:absolute;inset:0;border-radius:50%;border:1.5px solid var(--gold-bright,#ff9800);opacity:0;animation:siberadPulseRing 2.6s ease-out infinite;}
-        .siberad-pengumuman-icon-ring.ring2{animation-delay:1.3s;}
-        @keyframes siberadPulseRing{0%{transform:scale(.85);opacity:.55;}75%{transform:scale(1.55);opacity:0;}100%{transform:scale(1.55);opacity:0;}}
+        /* Banner statis -- gradasi gelap + grid tipis ala blueprint teknik
+           (bukan garis diagonal hazard, tidak bergerak) + siku HUD di dua
+           sudut + ikon kunci pas raksasa transparan sebagai watermark. */
+        .siberad-pengumuman-banner{position:relative;height:104px;background:linear-gradient(150deg,#0c1116 0%,#171016 60%,#1c1108 100%);overflow:hidden;flex-shrink:0;}
+        .siberad-pengumuman-banner::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,152,0,.14) 1px,transparent 1px),linear-gradient(90deg,rgba(255,152,0,.14) 1px,transparent 1px);background-size:16px 16px;-webkit-mask-image:linear-gradient(180deg,rgba(0,0,0,.9),transparent 92%);mask-image:linear-gradient(180deg,rgba(0,0,0,.9),transparent 92%);}
+        .siberad-pengumuman-banner-icon-bg{position:absolute;right:-14px;top:50%;transform:translateY(-52%) rotate(12deg);width:96px;height:96px;color:rgba(255,152,0,.14);}
+        .siberad-pengumuman-banner-icon-bg svg{width:100%;height:100%;stroke:currentColor;fill:none;stroke-width:1.2;}
+        .siberad-pengumuman-corner{position:absolute;width:16px;height:16px;border-color:rgba(255,152,0,.55);}
+        .siberad-pengumuman-corner.tl{top:10px;left:10px;border-top:1.5px solid;border-left:1.5px solid;}
+        .siberad-pengumuman-corner.br{bottom:10px;right:10px;border-bottom:1.5px solid;border-right:1.5px solid;}
+        .siberad-pengumuman-icon-wrap{position:absolute;left:26px;bottom:-28px;width:60px;height:60px;z-index:2;}
+        .siberad-pengumuman-icon-ring{position:absolute;inset:-5px;border-radius:50%;border:1px solid var(--border-soft,rgba(217,146,11,.3));}
         .siberad-pengumuman-icon{position:relative;width:100%;height:100%;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--panel,#11181f);border:1px solid var(--border,rgba(217,146,11,.3));color:var(--gold-bright,#ff9800);box-shadow:0 6px 18px rgba(0,0,0,.35);}
-        .siberad-pengumuman-icon svg{width:28px;height:28px;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;}
-        .siberad-pengumuman-content{padding:40px 26px 24px;text-align:center;}
+        .siberad-pengumuman-icon svg{width:26px;height:26px;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;}
+        .siberad-pengumuman-content{padding:38px 24px 22px 100px;}
         .siberad-pengumuman-badge{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono,monospace);font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;background:var(--gold-dim,rgba(255,152,0,.14));color:var(--gold-bright,#ff9800);border:1px solid var(--border,rgba(217,146,11,.3));border-radius:999px;padding:5px 12px;}
-        .siberad-pengumuman-badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;animation:siberadDotBlink 1.6s ease-in-out infinite;}
-        @keyframes siberadDotBlink{0%,100%{opacity:1;}50%{opacity:.25;}}
-        .siberad-pengumuman-title{margin:14px 0 6px;font-family:var(--display,inherit);font-size:19px;font-weight:700;color:var(--text,var(--p-text,#17212b));line-height:1.35;}
-        .siberad-pengumuman-meta{display:flex;align-items:center;justify-content:center;gap:6px;font-family:var(--mono,monospace);font-size:11px;color:var(--text-dim,var(--p-muted,#77736c));margin-bottom:18px;}
-        .siberad-pengumuman-meta svg{width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0;}
-        .siberad-pengumuman-body-wrap{text-align:left;background:var(--gold-dim,rgba(255,152,0,.08));border:1px solid var(--border-soft,rgba(217,146,11,.16));border-left:3px solid var(--gold-bright,#ff9800);border-radius:0 10px 10px 0;padding:14px 16px;}
+        .siberad-pengumuman-badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;}
+        .siberad-pengumuman-title{margin:12px 0 0;font-family:var(--display,inherit);font-size:19px;font-weight:700;color:var(--text,var(--p-text,#17212b));line-height:1.35;}
+        .siberad-pengumuman-details{list-style:none;margin:16px 0 0;padding:0;border-top:1px solid var(--border-soft,rgba(217,146,11,.16));}
+        .siberad-pengumuman-details li{display:flex;align-items:center;gap:8px;padding:9px 0;border-bottom:1px solid var(--border-soft,rgba(217,146,11,.16));font-size:12.5px;}
+        .siberad-pengumuman-details li svg{width:14px;height:14px;stroke:var(--gold-bright,#ff9800);fill:none;stroke-width:1.8;flex-shrink:0;}
+        .siberad-pengumuman-details .label{color:var(--text-dim,var(--p-muted,#77736c));font-family:var(--mono,monospace);font-size:10.5px;letter-spacing:.06em;text-transform:uppercase;}
+        .siberad-pengumuman-details .value{margin-left:auto;color:var(--text,var(--p-text,#17212b));font-weight:600;text-align:right;}
+        .siberad-pengumuman-eyebrow{margin:20px 0 8px;font-family:var(--mono,monospace);font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--text-dim,var(--p-muted,#77736c));}
+        .siberad-pengumuman-body-wrap{background:var(--gold-dim,rgba(255,152,0,.08));border:1px solid var(--border-soft,rgba(217,146,11,.16));border-left:3px solid var(--gold-bright,#ff9800);border-radius:0 10px 10px 0;padding:14px 16px;}
         .siberad-pengumuman-body{margin:0;font-size:13px;line-height:1.7;color:var(--text-muted,var(--p-muted,#64748b));white-space:pre-wrap;}
-        .siberad-pengumuman-foot{margin-top:16px;font-size:11px;line-height:1.6;color:var(--text-dim,var(--p-muted,#77736c));}
-        .siberad-pengumuman-actions{margin-top:20px;}
+        .siberad-pengumuman-actions{padding:20px 24px 24px 100px;}
         .siberad-pengumuman-actions button{width:100%;display:flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:10px;padding:12px;font-size:13px;font-weight:700;color:#0c1116;background:var(--gold-bright,var(--p-accent,#c97a00));cursor:pointer;transition:filter .15s ease,transform .15s ease;}
         .siberad-pengumuman-actions button svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round;}
         .siberad-pengumuman-actions button:hover{filter:brightness(1.08);transform:translateY(-1px);}
-        @media (prefers-reduced-motion:reduce){
-          .siberad-pengumuman-banner::before{animation:none;}
-          .siberad-pengumuman-icon-ring{animation:none;opacity:0;}
-          .siberad-pengumuman-badge::before{animation:none;}
+        @media (max-width:420px){
+          .siberad-pengumuman-content{padding-left:24px;}
+          .siberad-pengumuman-actions{padding-left:24px;}
+          .siberad-pengumuman-icon-wrap{left:50%;transform:translateX(-50%);}
         }
       `;
       document.head.appendChild(style);
@@ -450,20 +456,24 @@
       overlay.innerHTML = '<div class="siberad-pengumuman-card" role="alertdialog" aria-modal="true" aria-labelledby="siberadPengumumanTitle" style="position:relative;">' +
         '<button type="button" class="siberad-pengumuman-close" id="siberadPengumumanClose" aria-label="Tutup"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>' +
         '<div class="siberad-pengumuman-banner">' +
+          '<div class="siberad-pengumuman-banner-icon-bg" id="siberadPengumumanIconBg"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.6 5.6L3 18v3h3l6.1-6.1a4 4 0 0 0 5.6-5.6l-2.8 2.8a2 2 0 1 1-2.8-2.8l2.8-2.8Z"></path></svg></div>' +
+          '<span class="siberad-pengumuman-corner tl"></span><span class="siberad-pengumuman-corner br"></span>' +
           '<div class="siberad-pengumuman-icon-wrap">' +
             '<span class="siberad-pengumuman-icon-ring"></span>' +
-            '<span class="siberad-pengumuman-icon-ring ring2"></span>' +
             '<div class="siberad-pengumuman-icon" id="siberadPengumumanIcon"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.6 5.6L3 18v3h3l6.1-6.1a4 4 0 0 0 5.6-5.6l-2.8 2.8a2 2 0 1 1-2.8-2.8l2.8-2.8Z"></path></svg></div>' +
           '</div>' +
         '</div>' +
         '<div class="siberad-pengumuman-content">' +
           '<span class="siberad-pengumuman-badge" id="siberadPengumumanBadge">Pemeliharaan Sistem</span>' +
           '<h3 class="siberad-pengumuman-title" id="siberadPengumumanTitle"></h3>' +
-          '<div class="siberad-pengumuman-meta" id="siberadPengumumanMeta"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg><span id="siberadPengumumanWaktu"></span></div>' +
+          '<ul class="siberad-pengumuman-details" id="siberadPengumumanDetails">' +
+            '<li><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg><span class="label">Dikirim</span><span class="value" id="siberadPengumumanWaktu">-</span></li>' +
+            '<li><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg><span class="label">Sumber</span><span class="value">Admin Sistem</span></li>' +
+          '</ul>' +
+          '<p class="siberad-pengumuman-eyebrow">Isi Pengumuman</p>' +
           '<div class="siberad-pengumuman-body-wrap"><p class="siberad-pengumuman-body" id="siberadPengumumanBody"></p></div>' +
-          '<p class="siberad-pengumuman-foot">Pengumuman ini dikirim oleh Admin ke seluruh pengguna sistem.</p>' +
         '</div>' +
-        '<div class="siberad-pengumuman-actions" style="padding:0 26px 26px;"><button type="button" id="siberadPengumumanTutup"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Mengerti, Tutup</button></div>' +
+        '<div class="siberad-pengumuman-actions"><button type="button" id="siberadPengumumanTutup"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Mengerti, Tutup</button></div>' +
         '</div>';
       document.body.appendChild(overlay);
       function tutupOverlay() { overlay.classList.remove('open'); }
@@ -475,23 +485,21 @@
     }
 
     // Ikon & label badge disesuaikan kategori -- 'maintenance' (atau kosong/
-    // null, pengumuman lama) pakai tema pemeliharaan (kunci pas + hazard
-    // stripe di CSS). Kategori lain (kalaupun suatu saat ditambah & dibuat
+    // null, pengumuman lama) pakai tema pemeliharaan (kunci pas di banner &
+    // watermark). Kategori lain (kalaupun suatu saat ditambah & dibuat
     // clickable) fallback ke tema pengumuman umum supaya tetap wajar dipakai.
+    var IKON_PEMELIHARAAN = '<svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.6 5.6L3 18v3h3l6.1-6.1a4 4 0 0 0 5.6-5.6l-2.8 2.8a2 2 0 1 1-2.8-2.8l2.8-2.8Z"></path></svg>';
+    var IKON_PENGUMUMAN = '<svg viewBox="0 0 24 24"><path d="M3 11l18-5v14L3 15v-4Z"></path><path d="M7 15v5a2 2 0 0 0 2 2h1"></path></svg>';
+
     function tampilkanPengumuman(judul, pesan, kategori, waktu) {
       var overlay = ensurePengumumanModal();
       var isMaintenance = !kategori || kategori === 'maintenance';
       overlay.querySelector('#siberadPengumumanTitle').textContent = judul || 'Pengumuman';
       overlay.querySelector('#siberadPengumumanBody').textContent = pesan || '';
       overlay.querySelector('#siberadPengumumanBadge').textContent = isMaintenance ? 'Pemeliharaan Sistem' : 'Pengumuman';
-      var waktuEl = overlay.querySelector('#siberadPengumumanWaktu');
-      var metaEl = overlay.querySelector('#siberadPengumumanMeta');
-      if (waktu) { waktuEl.textContent = 'Dikirim ' + waktu; metaEl.style.display = ''; }
-      else { metaEl.style.display = 'none'; }
-      var iconEl = overlay.querySelector('#siberadPengumumanIcon');
-      iconEl.innerHTML = isMaintenance
-        ? '<svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.6 5.6L3 18v3h3l6.1-6.1a4 4 0 0 0 5.6-5.6l-2.8 2.8a2 2 0 1 1-2.8-2.8l2.8-2.8Z"></path></svg>'
-        : '<svg viewBox="0 0 24 24"><path d="M3 11l18-5v14L3 15v-4Z"></path><path d="M7 15v5a2 2 0 0 0 2 2h1"></path></svg>';
+      overlay.querySelector('#siberadPengumumanWaktu').textContent = waktu || '-';
+      overlay.querySelector('#siberadPengumumanIcon').innerHTML = isMaintenance ? IKON_PEMELIHARAAN : IKON_PENGUMUMAN;
+      overlay.querySelector('#siberadPengumumanIconBg').innerHTML = isMaintenance ? IKON_PEMELIHARAAN : IKON_PENGUMUMAN;
       overlay.offsetHeight; // force reflow biar animasi open konsisten (lihat pola sesiBerakhirOverlay)
       overlay.classList.add('open');
     }
'@

[System.IO.File]::WriteAllText($patchPath, ($patchContent -replace "`r`n","`n"), (New-Object System.Text.UTF8Encoding $false))

Write-Host "Patch ditulis ke: $patchPath"

git checkout refactor-fokus-laporan
git pull origin refactor-fokus-laporan

Write-Host "Mengecek apakah patch bisa diterapkan..."
git apply --check $patchPath
git apply $patchPath

git status
git diff --stat

git add resources/views/siberad/dashboards/partials/notification-controls.blade.php
git commit -m "style(notifikasi): modal maintenance jadi statis (hapus hazard-stripe animasi), tambah detail dikirim & sumber"
git push origin refactor-fokus-laporan

Remove-Item $patchPath
Write-Host "Selesai." -ForegroundColor Green
