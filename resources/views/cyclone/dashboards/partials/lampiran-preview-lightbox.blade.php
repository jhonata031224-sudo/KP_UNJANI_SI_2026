{{--
    lampiran-preview-lightbox.blade.php
    Komponen pratinjau lampiran yang dipakai bersama oleh form Buat Surat,
    Kirim Kendala, Kirim Laporan, dan Balasan Surat saat pengguna
    MELAMPIRKAN file (sebelum dikirim):

      - Kartu pratinjau kecil (gambar / halaman 1 PDF) yang bisa diklik.
      - Klik -> terbuka jendela pembesar (gambar penuh / semua halaman PDF),
        mirip gambar Struktur Organisasi yang bisa diklik untuk diperbesar,
        tapi tetap di halaman yang sama (tombol "Buka di tab baru" tersedia).

    API global (semua class/ID ber-namespace "lpv-" supaya tidak menabrak CSS
    lain di dashboard):
      window.cycloneLampiranPreview.open({file?, url?, name?})
      window.cycloneLampiranPreview.renderGrid(containerEl, [{file?, url?, name?}, ...])
    `file` = objek File dari <input type=file> (belum diunggah), `url` = URL
    file yang sudah tersimpan di server. Cukup salah satu.

    PDF dirender pakai pdf.js dari CDN yang sama dengan pratinjau form Surat
    (promise dibagi lewat window.__pdfjsLibPromise supaya tidak dimuat dobel).
--}}

