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

/* Toggle switch */
.notif-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer;user-select:none}
.notif-toggle input{position:absolute;opacity:0;width:1px;height:1px}
.notif-toggle-track{width:46px;height:26px;border-radius:99px;background:var(--border-strong);transition:background .2s ease;position:relative}
.notif-toggle input:checked+.notif-toggle-track{background:var(--gold-bright)}
.notif-toggle-track::after{content:'';position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s cubic-bezier(.4,0,.2,1)}
.notif-toggle input:checked+.notif-toggle-track::after{transform:translateX(20px)}
.notif-toggle input:disabled+.notif-toggle-track{opacity:.45;cursor:not-allowed}

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
                <p class="lainnya-section-sub">Atur notifikasi yang muncul di perangkat kamu, bahkan saat SIBERAD sedang tidak dibuka.</p>
            </div>
        </div>

        {{-- Box peringatan permission browser --}}
        <div class="notif-permission-box denied" id="notifPermDenied">
            <b>Notifikasi diblokir browser.</b> Kamu sudah menonaktifkan izin notifikasi di browser ini. Untuk mengaktifkan kembali, buka pengaturan browser dan izinkan notifikasi dari situs ini, lalu muat ulang halaman.
        </div>
        <div class="notif-permission-box default" id="notifPermDefault">
            <b>Izin notifikasi belum diberikan.</b> Aktifkan tombol di bawah untuk meminta izin notifikasi dari browser.
        </div>

        <div class="notif-help-box">
            Notifikasi <b>push</b> muncul di tray/status bar perangkat kamu — bahkan saat tab SIBERAD tidak aktif atau browser tertutup. Notifikasi <b>lonceng</b> (ikon di navbar) selalu aktif selama kamu login dan tidak dapat dimatikan di sini.
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
                    <div class="notif-setting-row-desc">Terima notifikasi di tray OS meski SIBERAD tidak dibuka. Berlaku untuk semua perangkat yang terhubung.</div>
                </div>
                <div class="notif-setting-row-right" style="display:flex;align-items:center;gap:10px">
                    <span class="notif-status-pill" id="notifPushPill">
                        <svg viewBox="0 0 10 10" fill="currentColor"><circle cx="5" cy="5" r="5"/></svg>
                        <span id="notifPushPillText">Memuat…</span>
                    </span>
                    <label class="notif-toggle" id="notifPushToggleLabel" title="Aktifkan/matikan notifikasi push">
                        <input type="checkbox" id="notifPushToggle"
                            {{ $user->notif_push_enabled ? 'checked' : '' }}
                            @if(! ($pengaturan->notifikasi_push_aktif ?? true)) disabled @endif>
                        <span class="notif-toggle-track"></span>
                    </label>
                </div>
            </div>

            {{-- Row 2: Status global admin (readonly info) --}}
            <div class="notif-setting-row">
                <div class="notif-setting-row-icon" style="background:var(--panel-alt);color:var(--text-muted)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <div class="notif-setting-row-body">
                    <div class="notif-setting-row-label">Status Global (dikelola Admin)</div>
                    <div class="notif-setting-row-desc">
                        @if($pengaturan->notifikasi_push_aktif ?? true)
                            Push notification sedang <b style="color:var(--green-bright,#16834b)">aktif</b> secara global. Tombol di atas dapat kamu gunakan.
                        @else
                            Push notification sedang <b style="color:var(--red)">dimatikan</b> oleh Admin. Pengaturan pribadimu tidak berpengaruh sementara ini.
                        @endif
                    </div>
                </div>
                <div class="notif-setting-row-right">
                    @if($pengaturan->notifikasi_push_aktif ?? true)
                        <span class="notif-status-pill aktif">
                            <svg viewBox="0 0 10 10" fill="currentColor"><circle cx="5" cy="5" r="5"/></svg>
                            Aktif
                        </span>
                    @else
                        <span class="notif-status-pill mati">
                            <svg viewBox="0 0 10 10" fill="currentColor"><circle cx="5" cy="5" r="5"/></svg>
                            Nonaktif
                        </span>
                    @endif
                </div>
            </div>

            {{-- Row 3: Notifikasi in-app (lonceng) - selalu aktif --}}
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
                <p class="lainnya-section-sub">Bagan hubungan hierarki antar-satuan dalam lingkungan SIBERAD.</p>
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

