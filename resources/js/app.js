import { db } from './firebase.js';
import './bootstrap';

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

function rapikanLoginLightTheme() {
  const root = document.documentElement;
  const apply = () => {
    const isLight = root.getAttribute('data-theme') === 'light';
    const card = document.querySelector('.login-card');
    const inputs = document.querySelectorAll('.login-input');
    if (card) {
      if (isLight) card.style.setProperty('background', '#ffffff', 'important');
      else card.style.removeProperty('background');
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
  apply();
  const themeObserver = new MutationObserver(apply);
  themeObserver.observe(root, { attributes: true, attributeFilter: ['data-theme'] });
  const domObserver = new MutationObserver(apply);
  domObserver.observe(document.body, { childList: true, subtree: true });
}

function terapkanTemaLandingPadaDashboard() {
  if (!document.querySelector('.shell')) return;
  const id = 'siberad-landing-theme-dashboard';
  if (document.getElementById(id)) return;

  const style = document.createElement('style');
  style.id = id;
  style.textContent = `
    :root:not([data-theme="light"]) {
      --bg:#06090c !important;
      --bg-deep:#030609 !important;
      --panel:#11181f !important;
      --panel-2:#151f27 !important;
      --panel-alt:#0b1218 !important;
      --border:rgba(217,146,11,.22) !important;
      --border-soft:rgba(217,146,11,.13) !important;
      --border-strong:rgba(217,146,11,.42) !important;
      --gold:#d9920b !important;
      --gold-bright:#f2b94b !important;
      --gold-dim:rgba(217,146,11,.14) !important;
      --green:#d9920b !important;
      --green-bright:#f2b94b !important;
      --green-dim:rgba(242,185,75,.14) !important;
      --amber:#e6a52b !important;
      --amber-dim:rgba(230,165,43,.15) !important;
      --text:#f5f1e8 !important;
      --text-muted:#a9a39a !important;
      --text-dim:#746e65 !important;
      --gold-solid:#d9920b !important;
      --gold-solid-bright:#f2b94b !important;
      --on-gold:#181006 !important;
      --surface:rgba(5,7,10,.92) !important;
      --hover-tint:rgba(255,255,255,.045) !important;
      color-scheme:dark;
    }

    :root:not([data-theme="light"]) body {
      background-color:#06090c !important;
      background-image:radial-gradient(ellipse 70% 45% at 15% -10%,rgba(217,146,11,.09),transparent 60%),radial-gradient(ellipse 50% 35% at 100% 10%,rgba(242,185,75,.055),transparent 60%) !important;
    }

    :root:not([data-theme="light"]) body::before {
      background-image:linear-gradient(rgba(217,146,11,.028) 1px,transparent 1px),linear-gradient(90deg,rgba(217,146,11,.028) 1px,transparent 1px) !important;
    }

    /* Paksa seluruh semantic success/green dashboard menjadi gold pada dark theme.
       Status danger/error tetap merah karena merupakan status fungsional. */
    :root:not([data-theme="light"]) .green,
    :root:not([data-theme="light"]) .text-green,
    :root:not([data-theme="light"]) .bg-green,
    :root:not([data-theme="light"]) .border-green,
    :root:not([data-theme="light"]) .success,
    :root:not([data-theme="light"]) .text-success,
    :root:not([data-theme="light"]) .bg-success,
    :root:not([data-theme="light"]) .border-success,
    :root:not([data-theme="light"]) .btn-success,
    :root:not([data-theme="light"]) .badge.green,
    :root:not([data-theme="light"]) [class~="green"],
    :root:not([data-theme="light"]) [class~="success"] {
      color:var(--gold-bright) !important;
      border-color:rgba(217,146,11,.42) !important;
      background-color:rgba(217,146,11,.14) !important;
      background-image:none !important;
      box-shadow:none !important;
    }

    :root:not([data-theme="light"]) .btn-success,
    :root:not([data-theme="light"]) button.green,
    :root:not([data-theme="light"]) a.green {
      background:#d9920b !important;
      color:#181006 !important;
      border-color:#d9920b !important;
    }

    :root:not([data-theme="light"]) .btn-success:hover,
    :root:not([data-theme="light"]) button.green:hover,
    :root:not([data-theme="light"]) a.green:hover {
      background:#f2b94b !important;
      color:#181006 !important;
      border-color:#f2b94b !important;
    }

    :root:not([data-theme="light"]) .hud-panel,
    :root:not([data-theme="light"]) .panel,
    :root:not([data-theme="light"]) .stat-card {
      box-shadow:0 1px 0 rgba(255,255,255,.025) inset,0 10px 30px rgba(0,0,0,.30);
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
