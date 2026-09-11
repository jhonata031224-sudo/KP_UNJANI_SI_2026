<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $satuan->nama ?? 'Laporan' }} — {{ $pengaturan?->namaSistem() ?? 'SIBERAD' }}</title>
<link rel="icon" type="image/jpeg" href="{{ asset('images/logo-pussiberad.jpg') }}">
@include('siberad.dashboards.partials.dash-styles')
{{-- CSS grid kartu Permintaan Laporan HARUS kepasang di <head> sebelum kartu-
     nya sendiri dirender di <body> -- kalau nunggu di-include lewat
     laporan-role-shell.blade.php (posisinya SETELAH seluruh halaman ini,
     termasuk </html>), ada jeda flash-of-unstyled-content sesaat pas render
     pertama/refresh (elemen kartu/dropdown/sidebar kelihatan "glitch" sebelum
     opacity:0 dkk kepasang). Script-nya (permintaan-laporan-deadline.blade.php,
     tanpa <style> lagi) TETAP di-include lewat shell seperti biasa. --}}
@include('siberad.dashboards.partials.permintaan-laporan-deadline-styles')
@include('siberad.dashboards.partials.surat-card-styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
:root[data-theme="light"]{--bg:#f5f7f9;--bg-deep:#ffffff;--panel:#ffffff;--panel-2:#f8fafc;--panel-alt:#f8fafc;--border:#e2e8f0;--border-soft:#e2e8f0;--border-strong:#cbd5e1;--gold:#FF9800;--gold-bright:#FF9800;--gold-dim:rgba(201,122,0,.12);--green:#16834b;--green-bright:#16834b;--green-dim:rgba(22,131,75,.12);--amber:#b77900;--amber-dim:rgba(183,121,0,.14);--red:#c83b3b;--red-dim:rgba(200,59,59,.12);--text:#17212b;--text-muted:#64748b;--text-dim:#64748b;--surface:rgba(255,255,255,.9);--hover-tint:rgba(15,23,42,.035)}
.report-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}.report-grid .stat-card .lbl{font-weight:800;}.report-grid .stat-card .val{font-family:var(--mono);}.report-layout{display:grid;grid-template-columns:1fr;gap:18px}.report-card{background:var(--panel);border:1px solid var(--border-soft);border-radius:12px;padding:20px;min-width:0;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25)}.report-card h3{font-family:var(--display);font-size:16px;margin:0 0 4px}.report-card p{font-size:12px;color:var(--text-muted);line-height:1.5}
{{-- 3 kartu KPI Beranda Satuan (Total Pelaporan/Surat/Kendala Kasansi) --
     MIRROR kartu KPI Beranda Pimpinan (laporan-pimpinan.blade.php,
     ".pimp-kpis"/".pimp-kpi" dkk) supaya style-nya sama persis. Markup+
     partial-nya SUDAH shared (partials/pimpinan-kpi-cards.blade.php,
     dipakai bareng oleh DashboardController::pelaporan()/satuanKpiRealtime())
     -- tapi CSS ini SENGAJA disalin+dipetakan ke token dashboard Satuan
     sendiri (var(--panel)/var(--border-soft)/var(--text)/var(--text-muted),
     BUKAN var(--p-surface)/var(--p-border)/var(--p-text)/var(--p-muted))
     karena dashboard Pimpinan punya lapisan alias --p-* sendiri yang TIDAK
     ada di halaman ini. Kalau style kartu KPI Pimpinan diubah lagi nanti,
     salin ulang perubahannya ke sini juga (2 lokasi, jangan lupa). --}}
.pimp-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:22px}.pimp-kpi{position:relative;overflow:hidden;background:var(--panel);border:1px solid var(--border-soft);border-radius:18px;padding:20px 22px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25);min-width:0}.pimp-kpi>:not(.kpi-deco){position:relative;z-index:1}.pimp-kpi .kpi-deco{position:absolute;right:0;bottom:0;width:68%;height:60%;z-index:0;color:var(--kpi-accent);fill:currentColor;opacity:.15;pointer-events:none;transform-origin:bottom;transform:scaleY(1)}
@keyframes kpiDecoGrow{0%{transform:scaleY(0)}55%{transform:scaleY(1.12)}100%{transform:scaleY(1)}}
.kpi-deco.is-growing{animation:kpiDecoGrow .9s cubic-bezier(.33,1,.68,1) both}
@media(prefers-reduced-motion:reduce){.kpi-deco.is-growing{animation:none}}.pimp-kpi .kpi-top{display:flex;align-items:center;gap:12px}.pimp-kpi .kpi-badge{flex:0 0 auto;width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--kpi-accent) 15%,transparent);color:var(--kpi-accent)}.pimp-kpi .kpi-badge svg{width:22px;height:22px}.pimp-kpi .kpi-eyebrow{font-size:11px;font-weight:600;letter-spacing:.09em;text-transform:uppercase;color:var(--text-muted);line-height:1.3}.pimp-kpi .kpi-eyebrow b{font-weight:800;color:var(--text)}.pimp-kpi .kpi-value{font-family:var(--display);font-size:38px;font-weight:800;line-height:1;margin-top:16px;color:var(--text)}.pimp-kpi .kpi-desc{font-size:13px;color:var(--text-muted);margin-top:8px}.pimp-kpi .kpi-trend{display:flex;align-items:center;gap:5px;margin-top:12px;font-family:var(--mono);font-size:13px;font-weight:800;color:var(--text-muted)}.pimp-kpi .kpi-trend svg{width:14px;height:14px;display:none}.pimp-kpi .kpi-trend.is-up{color:var(--kpi-accent)}.pimp-kpi .kpi-trend.is-up svg{display:block}.pimp-kpi .kpi-trend-cap{font-size:11px;color:var(--text-muted);margin-top:2px}
{{-- Kartu "Distribusi Status Laporan" (donut) -- MIRROR kartu Distribusi
     Status Laporan Beranda Pimpinan (laporan-pimpinan.blade.php,
     ".status-dist-card"/".status-donut-wrap"/".status-bd" dkk + partial
     pimpinan-status-distribusi-list.blade.php yang dipakai BARENG). Sama
     alasan kayak blok .pimp-kpis di atas: CSS ini disalin+dipetakan ke
     token dashboard Satuan sendiri (var(--panel)/var(--border-soft)/
     var(--text)/var(--text-muted)/var(--panel-alt)), bukan var(--p-*).
     Kalau style-nya diubah lagi di Pimpinan nanti, salin ulang ke sini
     juga (2 lokasi, jangan lupa). --}}
.chart-card{background:var(--panel);border:1px solid var(--border-soft);border-radius:16px;padding:18px 20px;box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25);min-width:0}
.status-dist-card{width:100%}
.pimp-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap}
.pimp-card-head-main{display:flex;align-items:flex-start;gap:12px;min-width:0}
.pimp-card-ico{flex:0 0 auto;width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center}
.pimp-card-ico svg{width:19px;height:19px}
.pimp-card-head h3{font-family:var(--display);font-size:16px;margin:0;color:var(--text)}
.pimp-card-head p{font-size:11px;color:var(--text-muted);margin:4px 0 0;line-height:1.5}
.status-donut-wrap{position:relative;width:100%;height:230px;margin:6px 0 2px}
.status-donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;pointer-events:none}
.status-donut-center span{font-size:11px;color:var(--text-muted)}
.status-donut-center strong{font-family:var(--mono);font-size:30px;font-weight:700;color:var(--text);line-height:1}
.status-bd{display:flex;flex-direction:column;gap:12px;margin-top:14px;padding-top:16px;border-top:1px solid var(--border-soft)}
.status-bd-row{display:grid;grid-template-columns:10px 1fr auto minmax(90px,1.6fr) auto;align-items:center;gap:10px}
.status-bd-row.is-zero{opacity:.5}
.status-bd-dot{width:10px;height:10px;border-radius:50%}
.status-bd-name{font-size:12.5px;font-weight:700;color:var(--text);white-space:nowrap}
.status-bd-count{font-family:var(--mono);font-size:12.5px;font-weight:700;color:var(--text);text-align:right}
.status-bd-pct{font-family:var(--mono);font-size:10.5px;font-weight:800;padding:3px 8px;border-radius:999px;white-space:nowrap;text-align:center}
.status-bd-bar{position:relative;height:9px;border-radius:999px;background:var(--panel-alt);border:1px solid var(--border-soft);overflow:hidden}
.status-bd-bar-fill{position:absolute;left:0;top:0;bottom:0;border-radius:999px;transition:width .9s cubic-bezier(.22,1,.36,1)}
@media(prefers-reduced-motion:reduce){.status-bd-bar-fill{transition:none}}
{{-- Kartu "Surat Terbaru" & "Kendala Kasansi Terbaru" -- MIRROR kartu yang
     sama di Beranda Pimpinan (laporan-pimpinan.blade.php). Reuse partial
     yang SAMA (partials/pimpinan-surat-terbaru-rows.blade.php &
     pimpinan-kendala-terbaru-list.blade.php) apa adanya -- CSS di sini
     cuma nyediain class yang dipakai markup-nya, token-map sama alasan
     kayak blok-blok di atas. --}}
.satuan-terbaru-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.card-link{display:flex;align-items:center;justify-content:center;gap:5px;margin-top:14px;padding:8px;border-radius:8px;border:1px solid var(--border-soft);background:var(--panel);font-size:11px;font-weight:600;color:var(--text-muted);text-decoration:none;transition:.15s ease}
.card-link:hover{border-color:var(--gold-bright);color:var(--gold-bright);background:var(--panel-alt)}
{{-- "Lihat Semua Surat"/"Lihat Semua Kendala" dipindah dari footer kartu ke
     pojok kanan-atas header (sejajar judul) -- niru posisi+style tombol
     "Lihat Semua" Aktivitas Terbaru Admin (.panel-head, class btn btn-ghost
     btn-sm -- kelas .btn/.btn-sm-nya SUDAH ada & sama persis dari
     partials/dash-styles.blade.php yang dipakai bareng, gak perlu
     token-map apa-apa lagi). TIDAK pakai .card-link lagi (biar gak numpang
     cascade CSS lama yang box-penuh/footer) -- JS tab-jump-nya sekarang
     lewat selector .btn.btn-ghost[href^="#"]. align-self:center biar
     nempel rata tengah sejajar blok judul, niru align-items:center punya
     .panel-head Admin (.pimp-card-head sendiri pakai align-items:flex-start). --}}