{{-- ===== Script: toggle notifikasi push ===== --}}
<script>
(function () {
    'use strict';

    var TOGGLE_URL = @json(route('notifikasi.toggle-user'));
    var SUBSCRIBE_URL = @json(route('push.subscribe'));
    var UNSUBSCRIBE_URL = @json(route('push.unsubscribe'));
    var VAPID_KEY = @json(config('webpush.vapid.publicKey'));
    var GLOBAL_AKTIF = @json((bool)($pengaturan->notifikasi_push_aktif ?? true));

    var toggle = document.getElementById('notifPushToggle');
    var pill   = document.getElementById('notifPushPill');
    var pillTxt = document.getElementById('notifPushPillText');
    var permDenied  = document.getElementById('notifPermDenied');
    var permDefault = document.getElementById('notifPermDefault');

    if (!toggle) return;

    function csrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    }

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken()
            },
            body: JSON.stringify(body || {})
        });
    }

    function urlBase64ToUint8Array(b64) {
        var pad = '='.repeat((4 - b64.length % 4) % 4);
        var b = (b64 + pad).replace(/-/g, '+').replace(/_/g, '/');
        var raw = atob(b);
        var out = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; ++i) out[i] = raw.charCodeAt(i);
        return out;
    }

    function syncPill(enabled) {
        if (enabled && GLOBAL_AKTIF) {
            pill.className = 'notif-status-pill aktif';
            pillTxt.textContent = 'Aktif';
        } else if (!GLOBAL_AKTIF) {
            pill.className = 'notif-status-pill mati';
            pillTxt.textContent = 'Diblokir Admin';
        } else {
            pill.className = 'notif-status-pill mati';
            pillTxt.textContent = 'Nonaktif';
        }
    }

    // Inisialisasi pill sesuai state awal checkbox
    syncPill(toggle.checked);

    // Tampilkan peringatan permission browser
    function checkBrowserPermission() {
        if (!('Notification' in window)) return;
        if (Notification.permission === 'denied') {
            if (permDenied) permDenied.style.display = 'block';
            toggle.disabled = true;
        } else if (Notification.permission === 'default') {
            if (permDefault) permDefault.style.display = 'block';
        }
    }
    checkBrowserPermission();

    function subscribePush() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !VAPID_KEY) return;
        navigator.serviceWorker.register('/sw.js').then(function (reg) {
            return reg.pushManager.getSubscription().then(function (existing) {
                if (existing) {
                    return postJson(SUBSCRIBE_URL, subscribePayload(existing));
                }
                return reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_KEY)
                }).then(function (sub) {
                    return postJson(SUBSCRIBE_URL, subscribePayload(sub));
                });
            });
        }).catch(function () {});
    }

    function subscribePayload(sub) {
        var j = sub.toJSON();
        return { endpoint: j.endpoint, keys: j.keys };
    }

    function unsubscribePush() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
        navigator.serviceWorker.getRegistration().then(function (reg) {
            if (!reg) return;
            return reg.pushManager.getSubscription().then(function (sub) {
                if (!sub) return;
                postJson(UNSUBSCRIBE_URL, { endpoint: sub.endpoint }).catch(function(){});
                return sub.unsubscribe().catch(function(){});
            });
        }).catch(function(){});
    }

    toggle.addEventListener('change', function () {
        var enabled = toggle.checked;

        // Minta izin browser kalau belum (hanya saat diaktifkan)
        if (enabled && 'Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(function (perm) {
                if (perm !== 'granted') {
                    toggle.checked = false;
                    syncPill(false);
                    if (permDenied) permDenied.style.display = 'block';
                    return;
                }
                if (permDefault) permDefault.style.display = 'none';
                simpanToggle(true);
            });
            return;
        }

        simpanToggle(enabled);
    });

    function simpanToggle(enabled) {
        toggle.disabled = true;

        postJson(TOGGLE_URL, { enabled: enabled }).then(function (res) {
            return res.json();
        }).then(function (data) {
            toggle.disabled = GLOBAL_AKTIF ? false : true;
            toggle.checked = data.enabled;
            syncPill(data.enabled);

            // Sinkron subscription browser
            if (data.enabled) {
                subscribePush();
            } else if (data.unsubscribe_browser) {
                unsubscribePush();
            }

            if (window.siberadShowToast) {
                window.siberadShowToast(
                    data.enabled ? 'success' : 'info',
                    data.enabled ? 'Notifikasi push diaktifkan.' : 'Notifikasi push dimatikan.'
                );
            }
        }).catch(function () {
            // Rollback UI kalau gagal
            toggle.checked = !enabled;
            syncPill(!enabled);
            toggle.disabled = GLOBAL_AKTIF ? false : true;
            if (window.siberadShowToast) {
                window.siberadShowToast('error', 'Gagal menyimpan pengaturan notifikasi.');
            }
        });
    }
})();
</script>
