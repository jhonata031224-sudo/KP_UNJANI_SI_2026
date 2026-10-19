{{--
    profile-notifikasi-view.blade.php
    Tampilan "Notifikasi" di dalam modal profil (dibuka dari item "Notifikasi"
    di dropdown avatar), untuk SEMUA pengguna non-admin (Kasansi, Satlak,
    Wadan, Danpus). Dulu berupa panel #lainnya-notifikasi di sidebar
    "Lainnya"; isinya cuma keterangan (semua notifikasi selalu aktif), jadi
    dipindah ke menu profil. Admin tidak memakai partial ini -- Admin punya
    pengaturan notifikasi sendiri (tab setelan-notifikasi).

    WAJIB di-include DI DALAM #profileModalCard (sejajar dengan
    #profileSettingsView / #profileHelpView), karena openProfileModal(id)
    hanya menampilkan elemen .profile-dropdown-view di dalam
    #profileModalOverlay.
--}}

<style>
/* ---- Notifikasi di modal profil (gaya card terpisah, bukan tabel/baris bergaris) ---- */
#profileNotifView .notif-setting-card{display:flex;flex-direction:column;gap:12px;margin-bottom:6px}
#profileNotifView .notif-setting-row{display:flex;align-items:center;gap:14px;padding:14px 16px;background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:14px;flex-wrap:wrap}
#profileNotifView .notif-setting-row-icon{flex-shrink:0;width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--gold-dim);color:var(--gold-bright)}
#profileNotifView .notif-setting-row-icon svg{width:17px;height:17px}
#profileNotifView .notif-setting-row-body{flex:1;min-width:180px}
#profileNotifView .notif-setting-row-label{font-size:13.5px;font-weight:600;color:var(--text)}
#profileNotifView .notif-setting-row-desc{font-size:11.5px;color:var(--text-muted);line-height:1.5;margin-top:2px}
#profileNotifView .notif-setting-row-right{flex-shrink:0}

/* Status badge notif */
#profileNotifView .notif-status-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:99px;font-size:11px;font-weight:700;border:1px solid transparent}
#profileNotifView .notif-status-pill.aktif{background:rgba(22,131,75,.12);border-color:rgba(22,131,75,.3);color:var(--green-bright,#16834b)}
#profileNotifView .notif-status-pill.mati{background:var(--panel-alt);border-color:var(--border-soft);color:var(--text-muted)}
#profileNotifView .notif-status-pill svg{width:8px;height:8px}

/* Bantuan notif */
#profileNotifView .notif-help-box{padding:13px 16px;background:var(--panel-alt);border:1px solid var(--border-soft);border-radius:11px;font-size:12px;line-height:1.65;color:var(--text-muted);margin-bottom:16px}
#profileNotifView .notif-help-box b{color:var(--text)}
#profileNotifView .notif-permission-box{padding:13px 16px;border-radius:11px;border:1px solid;font-size:12px;line-height:1.6;margin-bottom:16px;display:none}
#profileNotifView .notif-permission-box.denied{background:rgba(200,59,59,.07);border-color:rgba(200,59,59,.2);color:var(--red)}
#profileNotifView .notif-permission-box.default{background:rgba(183,121,0,.07);border-color:rgba(183,121,0,.25);color:var(--amber)}
</style>

<div class="profile-dropdown-view" id="profileNotifView" style="display:none;">
    <div class="profile-modal-title">Notifikasi</div>
    <p class="help-intro">Atur notifikasi yang muncul di perangkat kamu, bahkan saat {{ $pengaturan?->namaSistem() ?? "Cyclone" }} sedang tidak dibuka.</p>

    {{-- Box peringatan permission browser --}}
    <div class="notif-permission-box denied" id="notifPermDenied">
        <b>Notifikasi diblokir browser.</b> Kamu sudah menonaktifkan izin notifikasi di browser ini. Untuk mengaktifkan kembali, buka pengaturan browser dan izinkan notifikasi dari situs ini, lalu muat ulang halaman.
    </div>
    <div class="notif-permission-box default" id="notifPermDefault">
        <b>Izin notifikasi belum diberikan.</b> Browser akan meminta izin secara otomatis saat kamu membuka {{ $pengaturan?->namaSistem() ?? "Cyclone" }}.
    </div>

    <div class="notif-help-box">
        Notifikasi <b>push</b> muncul di tray/status bar perangkat kamu — bahkan saat tab {{ $pengaturan?->namaSistem() ?? "Cyclone" }} tidak aktif atau browser tertutup. Notifikasi <b>lonceng</b> (ikon di navbar) selalu aktif selama kamu login dan tidak dapat dimatikan di sini.
    </div>

    <div class="notif-setting-card">
        {{-- Row 1: Push notification -- selalu aktif --}}
        <div class="notif-setting-row">
            <div class="notif-setting-row-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </div>
            <div class="notif-setting-row-body">
                <div class="notif-setting-row-label">Notifikasi Push (di luar sistem)</div>
                <div class="notif-setting-row-desc">Terima notifikasi di tray OS meski {{ $pengaturan?->namaSistem() ?? "Cyclone" }} tidak dibuka. Selalu aktif untuk semua pengguna &mdash; dianggap penting (mis. notifikasi kendala/laporan darurat) sehingga tidak dapat dimatikan secara manual.</div>
            </div>
            <div class="notif-setting-row-right">
                <span class="notif-status-pill aktif">
                    <svg viewBox="0 0 10 10" fill="currentColor"><circle cx="5" cy="5" r="5"/></svg>
                    Selalu Aktif
                </span>
            </div>
        </div>

        {{-- Row 2: Notifikasi in-app (lonceng) -- selalu aktif --}}
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

{{-- Info status izin notifikasi BROWSER (denied/default) sebagai informasi saja.
     Push notification selalu aktif (lihat UserNotifikasiController::toggle),
     jadi tidak ada toggle/subscribe manual di sini; proses minta izin &
     subscribe otomatisnya ditangani script global
     push-notification-controls.blade.php (di-inject lewat InjectWebPushUi). --}}
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
