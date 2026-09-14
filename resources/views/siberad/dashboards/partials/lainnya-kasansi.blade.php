{{--
    lainnya-kasansi.blade.php
    Konten section "Lainnya" untuk sidebar Kasansi: dua subsection
    (Notifikasi & Struktur Organisasi) yang di-render setelah halaman
    dimuat via partial ini, konsisten dengan pola partial lain di shell.
--}}

{{-- ===== CSS untuk kedua section ===== --}}
<style>
/* ---- Panel bersama ---- */
.lainnya-section-head{display:flex;align-items:flex-start;gap:14px;margin-bottom:22px}
.lainnya-section-icon{flex-shrink:0;width:44px;height:44px;border-radius:13px;display:flex;align-items:center;justify-content:center}
.lainnya-section-icon svg{width:22px;height:22px}
.lainnya-section-title{font-size:18px;font-weight:700;font-family:var(--display);margin:0 0 4px}
.lainnya-section-sub{font-size:12.5px;color:var(--text-muted);line-height:1.55;margin:0}

/* ---- Notifikasi (gaya card terpisah, bukan tabel/baris bergaris) ---- */
.notif-setting-card{display:flex;flex-direction:column;gap:12px;margin-bottom:14px}
.notif-setting-row{display:flex;align-items:center;gap:14px;padding:16px 18px;background:var(--panel);border:1px solid var(--border-soft);border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.06);transition:box-shadow .15s ease,border-color .15s ease;flex-wrap:wrap}
.notif-setting-row:hover{box-shadow:0 4px 16px rgba(0,0,0,.1);border-color:color-mix(in srgb,var(--border-soft) 60%,var(--gold-bright))}
.notif-setting-row-icon{flex-shrink:0;width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--gold-dim);color:var(--gold-bright)}
.notif-setting-row-icon svg{width:17px;height:17px}
.notif-setting-row-body{flex:1;min-width:180px}
.notif-setting-row-label{font-size:13.5px;font-weight:600;color:var(--text)}
.notif-setting-row-desc{font-size:11.5px;color:var(--text-muted);line-height:1.5;margin-top:2px}
.notif-setting-row-right{flex-shrink:0}

