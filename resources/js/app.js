import { db } from './firebase.js';
import './bootstrap';

// Pastikan logo PUSSIBERAD selalu tampil pada loader awal landing page.
// Jangan mengubah src jika logo sudah ada di markup karena hal itu dapat
// memicu request/decode ulang tepat setelah DOM siap dan membuat logo
// terlihat terlambat saat refresh.
function pastikanLogoLoaderTampil() {
  const loader = document.getElementById('loader');
  if (!loader) return;

  const plate = loader.querySelector('.mark-plate');
  if (!plate) return;

  let logo = plate.querySelector('img');
  if (!logo) {
    logo = document.createElement('img');
    logo.src = '/images/logo-pussiberad.jpg';
    logo.alt = 'Lambang Pussiberad';
    plate.appendChild(logo);
  }

  // Pertahankan src dari markup/preload yang sudah dimuat browser.
  // Atribut ini membuat browser memprioritaskan gambar loader sejak awal.
  logo.loading = 'eager';
  logo.fetchPriority = 'high';
  logo.decoding = 'sync';
  logo.style.setProperty('display', 'block', 'important');
  logo.style.setProperty('visibility', 'visible', 'important');
  logo.style.setProperty('opacity', '1', 'important');
  logo.style.setProperty('width', '100%', 'important');
  logo.style.setProperty('height', '100%', 'important');
  logo.style.setProperty('object-fit', 'cover', 'important');
  logo.style.setProperty('position', 'relative', 'important');
  logo.style.setProperty('z-index', '2', 'important');

  plate.style.setProperty('display', 'flex', 'important');
  plate.style.setProperty('align-items', 'center', 'important');
  plate.style.setProperty('justify-content', 'center', 'important');
}

// Pada tema light, kartu login harus benar-benar putih seperti form admin.
// Input tetap abu-abu sangat muda agar bidang input masih terbaca jelas.
// Terapkan lewat inline style + observer agar tetap benar walaupun atribut
// data-theme dipasang setelah module Vite selesai dieksekusi.
function rapikanLoginLightTheme() {
  const root = document.documentElement;
  const apply = () => {
    const isLight = root.getAttribute('data-theme') === 'light';
    const card = document.querySelector('.login-card');
    const inputs = document.querySelectorAll('.login-input');

    if (card) {
      if (isLight) {
        card.style.setProperty('background', '#ffffff', 'important');
      } else {
        card.style.removeProperty('background');
      }
    }

    inputs.forEach((input) => {
      if (isLight) {
        input.style.setProperty('background', '#f3f4f6', 'important');
        input.style.setProperty('border-color', '#cfd4d9', 'important');
      } else {
        input.style.removeProperty('background');
        input.style.removeProperty('border-color');
      }
    });
  };

  // Jalankan sekarang, lalu ulangi setelah DOM/theme berubah.
  apply();

  const themeObserver = new MutationObserver(apply);
  themeObserver.observe(root, { attributes: true, attributeFilter: ['data-theme'] });

  const domObserver = new MutationObserver(apply);
  domObserver.observe(document.body, { childList: true, subtree: true });
}

// Samakan dark theme seluruh dashboard role dengan landing page terbaru:
// obsidian/black sebagai dasar dan warm gold sebagai aksen SIBERAD.
// Dibuat sebagai override terpusat supaya semua dashboard yang memakai
// partial dash-styles mendapat palet yang sama tanpa menyentuh layout/sidebar
// yang sebelumnya sudah diperbaiki.
function terapkanTemaLandingPadaDashboard() {
  if (!document.querySelector('.shell')) return;

  const id = 'siberad-landing-theme-dashboard';
  if (document.getElementById(id)) return;

  const style = document.createElement('style');
  style.id = id;
  style.textContent = `
    :root:not([data-theme="light"]) {
      --bg:#080b0f;
      --bg-deep:#030506;
      --panel:#11161c;
      --panel-2:#171d24;
      --panel-alt:#0c1117;
      --border:rgba(217,146,11,.26);
      --border-soft:rgba(217,146,11,.14);
      --border-strong:rgba(217,146,11,.48);
      --gold:#d9920b;
      --gold-bright:#f2b94b;
      --gold-dim:rgba(217,146,11,.14);
      --green:#d9920b;
      --green-bright:#f2b94b;
      --green-dim:rgba(217,146,11,.14);
      --amber:#e6a52b;
      --amber-dim:rgba(230,165,43,.15);
      --red:#d45b52;
      --red-dim:rgba(212,91,82,.16);
      --text:#f5f2e9;
      --text-muted:#aeb3b1;
      --text-dim:#7d8585;
      --gold-solid:#d9920b;
      --gold-solid-bright:#f2b94b;
      --on-gold:#181006;
      --surface:rgba(5,7,10,.92);
      --hover-tint:rgba(255,255,255,.045);
      color-scheme:dark;
    }

    :root:not([data-theme="light"]) body {
      background-color:var(--bg) !important;
      background-image:
        radial-gradient(ellipse 70% 45% at 15% -10%, rgba(217,146,11,.09), transparent 60%),
        radial-gradient(ellipse 50% 35% at 100% 10%, rgba(242,185,75,.055), transparent 60%) !important;
    }

    :root:not([data-theme="light"]) body::before {
      background-image:
        linear-gradient(rgba(217,146,11,.028) 1px, transparent 1px),
        linear-gradient(90deg, rgba(217,146,11,.028) 1px, transparent 1px) !important;
    }

    :root:not([data-theme="light"]) .hud-panel,
    :root:not([data-theme="light"]) .panel,
    :root:not([data-theme="light"]) .stat-card {
      box-shadow:0 1px 0 rgba(255,255,255,.025) inset, 0 10px 30px rgba(0,0,0,.30);
    }

    :root:not([data-theme="light"]) .badge.green {
      background:var(--gold-dim);
      color:var(--gold-bright);
      border-color:var(--border);
    }
  `;
  document.head.appendChild(style);
}

function inisialisasiLandingLightTheme() {
  pastikanLogoLoaderTampil();
  rapikanLoginLightTheme();
  terapkanTemaLandingPadaDashboard();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', inisialisasiLandingLightTheme);
} else {
  inisialisasiLandingLightTheme();
}