<style>
/* ---- Jendela pembesar ---- */
.lpv-overlay{position:fixed;inset:0;z-index:1400;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.72);opacity:0;transition:opacity .15s ease;overscroll-behavior:contain}
.lpv-overlay[hidden]{display:none}
.lpv-overlay.is-open{opacity:1}
.lpv-card{width:min(1000px,100%);height:min(92vh,900px);display:flex;flex-direction:column;background:var(--panel);border:1px solid var(--border-soft);border-radius:16px;overflow:hidden;box-shadow:0 24px 70px rgba(0,0,0,.45)}
.lpv-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px 12px 16px;border-bottom:1px solid var(--border-soft);flex-shrink:0}
.lpv-title{display:flex;align-items:center;gap:10px;min-width:0}
.lpv-title-badge.lampiran-file-row-icon{width:32px;height:32px;font-size:8px}
.lpv-title-name{font-size:13.5px;font-weight:700;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lpv-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}
.lpv-btn{display:inline-flex;align-items:center;padding:7px 12px;border-radius:9px;border:1px solid var(--border);background:var(--panel-alt);color:var(--text);font-size:12px;font-weight:600;text-decoration:none;cursor:pointer;white-space:nowrap}
.lpv-btn:hover{border-color:var(--gold-bright);color:var(--gold-bright)}
.lpv-close{width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;border:1px solid var(--border);background:var(--panel-alt);color:var(--text-muted);cursor:pointer;padding:0}
.lpv-close:hover{color:var(--text);border-color:var(--gold-bright)}
.lpv-close svg{width:16px;height:16px}
.lpv-body{flex:1;min-height:0;overflow:auto;padding:16px;background:var(--panel-alt);-webkit-overflow-scrolling:touch}
.lpv-status{padding:40px 10px;text-align:center;font-size:12.5px;color:var(--text-muted)}
.lpv-pages{display:flex;flex-direction:column;align-items:center;gap:14px}
.lpv-page{display:block;max-width:100%;height:auto;background:#fff;border-radius:4px;box-shadow:0 4px 18px rgba(0,0,0,.28)}
.lpv-note{margin-top:14px;text-align:center;font-size:11.5px;color:var(--text-muted)}
.lpv-body-image{display:flex}
.lpv-img{display:block;margin:auto;max-width:100%;max-height:calc(min(92vh,900px) - 110px);object-fit:contain;border-radius:8px;box-shadow:0 4px 18px rgba(0,0,0,.28);cursor:zoom-in}
.lpv-body-image.is-zoomed .lpv-img{max-width:none;max-height:none;cursor:zoom-out}
.lpv-fallback{margin:auto;display:flex;flex-direction:column;align-items:center;gap:10px;padding:40px 16px;text-align:center}
.lpv-fallback-badge.lampiran-file-row-icon{width:56px;height:56px;border-radius:14px;font-size:12px}
.lpv-fallback-title{font-size:14px;font-weight:700;color:var(--text);word-break:break-word}
.lpv-fallback-text{font-size:12px;color:var(--text-muted);max-width:360px;line-height:1.55}

/* ---- Kartu pratinjau (di bawah daftar file) ---- */
.lpv-thumb-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;margin-top:10px}
.lpv-thumb-grid[hidden]{display:none}
.lpv-thumb{display:flex;flex-direction:column;gap:6px;min-width:0;margin:0;padding:0;border:0;background:transparent;color:inherit;font:inherit;text-align:left;cursor:zoom-in}
.lpv-thumb-frame,.surat-lampiran-preview-frame.lpv-clickable{position:relative;overflow:hidden}
.lpv-thumb-frame{display:flex;align-items:center;justify-content:center;height:120px;border-radius:9px;border:1px solid var(--border-soft);background:var(--panel);transition:border-color .15s ease,box-shadow .15s ease}
.lpv-thumb-frame img{width:100%;height:100%;object-fit:cover;display:block}
.lpv-thumb-canvas{position:absolute;top:0;left:0;width:100%;height:auto;display:block}
.lpv-thumb-caption{font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lpv-thumb-fallback{display:flex;flex-direction:column;align-items:center;gap:6px;padding:8px;text-align:center;font-size:10.5px;color:var(--text-muted)}
.lpv-thumb-fallback .lampiran-file-row-icon{width:42px;height:42px;border-radius:10px;font-size:10px}

/* Efek "bisa diklik" -- dipakai kartu baru DAN kotak pratinjau form Surat yang sudah ada */
.lpv-thumb:hover .lpv-thumb-frame,.lpv-thumb:focus-visible .lpv-thumb-frame,
.surat-lampiran-preview-frame.lpv-clickable:hover,.surat-lampiran-preview-frame.lpv-clickable:focus-visible{border-color:var(--gold-bright);box-shadow:0 0 0 3px color-mix(in srgb,var(--gold-bright) 22%,transparent);outline:none}
.surat-lampiran-preview-frame.lpv-clickable{cursor:zoom-in}
.lpv-thumb-frame::after,.surat-lampiran-preview-frame.lpv-clickable::after{content:'Klik untuk memperbesar';position:absolute;left:8px;bottom:8px;padding:3px 9px;border-radius:99px;font-size:10px;font-weight:700;background:rgba(0,0,0,.66);color:#fff;pointer-events:none;opacity:0;transition:opacity .15s ease}
.lpv-thumb:hover .lpv-thumb-frame::after,.lpv-thumb:focus-visible .lpv-thumb-frame::after,
.surat-lampiran-preview-frame.lpv-clickable:hover::after,.surat-lampiran-preview-frame.lpv-clickable:focus-visible::after{opacity:1}
@media(hover:none){.lpv-thumb-frame::after,.surat-lampiran-preview-frame.lpv-clickable::after{opacity:1}}
@media(max-width:640px){
  .lpv-overlay{padding:0}
  .lpv-card{width:100%;height:100%;border-radius:0;border:0}
  .lpv-btn{padding:7px 10px}
  .lpv-img{max-height:calc(100vh - 110px)}
}
</style>

<script>
(function () {
    'use strict';
    if (window.cycloneLampiranPreview) return;

    var PDF_MAX_PAGES = 40;
    var overlay = null, bodyEl = null, nameEl = null, badgeEl = null, openLink = null, closeBtn = null;
    var gen = 0;             // naik tiap buka/tutup -- membatalkan render PDF yang masih berjalan
    var lastObjUrl = null;   // blob: URL terakhir untuk file lokal (dipakai "Buka di tab baru")
    var prevFocus = null, prevOverflow = '';

    function nameOf(item) {
        return (item && (item.name || (item.file && item.file.name))) || 'Lampiran';
    }

    function kindOf(item) {
        var n = nameOf(item).toLowerCase();
        var t = (item && item.file && item.file.type) || '';
        if (t.indexOf('image/') === 0 || /\.(jpe?g|png|gif|webp|bmp|svg)$/.test(n)) return 'image';
        if (t === 'application/pdf' || /\.pdf$/.test(n)) return 'pdf';
        return 'other';
    }

    function badgeOf(name) {
        return (window.cycloneLampiranBadge && window.cycloneLampiranBadge(name)) || { text: 'FILE', cls: 'lfx-other' };
    }

    function loadPdfJs() {
        if (!window.__pdfjsLibPromise) {
            window.__pdfjsLibPromise = import('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/6.3.289/pdf.min.mjs').then(function (lib) {
                lib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/6.3.289/pdf.worker.min.mjs';
                return lib;
            }).catch(function (err) {
                window.__pdfjsLibPromise = null;
                throw err;
            });
        }
        return window.__pdfjsLibPromise;
    }

    function pdfSource(item) {
        if (item.file) {
            return item.file.arrayBuffer().then(function (buf) { return { data: buf }; });
        }
        return Promise.resolve({ url: item.url });
    }

    function hrefFor(item) {
        if (item.url) return item.url;
        if (item.file) {
            if (lastObjUrl) URL.revokeObjectURL(lastObjUrl);
            lastObjUrl = URL.createObjectURL(item.file);
            return lastObjUrl;
        }
        return '#';
    }

    /* ---------- Jendela pembesar ---------- */
    function ensureOverlay() {
        if (overlay) return;
        overlay = document.createElement('div');
        overlay.className = 'lpv-overlay';
        overlay.hidden = true;
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Pratinjau lampiran');
        overlay.innerHTML =
            '<div class="lpv-card">' +
              '<div class="lpv-head">' +
                '<div class="lpv-title"><span class="lpv-title-badge lampiran-file-row-icon"></span><span class="lpv-title-name"></span></div>' +
                '<div class="lpv-actions">' +
                  '<a class="lpv-btn" target="_blank" rel="noopener">Buka di tab baru</a>' +
                  '<button type="button" class="lpv-close" aria-label="Tutup pratinjau">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>' +
                  '</button>' +
                '</div>' +
              '</div>' +
              '<div class="lpv-body"></div>' +
            '</div>';
        document.body.appendChild(overlay);
        bodyEl = overlay.querySelector('.lpv-body');
        nameEl = overlay.querySelector('.lpv-title-name');
        badgeEl = overlay.querySelector('.lpv-title-badge');
        openLink = overlay.querySelector('.lpv-btn');
        closeBtn = overlay.querySelector('.lpv-close');

        closeBtn.addEventListener('click', close);
        overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
        // Tab dijaga tetap di dalam jendela (link "Buka di tab baru" <-> tombol tutup).
        overlay.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab') return;
            var first = openLink, last = closeBtn;
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });
    }

    // Esc menutup HANYA jendela pembesar (fase capture + stopImmediatePropagation)
    // supaya modal form di belakangnya (Buat Surat, Kirim Kendala, dll) tidak
    // ikut tertutup lewat handler Escape miliknya sendiri.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay && !overlay.hidden) {
            e.stopImmediatePropagation();
            e.preventDefault();
            close();
        }
    }, true);

    function close() {
        if (!overlay || overlay.hidden) return;
        gen++;
        overlay.classList.remove('is-open');
        overlay.hidden = true;
        bodyEl.innerHTML = '';
        document.body.style.overflow = prevOverflow;
        if (prevFocus && typeof prevFocus.focus === 'function') {
            try { prevFocus.focus(); } catch (err) {}
        }
    }

    function renderOther(item, message) {
        var name = nameOf(item);
        var badge = badgeOf(name);
        bodyEl.innerHTML = '';
        bodyEl.className = 'lpv-body';
        var box = document.createElement('div');
        box.className = 'lpv-fallback';
        var b = document.createElement('span');
        b.className = 'lpv-fallback-badge lampiran-file-row-icon ' + badge.cls;
        b.textContent = badge.text;
        var t = document.createElement('div');
        t.className = 'lpv-fallback-title';
        t.textContent = name;
        var m = document.createElement('div');
        m.className = 'lpv-fallback-text';
        m.textContent = message || 'Format file ini tidak bisa ditampilkan langsung di sini. Gunakan "Buka di tab baru" untuk melihatnya.';
        box.appendChild(b); box.appendChild(t); box.appendChild(m);
        bodyEl.appendChild(box);
    }

    function renderImage(item, src, my) {
        bodyEl.className = 'lpv-body lpv-body-image';
        var img = new Image();
        img.className = 'lpv-img';
        img.alt = nameOf(item);
        img.draggable = false;
        img.title = 'Klik untuk ukuran asli / sesuaikan layar';
        img.addEventListener('click', function () { bodyEl.classList.toggle('is-zoomed'); });
        img.addEventListener('error', function () {
            if (my !== gen) return;
            renderOther(item, 'Gambar tidak dapat ditampilkan.');
        });
        img.src = src;
        bodyEl.appendChild(img);
    }

    function renderPdf(item, my) {
        bodyEl.className = 'lpv-body';
        var status = document.createElement('div');
        status.className = 'lpv-status';
        status.textContent = 'Memuat pratinjau…';
        var pages = document.createElement('div');
        pages.className = 'lpv-pages';
        bodyEl.appendChild(status);
        bodyEl.appendChild(pages);

        loadPdfJs().then(function (lib) {
            return pdfSource(item).then(function (src) { return lib.getDocument(src).promise; });
        }).then(function (pdf) {
            if (my !== gen) return;
            var total = Math.min(pdf.numPages, PDF_MAX_PAGES);
            var width = Math.max(240, Math.min(bodyEl.clientWidth - 32, 980));
            var dpr = window.devicePixelRatio || 1;
            var chain = Promise.resolve();
            var addPage = function (n) {
                chain = chain.then(function () {
                    if (my !== gen) return;
                    return pdf.getPage(n).then(function (page) {
                        if (my !== gen) return;
                        var base = page.getViewport({ scale: 1 });
                        var vp = page.getViewport({ scale: (width / base.width) * dpr });
                        var canvas = document.createElement('canvas');
                        canvas.className = 'lpv-page';
                        canvas.width = Math.ceil(vp.width);
                        canvas.height = Math.ceil(vp.height);
                        canvas.style.width = width + 'px';
                        return page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise.then(function () {
                            if (my !== gen) return;
                            if (status.parentNode) status.parentNode.removeChild(status);
                            pages.appendChild(canvas);
                        });
                    });
                });
            };
            for (var n = 1; n <= total; n++) addPage(n);
            return chain.then(function () {
                if (my !== gen) return;
                if (pdf.numPages > PDF_MAX_PAGES) {
                    var note = document.createElement('div');
                    note.className = 'lpv-note';
                    note.textContent = 'Menampilkan ' + PDF_MAX_PAGES + ' halaman pertama dari ' + pdf.numPages + ' halaman. Gunakan "Buka di tab baru" untuk melihat semuanya.';
                    bodyEl.appendChild(note);
                }
            });
        }).catch(function () {
            if (my !== gen) return;
            renderOther(item, 'Pratinjau PDF tidak dapat ditampilkan di sini. Gunakan "Buka di tab baru" untuk melihatnya.');
        });
    }

    function open(item) {
        if (!item || (!item.file && !item.url)) return;
        ensureOverlay();
        gen++;
        var my = gen;
        prevFocus = document.activeElement;
        if (overlay.hidden) prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        var name = nameOf(item);
        var badge = badgeOf(name);
        var href = hrefFor(item);
        nameEl.textContent = name;
        badgeEl.className = 'lpv-title-badge lampiran-file-row-icon ' + badge.cls;
        badgeEl.textContent = badge.text;
        openLink.href = href;
        bodyEl.innerHTML = '';
        bodyEl.className = 'lpv-body';
        bodyEl.scrollTop = 0;

        overlay.hidden = false;
        requestAnimationFrame(function () { overlay.classList.add('is-open'); });

        var k = kindOf(item);
        if (k === 'image') renderImage(item, item.url || href, my);
        else if (k === 'pdf') renderPdf(item, my);
        else renderOther(item);

        closeBtn.focus();
    }

    /* ---------- Kartu pratinjau ---------- */
    function thumbFallback(frame, name) {
        frame.innerHTML = '';
        var badge = badgeOf(name);
        var box = document.createElement('span');
        box.className = 'lpv-thumb-fallback';
        var b = document.createElement('span');
        b.className = 'lampiran-file-row-icon ' + badge.cls;
        b.textContent = badge.text;
        var t = document.createElement('span');
        t.textContent = 'Tanpa pratinjau';
        box.appendChild(b); box.appendChild(t);
        frame.appendChild(box);
    }

    function thumbPdf(item, frame, name) {
        var canvas = document.createElement('canvas');
        canvas.className = 'lpv-thumb-canvas';
        loadPdfJs().then(function (lib) {
            return pdfSource(item).then(function (src) { return lib.getDocument(src).promise; });
        }).then(function (pdf) {
            return pdf.getPage(1);
        }).then(function (page) {
            if (!frame.isConnected) return;
            var base = page.getViewport({ scale: 1 });
            var vp = page.getViewport({ scale: 320 / base.width });
            canvas.width = Math.ceil(vp.width);
            canvas.height = Math.ceil(vp.height);
            return page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise.then(function () {
                if (frame.isConnected) frame.appendChild(canvas);
            });
        }).catch(function () {
            if (frame.isConnected) thumbFallback(frame, name);
        });
    }

    function thumb(item, urlTrack) {
        var name = nameOf(item);
        var k = kindOf(item);
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'lpv-thumb';
        btn.title = 'Klik untuk memperbesar pratinjau';
        btn.setAttribute('aria-label', 'Pratinjau ' + name + ', klik untuk memperbesar');
        var frame = document.createElement('span');
        frame.className = 'lpv-thumb-frame';
        var cap = document.createElement('span');
        cap.className = 'lpv-thumb-caption';
        cap.textContent = name;
        btn.appendChild(frame);
        btn.appendChild(cap);

        if (k === 'image') {
            var src = item.url;
            if (!src && item.file) {
                src = URL.createObjectURL(item.file);
                if (urlTrack) urlTrack.push(src);
            }
            var img = new Image();
            img.alt = '';
            img.draggable = false;
            img.addEventListener('error', function () { thumbFallback(frame, name); });
            img.src = src;
            frame.appendChild(img);
        } else if (k === 'pdf') {
            thumbPdf(item, frame, name);
        } else {
            thumbFallback(frame, name);
        }
        btn.addEventListener('click', function (e) { e.preventDefault(); open(item); });
        return btn;
    }

    function renderGrid(container, items) {
        if (!container) return;
        (container.__lpvUrls || []).forEach(function (u) { URL.revokeObjectURL(u); });
        container.__lpvUrls = [];
        container.innerHTML = '';
        if (!items || !items.length) { container.hidden = true; return; }
        container.hidden = false;
        items.forEach(function (it) { container.appendChild(thumb(it, container.__lpvUrls)); });
    }

    window.cycloneLampiranPreview = { open: open, renderGrid: renderGrid };
})();
</script>