/* Status badge notif */
.notif-status-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:99px;font-size:11px;font-weight:700;border:1px solid transparent}
.notif-status-pill.aktif{background:rgba(22,131,75,.12);border-color:rgba(22,131,75,.3);color:var(--green-bright,#16834b)}
.notif-status-pill.mati{background:var(--panel-alt);border-color:var(--border-soft);color:var(--text-muted)}
.notif-status-pill svg{width:8px;height:8px}

/* Bantuan notif */
.notif-help-box{padding:13px 16px;background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:11px;font-size:12px;line-height:1.65;color:var(--text-muted);margin-bottom:16px}
.notif-help-box b{color:var(--text)}
.notif-permission-box{padding:13px 16px;border-radius:11px;border:1px solid;font-size:12px;line-height:1.6;margin-bottom:16px;display:none}
.notif-permission-box.denied{background:rgba(200,59,59,.07);border-color:rgba(200,59,59,.2);color:var(--red)}
.notif-permission-box.default{background:rgba(183,121,0,.07);border-color:rgba(183,121,0,.25);color:var(--amber)}

/* ---- Struktur Organisasi (gambar unggahan Admin) ---- */
.struktur-org-image-wrap{display:flex;flex-direction:column;align-items:center;gap:10px}
.struktur-org-image{display:block;max-width:100%;width:auto;max-height:640px;object-fit:contain;border-radius:14px;border:1px solid var(--border-soft);background:var(--panel);box-shadow:0 4px 16px rgba(0,0,0,.12);cursor:zoom-in}
.struktur-org-image-hint{margin:0;font-size:11.5px;color:var(--text-muted)}
</style>

{{-- ===== SECTION: Notifikasi ===== --}}
<section id="lainnya-notifikasi" class="tab-panel">
    <div class="report-card">
        <div class="lainnya-section-head">
            <div class="lainnya-section-icon" style="background:var(--gold-dim);color:var(--gold-bright)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </div>
            <div>
                <h2 class="lainnya-section-title">Pengaturan Notifikasi</h2>
                <p class="lainnya-section-sub">Atur notifikasi yang muncul di perangkat kamu, bahkan saat {{ $pengaturan?->namaSistem() ?? "SIBERAD" }} sedang tidak dibuka.</p>
            </div>
        </div>

        {{-- Box peringatan permission browser --}}
        <div class="notif-permission-box denied" id="notifPermDenied">
            <b>Notifikasi diblokir browser.</b> Kamu sudah menonaktifkan izin notifikasi di browser ini. Untuk mengaktifkan kembali, buka pengaturan browser dan izinkan notifikasi dari situs ini, lalu muat ulang halaman.
        </div>
        <div class="notif-permission-box default" id="notifPermDefault">
            <b>Izin notifikasi belum diberikan.</b> Browser akan meminta izin secara otomatis saat kamu membuka {{ $pengaturan?->namaSistem() ?? "SIBERAD" }}.
        </div>

        <div class="notif-help-box">
            Notifikasi <b>push</b> muncul di tray/status bar perangkat kamu — bahkan saat tab {{ $pengaturan?->namaSistem() ?? "SIBERAD" }} tidak aktif atau browser tertutup. Notifikasi <b>lonceng</b> (ikon di navbar) selalu aktif selama kamu login dan tidak dapat dimatikan di sini.
        </div>

        <div class="notif-setting-card">
            {{-- Row 1: Push notification on/off --}}
            <div class="notif-setting-row">
                <div class="notif-setting-row-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </div>
                <div class="notif-setting-row-body">
                    <div class="notif-setting-row-label">Notifikasi Push (di luar sistem)</div>
                    <div class="notif-setting-row-desc">Terima notifikasi di tray OS meski {{ $pengaturan?->namaSistem() ?? "SIBERAD" }} tidak dibuka. Selalu aktif untuk semua pengguna &mdash; dianggap penting (mis. notifikasi kendala/laporan darurat) sehingga tidak dapat dimatikan secara manual.</div>
                </div>
                <div class="notif-setting-row-right">
                    <span class="notif-status-pill aktif">
                        <svg viewBox="0 0 10 10" fill="currentColor"><circle cx="5" cy="5" r="5"/></svg>
                        Selalu Aktif
                    </span>
                </div>
            </div>

            {{-- Row 2: Notifikasi in-app (lonceng) - selalu aktif --}}
            <div class="notif-setting-row">
                <div class="notif-setting-row-icon" style="background:rgba(22,131,75,.1);color:var(--green-bright,#16834b)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v4"/>
                        <path d="M12 16h.01"/>
                    </svg>
                </div>
                <div class="notif-setting-row-body">
                    <div class="notif-setting-row-label">Notifikasi In-App (Lonceng)</div>
                    <div class="notif-setting-row-desc">Muncul sebagai ikon lonceng di pojok kanan atas. Selalu aktif selama kamu login.</div>
                </div>
                <div class="notif-setting-row-right">
                    <span class="notif-status-pill aktif">
                        <svg viewBox="0 0 10 10" fill="currentColor"><circle cx="5" cy="5" r="5"/></svg>
                        Selalu Aktif
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===== SECTION: Struktur Organisasi ===== --}}
<section id="lainnya-struktur-org" class="tab-panel">
    <div class="report-card">
        <div class="lainnya-section-head">
            <div class="lainnya-section-icon" style="background:rgba(99,102,241,.1);color:#6366f1">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="2" width="6" height="4" rx="1"/>
                    <rect x="1" y="18" width="6" height="4" rx="1"/>
                    <rect x="9" y="18" width="6" height="4" rx="1"/>
                    <rect x="17" y="18" width="6" height="4" rx="1"/>
                    <path d="M4 18v-4h16v4"/>
                    <path d="M12 6v8"/>
                </svg>
            </div>
            <div>
                <h2 class="lainnya-section-title">Struktur Organisasi</h2>
                <p class="lainnya-section-sub">Bagan hubungan hierarki antar-satuan dalam lingkungan {{ $pengaturan?->namaSistem() ?? "SIBERAD" }}.</p>
            </div>
        </div>

        @php
          // Verifikasi file struktur organisasi benar-benar ada di disk, bukan
          // cuma percaya kolom struktur_organisasi_path terisi -- path bisa
          // "dangling" (file sudah tidak ada) kalau upload gagal senyap atau
          // file terhapus manual di server, lihat catatan yang sama di
          // welcome.blade.php & admin.blade.php (commit 9fbcbc21).
          $strukturOrgExists = ($pengaturan->struktur_organisasi_path ?? null)
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($pengaturan->struktur_organisasi_path);
        @endphp
        @if($strukturOrgExists)
            <div class="struktur-org-image-wrap">
                <a href="{{ asset('storage/'.$pengaturan->struktur_organisasi_path) }}" target="_blank" rel="noopener" title="Buka gambar ukuran penuh di tab baru">
                    <img src="{{ asset('storage/'.$pengaturan->struktur_organisasi_path) }}" alt="Struktur Organisasi" class="struktur-org-image">
                </a>
                <p class="struktur-org-image-hint">Klik gambar untuk melihat ukuran penuh.</p>
            </div>
        @else
            <div class="kcard-empty">
                <svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="2" width="6" height="4" rx="1"/>
                    <rect x="1" y="18" width="6" height="4" rx="1"/>
                    <rect x="9" y="18" width="6" height="4" rx="1"/>
                    <rect x="17" y="18" width="6" height="4" rx="1"/>
                    <path d="M4 18v-4h16v4"/>
                    <path d="M12 6v8"/>
                </svg>
                <div class="kcard-empty-title">Belum ada gambar struktur organisasi</div>
                <div class="kcard-empty-sub">Gambar bagan struktur organisasi akan tampil di sini setelah diunggah oleh Admin.</div>
            </div>
        @endif
    </div>
</section>

{{-- ===== Script: info status izin notifikasi browser =====
     Push notification sekarang wajib selalu aktif (lihat
     UserNotifikasiController::toggle & Row 1 di atas yang sudah jadi
     status baca-saja "Selalu Aktif"), jadi TIDAK ADA LAGI toggle/manual
     subscribe-unsubscribe di sini. Satu-satunya yang masih relevan
     ditampilkan di panel ini adalah status izin notifikasi BROWSER
     (denied/default) sebagai informasi -- proses minta izin & subscribe
     otomatisnya sendiri sudah ditangani sekali di awal oleh script global
     push-notification-controls.blade.php (di-inject ke semua dashboard
     lewat InjectWebPushUi), bukan di sini. --}}
<script>
(function () {
    'use strict';

    var permDenied  = document.getElementById('notifPermDenied');
    var permDefault = document.getElementById('notifPermDefault');

    if (!('Notification' in window)) return;

    if (Notification.permission === 'denied') {
        if (permDenied) permDenied.style.display = 'block';
    } else if (Notification.permission === 'default') {
        if (permDefault) permDefault.style.display = 'block';
    }
})();
</script>
