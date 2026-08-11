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
// Input tetap abu-abu sangat muda agar bidang input masih terbaca jelas,
// tetapi tidak sepekat warna lama yang masih bernuansa cream.
function rapikanLoginLightTheme() {
  if (document.getElementById('login-light-theme-fix')) return;

  const style = document.createElement('style');
  style.id = 'login-light-theme-fix';
  style.textContent = `
    html[data-theme="light"] .login-card {
      background: #ffffff !important;
    }

    html[data-theme="light"] .login-input {
      background: #f3f4f6 !important;
      border-color: #cfd4d9 !important;
    }

    html[data-theme="light"] .login-input:focus {
      background: #f5f6f7 !important;
    }
  `;

  document.head.appendChild(style);
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
