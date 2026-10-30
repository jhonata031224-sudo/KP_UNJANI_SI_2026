# Terapkan patch v2 (banner statis, tanpa animasi) & push ke refactor-fokus-laporan
# Jalankan dari DALAM folder repo (yang ada folder .git)

$ErrorActionPreference = "Stop"

if (-not (Test-Path ".git")) { Write-Host "ERROR: jalankan dari root folder repo." -ForegroundColor Red; exit 1 }

$patchPath = Join-Path $PWD "modal-maintenance-static-v2.generated.patch"

$patchContent = @'
diff --git a/resources/views/cyclone/dashboards/partials/notification-controls.blade.php b/resources/views/cyclone/dashboards/partials/notification-controls.blade.php
index c9e22dbb..eb3335ce 100644
--- a/resources/views/cyclone/dashboards/partials/notification-controls.blade.php
+++ b/resources/views/cyclone/dashboards/partials/notification-controls.blade.php
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
         .cyclone-pengumuman-overlay{position:fixed;inset:0;z-index:100210;background:rgba(2,4,6,.68);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease;box-sizing:border-box;}
         :root[data-theme="light"] .cyclone-pengumuman-overlay{background:rgba(60,50,20,.4);}
         .cyclone-pengumuman-overlay.open{opacity:1;visibility:visible;pointer-events:auto;}
         .cyclone-pengumuman-card{width:min(440px,100%);background:var(--panel,var(--p-surface,#fff));border:1px solid var(--border-soft,var(--p-border,#e2e8f0));border-radius:18px;box-shadow:0 25px 70px rgba(0,0,0,.4);box-sizing:border-box;overflow:hidden;transform:translateY(14px) scale(.97);transition:transform .2s ease;}
         .cyclone-pengumuman-overlay.open .cyclone-pengumuman-card{transform:translateY(0) scale(1);}
-        .cyclone-pengumuman-close{position:absolute;top:14px;right:14px;width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.35);background:rgba(6,9,12,.25);backdrop-filter:blur(2px);color:#fff;cursor:pointer;transition:border-color .2s ease,color .2s ease,transform .2s ease,background .2s ease;z-index:2;}
+        .cyclone-pengumuman-close{position:absolute;top:14px;right:14px;width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.35);background:rgba(6,9,12,.25);backdrop-filter:blur(2px);color:#fff;cursor:pointer;transition:border-color .2s ease,color .2s ease,transform .2s ease,background .2s ease;z-index:3;}
         .cyclone-pengumuman-close:hover{border-color:var(--red,#c83b3b);color:var(--red,#c83b3b);background:rgba(6,9,12,.5);transform:rotate(90deg);}
         .cyclone-pengumuman-close svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;display:block;}
-        /* Banner hazard-stripe -- amber/gelap berjalan pelan, motif dasar
-           "sedang ada pekerjaan berlangsung" yang langsung dikenali tanpa
-           perlu baca teks dulu. */
-        .cyclone-pengumuman-banner{position:relative;height:92px;background:#0c1116;overflow:hidden;flex-shrink:0;}
-        .cyclone-pengumuman-banner::before{content:'';position:absolute;inset:-4px;background:repeating-linear-gradient(-45deg,var(--gold-bright,#ff9800) 0 18px,#0c1116 18px 36px);opacity:.85;animation:cycloneHazardScroll 6s linear infinite;}
-        .cyclone-pengumuman-banner::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(6,9,12,.35),rgba(6,9,12,.88) 88%);}
-        @keyframes cycloneHazardScroll{from{transform:translate3d(0,0,0);}to{transform:translate3d(51px,0,0);}}
-        .cyclone-pengumuman-icon-wrap{position:absolute;left:50%;bottom:-30px;transform:translateX(-50%);width:64px;height:64px;z-index:2;}
-        .cyclone-pengumuman-icon-ring{position:absolute;inset:0;border-radius:50%;border:1.5px solid var(--gold-bright,#ff9800);opacity:0;animation:cyclonePulseRing 2.6s ease-out infinite;}
-        .cyclone-pengumuman-icon-ring.ring2{animation-delay:1.3s;}
-        @keyframes cyclonePulseRing{0%{transform:scale(.85);opacity:.55;}75%{transform:scale(1.55);opacity:0;}100%{transform:scale(1.55);opacity:0;}}
+        /* Banner statis -- gradasi gelap + grid tipis ala blueprint teknik
+           (bukan garis diagonal hazard, tidak bergerak) + siku HUD di dua
+           sudut + ikon kunci pas raksasa transparan sebagai watermark. */
+        .cyclone-pengumuman-banner{position:relative;height:104px;background:linear-gradient(150deg,#0c1116 0%,#171016 60%,#1c1108 100%);overflow:hidden;flex-shrink:0;}
+        .cyclone-pengumuman-banner::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,152,0,.14) 1px,transparent 1px),linear-gradient(90deg,rgba(255,152,0,.14) 1px,transparent 1px);background-size:16px 16px;-webkit-mask-image:linear-gradient(180deg,rgba(0,0,0,.9),transparent 92%);mask-image:linear-gradient(180deg,rgba(0,0,0,.9),transparent 92%);}
+        .cyclone-pengumuman-banner-icon-bg{position:absolute;right:-14px;top:50%;transform:translateY(-52%) rotate(12deg);width:96px;height:96px;color:rgba(255,152,0,.14);}
+        .cyclone-pengumuman-banner-icon-bg svg{width:100%;height:100%;stroke:currentColor;fill:none;stroke-width:1.2;}
+        .cyclone-pengumuman-corner{position:absolute;width:16px;height:16px;border-color:rgba(255,152,0,.55);}
+        .cyclone-pengumuman-corner.tl{top:10px;left:10px;border-top:1.5px solid;border-left:1.5px solid;}
+        .cyclone-pengumuman-corner.br{bottom:10px;right:10px;border-bottom:1.5px solid;border-right:1.5px solid;}
+        .cyclone-pengumuman-icon-wrap{position:absolute;left:26px;bottom:-28px;width:60px;height:60px;z-index:2;}
+        .cyclone-pengumuman-icon-ring{position:absolute;inset:-5px;border-radius:50%;border:1px solid var(--border-soft,rgba(217,146,11,.3));}
         .cyclone-pengumuman-icon{position:relative;width:100%;height:100%;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--panel,#11181f);border:1px solid var(--border,rgba(217,146,11,.3));color:var(--gold-bright,#ff9800);box-shadow:0 6px 18px rgba(0,0,0,.35);}
-        .cyclone-pengumuman-icon svg{width:28px;height:28px;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;}
-        .cyclone-pengumuman-content{padding:40px 26px 24px;text-align:center;}
+        .cyclone-pengumuman-icon svg{width:26px;height:26px;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;}
+        .cyclone-pengumuman-content{padding:38px 24px 22px 100px;}
         .cyclone-pengumuman-badge{display:inline-flex;align-items:center;gap:6px;font-family:var(--mono,monospace);font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;background:var(--gold-dim,rgba(255,152,0,.14));color:var(--gold-bright,#ff9800);border:1px solid var(--border,rgba(217,146,11,.3));border-radius:999px;padding:5px 12px;}
-        .cyclone-pengumuman-badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;animation:cycloneDotBlink 1.6s ease-in-out infinite;}
-        @keyframes cycloneDotBlink{0%,100%{opacity:1;}50%{opacity:.25;}}
-        .cyclone-pengumuman-title{margin:14px 0 6px;font-family:var(--display,inherit);font-size:19px;font-weight:700;color:var(--text,var(--p-text,#17212b));line-height:1.35;}
-        .cyclone-pengumuman-meta{display:flex;align-items:center;justify-content:center;gap:6px;font-family:var(--mono,monospace);font-size:11px;color:var(--text-dim,var(--p-muted,#77736c));margin-bottom:18px;}
-        .cyclone-pengumuman-meta svg{width:12px;height:12px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0;}
-        .cyclone-pengumuman-body-wrap{text-align:left;background:var(--gold-dim,rgba(255,152,0,.08));border:1px solid var(--border-soft,rgba(217,146,11,.16));border-left:3px solid var(--gold-bright,#ff9800);border-radius:0 10px 10px 0;padding:14px 16px;}
+        .cyclone-pengumuman-badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;}
+        .cyclone-pengumuman-title{margin:12px 0 0;font-family:var(--display,inherit);font-size:19px;font-weight:700;color:var(--text,var(--p-text,#17212b));line-height:1.35;}
+        .cyclone-pengumuman-details{list-style:none;margin:16px 0 0;padding:0;border-top:1px solid var(--border-soft,rgba(217,146,11,.16));}
+        .cyclone-pengumuman-details li{display:flex;align-items:center;gap:8px;padding:9px 0;border-bottom:1px solid var(--border-soft,rgba(217,146,11,.16));font-size:12.5px;}
+        .cyclone-pengumuman-details li svg{width:14px;height:14px;stroke:var(--gold-bright,#ff9800);fill:none;stroke-width:1.8;flex-shrink:0;}
+        .cyclone-pengumuman-details .label{color:var(--text-dim,var(--p-muted,#77736c));font-family:var(--mono,monospace);font-size:10.5px;letter-spacing:.06em;text-transform:uppercase;}
+        .cyclone-pengumuman-details .value{margin-left:auto;color:var(--text,var(--p-text,#17212b));font-weight:600;text-align:right;}
+        .cyclone-pengumuman-eyebrow{margin:20px 0 8px;font-family:var(--mono,monospace);font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--text-dim,var(--p-muted,#77736c));}
+        .cyclone-pengumuman-body-wrap{background:var(--gold-dim,rgba(255,152,0,.08));border:1px solid var(--border-soft,rgba(217,146,11,.16));border-left:3px solid var(--gold-bright,#ff9800);border-radius:0 10px 10px 0;padding:14px 16px;}
         .cyclone-pengumuman-body{margin:0;font-size:13px;line-height:1.7;color:var(--text-muted,var(--p-muted,#64748b));white-space:pre-wrap;}
-        .cyclone-pengumuman-foot{margin-top:16px;font-size:11px;line-height:1.6;color:var(--text-dim,var(--p-muted,#77736c));}
-        .cyclone-pengumuman-actions{margin-top:20px;}
+        .cyclone-pengumuman-actions{padding:20px 24px 24px 100px;}
         .cyclone-pengumuman-actions button{width:100%;display:flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:10px;padding:12px;font-size:13px;font-weight:700;color:#0c1116;background:var(--gold-bright,var(--p-accent,#c97a00));cursor:pointer;transition:filter .15s ease,transform .15s ease;}
         .cyclone-pengumuman-actions button svg{width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round;}
         .cyclone-pengumuman-actions button:hover{filter:brightness(1.08);transform:translateY(-1px);}
-        @media (prefers-reduced-motion:reduce){
-          .cyclone-pengumuman-banner::before{animation:none;}
-          .cyclone-pengumuman-icon-ring{animation:none;opacity:0;}
-          .cyclone-pengumuman-badge::before{animation:none;}
+        @media (max-width:420px){
+          .cyclone-pengumuman-content{padding-left:24px;}
+          .cyclone-pengumuman-actions{padding-left:24px;}
+          .cyclone-pengumuman-icon-wrap{left:50%;transform:translateX(-50%);}
         }
       `;
       document.head.appendChild(style);
@@ -450,20 +456,24 @@
       overlay.innerHTML = '<div class="cyclone-pengumuman-card" role="alertdialog" aria-modal="true" aria-labelledby="cyclonePengumumanTitle" style="position:relative;">' +
         '<button type="button" class="cyclone-pengumuman-close" id="cyclonePengumumanClose" aria-label="Tutup"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>' +
         '<div class="cyclone-pengumuman-banner">' +
+          '<div class="cyclone-pengumuman-banner-icon-bg" id="cyclonePengumumanIconBg"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.6 5.6L3 18v3h3l6.1-6.1a4 4 0 0 0 5.6-5.6l-2.8 2.8a2 2 0 1 1-2.8-2.8l2.8-2.8Z"></path></svg></div>' +
+          '<span class="cyclone-pengumuman-corner tl"></span><span class="cyclone-pengumuman-corner br"></span>' +
           '<div class="cyclone-pengumuman-icon-wrap">' +
             '<span class="cyclone-pengumuman-icon-ring"></span>' +
-            '<span class="cyclone-pengumuman-icon-ring ring2"></span>' +
             '<div class="cyclone-pengumuman-icon" id="cyclonePengumumanIcon"><svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.6 5.6L3 18v3h3l6.1-6.1a4 4 0 0 0 5.6-5.6l-2.8 2.8a2 2 0 1 1-2.8-2.8l2.8-2.8Z"></path></svg></div>' +
           '</div>' +
         '</div>' +
         '<div class="cyclone-pengumuman-content">' +
           '<span class="cyclone-pengumuman-badge" id="cyclonePengumumanBadge">Pemeliharaan Sistem</span>' +
           '<h3 class="cyclone-pengumuman-title" id="cyclonePengumumanTitle"></h3>' +
-          '<div class="cyclone-pengumuman-meta" id="cyclonePengumumanMeta"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg><span id="cyclonePengumumanWaktu"></span></div>' +
+          '<ul class="cyclone-pengumuman-details" id="cyclonePengumumanDetails">' +
+            '<li><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg><span class="label">Dikirim</span><span class="value" id="cyclonePengumumanWaktu">-</span></li>' +
+            '<li><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg><span class="label">Sumber</span><span class="value">Admin Sistem</span></li>' +
+          '</ul>' +
+          '<p class="cyclone-pengumuman-eyebrow">Isi Pengumuman</p>' +
           '<div class="cyclone-pengumuman-body-wrap"><p class="cyclone-pengumuman-body" id="cyclonePengumumanBody"></p></div>' +
-          '<p class="cyclone-pengumuman-foot">Pengumuman ini dikirim oleh Admin ke seluruh pengguna sistem.</p>' +
         '</div>' +
-        '<div class="cyclone-pengumuman-actions" style="padding:0 26px 26px;"><button type="button" id="cyclonePengumumanTutup"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Mengerti, Tutup</button></div>' +
+        '<div class="cyclone-pengumuman-actions"><button type="button" id="cyclonePengumumanTutup"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"></path></svg>Mengerti, Tutup</button></div>' +
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
       overlay.querySelector('#cyclonePengumumanTitle').textContent = judul || 'Pengumuman';
       overlay.querySelector('#cyclonePengumumanBody').textContent = pesan || '';
       overlay.querySelector('#cyclonePengumumanBadge').textContent = isMaintenance ? 'Pemeliharaan Sistem' : 'Pengumuman';
-      var waktuEl = overlay.querySelector('#cyclonePengumumanWaktu');
-      var metaEl = overlay.querySelector('#cyclonePengumumanMeta');
-      if (waktu) { waktuEl.textContent = 'Dikirim ' + waktu; metaEl.style.display = ''; }
-      else { metaEl.style.display = 'none'; }
-      var iconEl = overlay.querySelector('#cyclonePengumumanIcon');
-      iconEl.innerHTML = isMaintenance
-        ? '<svg viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.6 5.6L3 18v3h3l6.1-6.1a4 4 0 0 0 5.6-5.6l-2.8 2.8a2 2 0 1 1-2.8-2.8l2.8-2.8Z"></path></svg>'
-        : '<svg viewBox="0 0 24 24"><path d="M3 11l18-5v14L3 15v-4Z"></path><path d="M7 15v5a2 2 0 0 0 2 2h1"></path></svg>';
+      overlay.querySelector('#cyclonePengumumanWaktu').textContent = waktu || '-';
+      overlay.querySelector('#cyclonePengumumanIcon').innerHTML = isMaintenance ? IKON_PEMELIHARAAN : IKON_PENGUMUMAN;
+      overlay.querySelector('#cyclonePengumumanIconBg').innerHTML = isMaintenance ? IKON_PEMELIHARAAN : IKON_PENGUMUMAN;
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

git add resources/views/cyclone/dashboards/partials/notification-controls.blade.php
git commit -m "style(notifikasi): modal maintenance jadi statis (hapus hazard-stripe animasi), tambah detail dikirim & sumber"
git push origin refactor-fokus-laporan

Remove-Item $patchPath
Write-Host "Selesai." -ForegroundColor Green
