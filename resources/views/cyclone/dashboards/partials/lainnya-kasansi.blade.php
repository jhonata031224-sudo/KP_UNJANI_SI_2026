{{--
    lainnya-kasansi.blade.php
    Konten section "Lainnya" untuk sidebar non-admin: subsection Struktur
    Organisasi yang di-render via partial ini, konsisten dengan pola partial
    lain di shell. Notifikasi sudah dipindah ke menu profil (lihat
    profile-notifikasi-view.blade.php).
--}}

{{-- ===== CSS section ===== --}}
<style>
/* ---- Panel bersama ---- */
.lainnya-section-head{display:flex;align-items:flex-start;gap:14px;margin-bottom:22px}
.lainnya-section-icon{flex-shrink:0;width:44px;height:44px;border-radius:13px;display:flex;align-items:center;justify-content:center}
.lainnya-section-icon svg{width:22px;height:22px}
.lainnya-section-title{font-size:18px;font-weight:700;font-family:var(--display);margin:0 0 4px}
.lainnya-section-sub{font-size:12.5px;color:var(--text-muted);line-height:1.55;margin:0}

/* ---- Struktur Organisasi (gambar unggahan Admin) ---- */
.struktur-org-image-wrap{display:flex;flex-direction:column;align-items:center;gap:10px}
.struktur-org-image{display:block;max-width:100%;width:auto;max-height:640px;object-fit:contain;border-radius:14px;border:1px solid var(--border-soft);background:var(--panel);box-shadow:0 4px 16px rgba(0,0,0,.12);cursor:zoom-in}
.struktur-org-image-hint{margin:0;font-size:11.5px;color:var(--text-muted)}
</style>

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
                <p class="lainnya-section-sub">Bagan hubungan hierarki antar-satuan dalam lingkungan {{ $pengaturan?->namaSistem() ?? "Cyclone" }}.</p>
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