.pimp-card-head>.btn{align-self:center}
.status-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:10px;font-weight:800;border:1px solid transparent}
.status-pill:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}
.status-pill.wait{color:var(--amber);background:rgba(224,168,58,.12);border-color:rgba(224,168,58,.35)}
.status-pill.ok{color:var(--green);background:rgba(63,194,125,.12);border-color:rgba(63,194,125,.28)}
.status-pill.bad{color:var(--red);background:rgba(181,52,47,.12);border-color:rgba(198,40,40,.3)}
.clean-table-wrap{overflow-x:auto}
.clean-table{width:100%;border-collapse:collapse;min-width:780px}
.clean-table th{font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);text-align:left;padding:11px 12px;border-bottom:1px solid var(--border-soft);white-space:nowrap}
.clean-table td{padding:13px 12px;border-bottom:1px solid var(--border-soft);font-size:12px;color:var(--text);vertical-align:middle}
.clean-table tbody tr:hover{background:var(--hover-tint)}
.clean-table tbody tr:last-child td{border-bottom:0}
.kcard-empty{grid-column:1/-1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:48px 20px;text-align:center;color:var(--text-muted)}
.kcard-empty-title{font-size:14px;font-weight:700;color:var(--text-muted)}
.kcard-empty-sub{font-size:12px;color:var(--text-muted);line-height:1.5;max-width:320px;opacity:.7}
.pimp-empty-compact{padding:30px 16px;gap:8px}
.pimp-empty-compact .kcard-empty-title{font-size:12.5px}
.pimp-empty-compact .kcard-empty-sub{font-size:11px;max-width:260px}
.clean-table.pimp-mini-table{min-width:0;width:100%;table-layout:fixed}
.pimp-mini-table th,.pimp-mini-table td{padding:9px 6px;font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pimp-mini-table th{font-size:9px;letter-spacing:0}
.pimp-mini-table .surat-arah{justify-content:center}
.pimp-mini-table .status-badge,.pimp-mini-table .satuan-pill{font-size:9.5px;padding:3px 7px}
{{-- "Menunggu" di tabel mini ini HARUS ikut warna BIRU yang beneran dipakai
     di kartu Surat Keluar/Surat Masuk/Arsip Surat & modal Detail Surat
     (lihat partials/surat-card-styles.blade.php, selector
     ".status-badge.status-menunggu.surat-file-card-badge") -- BUKAN warna
     amber di aturan umum ".status-badge.status-menunggu" (itu cuma fallback
     buat konteks LAIN yang gak scoped, kayak badge Kendala Terkirim/
     Tembusan yang gak boleh ikut kesenggol). Scoped ke .pimp-mini-table
     spesifik, bukan ubah aturan umum. --}}
.pimp-mini-table .status-badge.status-menunggu{color:#2476ad;background:rgba(52,152,219,.1);border-color:rgba(52,152,219,.25)}
.surat-arah{display:inline-flex;align-items:center;gap:3px;font-size:10.5px;font-weight:800;white-space:nowrap}
.surat-arah-masuk{color:var(--green)}
.surat-arah-keluar{color:#3b82f6}
.pimp-activity-list{display:flex;flex-direction:column}
.pimp-activity-item{display:flex;align-items:flex-start;gap:12px;padding:13px 0}
.pimp-activity-item:first-child{padding-top:0}
.pimp-activity-item:last-child{padding-bottom:0}
.pimp-activity-ico{flex:0 0 auto;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center}
.pimp-activity-ico svg{width:16px;height:16px}
.pimp-activity-ico-kendala{background:color-mix(in srgb,#f59e0b 15%,transparent);color:#f59e0b}
.pimp-activity-body{flex:1;min-width:0}
.pimp-activity-title{font-size:13px;font-weight:700;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pimp-activity-sub{font-size:11px;color:var(--text-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pimp-activity-item .status-pill{flex:0 0 auto;white-space:nowrap}
{{-- Animasi "baris/item baru" buat Surat Terbaru & Kendala Kasansi Terbaru
     -- MIRROR PERSIS animasi yang sama di Beranda Pimpinan (identik byte-
     for-byte, cuma pakai rgba literal jadi gak perlu token-map). Fade-in +
     flash background, dipicu manual dari JS (animateTerbaruRows) tiap kali
     kontennya baru dirender (load pertama maupun abis di-swap realtime). --}}
@keyframes terbaruFreshIn{0%{opacity:0;background-color:rgba(59,130,246,.14)}100%{opacity:1;background-color:transparent}}
.pimp-mini-table tbody tr.is-fresh{animation:terbaruFreshIn .8s ease both}
.pimp-activity-item.is-fresh{animation:terbaruFreshIn .8s ease both}
@media(prefers-reduced-motion:reduce){.pimp-mini-table tbody tr.is-fresh,.pimp-activity-item.is-fresh{animation:none}}
.chart-legend{display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:8px 14px;margin-top:14px}.chart-legend-item{display:flex;align-items:center;gap:6px;font-size:11.5px;font-weight:600;color:var(--text-muted);white-space:nowrap;cursor:pointer;user-select:none}.chart-legend-item.is-hidden{text-decoration:line-through;opacity:.5}.chart-legend-dot{width:9px;height:9px;border-radius:50%;flex:0 0 auto}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.form-field{display:flex;flex-direction:column;gap:7px}.form-field.full{grid-column:1/-1}.form-field label{font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em}.form-field input,.form-field select,.form-field textarea{width:100%;box-sizing:border-box;background:var(--panel-alt);border:1px solid var(--border);border-radius:7px;color:var(--text);padding:10px 11px;font:inherit;font-size:13px}.form-field textarea{resize:none;min-height:120px}.form-hint{font-size:11px;color:var(--text-dim);line-height:1.5}.monitor-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px}.monitor-card{padding:14px;border:1px solid var(--border-soft);border-radius:9px;background:var(--panel-alt);box-shadow:0 1px 0 rgba(255,255,255,.02) inset, 0 10px 30px rgba(0,0,0,.25)}.monitor-card .name{font-weight:700;font-size:13px}.monitor-card .num{font-family:var(--mono);font-size:24px;margin-top:7px}.monitor-card .sub{font-size:11px;color:var(--text-muted);margin-top:3px}.review-actions{display:flex;gap:7px;flex-wrap:wrap}.review-actions form{display:inline-flex}.review-actions button,.monitor-detail-btn{border:1px solid var(--border);background:var(--panel-alt);color:var(--text);border-radius:6px;padding:6px 9px;font-size:11px;cursor:pointer}.review-actions .approve{border-color:var(--success);color:var(--success)}
.approve:hover{background:var(--success-dim);transform:translateY(-1px)}
#konfirmasiSuratYa:hover{border-color:var(--success-bright);color:var(--success-bright);transform:translateY(-1px)}.review-actions .reject{border-color:var(--red);color:var(--red)}
.reject:hover{background:var(--red-dim);transform:translateY(-1px)}.detail-btn{border:1px solid var(--border);background:var(--panel);color:var(--text);border-radius:8px;padding:7px 10px;font-size:10px;font-weight:700;cursor:pointer}.detail-btn:hover{border-color:var(--gold-bright);background:var(--panel-alt)}.monitor-detail-btn{border-color:var(--border)!important;color:var(--text)!important}.status-dot.green{color:var(--success-bright)!important}.status-dot.green::before{animation:none!important}.status-dot.amber{color:var(--amber)!important}.status-dot.bad{color:var(--red)!important}.status-dot.blue{color:var(--p-orange)!important}.satuan-pill{display:inline-flex;align-items:center;border-radius:8px;padding:4px 9px;font-size:10px;font-weight:800;letter-spacing:.03em;color:var(--gold-bright);background:rgba(201,122,0,.1);border:1px solid rgba(201,122,0,.22);white-space:nowrap}.priority-tag{display:inline-flex;align-items:center;border-radius:999px;padding:5px 10px;font-size:10px;font-weight:800;border:1px solid transparent;white-space:nowrap}.priority-tag.prio-tinggi{color:var(--red);background:rgba(181,52,47,.12);border-color:rgba(198,40,40,.3)}.priority-tag.prio-sedang{color:var(--amber);background:rgba(224,168,58,.12);border-color:rgba(224,168,58,.35)}.priority-tag.prio-rendah{color:var(--text-muted);background:var(--panel);border-color:var(--border)}.status-badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 10px;font-size:10px;font-weight:800;border:1px solid transparent;white-space:nowrap;letter-spacing:.02em}.status-badge.status-menunggu{color:var(--amber);background:rgba(224,168,58,.13);border-color:rgba(224,168,58,.35)}.status-badge.status-dikonfirmasi{color:var(--success-bright,#3dba7e);background:rgba(61,186,126,.12);border-color:rgba(61,186,126,.35)}.status-badge.status-ditolak{color:var(--red);background:rgba(181,52,47,.12);border-color:rgba(198,40,40,.3)}.request-deadline{display:inline-flex;align-items:center;gap:5px;font-weight:700}.request-deadline svg{width:13px;height:13px;flex-shrink:0;opacity:.75}.report-modal{position:fixed;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;padding:20px;z-index:1000;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease}.report-modal.open{opacity:1;visibility:visible;pointer-events:auto}.report-modal-card{width:min(720px,100%);max-height:90vh;overflow-y:auto;overflow-x:hidden;background:var(--panel);border:1px solid var(--border-soft);border-radius:20px;padding:22px;box-shadow:0 20px 60px rgba(0,0,0,.25);box-sizing:border-box;transform:translateY(14px) scale(.97);transition:transform .2s ease;-webkit-mask-image:radial-gradient(white,white);mask-image:radial-gradient(white,white)}#kirimKendalaModal .satuan-pill-tujuan{display:inline-flex;width:auto;max-width:fit-content}.report-modal.open .report-modal-card{transform:translateY(0) scale(1)}.report-modal-head{display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:16px}.report-modal-head h3{margin:0;font-family:var(--display)}.report-modal-close{flex-shrink:0;width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;transition:border-color .2s ease,color .2s ease,transform .2s ease}.report-modal-close:hover{border-color:var(--red);color:var(--red);transform:rotate(90deg);}.report-modal-close svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;}.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.detail-item{padding:11px;border:1px solid var(--border-soft);border-radius:8px;background:var(--panel-alt)}.detail-item.full{grid-column:1/-1}.detail-label{font-size:10px;text-transform:uppercase;color:var(--text-muted);font-weight:700;letter-spacing:.05em;margin-bottom:5px}.detail-value{font-size:13px;line-height:1.6;white-space:pre-wrap}.lampiran-btn{display:inline-flex;align-items:center;gap:7px;border:1px solid color-mix(in srgb,var(--gold-bright) 45%,var(--border));background:color-mix(in srgb,var(--gold-bright) 10%,var(--panel-alt));color:var(--gold-bright);border-radius:8px;padding:8px 12px;font-size:11.5px;font-weight:700;text-decoration:none;cursor:pointer;transition:background .15s ease,transform .15s ease;margin-bottom:6px}.lampiran-btn:last-child{margin-bottom:0}.lampiran-btn:hover{background:color-mix(in srgb,var(--gold-bright) 20%,var(--panel-alt));transform:translateY(-1px)}.lampiran-btn svg{width:15px;height:15px;flex-shrink:0}.lampiran-preview-list{display:flex;flex-direction:column;gap:10px}.lampiran-preview-item{border:1px solid var(--border-soft);border-radius:10px;overflow:hidden;background:var(--panel-2,#0000000d)}.lampiran-preview-item img{display:block;width:100%;max-height:420px;object-fit:contain;background:#111;cursor:zoom-in}.lampiran-preview-item iframe{display:block;width:100%;height:420px;border:0;background:#fff}.lampiran-preview-caption{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 10px;border-top:1px solid var(--border-soft)}.lampiran-preview-name{font-size:11px;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.lampiran-preview-caption a{flex-shrink:0;font-size:11px;font-weight:700;color:var(--gold-bright);text-decoration:none}.lampiran-preview-caption a:hover{text-decoration:underline}.lampiran-preview-file{display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--border-soft);border-radius:10px;background:var(--panel-alt)}.lampiran-preview-file>svg{width:22px;height:22px;flex-shrink:0;color:var(--gold-bright)}.lampiran-preview-file-info{flex:1;min-width:0;display:flex;flex-direction:column;gap:2px}.lampiran-preview-file-name{font-size:12px;font-weight:700;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.lampiran-preview-file-hint{font-size:10px;color:var(--text-dim)}.lampiran-preview-file>a{flex-shrink:0;font-size:11px;font-weight:700;color:var(--gold-bright);text-decoration:none}.lampiran-preview-file>a:hover{text-decoration:underline}.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:18px;flex-wrap:wrap}#kirimLaporanModal select{appearance:none;-webkit-appearance:none;-moz-appearance:none;cursor:pointer;padding-right:34px;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='m6 9 6 6 6-6'/></svg>");background-repeat:no-repeat;background-position:right 12px center;background-size:14px;transition:border-color .15s ease,box-shadow .15s ease}#kirimLaporanModal select:hover{border-color:var(--gold)}#kirimLaporanModal .form-field input:focus,#kirimLaporanModal .form-field select:focus,#kirimLaporanModal .form-field textarea:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 3px var(--gold-dim)}#kirimLaporanModal .form-field input[readonly],#kirimLaporanModal .form-field select:disabled{opacity:.6;cursor:not-allowed;background:var(--panel-2)}#kirimLaporanModal select:disabled{background-image:none!important;padding-right:11px;appearance:none;-webkit-appearance:none;-moz-appearance:none;}
#kirimLaporanModal .field-invalid,#kirimKendalaModal .field-invalid,#kirimSuratModal .field-invalid{border-color:var(--red)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--red) 15%,transparent)}
#kirimLaporanModal .kirim-laporan-error,#kirimKendalaModal .kirim-laporan-error,#kirimSuratModal .kirim-laporan-error{display:flex;align-items:center;gap:6px;font-size:10.5px;color:var(--red)}
#kirimLaporanModal .lampiran-dropzone.field-invalid{border-color:var(--red)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--red) 15%,transparent)}
#kirimLaporanModal .kirim-laporan-modal-head{align-items:center}
#kirimLaporanModal .task-detail-btn{margin-left:auto;flex-shrink:0;display:inline-flex;align-items:center;gap:6px;border:1px solid color-mix(in srgb,var(--gold-bright) 45%,var(--border));background:color-mix(in srgb,var(--gold-bright) 10%,var(--panel-alt));color:var(--gold-bright);border-radius:9px;padding:8px 12px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .15s ease,transform .15s ease,border-color .15s ease}
#kirimLaporanModal .task-detail-btn:hover{background:color-mix(in srgb,var(--gold-bright) 20%,var(--panel-alt));transform:translateY(-1px)}
#kirimLaporanModal .task-detail-btn svg{width:14px;height:14px;flex-shrink:0}
#kirimLaporanModal .task-detail-btn[hidden]{display:none}
#taskDetailModal .report-modal-card{width:min(480px,100%)}
#taskDetailModal .task-detail-modal-sub{margin:2px 0 12px;font-size:12px;color:var(--text-muted);line-height:1.55}
#taskDetailModal .task-detail-modal-body{font-size:13px;line-height:1.7;white-space:pre-wrap;color:var(--text);border:1px solid var(--border-soft);border-radius:10px;background:var(--panel-alt);padding:13px 15px;max-height:56vh;overflow-y:auto}
.lampiran-input-wrap{display:flex;align-items:center;gap:8px}
.lampiran-input-wrap .siberad-file-input{flex:1;min-width:0}
.lampiran-clear-btn{flex-shrink:0;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);background:transparent;color:var(--text-muted);cursor:pointer;transition:border-color .15s ease,color .15s ease}
.lampiran-clear-btn:hover{border-color:var(--red);color:var(--red)}
.lampiran-clear-btn svg{width:14px;height:14px;stroke:currentColor;fill:none;stroke-width:2}
.surat-modal-icon{flex-shrink:0;width:44px;height:44px;border-radius:12px;background:color-mix(in srgb,var(--gold-solid-bright) 16%,transparent);color:var(--gold-solid-bright);display:flex;align-items:center;justify-content:center}
.surat-modal-icon svg{width:21px;height:21px}
#kirimSuratModal .report-modal-head{margin-bottom:28px}
#kirimSuratModal .report-modal-head h3{font-size:19px}
#kirimSuratModal .modal-actions{border-top:1px solid var(--border-soft);padding-top:18px;margin-top:24px}
.surat-combobox{position:relative;width:100%}
.surat-combobox input[type="text"]{width:100%;box-sizing:border-box}
.surat-form-grid{display:grid;grid-template-columns:1.3fr 1fr;gap:0 26px}
.surat-form-col{display:flex;flex-direction:column;gap:14px}
.surat-form-col-lampiran{border-left:1px solid var(--border-soft);padding-left:26px}
.surat-section-label{font-family:var(--mono);font-size:10.5px;letter-spacing:.08em;text-transform:uppercase;color:var(--gold-bright);font-weight:700}
.priority-toggle{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}
.priority-option{position:relative;display:flex;align-items:center;justify-content:center;border:1px solid var(--p-border,var(--border));border-radius:10px;padding:9px 6px;font-size:11px;font-weight:700;color:var(--p-muted,var(--text-muted));background:var(--p-surface-2,var(--panel-alt));cursor:pointer;transition:border-color .15s ease,background .15s ease,color .15s ease}
.priority-option input{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none}
.priority-option.prio-rendah:hover{border-color:#8b5cf6;color:#8b5cf6}
.priority-option.prio-sedang:hover{border-color:#a855f7;color:#a855f7}
.priority-option.prio-tinggi:hover{border-color:#6d28d9;color:#6d28d9}
.priority-option.prio-rendah:has(input:checked){border-color:#8b5cf6;background:#8b5cf6;color:#fff}
.priority-option.prio-sedang:has(input:checked){border-color:#a855f7;background:#a855f7;color:#fff}
.priority-option.prio-tinggi:has(input:checked){border-color:#6d28d9;background:#6d28d9;color:#fff}
.surat-lampiran-zone{position:relative;border:1.5px dashed var(--border-soft,var(--border));border-radius:12px;padding:26px 16px;background:var(--panel);text-align:center;transition:border-color .15s ease,background-color .15s ease}
.surat-lampiran-zone:hover,.surat-lampiran-zone.is-dragover{border-color:var(--gold-bright);background:var(--gold-dim)}
.surat-lampiran-zone-input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;margin:0}
.surat-lampiran-zone-prompt{display:flex;flex-direction:column;align-items:center;gap:8px;pointer-events:none}
.surat-lampiran-zone-icon{color:var(--gold-bright)}
.surat-lampiran-zone-icon svg{width:24px;height:24px}
.surat-lampiran-zone-text{display:flex;flex-direction:column;gap:8px}
.surat-lampiran-zone-text-main{font-size:13px;font-weight:700;color:var(--text)}
.surat-lampiran-zone-text-sub{font-size:11.5px;color:var(--text-muted)}
.surat-lampiran-preview{margin-top:10px;flex:1;min-height:0;display:flex}
.surat-lampiran-preview[hidden]{display:none}
.surat-lampiran-preview-frame{position:relative;width:100%;flex:1;min-height:90px;border-radius:9px;overflow:hidden;border:1px solid var(--border-soft);background:var(--panel-alt);display:flex;align-items:center;justify-content:center}
.surat-lampiran-preview-fallback{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;color:var(--text-muted);padding:10px;height:100%;box-sizing:border-box}
.surat-lampiran-preview-fallback-badge{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;letter-spacing:.02em;color:#fff}
.surat-lampiran-preview-fallback-text{font-size:10.5px;text-align:center;line-height:1.4}
@media(max-width:640px){.surat-form-grid{grid-template-columns:1fr}.surat-form-col-lampiran{border-left:none;padding-left:0;border-top:1px solid var(--border-soft);padding-top:16px;margin-top:2px}.surat-lampiran-preview{flex:none;height:170px}.surat-lampiran-preview-frame{flex:none;height:100%}#kirimSuratModal .priority-toggle{grid-template-columns:1fr}}
@media(max-width:980px){.report-grid{grid-template-columns:1fr 1fr}.pimp-kpis{grid-template-columns:repeat(2,1fr)}.report-layout{grid-template-columns:1fr}.monitor-grid{grid-template-columns:1fr 1fr}.satuan-terbaru-row{grid-template-columns:1fr}}@media(max-width:650px){.report-grid,.pimp-kpis,.form-grid,.monitor-grid,.detail-grid{grid-template-columns:1fr}.form-field.full,.detail-item.full{grid-column:auto}}
.side-nav-group{margin:0}.side-nav-group-title{width:100%;display:flex;align-items:center;gap:10px;padding:10px 12px;margin:2px 0;border:1px solid transparent;border-radius:9px;background:transparent;color:var(--text-muted);font-family:var(--body);font-size:13.5px;font-weight:500;cursor:pointer;text-align:left;box-sizing:border-box;transition:background .15s ease,color .15s ease}.side-nav-group-title:hover{background:var(--hover-tint);color:var(--text)}.side-nav-group.open .side-nav-group-title{color:var(--text)}.side-nav-group-title .side-text{flex:1}.side-nav-group-title .chevron{margin-left:auto;width:15px;height:15px;flex-shrink:0;opacity:.6;transition:transform .25s cubic-bezier(.4,0,.2,1),opacity .2s ease}.side-nav-group.open .chevron{transform:rotate(180deg);opacity:1}.side-subnav{display:grid;grid-template-rows:0fr;opacity:0;transition:grid-template-rows .3s cubic-bezier(.4,0,.2,1),opacity .25s ease;overflow:hidden}.side-subnav>div{min-height:0;padding:3px 0;margin-left:18px;border-left:1px solid var(--border-soft)}.side-nav-group.open .side-subnav{grid-template-rows:1fr;opacity:1}.side-sub-link{position:relative;display:flex;align-items:center;gap:10px;padding:9px 12px 9px 17px;border-radius:0 9px 9px 0;color:var(--text-muted);font-family:var(--body);font-size:13px;font-weight:500;text-decoration:none;margin:1px 0;box-sizing:border-box;transition:background .15s ease,color .15s ease}.side-sub-link:hover{background:var(--hover-tint);color:var(--text)}.side-sub-link .sub-dot{width:5px;height:5px;border-radius:50%;background:currentColor;opacity:.5;flex:0 0 auto;transition:opacity .15s ease,background .15s ease,box-shadow .15s ease}.side-sub-link.active{background:var(--gold-dim);color:var(--gold-bright);font-weight:600}.side-sub-link.active:before{content:"";position:absolute;left:-1px;top:8px;bottom:8px;width:2px;border-radius:2px;background:var(--gold-bright)}.side-sub-link.active .sub-dot{background:var(--gold-bright);opacity:1;box-shadow:0 0 0 3px rgba(201,122,0,.15)}.side-subnav-label{display:none}
.sidebar.collapsed .side-subnav{display:none}.sidebar.collapsed .side-nav-group.open .side-subnav{display:block;position:fixed;min-width:216px;background:var(--panel);border:1px solid var(--border-soft);border-radius:12px;box-shadow:0 14px 34px rgba(0,0,0,.22);padding:8px;z-index:100020;max-height:min(420px,calc(100vh - 80px));overflow-y:auto;overflow-x:hidden}.sidebar.collapsed .side-nav-group.open .side-subnav::-webkit-scrollbar{width:4px}.sidebar.collapsed .side-nav-group.open .side-subnav::-webkit-scrollbar-thumb{background:var(--border-soft);border-radius:99px}.sidebar.collapsed .side-nav-group.open .side-subnav::-webkit-scrollbar-track{background:transparent}.sidebar.collapsed .side-subnav>div{margin-left:0;border-left:none;padding:0}.sidebar.collapsed .side-subnav-label{display:block;font-family:var(--mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);padding:4px 10px 8px}.sidebar.collapsed .side-sub-link{padding:9px 10px;border-radius:8px}.sidebar.collapsed .side-nav-group.has-active-child .side-nav-group-title{color:var(--gold-bright);background:var(--gold-dim)}
@media(max-width:900px){.sidebar.collapsed .side-subnav{display:grid}.sidebar.collapsed .side-nav-group.open .side-subnav{position:static;top:auto!important;left:auto!important;min-width:0;background:none;border:0;box-shadow:none;padding:0;z-index:auto}.sidebar.collapsed .side-subnav>div{margin-left:18px;border-left:1px solid var(--border-soft);padding:3px 0}.sidebar.collapsed .side-subnav-label{display:none}}
</style>
</head>
<body>
<div class="profile-modal-overlay" id="profileModalOverlay"><div class="profile-modal-card" id="profileModalCard" role="dialog" aria-modal="true" aria-label="Detail profil"><button type="button" class="profile-modal-close" id="profileModalCloseBtn" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button><div class="profile-dropdown-view" id="profileSettingsView" style="display:none;"><div class="profile-modal-title">Pengaturan Akun</div><div class="profile-subtabs" role="tablist"><button type="button" class="profile-subtab-btn active" data-subtab-target="profilePhotoView" role="tab" aria-selected="true"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8.5A1.5 1.5 0 0 1 5.5 7h2l1-2h7l1 2h2A1.5 1.5 0 0 1 20 8.5v9A1.5 1.5 0 0 1 18.5 19h-13A1.5 1.5 0 0 1 4 17.5Z"></path><circle cx="12" cy="13" r="3.4"></circle></svg>Foto Profil</button><button type="button" class="profile-subtab-btn" data-subtab-target="profilePasswordView" role="tab" aria-selected="false"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2.2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>Ganti Password</button></div><div class="profile-subtab-panel active" id="profilePhotoView" role="tabpanel"><div class="profile-dropdown-head-lg"><div class="profile-dropdown-avatar-lg"><span class="profile-initial" id="profileInitialLarge" style="display:{{ $user->foto_path ? 'none' : '' }};">{{ strtoupper(mb_substr($user->name ?? 'U',0,1)) }}</span><img class="profile-photo" id="profilePhotoLarge" alt="Foto profil {{ $user->name }}" @if($user->foto_path) src="{{ asset('storage/'.$user->foto_path) }}" style="display:block;" @endif></div><div class="profile-dropdown-name">{{ $user->name }}</div><div class="profile-dropdown-role">{{ $user->jabatan ?? 'Pengguna' }}</div></div><div class="profile-photo-actions"><form method="POST" action="{{ route('profil-foto.update') }}" enctype="multipart/form-data" id="formGantiFoto">@csrf<button type="button" class="profile-btn profile-btn-primary" id="gantiFotoBtn"><span id="gantiFotoLabel">Ganti Foto</span></button><input type="file" name="foto" id="fotoProfilInput" accept="image/png,image/jpeg,image/webp" hidden></form><button type="button" class="profile-btn profile-btn-outline" id="hapusFotoBtn" style="display:{{ $user->foto_path ? '' : 'none' }};"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg>Hapus</button></div><p class="profile-photo-hint">Format JPG, PNG, atau WEBP — ukuran maksimal 10 MB.</p></div><div class="profile-subtab-panel" id="profilePasswordView" role="tabpanel">@if($permintaanGantiPasswordPending)@include('siberad.dashboards.partials.profile-password-pending', ['permintaan' => $permintaanGantiPasswordPending])@endif<div id="profilePasswordFormWrap" @if($permintaanGantiPasswordPending)style="display:none"@endif><div class="profile-form-notice">Perubahan kata sandi tidak langsung berlaku. Permintaan akan dikirim ke <b>Admin</b> untuk diverifikasi terlebih dahulu.</div><form class="profile-form" id="formGantiPassword" method="POST" action="{{ route('permintaan-reset-password.store') }}">@csrf<div class="profile-form-field"><label for="passBaru">Kata Sandi Baru</label><div class="profile-field-toggle-wrap"><input type="password" id="passBaru" name="password_baru" required placeholder="Kata sandi baru"><button class="field-toggle" type="button" data-target="passBaru" aria-label="Tampilkan Password"><svg class="icon-eye" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"></path><circle cx="12" cy="12" r="3.2"></circle></svg><svg class="icon-eye-off" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"></path><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c7 0 10.5 7 10.5 7a13.6 13.6 0 0 1-3.2 4.1M6.6 6.6C3.5 8.5 1.5 12 1.5 12s3.5 7 10.5 7a10.6 10.6 0 0 0 4.2-.85"></path><path d="M9.5 9.7a3.2 3.2 0 0 0 4.5 4.5"></path></svg></button></div></div><div class="profile-form-field"><label for="passKonfirmasi">Konfirmasi Kata Sandi Baru</label><div class="profile-field-toggle-wrap"><input type="password" id="passKonfirmasi" name="password_baru_confirmation" required placeholder="Ulangi kata sandi baru"><button class="field-toggle" type="button" data-target="passKonfirmasi" aria-label="Tampilkan Password"><svg class="icon-eye" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"></path><circle cx="12" cy="12" r="3.2"></circle></svg><svg class="icon-eye-off" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"></path><path d="M10.6 5.1A10.9 10.9 0 0 1 12 5c7 0 10.5 7 10.5 7a13.6 13.6 0 0 1-3.2 4.1M6.6 6.6C3.5 8.5 1.5 12 1.5 12s3.5 7 10.5 7a10.6 10.6 0 0 0 4.2-.85"></path><path d="M9.5 9.7a3.2 3.2 0 0 0 4.5 4.5"></path></svg></button></div></div><div class="profile-form-field"><label for="passCatatan">Catatan untuk Admin (opsional)</label><textarea id="passCatatan" name="catatan" rows="2" placeholder="Contoh: lupa kata sandi lama"></textarea></div><button type="submit" class="btn btn-primary">Kirim Permintaan ke Admin</button></form></div></div></div><div class="profile-dropdown-view" id="profileHelpView" style="display:none;"><div class="profile-modal-title">Bantuan &amp; Panduan</div><p class="help-intro">Ringkasan menu di dashboard ini. Semua menu ada di sidebar kiri.</p><div class="help-topics">@if($modulAktif['laporan'] ?? true) <div class="help-topic"><div class="help-topic-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"></path></svg></div><div class="help-topic-body"><div class="help-topic-title">Pelaporan</div><div class="help-topic-desc">@if(! in_array(strtoupper($satuan->kode ?? ''), ['ADMIN','DANPUS','WADAN'], true))Permintaan Laporan — kerjakan tugas dari Danpus/Wadan sebelum deadline. @endif Arsip Laporan — status semua laporan yang pernah dikirim (menunggu / disetujui / revisi / ditolak).</div></div></div> @endif @if($modulAktif['surat'] ?? true) <div class="help-topic"><div class="help-topic-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg></div><div class="help-topic-body"><div class="help-topic-title">Surat</div><div class="help-topic-desc">@if($bisaKirimSurat) Buat surat lewat Surat Keluar, lalu pantau Surat Masuk dan Arsip Surat. @else Lihat Surat Masuk dan Arsip Surat untuk surat yang sudah dikonfirmasi. @endif </div></div></div> @endif @if($modulAktif['kendala'] ?? true) @if($isKasansi) <div class="help-topic"><div class="help-topic-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></div><div class="help-topic-body"><div class="help-topic-title">Kendala</div><div class="help-topic-desc">Kirim Kendala untuk melaporkan hambatan ke Danpus; yang sudah selesai ditindaklanjuti ada di Arsip Kendala.</div></div></div> @endif @if($isPenerimaTembusan) <div class="help-topic"><div class="help-topic-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></div><div class="help-topic-body"><div class="help-topic-title">Kendala</div><div class="help-topic-desc">Tembusan Kendala — salinan laporan kendala satuan lain yang ditujukan ke satuanmu (info/koordinasi, bukan buat diputuskan).</div></div></div> @endif @endif @if($mode === 'duktek' && ($modulAktif['monitoring'] ?? true)) <div class="help-topic"><div class="help-topic-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg></div><div class="help-topic-body"><div class="help-topic-title">Monitoring 3 Satlak</div><div class="help-topic-desc">Pantau aktivitas laporan dari Penangkalan, Siber Sosial, dan Penindakan.</div></div></div> @endif </div><div class="help-footer"><div class="help-footer-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 6-10 7L2 6"></path></svg></div><p>Butuh bantuan lebih lanjut? Hubungi <b>Admin Pussiberad</b> melalui jalur koordinasi internal.</p></div></div></div></div>
<div class="report-modal" id="reportDetailModal"><div class="report-modal-card"><div class="report-modal-head"><h3>Detail Aktivitas Laporan</h3><button type="button" class="report-modal-close" id="reportDetailClose" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div><div class="detail-grid"><div class="detail-item"><div class="detail-label">Pengirim</div><div class="detail-value" id="detailPengirim">-</div></div><div class="detail-item"><div class="detail-label">Tujuan</div><div class="detail-value" id="detailTujuan">-</div></div><div class="detail-item"><div class="detail-label">Perihal</div><div class="detail-value" id="detailPerihal">-</div></div><div class="detail-item"><div class="detail-label">Prioritas</div><div class="detail-value" id="detailPrioritas">-</div></div><div class="detail-item" id="detailProgresWrap" style="display:none"><div class="detail-label">Progres</div><div class="detail-value" id="detailProgres">-</div></div><div class="detail-item"><div class="detail-label">Kategori/Kegiatan</div><div class="detail-value" id="detailProyek">-</div></div><div class="detail-item"><div class="detail-label">Tanggal</div><div class="detail-value" id="detailTanggal">-</div></div><div class="detail-item" id="detailDeskripsiWrap"><div class="detail-label" id="detailDeskripsiLabel">Isi Laporan</div><div class="detail-value" id="detailDeskripsi">-</div></div><div class="detail-item" id="detailTembusanWrap" style="display:none"><div class="detail-label">Tembusan</div><div class="detail-value" id="detailTembusanList"></div></div><div class="detail-item" id="detailBalasanKolom" style="display:none"><div class="detail-label">Balasan</div><div class="detail-value" id="detailBalasanList"></div></div><div class="detail-item full" id="detailBalasanTembusanWrap" style="display:none"><div class="detail-label">Balasan Anda ke Kasansi</div><div class="detail-value" id="detailBalasanTembusanValue"></div></div><div class="detail-item full" id="detailKendalaWrap" style="display:none"><div class="detail-label">Kendala/Alasan</div><div class="detail-value" id="detailKendala">-</div></div><div class="detail-item full" id="detailLampiranWrap" style="display:none"><div class="detail-label">Lampiran</div><div class="detail-value" id="detailLampiran"></div></div></div><div class="modal-actions" id="detailActions"></div></div></div>
@if($canSend)<div class="report-modal" id="kirimLaporanModal"><div class="report-modal-card"><div class="kirim-laporan-wizard-body" id="kirimLaporanWizardBody"><div class="kirim-laporan-wizard-topbar" id="kirimLaporanWizardTopbar" hidden><button type="button" class="wizard-topbar-nav wizard-topbar-nav-prev" id="kirimLaporanWizardPrev" aria-label="Task sebelumnya" hidden><svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"></path></svg></button><ol class="wizard-step-list" id="kirimLaporanWizardSteps"></ol><button type="button" class="wizard-topbar-nav wizard-topbar-nav-next" id="kirimLaporanWizardNext" aria-label="Task selanjutnya" hidden><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"></path></svg></button></div><div class="kirim-laporan-wizard-panel"><div class="kirim-laporan-modal-head"><span class="kirim-laporan-modal-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect><path d="m9 14 2 2 4-4"></path></svg></span><span><h3 id="kirimLaporanTitle" style="margin:0 0 4px;">Kirim Laporan</h3><p id="kirimLaporanDesc" style="margin:0;font-size:12px;color:var(--text-muted);line-height:1.5;">Sampaikan laporan kepada pimpinan</p></span><button type="button" id="taskDetailBtn" class="task-detail-btn" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>Detail Task</button></div><form class="form-grid" method="POST" action="{{ route('laporan.store') }}" data-store-action="{{ route('laporan.store') }}" data-mode="create" enctype="multipart/form-data" id="kirimLaporanForm">@csrf<div class="kirim-laporan-form-card"><div class="form-field" id="tujuanSatuanField" hidden><label for="tujuan_satuan_id">Tujuan Laporan</label><select id="tujuan_satuan_id" name="tujuan_satuan_id" required>@foreach($tujuan as $t)<option value="{{ $t->id }}" @selected($defaultTujuanId == $t->id)>{{ $t->nama }}</option>@endforeach</select></div><div class="form-field full" id="progresField" hidden><label for="progres">Progres (%)</label><input id="progres" type="number" name="progres" min="0" max="100" required placeholder="0-100"><span class="form-hint" id="progresHint"></span></div><div class="form-field" id="prioritasField" hidden><label for="prioritas">Prioritas</label><select id="prioritas" name="prioritas" required><option>Rendah</option><option selected>Sedang</option><option>Tinggi</option></select></div><div class="form-field" id="kategoriField" hidden><label for="proyek">Kategori</label><input id="proyek" name="proyek" maxlength="255" placeholder="Contoh: kegiatan, koordinasi, temuan"></div><div class="form-field" id="perihalField" hidden><label for="perihal">Perihal</label><input id="perihal" name="perihal" maxlength="255" required placeholder="Judul singkat laporan"></div><div class="form-field"><label for="deskripsi"><svg class="form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>Isi Laporan</label><textarea id="deskripsi" name="deskripsi" required placeholder="Jelaskan progres atau hasil pekerjaan untuk permintaan ini..."></textarea></div><div class="form-field"><label for="kendala"><svg class="form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>Kendala/Alasan (opsional)</label><textarea id="kendala" name="kendala" maxlength="5000" placeholder="Jelaskan kendala yang membuat progres tertunda, kalau ada..."></textarea></div><div class="form-field full"><label for="lampiran"><svg class="form-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>Lampiran <span style="font-weight:400;color:var(--text-muted);">(opsional)</span></label><div class="lampiran-dropzone" id="lampiranDropzone"><input id="lampiran" type="file" class="lampiran-dropzone-input" name="lampiran[]" data-file-picker-ready="1" multiple><div class="lampiran-dropzone-prompt" id="lampiranDropzonePrompt"><span class="lampiran-dropzone-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 17l4-4 4 4"></path><path d="M12 13v9"></path><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path></svg></span><span class="lampiran-dropzone-text"><span class="lampiran-dropzone-text-main">Tarik &amp; lepas file di sini, atau <span class="lampiran-dropzone-browse-btn">Pilih File</span></span><span class="lampiran-dropzone-text-sub">Maksimal 10 MB per file.</span></span></div></div><div class="lampiran-file-list" id="lampiranFileList"><div class="lampiran-file-list-empty" id="lampiranFileListEmpty">Belum ada file yang diupload</div></div></div></div><div class="form-field full" style="display:flex;flex-direction:row;justify-content:flex-end;gap:8px;margin-top:4px;"><button type="button" class="btn" id="kirimLaporanCancel">Tutup</button><button class="btn btn-primary" type="submit" id="kirimLaporanSubmitBtn">Kirim Laporan</button></div></form></div></div></div></div>
{{-- Sub-modal "Detail Task": dibuka dari tombol #taskDetailBtn di pojok kanan
     header modal Update Progres. Isi teksnya di-set applyTaskDetail() di
     permintaan-laporan-deadline.blade.php dari data-task-detail step task. --}}
<div class="report-modal" id="taskDetailModal" style="z-index:100300"><div class="report-modal-card"><div class="report-modal-head"><h3>Detail Task</h3></div><p class="task-detail-modal-sub">Instruksi rinci dari Pimpinan untuk task yang sedang kamu kerjakan.</p><div class="task-detail-modal-body" id="taskDetailModalBody">-</div><div class="modal-actions"><button type="button" class="btn" id="taskDetailModalClose">Tutup</button></div></div></div>@endif
@if($canSend)<div class="confirm-overlay" id="konfirmasiKirimOverlay"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="konfirmasiKirimTitle"><div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg></div><h3 id="konfirmasiKirimTitle">Kirim Laporan?</h3><p id="konfirmasiKirimBody">Pastikan data yang kamu isi sudah benar. Laporan yang sudah terkirim tidak dapat diedit lagi.</p><div class="confirm-actions"><button type="button" class="btn" id="konfirmasiKirimBatal">Batal</button><button type="button" class="btn btn-primary" id="konfirmasiKirimYa">Ya, Kirim</button></div></div></div>@endif
@if($isKasansi)<div class="report-modal" id="kirimKendalaModal"><div class="report-modal-card"><div class="report-modal-head"><h3>Kirim Kendala</h3><button type="button" class="report-modal-close" id="kirimKendalaClose" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div><p style="margin:-8px 0 16px;font-size:12px;color:var(--text-muted);line-height:1.5;">Sampaikan kendala {{ $satuan->nama }} langsung kepada Danpus.</p><form class="form-grid" method="POST" action="{{ route('laporan-kendala.store') }}" enctype="multipart/form-data" id="kirimKendalaForm">@csrf<div class="form-field full"><label>Tujuan</label><div style="display:flex"><div class="satuan-pill">DANPUS</div></div></div><div class="form-field"><label for="kendala_prioritas">Prioritas</label><select id="kendala_prioritas" name="prioritas" required><option>Rendah</option><option selected>Sedang</option><option>Tinggi</option></select></div><div class="form-field"><label for="kendala_perihal">Perihal</label><input id="kendala_perihal" name="perihal" maxlength="255" required placeholder="Judul singkat kendala"></div><div class="form-field full"><label for="kendala_kategori">Kategori <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-dim);font-size:10px">(opsional)</span></label><input id="kendala_kategori" name="kategori" maxlength="255" placeholder="Contoh: SDM, Sarana, Operasional, Anggaran"></div><div class="form-field full"><label for="kendala_deskripsi">Isi Kendala</label><textarea id="kendala_deskripsi" name="deskripsi" required placeholder="Jelaskan kendala yang ingin disampaikan ke Danpus..."></textarea></div><div class="form-field full"><label>Tembusan ke (opsional, maksimal 1)</label><div class="form-hint" style="margin-bottom:6px">Satuan lain hanya menerima info koordinasi, tidak ikut memutuskan laporan ini. Pilih maksimal 1 satuan.</div><div id="tembusanKeGroup" style="display:grid;grid-template-columns:1fr 1fr;gap:6px 14px">@foreach($satuanTembusanPilihan as $st)<label style="display:flex;align-items:center;gap:7px;font-size:12.5px;font-weight:500;cursor:pointer"><input type="checkbox" name="tembusan_ke[]" value="{{ $st->kode }}" class="tembusan-ke-checkbox" style="width:auto">{{ $st->nama_singkat }}</label>@endforeach</div></div><div class="form-field full"><label for="kendala_lampiran">Lampiran (wajib)</label><div class="lampiran-dropzone" id="kendalaLampiranDropzone"><input id="kendala_lampiran" type="file" class="lampiran-dropzone-input" name="lampiran[]" data-file-picker-ready="1" multiple><div class="lampiran-dropzone-prompt"><span class="lampiran-dropzone-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 17l4-4 4 4"></path><path d="M12 13v9"></path><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path></svg></span><span class="lampiran-dropzone-text"><span class="lampiran-dropzone-text-main">Tarik &amp; lepas file di sini, atau <span class="lampiran-dropzone-browse-btn">Pilih File</span></span><span class="lampiran-dropzone-text-sub">Bisa lebih dari 1 file, semua format, total maksimal 10 MB.</span></span></div></div><div class="lampiran-file-list" id="kendalaLampiranFileList"><div class="lampiran-file-list-empty" id="kendalaLampiranFileListEmpty">Belum ada file dipilih</div></div><span class="kirim-laporan-error" id="kendalaLampiranError" style="display:none"></span><span class="form-hint">Wajib dilampirkan sebagai bukti pendukung kendala. Bisa lebih dari 1 file (PDF, Excel, Word, gambar, dll), total ukuran maksimal 10 MB.</span></div><div class="form-field full" style="display:flex;flex-direction:row;justify-content:flex-end;gap:8px;margin-top:4px;"><button type="button" class="btn" id="kirimKendalaCancel">Batal</button><button class="btn btn-primary" type="submit" id="kirimKendalaSubmitBtn">Kirim Kendala</button></div></form></div></div>
@endif
@if($bisaKirimSurat)<div class="report-modal" id="kirimSuratModal"><div class="report-modal-card" style="width:min(860px,100%)"><div class="report-modal-head"><div style="display:flex;align-items:flex-start;gap:12px;min-width:0"><span class="surat-modal-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M12 12v6"></path><path d="M9 15h6"></path></svg></span><span style="min-width:0"><h3 style="margin:0 0 4px">Buat Surat Baru</h3><p style="margin:0;font-size:12px;color:var(--text-muted)">Dari {{ $satuan->nama }}</p></span></div><button type="button" class="report-modal-close" id="kirimSuratClose" aria-label="Tutup"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg></button></div><form method="POST" action="{{ route('laporan-surat.store') }}" enctype="multipart/form-data" id="kirimSuratForm">@csrf<div class="surat-form-grid"><div class="surat-form-col"><div class="surat-section-label">Informasi Surat</div><div class="form-field full"><label for="surat_tujuan_search">Tujuan</label><div class="surat-combobox" id="suratTujuanCombobox"><input type="hidden" id="surat_tujuan" name="tujuan_satuan_id"><input type="text" id="surat_tujuan_search" autocomplete="off" placeholder="Ketik nama atau satuan tujuan..."></div><script>window.__suratTujuanOptions=@json($satuanSuratTujuanPilihan->map(fn($st)=>['id'=>(string)$st->id,'name'=>$st->nama,'kode'=>$st->kode])->values());</script></div><div class="form-field full"><label for="surat_perihal">Perihal</label><input id="surat_perihal" name="perihal" maxlength="255" required autocomplete="off" placeholder="Judul singkat surat"></div><div class="form-field full"><label for="surat_kategori">Kategori</label><input id="surat_kategori" name="kategori" maxlength="255" required autocomplete="off" placeholder="Contoh: Undangan, Pemberitahuan, Koordinasi"></div><div class="form-field full"><label>Prioritas</label><div class="priority-toggle"><label class="priority-option prio-rendah"><input type="radio" name="prioritas" value="Rendah" required><span>Rendah</span></label><label class="priority-option prio-sedang"><input type="radio" name="prioritas" value="Sedang" required><span>Sedang</span></label><label class="priority-option prio-tinggi"><input type="radio" name="prioritas" value="Tinggi" required><span>Tinggi</span></label></div></div><div class="form-field full"><label for="surat_deskripsi">Isi Ringkasan Surat</label><textarea id="surat_deskripsi" name="deskripsi" required placeholder="Tuliskan isi surat yang ingin disampaikan..."></textarea></div></div><div class="surat-form-col surat-form-col-lampiran"><div class="surat-section-label">Lampiran</div><div class="surat-lampiran-zone" id="suratLampiranZone"><input id="surat_lampiran" type="file" class="surat-lampiran-zone-input" name="lampiran" data-file-picker-ready="1"><div class="surat-lampiran-zone-prompt"><span class="surat-lampiran-zone-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 17l4-4 4 4"></path><path d="M12 13v9"></path><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path></svg></span><span class="surat-lampiran-zone-text"><span class="surat-lampiran-zone-text-main">Klik untuk pilih file atau drag &amp; drop</span><span class="surat-lampiran-zone-text-sub">Maksimal 10 MB</span></span></div></div><div class="lampiran-file-list" id="suratLampiranFileList"><div class="lampiran-file-list-empty" id="suratLampiranFileListEmpty">Belum ada file dipilih</div></div><div class="surat-lampiran-preview" id="suratLampiranPreview" hidden></div><span class="kirim-laporan-error" id="suratLampiranError" style="display:none"></span></div></div><div class="modal-actions"><button type="button" class="btn" id="kirimSuratCancel">Batal</button><button class="btn btn-primary" type="submit" id="kirimSuratSubmitBtn">Buat Surat</button></div></form></div></div><div class="confirm-overlay" id="konfirmasiBuatSuratOverlay"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="konfirmasiBuatSuratTitle"><div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg></div><h3 id="konfirmasiBuatSuratTitle">Kirim Surat Baru?</h3><p>Pastikan data yang kamu isi sudah benar. Surat yang sudah dikirim tidak dapat diedit lagi.</p><div class="confirm-actions"><button type="button" class="btn" id="konfirmasiBuatSuratBatal">Batal</button><button type="button" class="btn btn-primary" id="konfirmasiBuatSuratYa">Ya, Kirim</button></div></div></div>
@include('siberad.dashboards.partials.surat-detail-modal')
<div class="confirm-overlay" id="konfirmasiKendalaOverlay"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="konfirmasiKendalaTitle"><div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg></div><h3 id="konfirmasiKendalaTitle">Kirim Laporan Kendala?</h3><p>Pastikan data yang kamu isi sudah benar. Laporan kendala yang sudah terkirim tidak dapat diedit lagi.</p><div class="confirm-actions"><button type="button" class="btn" id="konfirmasiKendalaBatal">Batal</button><button type="button" class="btn btn-primary" id="konfirmasiKendalaYa">Ya, Kirim</button></div></div></div>
<div class="confirm-overlay" id="konfirmasiTeruskanOverlay"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="konfirmasiTeruskanTitle"><div class="confirm-icon" style="background:rgba(34,197,94,.12);color:#16a34a"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M22 2 11 13"></path><path d="M22 2 15 22 11 13 2 9 22 2Z"></path></svg></div><h3 id="konfirmasiTeruskanTitle">Kirim ke Danpus?</h3><p id="konfirmasiTeruskanBody">Laporan ini akan langsung diteruskan ke Danpus. Tindakan ini tidak dapat dibatalkan.</p><div class="confirm-actions"><button type="button" class="btn" id="konfirmasiTeruskanBatal">Batal</button><button type="button" class="btn btn-primary" id="konfirmasiTeruskanYa">Ya, Kirim ke Danpus</button></div></div></div>
<form method="POST" id="formTeruskanKeDanpus" style="display:none"><input type="hidden" name="_token" id="formTeruskanToken"><input type="hidden" name="_method" value="PATCH"></form>
<div class="confirm-overlay" id="konfirmasiSuratOverlay"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="konfirmasiSuratTitle"><div class="confirm-icon" style="background:var(--success-dim);color:var(--success-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M20 6 9 17l-5-5"></path></svg></div><h3 id="konfirmasiSuratTitle">Konfirmasi Surat?</h3><p id="konfirmasiSuratBody">Konfirmasi surat ini? Pengirim akan mengetahui bahwa surat sudah diterima.</p><div class="confirm-actions"><button type="button" class="btn" id="konfirmasiSuratBatal">Batal</button><button type="button" class="btn" id="konfirmasiSuratYa">Ya, Konfirmasi</button></div></div></div>
<form method="POST" id="formKonfirmasiSurat" style="display:none"><input type="hidden" name="_token" id="formKonfirmasiSuratToken"><input type="hidden" name="_method" value="PATCH"></form>
<div class="confirm-overlay" id="lampiranKendalaWajibOverlay"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="lampiranKendalaWajibTitle"><div class="confirm-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path></svg></div><h3 id="lampiranKendalaWajibTitle">Lampiran Wajib Diisi</h3><p>Laporan kendala ke Danpus wajib disertai lampiran sebagai bukti pendukung. Silakan pilih file PDF terlebih dahulu sebelum mengirim.</p><div class="confirm-actions"><button type="button" class="btn btn-primary" id="lampiranKendalaWajibOk" style="flex:1">Mengerti</button></div></div></div>@endif
<div class="crop-modal" id="aturFotoOverlay"><div class="crop-modal-card"><div class="crop-modal-head"><h3>Atur Foto Profil</h3></div><div class="crop-stage" id="cropStage"><img id="cropImage" alt="Pratinjau foto profil" draggable="false"><div class="crop-mask"></div></div><div class="crop-zoom-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="6"></circle><path d="m21 21-4.35-4.35"></path></svg><input type="range" id="cropZoomRange" min="100" max="300" value="100" step="1" aria-label="Perbesar foto"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path><path d="M11 8v6"></path><path d="M8 11h6"></path></svg></div><p class="crop-modal-hint">Geser foto buat atur posisi, geser slider buat zoom.</p><div class="crop-modal-actions"><button type="button" class="btn" id="aturFotoBatal">Batal</button><button type="button" class="btn btn-primary" id="aturFotoSimpan">Ganti Foto</button></div></div></div>
<div class="confirm-overlay" id="hapusFotoOverlay"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="hapusFotoTitle"><div class="confirm-icon"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><path d="M4 7h16"></path><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"></path><path d="M18 7l-.8 12.1a1.8 1.8 0 0 1-1.8 1.7H8.6a1.8 1.8 0 0 1-1.8-1.7L6 7"></path></svg></div><h3 id="hapusFotoTitle">Hapus Foto Profil?</h3><p>Foto profil kamu akan dihapus dan kembali menampilkan inisial nama.</p><form id="formHapusFoto" method="POST" action="{{ route('profil-foto.destroy') }}">@csrf @method('DELETE')<div class="confirm-actions"><button type="button" class="btn" id="hapusFotoBatal">Batal</button><button type="submit" class="btn btn-ghost-red">Ya, Hapus</button></div></form></div></div>
<div class="confirm-overlay" id="kirimGantiPasswordOverlay"><div class="confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="kirimGantiPasswordTitle"><div class="confirm-icon" style="background:var(--gold-dim);color:var(--gold-bright)"><svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-width="1.9"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg></div><h3 id="kirimGantiPasswordTitle">Kirim Permintaan Ganti Password?</h3><p>Password baru akan aktif setelah disetujui Admin. Permintaan yang sudah terkirim tidak dapat diedit lagi.</p><div class="confirm-actions"><button type="button" class="btn" id="kirimGantiPasswordBatal">Batal</button><button type="button" class="btn btn-primary" id="kirimGantiPasswordYa">Ya, Kirim</button></div></div></div>
<div class="shell"><aside class="sidebar" id="sidebar"><div class="side-brand"><img src="{{ asset('images/logo-pussiberad.jpg') }}" alt="Lambang Pussiberad"><div class="logo">{{ $pengaturan->hero_judul_awal }}<span>{{ $pengaturan->hero_judul_aksen }}</span></div><button type="button" class="side-collapse-btn" id="sideCollapseBtn" aria-label="Ciutkan sidebar" title="Ciutkan sidebar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg></button></div><nav class="side-nav"><a href="#dashboard" class="side-link active" title="Dashboard"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/></svg></span><span class="side-text">Dashboard</span></a>@if($modulAktif['laporan'] ?? true)<div class="side-nav-group open" id="laporanGroup"><button type="button" class="side-nav-group-title" id="laporanGroupBtn" title="Pelaporan"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"/></svg></span><span class="side-text">Pelaporan</span> <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button><div class="side-subnav"><div><span class="side-subnav-label">Pelaporan</span>@if(! in_array(strtoupper($satuan->kode ?? ''), ['ADMIN','DANPUS','WADAN'], true))<a href="#permintaan-laporan" class="side-sub-link" title="Permintaan Laporan"><span class="sub-dot"></span>Permintaan Laporan</a>@endif<a href="#riwayat" class="side-sub-link" title="Arsip Laporan"><span class="sub-dot"></span>Arsip Laporan</a></div></div></div>@endif @if($modulAktif['surat'] ?? true)@if($bisaKirimSurat)<div class="side-nav-group" id="laporanSuratGroup"><button type="button" class="side-nav-group-title" id="laporanSuratGroupBtn" title="Surat"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg></span><span class="side-text">Surat</span> <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button><div class="side-subnav"><div><span class="side-subnav-label">Surat</span><a href="#kirim-surat" class="side-sub-link" title="Surat Keluar"><span class="sub-dot"></span>Surat Keluar</a><a href="#surat-masuk" class="side-sub-link" title="Surat Masuk"><span class="sub-dot"></span>Surat Masuk</a><a href="#arsip-surat" class="side-sub-link" title="Arsip Surat"><span class="sub-dot"></span>Arsip Surat</a></div></div></div>@else<div class="side-nav-group" id="laporanSuratGroup"><button type="button" class="side-nav-group-title" id="laporanSuratGroupBtn" title="Surat"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg></span><span class="side-text">Surat</span> <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button><div class="side-subnav"><div><span class="side-subnav-label">Surat</span><a href="#surat-masuk" class="side-sub-link" title="Surat Masuk"><span class="sub-dot"></span>Surat Masuk</a><a href="#arsip-surat" class="side-sub-link" title="Arsip Surat"><span class="sub-dot"></span>Arsip Surat</a></div></div></div>@endif @endif @if($modulAktif['kendala'] ?? true)@if($isPenerimaTembusan)<div class="side-nav-group" id="tembusanKendalaGroup"><button type="button" class="side-nav-group-title" id="tembusanKendalaGroupBtn" title="Kendala"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></span><span class="side-text">Kendala</span> <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button><div class="side-subnav"><div><span class="side-subnav-label">Kendala</span><a href="#laporan-tembusan" class="side-sub-link" title="Tembusan Kendala"><span class="sub-dot"></span>Tembusan Kendala</a><a href="#arsip-tembusan-kendala" class="side-sub-link" title="Arsip Kendala"><span class="sub-dot"></span>Arsip Kendala</a></div></div></div>@endif @if($isKasansi)<div class="side-nav-group" id="laporanKendalaGroup"><button type="button" class="side-nav-group-title" id="laporanKendalaGroupBtn" title="Kendala"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></span><span class="side-text">Kendala</span> <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button><div class="side-subnav"><div><span class="side-subnav-label">Kendala</span><a href="#kirim-laporan-kendala" class="side-sub-link" title="Kirim Kendala"><span class="sub-dot"></span>Kirim Kendala</a><a href="#arsip-kendala-kasansi" class="side-sub-link" title="Arsip Kendala"><span class="sub-dot"></span>Arsip Kendala</a></div></div></div>@endif @endif @if($mode === 'duktek' && ($modulAktif['monitoring'] ?? true))<a href="#monitoring" class="side-link" title="Monitoring 3 Satlak"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span><span class="side-text">Monitoring 3 Satlak</span></a>@endif @if($isKasansi)<div class="side-nav-group" id="lainnyaGroup"><button type="button" class="side-nav-group-title" id="lainnyaGroupBtn" title="Lainnya"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg></span><span class="side-text">Lainnya</span> <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 15l6-6 6 6"/></svg></button><div class="side-subnav"><div><span class="side-subnav-label">Lainnya</span><a href="#lainnya-notifikasi" class="side-sub-link" title="Notifikasi"><span class="sub-dot"></span>Notifikasi</a><a href="#lainnya-struktur-org" class="side-sub-link" title="Struktur Organisasi"><span class="sub-dot"></span>Struktur Organisasi</a></div></div></div>@endif</nav><div class="side-foot"><form class="logout logout-form" method="POST" action="{{ route('logout') }}">@csrf<button type="submit" title="Keluar"><span class="side-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span><span class="side-text">Keluar</span></button></form></div></aside>
<script>try{if(localStorage.getItem('siberad-sidebar-collapsed')==='1'){document.getElementById('sidebar').classList.add('collapsed');document.querySelectorAll('.side-nav-group.open').forEach(function(g){g.classList.remove('open')});}}catch(e){}</script>
<main class="main"><div class="topbar"><div style="display:flex;align-items:center;gap:12px"><button class="menu-btn" id="menuBtn" type="button">☰</button></div><div class="topbar-actions"><button type="button" class="btn-icon-toggle" id="themeToggleBtn" aria-pressed="false" aria-label="Ganti tema"><svg class="icon-moon" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path></svg><svg class="icon-sun" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.2"></circle><path d="M12 2.5v2.4M12 19.1v2.4M4.4 4.4l1.7 1.7M17.9 17.9l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.4 19.6l1.7-1.7M17.9 6.1l1.7-1.7"></path></svg></button><div class="profile-menu" id="profileMenu"><button type="button" class="profile-menu-btn" id="profileMenuBtn"><span class="profile-initial" id="profileInitial" style="display:{{ $user->foto_path ? 'none' : '' }};">{{ strtoupper(mb_substr($user->name ?? 'U',0,1)) }}</span><img class="profile-photo" id="profilePhotoBtn" alt="Foto profil {{ $user->name }}" @if($user->foto_path) src="{{ asset('storage/'.$user->foto_path) }}" style="display:block;" @endif></button><div class="profile-dropdown" id="profileDropdown"><div class="profile-dropdown-head"><div class="profile-dropdown-avatar"><span class="profile-initial" id="profileInitialDropdown" style="display:{{ $user->foto_path ? 'none' : '' }};">{{ strtoupper(mb_substr($user->name ?? 'U',0,1)) }}</span><img class="profile-photo" id="profilePhotoDropdown" alt="Foto profil {{ $user->name }}" @if($user->foto_path) src="{{ asset('storage/'.$user->foto_path) }}" style="display:block;" @endif></div><div><div class="profile-dropdown-name">{{ $user->name }}</div><div class="profile-dropdown-role">{{ $user->jabatan ?? 'Pengguna' }}</div></div></div><button type="button" class="profile-dropdown-item" onclick="openProfileModal('profileSettingsView')">Pengaturan Akun</button><button type="button" class="profile-dropdown-item" onclick="openProfileModal('profileHelpView')">Bantuan &amp; Panduan</button><div class="profile-dropdown-divider"></div><form class="logout-form" method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="profile-dropdown-item danger">Keluar</button></form></div></div></div></div>
<div class="content">@include('siberad.dashboards.partials.pengumuman-banner')
@if(session('status'))<script>document.addEventListener('DOMContentLoaded',function(){window.siberadShowToast?window.siberadShowToast('success',{!! json_encode(session('status')) !!}):null});</script>@endif
@if(session('error'))<script>document.addEventListener('DOMContentLoaded',function(){window.siberadShowToast?window.siberadShowToast('error',{!! json_encode(session('error')) !!}):null});</script>@endif
@if($errors->any())<script>document.addEventListener('DOMContentLoaded',function(){window.siberadShowToast?window.siberadShowToast('error',{!! json_encode($errors->first()) !!}):null});</script>@endif
<section id="dashboard" class="tab-panel active"><div class="dash-hero"><div><div class="dash-hero-eyebrow">{{ $pengaturan->namaSistem() }} // {{ $satuan->kode }}</div><h2>{{ $satuan->nama }}</h2><p>{{ now()->translatedFormat('l, d F Y') }}</p></div></div><div id="satuanKpisWrap">@include('siberad.dashboards.partials.pimpinan-kpi-cards', ['pimpTotalPelaporan' => $satuanTotalPelaporan, 'laporanPimpinanSatlak' => $laporanTerkirim, 'suratMasuk' => $suratMasuk, 'suratTerkirim' => $suratTerkirim, 'suratArsip' => $suratArsip, 'kendalaMasuk' => $kendalaKasansiKpiAktif, 'kendalaArsip' => $kendalaKasansiKpiArsip])</div><div class="report-layout"><div class="chart-card compact status-dist-card"><div class="pimp-card-head"><div class="pimp-card-head-main"><span class="pimp-card-ico" style="background:color-mix(in srgb,#22c55e 15%,transparent);color:#22c55e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"></path></svg></span><div><h3>Distribusi Status Laporan</h3><p>Proporsi status laporan yang sudah dikirim satuan ini.</p></div></div></div><div class="status-donut-wrap"><canvas id="statusDonut"></canvas><div class="status-donut-center"><span>Total Laporan</span><strong id="satuanDonutTotal">{{ $satuanTotalStatus }}</strong></div></div><div id="satuanStatusBdWrap">@include('siberad.dashboards.partials.pimpinan-status-distribusi-list', ['pimpStatusDist' => $satuanStatusDist])</div></div><div class="satuan-terbaru-row"><div class="chart-card"><div class="pimp-card-head"><div class="pimp-card-head-main"><span class="pimp-card-ico" style="background:color-mix(in srgb,#3b82f6 15%,transparent);color:#3b82f6"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg></span><div><h3>Surat Terbaru</h3><p>5 surat terbaru yang tercatat.</p></div></div><a href="#surat-masuk" class="btn btn-ghost btn-sm">Lihat Semua</a></div><div class="clean-table-wrap"><table class="clean-table pimp-mini-table"><thead><tr><th>Tanggal</th><th>Jenis Surat</th><th>Perihal</th><th>Pengirim</th><th>Status</th></tr></thead><tbody id="satuanSuratTerbaruBody">@include('siberad.dashboards.partials.pimpinan-surat-terbaru-rows', ['pimpSuratTerbaru' => $satuanSuratTerbaru, 'satuan' => $satuan])</tbody></table></div></div><div class="chart-card"><div class="pimp-card-head"><div class="pimp-card-head-main"><span class="pimp-card-ico" style="background:color-mix(in srgb,#f59e0b 15%,transparent);color:#f59e0b"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></span><div><h3>Kendala Kasansi Terbaru</h3><p>5 kendala kasansi terbaru yang dilaporkan.</p></div></div>@if($isKasansi || $isPenerimaTembusan)<a href="{{ $isKasansi ? '#kirim-laporan-kendala' : '#laporan-tembusan' }}" class="btn btn-ghost btn-sm">Lihat Semua</a>@endif</div><div class="pimp-activity-list" id="satuanKendalaTerbaruList">@include('siberad.dashboards.partials.pimpinan-kendala-terbaru-list', ['pimpKendalaTerbaru' => $satuanKendalaTerbaru])</div></div></div></div></section>
<section id="riwayat" class="tab-panel deadline-sender-section"><div class="report-card"><div class="panel-head"><div><h2>Arsip Laporan</h2><p>Laporan yang sudah diputuskan Pimpinan (disetujui/ditolak) atau diarsipkan (Terlambat/Dibatalkan) untuk {{ $satuan->nama }}.</p></div></div></div><div class="deadline-sender-list">@forelse($riwayatLaporan as $permintaan)@include('siberad.dashboards.partials.permintaan-laporan-item', ['permintaan' => $permintaan])@empty<div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada arsip laporan</div><div class="empty-state-sub">Laporan yang sudah diputuskan atau diarsipkan Pimpinan akan muncul di sini.</div></div>@endforelse</div></section>@if(! in_array(strtoupper($satuan->kode ?? ''), ['ADMIN','DANPUS','WADAN'], true))<section id="permintaan-laporan" class="tab-panel deadline-sender-section"><div class="report-card"><div class="panel-head"><div><h2>Permintaan Laporan</h2><p>Daftar tugas pelaporan dari Danpus/Wadan beserta batas waktu pengiriman.</p></div></div></div><div class="deadline-sender-list">@forelse($permintaanLaporan as $permintaan)@include('siberad.dashboards.partials.permintaan-laporan-item', ['permintaan' => $permintaan])@empty<div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada permintaan laporan</div><div class="empty-state-sub">Tugas pelaporan dari Danpus/Wadan akan muncul di sini.</div></div>@endforelse</div></section>@endif
@if(($modulAktif['kendala'] ?? true) && $isKasansi)<section id="kirim-laporan-kendala" class="tab-panel deadline-sender-section"><div class="report-card"><div class="panel-head"><div><h2>Kirim Kendala</h2><p>Sampaikan kendala {{ $satuan->nama }} langsung kepada Danpus, lalu pantau status tindak lanjutnya di sini.</p></div><button type="button" class="btn btn-primary" id="kirimKendalaOpen">Kirim Kendala</button></div></div><div class="kcard-grid" id="kcard-grid-terkirim">@forelse($kendalaTerkirim as $k)@include('siberad.dashboards.partials.kendala-terkirim-row', ['k' => $k])@empty<div class="kcard-empty"><svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><div class="kcard-empty-title">Belum ada laporan kendala</div><div class="kcard-empty-sub">Kendala yang kamu kirim ke Danpus akan muncul di sini.</div></div>@endforelse</div></section>@endif
@if(($modulAktif['kendala'] ?? true) && $isKasansi)<section id="arsip-kendala-kasansi" class="tab-panel deadline-sender-section"><div class="report-card"><div class="panel-head"><div><h2>Arsip Kendala</h2><p>Laporan kendala {{ $satuan->nama }} yang sudah dikonfirmasi selesai ditindaklanjuti oleh Danpus.</p></div></div></div><div class="kcard-grid" id="kcard-grid-arsip-kasansi">@forelse($kendalaArsip as $k)@include('siberad.dashboards.partials.kendala-terkirim-row', ['k' => $k])@empty<div class="kcard-empty"><svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><path d="M23 3H1v5h22V3z"/><path d="M10 12h4"/></svg><div class="kcard-empty-title">Belum ada kendala yang diarsipkan</div><div class="kcard-empty-sub">Kendala yang sudah dikonfirmasi Danpus akan otomatis pindah ke sini.</div></div>@endforelse</div></section>@endif
@if(($modulAktif['surat'] ?? true) && $bisaKirimSurat)<section id="kirim-surat" class="tab-panel"><div class="panel"><div class="panel-head"><div><h2>Surat Keluar</h2><p>Surat yang sudah dikirim dan <strong>belum dikonfirmasi</strong> oleh penerima. Setelah dikonfirmasi, surat otomatis pindah ke Arsip Surat.</p></div><button type="button" class="btn btn-primary" id="kirimSuratOpen">Buat Surat</button></div></div><div class="surat-file-grid" id="suratTerkirimGrid">@forelse($suratTerkirim as $s)@include('siberad.dashboards.partials.surat-terkirim-row', ['s' => $s])@empty<div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Tidak ada surat yang menunggu konfirmasi</div><div class="empty-state-sub">Semua surat yang terkirim sudah dikonfirmasi penerima dan masuk ke Arsip Surat.</div></div>@endforelse</div></section>@endif
@if($modulAktif['surat'] ?? true)<section id="arsip-surat" class="tab-panel"><div class="panel"><div class="panel-head"><div><h2>Arsip Surat</h2><p>Riwayat surat yang sudah <strong>dikonfirmasi</strong>, terkirim maupun masuk.</p></div></div></div><div class="surat-file-grid" id="suratArsipGrid">@forelse($suratArsip as $s)@include('siberad.dashboards.partials.surat-arsip-row', ['s' => $s])@empty<div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada surat yang diarsipkan</div><div class="empty-state-sub">Surat pindah ke sini setelah dikonfirmasi.</div></div>@endforelse</div></section>
<section id="surat-masuk" class="tab-panel"><div class="panel"><div class="panel-head"><div><h2>Surat Masuk</h2><p>Surat masuk yang <strong>menunggu konfirmasi</strong>.</p></div></div></div><div class="surat-file-grid" id="suratMasukGrid">@forelse($suratMasuk as $s)@include('siberad.dashboards.partials.surat-masuk-row', ['s' => $s])@empty<div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada surat masuk</div><div class="empty-state-sub">Surat yang ditujukan ke {{ $satuan->nama }} akan muncul di sini.</div></div>@endforelse</div></section>@endif
@if(($modulAktif['kendala'] ?? true) && $isPenerimaTembusan)<section id="laporan-tembusan" class="tab-panel deadline-sender-section"><div class="report-card"><div class="panel-head"><div><h2>Tembusan Kendala</h2><p>Info koordinasi dari Kasansi (Sansidam) yang menembuskan laporan kendalanya ke {{ $satuan->nama }} dan masih menunggu balasan Anda. Ini bukan laporan yang perlu Anda putuskan -- keputusan tetap ada di Danpus.</p></div></div></div><div class="kcard-grid" id="kcard-grid-tembusan">@forelse($tembusanMasuk as $t)@include('siberad.dashboards.partials.laporan-kendala-tembusan-card', ['t' => $t])@empty<div class="kcard-empty"><svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="kcard-empty-title">Belum ada tembusan kendala</div><div class="kcard-empty-sub">Tembusan dari Kasansi akan muncul di sini.</div></div>@endforelse</div></section><section id="arsip-tembusan-kendala" class="tab-panel deadline-sender-section"><div class="report-card"><div class="panel-head"><div><h2>Arsip Kendala</h2><p>Tembusan kendala dari Kasansi yang sudah Anda beri balasan/feedback.</p></div></div></div><div class="kcard-grid" id="kcard-grid-tembusan-arsip">@forelse($tembusanArsip as $t)@include('siberad.dashboards.partials.laporan-kendala-tembusan-card', ['t' => $t])@empty<div class="kcard-empty"><svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="var(--text-dim)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"></path><path d="M23 3H1v5h22V3z"></path><path d="M10 12h4"></path></svg><div class="kcard-empty-title">Belum ada kendala yang diarsipkan</div><div class="kcard-empty-sub">Tembusan yang sudah Anda beri balasan akan otomatis pindah ke sini.</div></div>@endforelse</div></section>@endif
@if($mode === 'duktek' && ($modulAktif['monitoring'] ?? true))<section id="monitoring" class="tab-panel"><div class="section-head panel"><h2>Monitoring 3 Satlak</h2><p>Duktek dapat melihat aktivitas laporan dari Penangkalan, Siber Sosial, dan Penindakan, termasuk tujuan laporan, isi aktivitas, status, serta lampiran yang tersedia.</p></div><div class="monitor-grid">@foreach($monitoringSatlak as $m)<div class="monitor-card"><div class="name">{{ $m['nama'] }}</div><div class="num">{{ $m['total'] }}</div><div class="sub">Laporan dalam sistem</div></div>@endforeach</div><div class="panel" style="margin-top:16px"><div class="tbl-wrap"><table class="dtbl"><thead><tr><th>Satlak</th><th>Perihal</th><th>Prioritas</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead><tbody>@forelse($laporanSatlak as $l)@include('siberad.dashboards.partials.monitor-satlak-row', ['l' => $l])@empty<tr><td colspan="6"><div class="empty-state"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="var(--text-dim)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"></rect><path d="M9 4h6"></path><path d="M9 10h6"></path><path d="M9 14h6"></path><path d="M9 18h3"></path></svg><div class="empty-state-title">Belum ada laporan dari 3 Satlak</div><div class="empty-state-sub">Aktivitas Penangkalan, Siber Sosial, dan Penindakan akan muncul di sini.</div></div></td></tr>@endforelse</tbody></table></div></div></section>@endif
@if($isKasansi)
@include('siberad.dashboards.partials.lainnya-kasansi')
@endif
</div></main></div>
<script>
(function(){const btn=document.getElementById('profileMenuBtn'),drop=document.getElementById('profileDropdown');if(btn&&drop&&!btn.dataset.uiBound){btn.dataset.uiBound='1';btn.addEventListener('click',e=>{e.stopPropagation();drop.classList.toggle('open')});}document.addEventListener('click',e=>{if(drop&&!drop.parentElement.contains(e.target))drop.classList.remove('open')});window.openProfileModal=function(id){document.querySelectorAll('#profileModalOverlay .profile-dropdown-view').forEach(v=>v.style.display=v.id===id?'block':'none');document.getElementById('profileModalOverlay').classList.add('open')};document.getElementById('profileModalCloseBtn')?.addEventListener('click',()=>document.getElementById('profileModalOverlay').classList.remove('open'));document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('profileModalOverlay')?.classList.contains('open'))document.getElementById('profileModalOverlay').classList.remove('open')});document.getElementById('sidebar');(function(){const themeBtn=document.getElementById('themeToggleBtn');if(!themeBtn||themeBtn.dataset.uiBound)return;themeBtn.dataset.uiBound='1';const THEME_KEY='siberad-theme';function applyTheme(theme){if(theme==='light')document.documentElement.setAttribute('data-theme','light');else document.documentElement.removeAttribute('data-theme');themeBtn.setAttribute('aria-pressed',theme==='light'?'true':'false');}let savedTheme='dark';try{savedTheme=localStorage.getItem(THEME_KEY)||'dark';}catch(e){}applyTheme(savedTheme);themeBtn.addEventListener('click',()=>{const current=document.documentElement.getAttribute('data-theme')==='light'?'light':'dark';const next=current==='light'?'dark':'light';try{localStorage.setItem(THEME_KEY,next);}catch(e){}applyTheme(next);});})();const sidebar=document.getElementById('sidebar');const ROLE_GROUP_STATE_KEY='siberad-role-group-';function positionGroupFlyout(g){const subnav=g.querySelector('.side-subnav');const gbtn=g.querySelector('.side-nav-group-title');if(!subnav||!gbtn)return;if(window.innerWidth<=900||!sidebar?.classList.contains('collapsed')||!g.classList.contains('open')){subnav.style.top='';subnav.style.left='';return;}const r=gbtn.getBoundingClientRect();subnav.style.top=r.top+'px';subnav.style.left=(r.right+8)+'px';}window.siberadRepositionSubnavFlyouts=function(){document.querySelectorAll('.side-nav-group').forEach(positionGroupFlyout);};window.addEventListener('resize',()=>window.siberadRepositionSubnavFlyouts());function restoreRoleGroupState(){document.querySelectorAll('.side-nav-group').forEach(g=>{let saved=null;try{saved=sessionStorage.getItem(ROLE_GROUP_STATE_KEY+g.id)}catch(e){}if(saved==='closed'||sidebar?.classList.contains('collapsed'))g.classList.remove('open');else if(saved==='open')g.classList.add('open');positionGroupFlyout(g);});}window.siberadRestoreGroupState=restoreRoleGroupState;restoreRoleGroupState();document.querySelectorAll('.side-nav-group').forEach(g=>{const gbtn=g.querySelector('.side-nav-group-title');gbtn?.addEventListener('click',e=>{e.stopPropagation();const willOpen=!g.classList.contains('open');if(willOpen&&sidebar?.classList.contains('collapsed')){document.querySelectorAll('.side-nav-group.open').forEach(other=>{if(other===g)return;other.classList.remove('open');try{sessionStorage.setItem(ROLE_GROUP_STATE_KEY+other.id,'closed')}catch(err){}positionGroupFlyout(other);});}g.classList.toggle('open');try{sessionStorage.setItem(ROLE_GROUP_STATE_KEY+g.id,g.classList.contains('open')?'open':'closed')}catch(e){}positionGroupFlyout(g);});});document.addEventListener('click',e=>{if(!sidebar?.classList.contains('collapsed'))return;if(e.target.closest('#sideCollapseBtn'))return;document.querySelectorAll('.side-nav-group.open').forEach(g=>{if(g.contains(e.target))return;g.classList.remove('open');try{sessionStorage.setItem(ROLE_GROUP_STATE_KEY+g.id,'closed')}catch(err){}positionGroupFlyout(g);});});document.addEventListener('keydown',e=>{if(e.key!=='Escape'||!sidebar?.classList.contains('collapsed'))return;document.querySelectorAll('.side-nav-group.open').forEach(g=>{g.classList.remove('open');try{sessionStorage.setItem(ROLE_GROUP_STATE_KEY+g.id,'closed')}catch(err){}positionGroupFlyout(g);});});document.querySelectorAll('.side-nav-group .side-sub-link').forEach(a=>a.addEventListener('click',()=>{if(!sidebar?.classList.contains('collapsed'))return;const g=a.closest('.side-nav-group');if(!g)return;g.classList.remove('open');try{sessionStorage.setItem(ROLE_GROUP_STATE_KEY+g.id,'closed')}catch(err){}positionGroupFlyout(g);}));const ROLE_ACTIVE_TAB_KEY='siberad-role-active-tab';function showRoleSection(id,link,skipSave){document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));const el=document.getElementById(id);if(!el)return;el.classList.add('active');document.querySelectorAll('.side-link,.side-sub-link').forEach(x=>x.classList.remove('active'));if(link)link.classList.add('active');document.querySelectorAll('.side-nav-group').forEach(g=>g.classList.remove('has-active-child'));const activeGroup=link?.closest('.side-nav-group');if(activeGroup){activeGroup.classList.add('has-active-child');if(activeGroup.id)try{sessionStorage.setItem(ROLE_GROUP_STATE_KEY+activeGroup.id,'open')}catch(e){}}document.getElementById('sidebar')?.classList.remove('open');if(!skipSave){try{sessionStorage.setItem(ROLE_ACTIVE_TAB_KEY,id)}catch(e){}}}document.querySelectorAll('.side-link[href^="#"],.side-sub-link[href^="#"],.card-link[href^="#"],.btn.btn-ghost[href^="#"]').forEach(a=>a.addEventListener('click',e=>{const id=a.getAttribute('href').slice(1);if(!document.getElementById(id))return;e.preventDefault();showRoleSection(id,a)}));(function(){let savedId=null;try{savedId=sessionStorage.getItem(ROLE_ACTIVE_TAB_KEY)}catch(e){}if(!savedId)return;function attempt(tries){if(!document.getElementById(savedId)){if(tries<20){setTimeout(()=>attempt(tries+1),50);}else{try{sessionStorage.removeItem(ROLE_ACTIVE_TAB_KEY)}catch(e){}}return;}const link=document.querySelector('.side-link[href="#'+savedId+'"],.side-sub-link[href="#'+savedId+'"]');showRoleSection(savedId,link,true);const group=link?.closest('.side-nav-group');if(group){group.classList.add('open');if(group.id)try{sessionStorage.setItem(ROLE_GROUP_STATE_KEY+group.id,'open')}catch(e){}positionGroupFlyout(group);}}attempt(0);})();window.siberadCharts=window.siberadCharts||[];
})();
window.renderLampiranPreview=function(list,container){var IMG_EXT=['jpg','jpeg','png','gif','webp','bmp','svg'];function getExt(x){var nama=x.nama||'Lampiran';var m=nama.match(/\.([a-z0-9]+)$/i);if(m)return m[1].toLowerCase();try{var p=new URL(x.url,window.location.href).pathname;var m2=p.match(/\.([a-z0-9]+)$/i);return m2?m2[1].toLowerCase():''}catch(e){return ''}}function renderRow(x,ext){var nama=x.nama||'Lampiran';var item=document.createElement('div');item.className='lampiran-preview-file';item.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg><div class="lampiran-preview-file-info"><span class="lampiran-preview-file-name"></span><span class="lampiran-preview-file-hint"></span></div>';item.querySelector('.lampiran-preview-file-name').textContent=nama;item.querySelector('.lampiran-preview-file-hint').textContent=IMG_EXT.indexOf(ext)!==-1?'Gambar':(ext==='pdf'?'Dokumen PDF':'Format ini tidak bisa ditampilkan langsung');var linkRow=document.createElement('a');linkRow.href=x.url;linkRow.setAttribute('download',nama);linkRow.textContent='Unduh File';item.appendChild(linkRow);container.appendChild(item)}list.forEach(function(x){renderRow(x,getExt(x))})};window.openReportDetail=function(button){const modal=document.getElementById('reportDetailModal');document.getElementById('detailPengirim').textContent=button.dataset.pengirim||'-';document.getElementById('detailTujuan').textContent=button.dataset.tujuan||'-';document.getElementById('detailPerihal').textContent=button.dataset.perihal||'-';document.getElementById('detailPrioritas').textContent=button.dataset.prioritas||'-';document.getElementById('detailProgres').textContent=button.dataset.progres?button.dataset.progres+'%':'-';document.getElementById('detailProgresWrap').style.display=button.dataset.progres?'block':'none';document.getElementById('detailProyek').textContent=button.dataset.proyek||'-';document.getElementById('detailTanggal').textContent=button.dataset.tanggal||'-';document.getElementById('detailDeskripsiLabel').textContent=button.dataset.deskripsiLabel||'Isi Laporan';document.getElementById('detailDeskripsi').textContent=button.dataset.deskripsi||'-';const kendala=button.dataset.kendala||'';const kendalaWrap=document.getElementById('detailKendalaWrap');if(kendala){document.getElementById('detailKendala').textContent=kendala;kendalaWrap.style.display='block'}else{kendalaWrap.style.display='none'}const wrap=document.getElementById('detailLampiranWrap');const linkBox=document.getElementById('detailLampiran');let daftarLampiran=[];try{daftarLampiran=button.dataset.lampiran?JSON.parse(button.dataset.lampiran):[]}catch(e){daftarLampiran=[]}linkBox.innerHTML='';linkBox.className='detail-value';if(daftarLampiran.length){if(button.dataset.kendalaReport==='1'){linkBox.classList.add('lampiran-preview-list');window.renderLampiranPreview(daftarLampiran,linkBox)}else{daftarLampiran.forEach(function(x,i){const a=document.createElement('a');a.href=x.url;a.target='_blank';a.rel='noopener';a.className='lampiran-btn';a.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg><span>'+(daftarLampiran.length>1?(x.nama||'Lihat Lampiran '+(i+1)):'Lihat Lampiran')+'</span>';linkBox.appendChild(a)})}wrap.style.display='block'}else{wrap.style.display='none'}const tembusanWrap=document.getElementById('detailTembusanWrap');const tembusanListBox=document.getElementById('detailTembusanList');const balasanKolom=document.getElementById('detailBalasanKolom');const balasanListBox=document.getElementById('detailBalasanList');let daftarTembusan=[];try{daftarTembusan=button.dataset.tembusanBalasan?JSON.parse(button.dataset.tembusanBalasan):[]}catch(e){daftarTembusan=[]}tembusanListBox.innerHTML='';balasanListBox.innerHTML='';let adaBalasan=false;if(daftarTembusan.length){daftarTembusan.forEach(function(t){const rowT=document.createElement('div');rowT.style.cssText='margin-bottom:6px;font-size:12.5px;line-height:1.5';rowT.textContent=(t.satuan||'-');tembusanListBox.appendChild(rowT);const rowB=document.createElement('div');rowB.style.cssText='margin-bottom:6px;font-size:12.5px;line-height:1.5';if(t.feedback){rowB.textContent=t.feedback;adaBalasan=true}else{rowB.style.color='var(--text-dim)';rowB.style.fontStyle='italic';rowB.textContent='Menunggu balasan…'}balasanListBox.appendChild(rowB)});tembusanWrap.style.display='block';balasanKolom.style.display='block'}else{tembusanWrap.style.display='none';balasanKolom.style.display='none'}const balasanTembusanWrap=document.getElementById('detailBalasanTembusanWrap');const balasanTembusanBox=document.getElementById('detailBalasanTembusanValue');if(button.dataset.tembusanFeedback==='1'){balasanTembusanBox.innerHTML='';const existingFeedback=button.dataset.feedbackExisting||'';if(existingFeedback){const val=document.createElement('div');val.style.cssText='font-size:13px;line-height:1.6;white-space:pre-wrap';val.textContent=existingFeedback;balasanTembusanBox.appendChild(val)}else{const bform=document.createElement('form');bform.method='POST';bform.action=button.dataset.feedbackAction||'';bform.style.cssText='display:flex;flex-direction:column;gap:7px';const btoken=document.createElement('input');btoken.type='hidden';btoken.name='_token';btoken.value=button.dataset.csrf||'';bform.appendChild(btoken);const bmethod=document.createElement('input');bmethod.type='hidden';bmethod.name='_method';bmethod.value='PATCH';bform.appendChild(bmethod);const btextarea=document.createElement('textarea');btextarea.name='feedback';btextarea.required=true;btextarea.maxLength=5000;btextarea.placeholder='Tulis catatan untuk Kasansi...';btextarea.style.cssText='width:100%;min-height:70px;box-sizing:border-box;resize:vertical;padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:var(--panel);color:var(--text);font:inherit;font-size:12px';bform.appendChild(btextarea);const bsubmit=document.createElement('button');bsubmit.type='submit';bsubmit.className='detail-btn';bsubmit.style.cssText='align-self:flex-end;font-size:11px';bsubmit.textContent='Kirim Balasan';bform.appendChild(bsubmit);balasanTembusanBox.appendChild(bform)}balasanTembusanWrap.style.display='block'}else{balasanTembusanWrap.style.display='none'}const actionsBox=document.getElementById('detailActions');actionsBox.innerHTML='';if(button.dataset.suratMasuk==='1'){const note=document.createElement('span');note.style.cssText='font-size:11px;color:var(--text-muted);margin-right:auto';note.textContent=button.dataset.readonlyText||'-';actionsBox.appendChild(note);if(button.dataset.konfirmasiAction){const kbtn=document.createElement('button');kbtn.type='button';kbtn.className='detail-btn';kbtn.style.cssText='background:var(--clr-accent,#1a7a4a);color:#fff;border-color:var(--clr-accent,#1a7a4a)';kbtn.textContent='Konfirmasi';kbtn.onclick=function(){if(typeof window.bukaKonfirmasiSurat==='function')window.bukaKonfirmasiSurat(button.dataset.konfirmasiAction,button.dataset.konfirmasiToken,button.dataset.konfirmasiPengirim)};actionsBox.appendChild(kbtn)}}else if(button.dataset.kendalaReport==='1'&&button.dataset.tembusanFeedback!=='1'){}else if(button.dataset.tembusanFeedback==='1'){}else if(button.dataset.readonly==='1'){const note=document.createElement('span');note.style.cssText='font-size:11px;color:var(--text-muted);margin-right:auto';note.textContent=button.dataset.readonlyText||'Detail ini hanya untuk melihat, tidak ada tindakan lebih lanjut.';actionsBox.appendChild(note)}else{const note=document.createElement('span');note.style.cssText='font-size:11px;color:var(--text-muted);margin-right:auto';note.textContent='Gunakan tombol aksi pada tabel untuk menindaklanjuti laporan.';actionsBox.appendChild(note)}modal.classList.add('open')};document.getElementById('reportDetailClose')?.addEventListener('click',()=>document.getElementById('reportDetailModal').classList.remove('open'));document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('reportDetailModal')?.classList.contains('open'))document.getElementById('reportDetailModal').classList.remove('open')});
document.getElementById('kirimLaporanOpen')?.addEventListener('click',()=>document.getElementById('kirimLaporanModal')?.classList.add('open'));document.getElementById('kirimLaporanClose')?.addEventListener('click',()=>document.getElementById('kirimLaporanModal')?.classList.remove('open'));document.getElementById('kirimLaporanCancel')?.addEventListener('click',()=>document.getElementById('kirimLaporanModal')?.classList.remove('open'));document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('kirimLaporanModal')?.classList.contains('open'))document.getElementById('kirimLaporanModal').classList.remove('open')});
document.getElementById('kirimKendalaOpen')?.addEventListener('click',()=>document.getElementById('kirimKendalaModal')?.classList.add('open'));document.getElementById('kirimKendalaClose')?.addEventListener('click',()=>document.getElementById('kirimKendalaModal')?.classList.remove('open'));document.getElementById('kirimKendalaCancel')?.addEventListener('click',()=>document.getElementById('kirimKendalaModal')?.classList.remove('open'));document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('kirimKendalaModal')?.classList.contains('open'))document.getElementById('kirimKendalaModal').classList.remove('open')});
document.getElementById('kirimSuratOpen')?.addEventListener('click',()=>{const m=document.getElementById('kirimSuratModal');if(!m)return;void m.offsetHeight;m.classList.add('open');});document.getElementById('kirimSuratClose')?.addEventListener('click',()=>document.getElementById('kirimSuratModal')?.classList.remove('open'));document.getElementById('kirimSuratCancel')?.addEventListener('click',()=>document.getElementById('kirimSuratModal')?.classList.remove('open'));document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('kirimSuratModal')?.classList.contains('open'))document.getElementById('kirimSuratModal').classList.remove('open')});(function(){const group=document.getElementById('tembusanKeGroup');if(!group)return;const boxes=group.querySelectorAll('.tembusan-ke-checkbox');const MAKS_TEMBUSAN=1;function sync(){const checked=Array.from(boxes).filter(b=>b.checked).length;boxes.forEach(function(b){b.disabled=!b.checked&&checked>=MAKS_TEMBUSAN});}boxes.forEach(function(b){b.addEventListener('change',sync)});sync();})();
(function(){
  const form=document.getElementById('kirimKendalaForm');
  const konfirmOverlay=document.getElementById('konfirmasiKendalaOverlay');
  const lampiranWajibOverlay=document.getElementById('lampiranKendalaWajibOverlay');
  const lampiranInput=document.getElementById('kendala_lampiran');
  if(!form||!konfirmOverlay)return;
  function closeKonfirm(){konfirmOverlay.classList.remove('open')}
  function closeLampiranWajib(){lampiranWajibOverlay?.classList.remove('open')}
  form.addEventListener('submit',function(e){
    if(form.dataset.confirmed==='1'){form.dataset.confirmed='';return}
    e.preventDefault();
    // Lampiran WAJIB khusus untuk form kendala ke Danpus ini (beda dari
    // form "Kirim Laporan" biasa yang lampirannya opsional). Kalau belum
    // pilih file, tampilkan modal peringatan dan jangan lanjut ke modal
    // konfirmasi kirim -- pengguna harus lampirkan file dulu baru bisa
    // kirim.
    if(lampiranInput && (!lampiranInput.files || lampiranInput.files.length===0)){
      lampiranWajibOverlay?.classList.add('open');
      return;
    }
    konfirmOverlay.classList.add('open');
  });
  document.getElementById('konfirmasiKendalaYa')?.addEventListener('click',function(){
    // Guard supaya laporan tidak ke-submit DUA KALI kalau tombol ini
    // sempat diklik/di-tap dua kali dengan cepat (mis. koneksi agak
    // lambat sehingga halaman belum langsung berpindah, atau double-tap
    // di HP). Tombolnya type="button" (bukan submit bawaan), jadi TIDAK
    // ada proteksi klik-ganda otomatis dari browser -- form.requestSubmit()
    // bisa kepanggil berkali-kali sebelum navigasi ke halaman berikutnya
    // benar-benar terjadi, dan tiap panggilan bikin 1 baris laporan
    // kendala baru di database. Akibatnya laporan yang BARU dikirim malah
    // kelihatan "duplikat 2 kartu identik", bukan hilang.
    if(this.disabled)return;
    this.disabled=true;
    document.getElementById('konfirmasiKendalaBatal')?.setAttribute('disabled','disabled');
    closeKonfirm();
    form.dataset.confirmed='1';
    form.requestSubmit?form.requestSubmit():form.submit();
  });
  document.getElementById('konfirmasiKendalaBatal')?.addEventListener('click',closeKonfirm);
  document.getElementById('lampiranKendalaWajibOk')?.addEventListener('click',closeLampiranWajib);
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape'&&konfirmOverlay.classList.contains('open'))closeKonfirm();
    if(e.key==='Escape'&&lampiranWajibOverlay?.classList.contains('open'))closeLampiranWajib();
  });
  // Validasi wajib-diisi custom untuk form Kirim Laporan Kendala (Kasansi) --
  // niru pola yang sama dipakai di modal "Buat Permintaan Laporan" &
  // "Kirim Laporan" biasa: ganti tooltip bawaan browser (Bahasa Inggris)
  // jadi pesan Bahasa Indonesia + border merah di bawah kolom, otomatis
  // ke-reset begitu field-nya diisi/diubah lagi. Lampiran sengaja TIDAK
  // dipasangi atribut required di HTML (lihat kendala_lampiran) supaya
  // wajib-lampiran ditangani modal "Lampiran Wajib Diisi" di atas, bukan
  // bubble bawaan browser.
  var kendalaMessages={
    prioritas:'Pilih salah satu prioritas.',
    perihal:'Perihal wajib diisi.',
    deskripsi:'Isi kendala wajib diisi.'
  };
  form.querySelectorAll('select[required],input[required],textarea[required]').forEach(function(input){
    var anchor=input.closest('.form-field')||input;
    var msg=anchor.querySelector(':scope > .kirim-laporan-error');
    if(!msg){
      msg=document.createElement('span');
      msg.className='kirim-laporan-error';
      msg.style.display='none';
      anchor.appendChild(msg);
    }
    input.addEventListener('invalid',function(e){
      e.preventDefault();
      input.classList.add('field-invalid');
      msg.textContent=kendalaMessages[input.name]||'Kolom ini wajib diisi.';
      msg.style.display='flex';
    });
    function clearInvalid(){
      input.classList.remove('field-invalid');
      msg.style.display='none';
    }
    input.addEventListener('input',clearInvalid);
    input.addEventListener('change',clearInvalid);
  });
})();
(function(){
  // Dropzone multi-file utk lampiran Kendala Kasansi -> Danpus: boleh lebih
  // dari 1 file, SEMUA format (bukan cuma PDF lagi), dibatasi TOTAL 10 MB
  // gabungan seluruh file (bukan per-file) -- beda dari dropzone
  // #lampiranDropzone punya modal "Kirim Laporan" (yang IDnya global/unik
  // 1 per halaman), makanya di sini dibikin sendiri pakai ID terpisah
  // (kendalaLampiranDropzone/kendalaLampiranFileList) biar kedua modal bisa
  // hidup berdampingan di 1 halaman tanpa rebutan ID.
  const zone=document.getElementById('kendalaLampiranDropzone');
  const input=document.getElementById('kendala_lampiran');
  const list=document.getElementById('kendalaLampiranFileList');
  const emptyEl=document.getElementById('kendalaLampiranFileListEmpty');
  const errEl=document.getElementById('kendalaLampiranError');
  if(!zone||!input||!list)return;
  const LAMPIRAN_TOTAL_MAX_BYTES=10*1024*1024;
  let staged=[];
  let stagedUrls=[];
  function formatSize(bytes){
    if(bytes<1024*1024) return Math.max(1,Math.round(bytes/1024))+' KB';
    return (bytes/1024/1024).toFixed(1)+' MB';
  }
  function syncInputFiles(){
    const dt=new DataTransfer();
    staged.forEach(function(f){dt.items.add(f)});
    input.files=dt.files;
  }
  function showError(text){
    zone.classList.add('field-invalid');
    if(errEl){errEl.textContent=text;errEl.style.display='flex';}
  }
  function clearError(){
    zone.classList.remove('field-invalid');
    if(errEl)errEl.style.display='none';
  }
  function render(){
    list.querySelectorAll('.lampiran-file-row').forEach(function(el){el.remove()});
    stagedUrls.forEach(function(url){URL.revokeObjectURL(url)});
    stagedUrls=[];
    staged.forEach(function(file,idx){
      const url=URL.createObjectURL(file);
      stagedUrls.push(url);
      const badge=(window.siberadLampiranBadge&&window.siberadLampiranBadge(file.name))||{text:'FILE',cls:'lfx-other'};
      const row=document.createElement('div');
      row.className='lampiran-file-row';
      const icon=document.createElement('span');
      icon.className='lampiran-file-row-icon '+badge.cls;
      icon.textContent=badge.text;
      const info=document.createElement('span');
      info.className='lampiran-file-row-info';
      const name=document.createElement('a');
      name.className='lampiran-file-row-name';
      name.href=url;name.target='_blank';name.rel='noopener';
      name.textContent=file.name;
      const size=document.createElement('span');
      size.className='lampiran-file-row-size';
      size.textContent=formatSize(file.size);
      info.appendChild(name);info.appendChild(size);
      const removeBtn=document.createElement('button');
      removeBtn.type='button';
      removeBtn.className='lampiran-file-row-remove';
      removeBtn.setAttribute('aria-label','Hapus file');
      removeBtn.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>';
      removeBtn.addEventListener('click',function(){
        staged.splice(idx,1);
        syncInputFiles();
        render();
      });
      row.appendChild(icon);row.appendChild(info);row.appendChild(removeBtn);
      list.appendChild(row);
    });
    if(emptyEl)emptyEl.hidden=staged.length>0;
    if(staged.length>0)clearError();
  }
  function addFiles(picked){
    if(!picked||!picked.length)return;
    var existing=staged.map(function(f){return f.name+'|'+f.size});
    var baru=Array.from(picked).filter(function(f){return existing.indexOf(f.name+'|'+f.size)===-1});
    if(!baru.length)return;
    var gabungan=staged.concat(baru);
    var total=gabungan.reduce(function(sum,f){return sum+f.size},0);
    if(total>LAMPIRAN_TOTAL_MAX_BYTES){
      showError('Total ukuran seluruh lampiran melebihi 10 MB. Kurangi jumlah/ukuran file lalu pilih ulang.');
      syncInputFiles();
      zone.scrollIntoView({block:'center',behavior:'smooth'});
      return;
    }
    staged=gabungan;
    syncInputFiles();
    render();
  }
  input.addEventListener('change',function(){
    addFiles(input.files);
  });
  ['dragenter','dragover'].forEach(function(evt){
    zone.addEventListener(evt,function(e){e.preventDefault();e.stopPropagation();zone.classList.add('is-dragover');});
  });
  zone.addEventListener('dragleave',function(){zone.classList.remove('is-dragover');});
  zone.addEventListener('drop',function(e){
    e.preventDefault();e.stopPropagation();
    zone.classList.remove('is-dragover');
    if(e.dataTransfer&&e.dataTransfer.files&&e.dataTransfer.files.length){addFiles(e.dataTransfer.files);}
  });
  render();
})();
(function(){
  // Tujuan surat: input teks + saran otomatis (autocomplete), niru tampilan
  // floating-menu yang sama dipakai .styled-select-menu (dropdown lain di
  // sistem ini) -- input hidden #surat_tujuan nyimpen ID satuan yang BENERAN
  // kepilih dari daftar; ngetik doang tanpa milih dari saran nggak dianggap
  // valid (hidden value dikosongin lagi tiap ngetik), jadi backend selalu
  // nerima ID satuan yang valid, bukan teks bebas.
  const wrap=document.getElementById('suratTujuanCombobox');
  const search=document.getElementById('surat_tujuan_search');
  const hidden=document.getElementById('surat_tujuan');
  const options=window.__suratTujuanOptions||[];
  if(!wrap||!search||!hidden)return;
  const menu=document.createElement('div');
  menu.className='styled-select-menu';
  const inner=document.createElement('div');
  inner.className='styled-select-menu-inner';
  menu.appendChild(inner);
  document.body.appendChild(menu);
  function clearInvalid(){
    search.classList.remove('field-invalid');
    const anchor=search.closest('.form-field');
    const msg=anchor&&anchor.querySelector(':scope > .kirim-laporan-error');
    if(msg)msg.style.display='none';
  }
  function renderOptions(){
    inner.innerHTML='';
    const q=search.value.trim().toLowerCase();
    const filtered=options.filter(function(o){return !q||o.name.toLowerCase().indexOf(q)>-1||(o.kode&&o.kode.toLowerCase().indexOf(q)>-1)});
    if(!filtered.length){
      const empty=document.createElement('div');
      empty.className='styled-select-option';
      empty.style.cssText='cursor:default;opacity:.6';
      empty.textContent='Tidak ada satuan yang cocok.';
      inner.appendChild(empty);
      return;
    }
    filtered.forEach(function(o){
      const item=document.createElement('button');
      item.type='button';
      item.className='styled-select-option'+(hidden.value===o.id?' active':'');
      const txt=document.createElement('span');
      txt.className='ss-opt-text';
      txt.textContent=o.name;
      item.appendChild(txt);
      item.addEventListener('mousedown',function(e){
        e.preventDefault();
        hidden.value=o.id;
        search.value=o.name;
        clearInvalid();
        hidden.dispatchEvent(new Event('change',{bubbles:true}));
        close();
      });
      inner.appendChild(item);
    });
  }
  function position(){
    const r=search.getBoundingClientRect();
    menu.style.minWidth=r.width+'px';
    let left=r.left,top=r.bottom+6;
    const vw=window.innerWidth;
    menu.style.left=left+'px';
    menu.style.top=top+'px';
    const mr=menu.getBoundingClientRect();
    if(mr.right>vw-8)left=Math.max(8,vw-8-mr.width);
    menu.style.left=left+'px';
  }
  function close(){
    menu.classList.remove('open');
  }
  search.addEventListener('input',function(){
    hidden.value='';
    if(!search.value.trim()){close();return}
    renderOptions();
    position();
    menu.classList.add('open');
  });
  document.addEventListener('click',function(e){
    if(!wrap.contains(e.target)&&!menu.contains(e.target))close();
  });
  window.addEventListener('scroll',function(e){
    if(menu.contains(e.target))return;
    if(menu.classList.contains('open'))close();
  },true);
  // Reposisi ulang pas viewport berubah ukuran -- termasuk keyboard HP
  // muncul/hilang yang ngubah window.innerHeight di sebagian besar browser
  // mobile, biar menu nggak "ketinggalan" posisi lama.
  window.addEventListener('resize',function(){
    if(menu.classList.contains('open'))position();
  });
})();
(function(){
  // Perihal & Kategori otomatis huruf kapital semua begitu diketik -- posisi
  // kursor dijaga biar nggak lompat ke akhir tiap kali ngetik di tengah teks
  // (pola sama persis kayak forceUppercase() di form Buat Permintaan Laporan
  // punya Pimpinan).
  function forceUppercase(el){
    el?.addEventListener('input',()=>{
      const pos=el.selectionStart;
      el.value=el.value.toUpperCase();
      el.setSelectionRange(pos,pos);
    });
  }
  forceUppercase(document.getElementById('surat_perihal'));
  forceUppercase(document.getElementById('surat_kategori'));
})();
(function(){
  const zone=document.getElementById('suratLampiranZone');
  const input=document.getElementById('surat_lampiran');
  const list=document.getElementById('suratLampiranFileList');
  const emptyEl=document.getElementById('suratLampiranFileListEmpty');
  const errEl=document.getElementById('suratLampiranError');
  const previewEl=document.getElementById('suratLampiranPreview');
  const form=document.getElementById('kirimSuratForm');
  const LAMPIRAN_MAX_BYTES=10*1024*1024;
  let fileObjectUrl=null;
  let previewGeneration=0;
  if(!zone||!input||!list)return;
  // PDF-nya di-render jadi bitmap <canvas> (halaman 1 doang, discale biar
  // pas selebar frame lalu dipotong ke tinggi frame -- efeknya kayak
  // "cover" majalah) pakai pdf.js, BUKAN <embed type="application/pdf">.
  // <embed> sempat dicoba duluan tapi PDF viewer bawaan Chrome nampilin
  // toolbar/scrollbar/letterbox abu-gelap sendiri yang nggak bisa
  // dihilangin total lewat parameter URL -- kelihatan kayak "ada lapisan
  // hitam" & scrollbar nyempil di kotak kecil. Canvas murni gak punya
  // chrome UI apa pun jadi bersih.
  function loadPdfJs(){
    if(!window.__pdfjsLibPromise){
      window.__pdfjsLibPromise=import('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/6.3.289/pdf.min.mjs').then(function(lib){
        lib.GlobalWorkerOptions.workerSrc='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/6.3.289/pdf.worker.min.mjs';
        return lib;
      });
    }
    return window.__pdfjsLibPromise;
  }
  function renderPdfPreview(file,frame,gen){
    const canvas=document.createElement('canvas');
    canvas.style.cssText='position:absolute;top:0;left:0;width:100%;height:auto;display:block';
    loadPdfJs().then(function(lib){
      return file.arrayBuffer().then(function(buf){
        return lib.getDocument({data:buf}).promise;
      });
    }).then(function(pdf){
      return pdf.getPage(1);
    }).then(function(page){
      if(gen!==previewGeneration)return;
      const frameWidth=frame.clientWidth||96;
      const dpr=window.devicePixelRatio||1;
      const unscaled=page.getViewport({scale:1});
      // Render dibikin lebih tinggi resolusinya (dikali dpr) daripada ukuran
      // tampil di layar (frameWidth CSS px) -- canvas cuma di-downscale
      // lewat CSS, bukan browser yang upscale bitmap kasar -- biar tajam
      // di layar high-DPI (retina/HP kebanyakan), gak pecah/blur.
      const viewport=page.getViewport({scale:(frameWidth/unscaled.width)*dpr});
      canvas.width=Math.ceil(viewport.width);
      canvas.height=Math.ceil(viewport.height);
      canvas.style.width=frameWidth+'px';
      return page.render({canvasContext:canvas.getContext('2d'),viewport:viewport}).promise;
    }).then(function(){
      if(gen!==previewGeneration)return;
      frame.appendChild(canvas);
    }).catch(function(){
      if(gen!==previewGeneration)return;
      renderFallback(file,frame);
    });
  }
  function renderFallback(file,frame){
    frame.innerHTML='';
    const badge=(window.siberadLampiranBadge&&window.siberadLampiranBadge(file.name))||{text:'FILE',cls:'lfx-other'};
    const fb=document.createElement('div');
    fb.className='surat-lampiran-preview-fallback';
    const box=document.createElement('span');
    box.className='surat-lampiran-preview-fallback-badge lampiran-file-row-icon '+badge.cls;
    box.textContent=badge.text;
    const txt=document.createElement('span');
    txt.className='surat-lampiran-preview-fallback-text';
    txt.textContent='Tanpa pratinjau';
    fb.appendChild(box);fb.appendChild(txt);
    frame.appendChild(fb);
  }
  function renderPreview(file,fileUrl){
    if(!previewEl)return;
    previewGeneration++;
    previewEl.innerHTML='';
    if(!file){previewEl.hidden=true;return}
    previewEl.hidden=false;
    const frame=document.createElement('div');
    frame.className='surat-lampiran-preview-frame';
    const isPdf=file.type==='application/pdf'||/\.pdf$/i.test(file.name);
    if(isPdf){
      renderPdfPreview(file,frame,previewGeneration);
    }else{
      renderFallback(file,frame);
    }
    previewEl.appendChild(frame);
  }
  function showLampiranError(text){
    zone.classList.add('field-invalid');
    if(errEl){errEl.textContent=text;errEl.style.display='flex';}
  }
  function clearLampiranError(){
    zone.classList.remove('field-invalid');
    if(errEl)errEl.style.display='none';
  }
  function formatSize(bytes){
    if(bytes<1024*1024) return Math.max(1,Math.round(bytes/1024))+' KB';
    return (bytes/1024/1024).toFixed(1)+' MB';
  }
  function render(){
    list.querySelectorAll('.lampiran-file-row').forEach(function(el){el.remove()});
    if(fileObjectUrl){URL.revokeObjectURL(fileObjectUrl);fileObjectUrl=null;}
    const file=input.files&&input.files[0];
    if(!file){if(emptyEl)emptyEl.hidden=false;renderPreview(null);return}
    if(emptyEl)emptyEl.hidden=true;
    fileObjectUrl=URL.createObjectURL(file);
    renderPreview(file,fileObjectUrl);
    const badge=(window.siberadLampiranBadge&&window.siberadLampiranBadge(file.name))||{text:'PDF',cls:'lfx-pdf'};
    const row=document.createElement('div');
    row.className='lampiran-file-row';
    const icon=document.createElement('span');
    icon.className='lampiran-file-row-icon '+badge.cls;
    icon.textContent=badge.text;
    const info=document.createElement('span');
    info.className='lampiran-file-row-info';
    const name=document.createElement('a');
    name.className='lampiran-file-row-name';
    name.href=fileObjectUrl;
    name.target='_blank';
    name.rel='noopener';
    name.textContent=file.name;
    const size=document.createElement('span');
    size.className='lampiran-file-row-size';
    size.textContent=formatSize(file.size);
    info.appendChild(name);info.appendChild(size);
    const removeBtn=document.createElement('button');
    removeBtn.type='button';
    removeBtn.className='lampiran-file-row-remove';
    removeBtn.setAttribute('aria-label','Hapus file');
    removeBtn.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>';
    removeBtn.addEventListener('click',function(){input.value='';render()});
    row.appendChild(icon);row.appendChild(info);row.appendChild(removeBtn);
    list.appendChild(row);
  }
  input.addEventListener('change',function(){
    const file=input.files&&input.files[0];
    if(file&&file.size>LAMPIRAN_MAX_BYTES){
      input.value='';
      showLampiranError('Ukuran file melebihi 10 MB, tidak bisa dipilih.');
      render();
      return;
    }
    if(file)clearLampiranError();
    render();
  });
  ['dragenter','dragover'].forEach(function(evt){zone.addEventListener(evt,function(){zone.classList.add('is-dragover')})});
  ['dragleave','drop'].forEach(function(evt){zone.addEventListener(evt,function(){zone.classList.remove('is-dragover')})});
  if(form)form.addEventListener('submit',function(e){
    if(!input.files||input.files.length===0){
      e.preventDefault();
      showLampiranError('Lampiran wajib diisi.');
      zone.scrollIntoView({block:'center',behavior:'smooth'});
    }
  });
  render();
})();
(function(){
  // Validasi wajib-diisi custom untuk form Buat Surat -- niru persis pola
  // yang sama dipakai di form Kirim Kendala: ganti tooltip bawaan browser
  // (Bahasa Inggris) jadi pesan Bahasa Indonesia + border merah di bawah
  // kolom, otomatis ke-reset begitu field-nya diisi/diubah lagi.
  const form=document.getElementById('kirimSuratForm');
  if(!form)return;
  const suratMessages={
    tujuan_satuan_id:'Tujuan surat wajib dipilih.',
    perihal:'Perihal wajib diisi.',
    kategori:'Kategori wajib diisi.',
    prioritas:'Pilih salah satu prioritas.',
    deskripsi:'Isi ringkasan surat wajib diisi.'
  };
  form.querySelectorAll('select[required],input[required],textarea[required]').forEach(function(input){
    var anchor=input.closest('.form-field')||input;
    var msg=anchor.querySelector(':scope > .kirim-laporan-error');
    if(!msg){
      msg=document.createElement('span');
      msg.className='kirim-laporan-error';
      msg.style.display='none';
      anchor.appendChild(msg);
    }
    input.addEventListener('invalid',function(e){
      e.preventDefault();
      input.classList.add('field-invalid');
      msg.textContent=suratMessages[input.name]||'Kolom ini wajib diisi.';
      msg.style.display='flex';
    });
    function clearInvalid(){
      input.classList.remove('field-invalid');
      msg.style.display='none';
    }
    input.addEventListener('input',clearInvalid);
    input.addEventListener('change',clearInvalid);
  });
  // Tujuan sekarang input teks+hidden (bukan <select required> lagi), jadi
  // wajib-diisinya dicek manual pas submit -- niru pola guard yang sama
  // dipakai buat cek Lampiran wajib.
  const tujuanSearch=document.getElementById('surat_tujuan_search');
  const tujuanHidden=document.getElementById('surat_tujuan');
  if(tujuanSearch&&tujuanHidden){
    form.addEventListener('submit',function(e){
      if(tujuanHidden.value)return;
      e.preventDefault();
      var anchor=tujuanSearch.closest('.form-field');
      var msg=anchor&&anchor.querySelector(':scope > .kirim-laporan-error');
      if(!msg&&anchor){
        msg=document.createElement('span');
        msg.className='kirim-laporan-error';
        anchor.appendChild(msg);
      }
      tujuanSearch.classList.add('field-invalid');
      if(msg){msg.textContent=suratMessages.tujuan_satuan_id;msg.style.display='flex';}
      tujuanSearch.scrollIntoView({block:'center',behavior:'smooth'});
    });
    tujuanSearch.addEventListener('input',function(){
      tujuanSearch.classList.remove('field-invalid');
      var anchor=tujuanSearch.closest('.form-field');
      var msg=anchor&&anchor.querySelector(':scope > .kirim-laporan-error');
      if(msg)msg.style.display='none';
    });
  }
})();
(function(){
  const form=document.getElementById('kirimSuratForm');
  const konfirmOverlay=document.getElementById('konfirmasiBuatSuratOverlay');
  if(!form||!konfirmOverlay)return;
  function closeKonfirm(){konfirmOverlay.classList.remove('open')}
  form.addEventListener('submit',function(e){
    if(form.dataset.confirmed==='1'){form.dataset.confirmed='';return}
    if(e.defaultPrevented)return;
    e.preventDefault();
    konfirmOverlay.classList.add('open');
  });
  document.getElementById('konfirmasiBuatSuratYa')?.addEventListener('click',function(){
    closeKonfirm();
    form.dataset.confirmed='1';
    form.requestSubmit?form.requestSubmit():form.submit();
  });
  document.getElementById('konfirmasiBuatSuratBatal')?.addEventListener('click',closeKonfirm);
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape'&&konfirmOverlay.classList.contains('open'))closeKonfirm();
  });
})();
(function(){
  const form=document.getElementById('kirimLaporanForm');
  const konfirmOverlay=document.getElementById('konfirmasiKirimOverlay');
  if(!form||!konfirmOverlay)return;
  function closeKonfirm(){konfirmOverlay.classList.remove('open')}
  form.addEventListener('submit',function(e){
    if(form.dataset.confirmed==='1'){form.dataset.confirmed='';return}
    e.preventDefault();
    konfirmOverlay.classList.add('open');
  });
  document.getElementById('konfirmasiKirimYa')?.addEventListener('click',function(){
    closeKonfirm();
    if(window.siberadSubmitKirimLaporanForm){
      window.siberadSubmitKirimLaporanForm(form);
    }else{
      form.dataset.confirmed='1';
      form.requestSubmit?form.requestSubmit():form.submit();
    }
  });
  document.getElementById('konfirmasiKirimBatal')?.addEventListener('click',closeKonfirm);
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&konfirmOverlay.classList.contains('open'))closeKonfirm()});
})();
(function(){
  var overlay=document.getElementById('konfirmasiTeruskanOverlay');
  var formTeruskan=document.getElementById('formTeruskanKeDanpus');
  if(!overlay||!formTeruskan)return;
  function buka(action,token,perihal){
    formTeruskan.action=action;
    document.getElementById('formTeruskanToken').value=token;
    var body=document.getElementById('konfirmasiTeruskanBody');
    if(body)body.textContent='Laporan "'+perihal+'" akan langsung diteruskan ke Danpus. Tindakan ini tidak dapat dibatalkan.';
    overlay.classList.add('open');
  }
  function tutup(){overlay.classList.remove('open');}
  window.bukaKonfirmasiTeruskan=buka;
  document.getElementById('konfirmasiTeruskanBatal')?.addEventListener('click',tutup);
  document.getElementById('konfirmasiTeruskanYa')?.addEventListener('click',function(){
    tutup();
    formTeruskan.requestSubmit?formTeruskan.requestSubmit():formTeruskan.submit();
  });
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape'&&overlay.classList.contains('open'))tutup();
  });
})();
(function(){
  var overlay=document.getElementById('konfirmasiSuratOverlay');
  var form=document.getElementById('formKonfirmasiSurat');
  if(!overlay||!form)return;
  function buka(action,token,pengirim){
    form.action=action;
    document.getElementById('formKonfirmasiSuratToken').value=token;
    var body=document.getElementById('konfirmasiSuratBody');
    if(body)body.textContent='Konfirmasi surat ini dari '+(pengirim||'-')+'? Pengirim akan mengetahui bahwa surat sudah diterima.';
    document.getElementById('reportDetailModal')?.classList.remove('open');
    overlay.classList.add('open');
  }
  function tutup(){overlay.classList.remove('open');}
  window.bukaKonfirmasiSurat=buka;
  document.getElementById('konfirmasiSuratBatal')?.addEventListener('click',tutup);
  document.getElementById('konfirmasiSuratYa')?.addEventListener('click',function(){
    tutup();
    form.requestSubmit?form.requestSubmit():form.submit();
  });
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape'&&overlay.classList.contains('open'))tutup();
  });
})();
(function(){
  // Kartu KPI (Total Pelaporan/Surat/Kendala Kasansi) + donut "Distribusi
  // Status Laporan" Beranda Satuan: animasi count-up angka + gunung
  // "tumbuh" dari dasar + progress bar rincian + poll realtime -- MIRROR
  // PERSIS logika yang sama di Beranda Pimpinan (laporan-pimpinan.blade.php,
  // fungsi countUp/animatePimpKpis/makeStatusDonut/animateStatusDistrib/
  // syncPimpinanKpis), cuma discoped ke 2 section doang (halaman ini gak
  // punya Tren Aktivitas/Surat Terbaru/Kendala Terbaru kayak Beranda
  // Pimpinan) dan nembak endpoint dashboard.satuan-kpi.realtime (data
  // per-satuan sendiri, bukan seluruh satuan pelaksana). SATU IIFE/SATU
  // poll buat kedua section (bukan 2 poller terpisah), sama alasannya
  // kayak pimpinanKpiRealtime() -- lihat komentar lengkap di sana.
  const wrap=document.getElementById('satuanKpisWrap');
  if(!wrap)return;
  const reduceMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ===== "Distribusi Status Laporan" (donut) -- MIRROR makeStatusDonut()/
  // animateStatusDistrib()/var statusData Beranda Pimpinan, token warna
  // dipetakan ke tema Satuan sendiri (--text-muted/--panel/--text/
  // --border-soft, bukan --p-*).
  let statusData={disetujui:{{ $stats['disetujui'] }},ditolak:{{ $stats['ditolak'] }},terlambat:{{ $stats['terlambat'] }},dibatalkan:{{ $stats['dibatalkan'] }}};
  if(typeof Chart!=='undefined'){
    const satuanChartRoot=getComputedStyle(document.documentElement);
    Chart.defaults.color=satuanChartRoot.getPropertyValue('--text-muted').trim()||'#64748b';
  }
  function makeStatusDonut(){
    const el=document.getElementById('statusDonut');
    if(!el||typeof Chart==='undefined')return;
    const reduce=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const donutRoot=getComputedStyle(document.documentElement);
    const donutSurfaceColor=donutRoot.getPropertyValue('--panel').trim()||'#fff';
    const donutTextColor=donutRoot.getPropertyValue('--text').trim()||'#17212b';
    const donutBorderColor=donutRoot.getPropertyValue('--border-soft').trim()||'rgba(127,127,127,.15)';
    const defs=[
      {label:'Disetujui',color:'#22c55e',count:statusData.disetujui},
      {label:'Ditolak',color:'#ef4444',count:statusData.ditolak},
      {label:'Terlambat',color:'#ff6b6b',count:statusData.terlambat},
      {label:'Dibatalkan',color:'#c1121f',count:statusData.dibatalkan}
    ];
    const total=defs.reduce((s,d)=>s+d.count,0);
    const empty=total<=0;
    const arcPct={id:'sbArcPct',afterDatasetsDraw(chart){
      if(empty)return;
      const t=chart.$sbT==null?1:Math.max(0,Math.min(1,chart.$sbT));
      const ctx=chart.ctx,meta=chart.getDatasetMeta(0);
      ctx.save();
      ctx.font='800 12px '+(getComputedStyle(el).fontFamily||'sans-serif');
      ctx.textBaseline='middle';
      meta.data.forEach((arc,i)=>{
        if(!defs[i].count)return;
        const pct=Math.round(defs[i].count/total*100);
        if(pct<4)return;
        const shown=t>=0.985?pct:Math.round(pct*t);
        if(shown<1)return;
        const ang=(arc.startAngle+arc.endAngle)/2;
        const rr=arc.outerRadius+13;
        const x=arc.x+Math.cos(ang)*rr,y=arc.y+Math.sin(ang)*rr;
        ctx.fillStyle=defs[i].color;
        ctx.textAlign=Math.cos(ang)>-0.1?'left':'right';
        ctx.fillText(shown+'%',x,y);
      });
      ctx.restore();
    }};
    const chart=new Chart(el,{
      type:'doughnut',
      data:{labels:defs.map(d=>d.label),datasets:[{
        data:empty?[1]:defs.map(d=>d.count),
        backgroundColor:empty?['rgba(127,127,127,.16)']:defs.map(d=>d.color),
        borderColor:'transparent',borderWidth:0,
        borderRadius:empty?0:7,spacing:empty?0:3,hoverOffset:empty?0:5
      }]},
      options:{cutout:'70%',responsive:true,maintainAspectRatio:false,layout:{padding:24},
        animation:reduce?false:{duration:1100,easing:'easeOutCubic',
          onProgress:a=>{if(a&&a.chart)a.chart.$sbT=a.numSteps?a.currentStep/a.numSteps:1;},
          onComplete:a=>{if(a&&a.chart)a.chart.$sbT=1;}},
        plugins:{legend:{display:false},tooltip:{enabled:!empty,backgroundColor:donutSurfaceColor,titleColor:donutTextColor,bodyColor:donutTextColor,borderColor:donutBorderColor,borderWidth:1,cornerRadius:10,padding:10,usePointStyle:true,titleFont:{weight:'700'},bodyFont:{weight:'600'},callbacks:{label:c=>' '+c.label+': '+c.raw+' ('+Math.round(c.raw/total*100)+'%)'}}}},
      plugins:[arcPct]
    });
    chart.$sbT=reduce?1:0;
    window.siberadCharts=window.siberadCharts||[];
    window.siberadCharts.push(chart);
    // Refresh realtime: destroy+recreate instance pakai statusData terbaru,
    // sama persis pola window.siberadRefreshStatusDonut Pimpinan.
    window.siberadRefreshStatusDonut=function(){
      const idx=window.siberadCharts.indexOf(chart);
      if(idx>-1)window.siberadCharts.splice(idx,1);
      chart.destroy();
      makeStatusDonut();
    };
  }
  makeStatusDonut();
  // "Mengisi perlahan": progress bar rincian + angka count-up (Total
  // Laporan di tengah donut, jumlah & persen tiap baris). Sama persis pola
  // animateStatusDistrib() Pimpinan.
  (function animateStatusDistrib(){
    const card=document.querySelector('.status-dist-card');
    if(!card)return;
    if(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
    const bars=card.querySelectorAll('.status-bd-bar-fill');
    const barTargets=[];
    bars.forEach(function(b){barTargets.push(b.style.width||'0%');b.style.width='0%';});
    const nums=[];
    const center=card.querySelector('.status-donut-center strong');
    if(center)nums.push({el:center,to:parseInt(center.textContent,10)||0,suf:''});
    card.querySelectorAll('.status-bd-count').forEach(function(el){nums.push({el:el,to:parseInt(el.textContent,10)||0,suf:''});});
    card.querySelectorAll('.status-bd-pct').forEach(function(el){nums.push({el:el,to:parseInt(el.textContent,10)||0,suf:'%'});});
    nums.forEach(function(n){n.el.textContent='0'+n.suf;});
    requestAnimationFrame(function(){
      bars.forEach(function(b,i){setTimeout(function(){b.style.width=barTargets[i];},120+i*90);});
    });
    const dur=1000,t0=performance.now();
    (function tick(now){
      const p=Math.min(1,((now||performance.now())-t0)/dur),e=1-Math.pow(1-p,3);
      nums.forEach(function(n){n.el.textContent=Math.round(n.to*e)+n.suf;});
      if(p<1)requestAnimationFrame(tick);
      else nums.forEach(function(n){n.el.textContent=n.to+n.suf;});
    })();
  })();

  function countUp(el,from,to,dur){
    if(!el)return;
    if(from===to){el.textContent=to;return;}
    const t0=performance.now();
    let done=false;
    function finish(){if(done)return;done=true;el.textContent=to;}
    (function tick(now){
      if(done)return;
      const p=Math.min(1,((now||performance.now())-t0)/dur),e=1-Math.pow(1-p,3);
      el.textContent=Math.round(from+(to-from)*e);
      if(p<1)requestAnimationFrame(tick);else finish();
    })();
    // Jaring pengaman -- kalau rAF gak sempat jalan lagi, angka tetap
    // dipaksa ke nilai benar, gak nyangkut di nilai "from" selamanya.
    setTimeout(finish,dur+150);
  }
  function animateSatuanKpis(fromList,trendFromList){
    const cards=wrap.querySelectorAll('.pimp-kpi');
    cards.forEach(function(card,i){
      const valEl=card.querySelector('.kpi-value');
      const to=parseInt(((valEl&&valEl.textContent)||'0').replace(/[^0-9-]/g,''),10)||0;
      const trendEl=card.querySelector('.kpi-trend span');
      const trendTo=parseInt(((trendEl&&trendEl.textContent)||'0').replace(/[^0-9-]/g,''),10)||0;
      if(reduceMotion){if(valEl)valEl.textContent=to;if(trendEl)trendEl.textContent=trendTo;return;}
      const deco=card.querySelector('.kpi-deco');
      if(deco){
        deco.classList.remove('is-growing');
        void deco.offsetWidth;
        deco.classList.add('is-growing');
      }
      const from=(fromList&&typeof fromList[i]==='number')?fromList[i]:0;
      const trendFrom=(trendFromList&&typeof trendFromList[i]==='number')?trendFromList[i]:0;
      countUp(valEl,from,to,900);
      countUp(trendEl,trendFrom,trendTo,900);
    });
  }
  // Cache HTML "fresh" TERAKHIR (bukan baca wrap.innerHTML tiap kali) --
  // sama persis alasannya kayak versi Pimpinan, lihat
  // [[feedback_dom_diff_flicker_gotcha]]: animateSatuanKpis() nempelin
  // class .is-growing permanen, jadi wrap.innerHTML LIVE gak boleh
  // dijadikan pembanding, harus fresh-vs-fresh.
  let lastFreshHtml=wrap.innerHTML.trim();
  animateSatuanKpis();
  // Cache section donut, sama pola & alasan persis kayak lastFreshHtml di
  // atas -- diambil SEBELUM ada mutasi JS apapun ke elemen-elemen ini.
  const statusBdWrap=document.getElementById('satuanStatusBdWrap');
  const donutTotalEl=document.getElementById('satuanDonutTotal');
  let lastStatusBdHtml=statusBdWrap?statusBdWrap.innerHTML.trim():'';
  let lastDonutKey=JSON.stringify(statusData);
  // "Surat Terbaru"/"Kendala Kasansi Terbaru" -- swap innerHTML biasa
  // (bukan dianimasiin kayak KPI/donut yang count-up), tapi baris/item-nya
  // dikasih fade-in + flash background pas baru dirender -- MIRROR PERSIS
  // animasi yang sama di Beranda Pimpinan (fungsi animateTerbaruRows).
  const suratBody=document.getElementById('satuanSuratTerbaruBody');
  const kendalaList=document.getElementById('satuanKendalaTerbaruList');
  let lastSuratHtml=suratBody?suratBody.innerHTML.trim():'';
  let lastKendalaHtml=kendalaList?kendalaList.innerHTML.trim():'';
  // Stagger 60ms per baris/item (pola sama kayak stagger progress bar
  // Distribusi Status di atas), dipanggil abis konten section itu
  // dirender ulang -- load pertama DAN tiap swap realtime.
  function animateTerbaruRows(container,selector){
    if(!container)return;
    container.querySelectorAll(selector).forEach(function(el,i){
      el.style.animationDelay=(i*60)+'ms';
      el.classList.add('is-fresh');
    });
  }
  animateTerbaruRows(suratBody,'tr');
  animateTerbaruRows(kendalaList,'.pimp-activity-item');
  const kpiEndpoint='{{ route('dashboard.satuan-kpi.realtime') }}';
  let kpiBusy=false;
  async function syncSatuanKpis(){
    if(kpiBusy||document.hidden)return;
    kpiBusy=true;
    try{
      const r=await fetch(kpiEndpoint+'?_='+Date.now(),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','Cache-Control':'no-cache'}});
      if(!r.ok)return;
      const data=await r.json();
      if(typeof data.kpis_html==='string'){
        const fresh=data.kpis_html.trim();
        if(lastFreshHtml!==fresh){
          const fromList=Array.prototype.map.call(wrap.querySelectorAll('.pimp-kpi .kpi-value'),function(el){return parseInt((el.textContent||'0').replace(/[^0-9-]/g,''),10)||0;});
          const trendFromList=Array.prototype.map.call(wrap.querySelectorAll('.pimp-kpi .kpi-trend span'),function(el){return parseInt((el.textContent||'0').replace(/[^0-9-]/g,''),10)||0;});
          lastFreshHtml=fresh;
          wrap.innerHTML=fresh;
          animateSatuanKpis(fromList,trendFromList);
        }
      }
      if(statusBdWrap&&typeof data.status_bd_html==='string'){
        const freshBd=data.status_bd_html.trim();
        if(lastStatusBdHtml!==freshBd){
          lastStatusBdHtml=freshBd;
          statusBdWrap.innerHTML=freshBd;
        }
      }
      if(Array.isArray(data.status_donut_counts)){
        statusData={disetujui:data.status_donut_counts[0]||0,ditolak:data.status_donut_counts[1]||0,terlambat:data.status_donut_counts[2]||0,dibatalkan:data.status_donut_counts[3]||0};
        const freshDonutKey=JSON.stringify(statusData);
        if(lastDonutKey!==freshDonutKey){
          lastDonutKey=freshDonutKey;
          window.siberadRefreshStatusDonut&&window.siberadRefreshStatusDonut();
        }
      }
      if(donutTotalEl&&typeof data.status_donut_total!=='undefined'){
        donutTotalEl.textContent=data.status_donut_total;
      }
      if(suratBody&&typeof data.surat_terbaru_html==='string'){
        const freshSurat=data.surat_terbaru_html.trim();
        if(lastSuratHtml!==freshSurat){
          lastSuratHtml=freshSurat;
          suratBody.innerHTML=freshSurat;
          animateTerbaruRows(suratBody,'tr');
        }
      }
      if(kendalaList&&typeof data.kendala_terbaru_html==='string'){
        const freshKendala=data.kendala_terbaru_html.trim();
        if(lastKendalaHtml!==freshKendala){
          lastKendalaHtml=freshKendala;
          kendalaList.innerHTML=freshKendala;
          animateTerbaruRows(kendalaList,'.pimp-activity-item');
        }
      }
    }catch(e){}
    finally{kpiBusy=false;}
  }
  window.setInterval(syncSatuanKpis,1000);
  document.addEventListener('visibilitychange',function(){if(!document.hidden)syncSatuanKpis();});
})();
</script>
</body></html>
