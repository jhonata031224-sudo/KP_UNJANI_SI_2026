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

function inisialisasiLandingLightTheme() {
  pastikanLogoLoaderTampil();
  rapikanLoginLightTheme();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', inisialisasiLandingLightTheme);
} else {
  inisialisasiLandingLightTheme();
}
