#!/usr/bin/env bash
set -e

# SQLite di filesystem ephemeral cuma dipakai kalau DB_CONNECTION=sqlite
# (fallback lokal/demo). Untuk production disarankan pakai DB_CONNECTION=mysql
# yang nunjuk ke service MySQL Railway (persisten via volume MySQL itu sendiri).
DB_CONNECTION="${DB_CONNECTION:-sqlite}"
if [ "$DB_CONNECTION" = "sqlite" ]; then
  DB_PATH="${DB_DATABASE:-/app/database/database.sqlite}"
  mkdir -p "$(dirname "$DB_PATH")"
  touch "$DB_PATH"
fi

echo "==> [1/5] config:clear"
php artisan config:clear
echo "==> [1/5] OK"

# Regenerate cache discovery package (bootstrap/cache/packages.php &
# services.php) di setiap container start. File ini sengaja TIDAK
# di-commit ke git (lihat .gitignore) supaya production selalu pakai
# daftar provider yang sesuai dependency production (--no-dev) yang
# benar-benar ter-install, bukan snapshot lama yang bisa saja masih
# menyertakan provider dev-only seperti Laravel Pail.
#
# Dibungkus `timeout` supaya kalau step ini macet (mis. karena provider
# tertentu nyambung ke sesuatu saat boot), container TETAP lanjut ke
# php artisan serve di bawah alih-alih nge-hang selamanya dan bikin situs
# 502 total tanpa sebab yang kelihatan di log. `set +e`/`set -e` di sekitarnya
# supaya exit code timeout tidak langsung mematikan script (karena `set -e`
# aktif di atas).
echo "==> [2/5] package:discover"
set +e
timeout 30 php artisan package:discover --ansi
DISCOVER_EXIT=$?
set -e
echo "==> [2/5] exit code: $DISCOVER_EXIT"

echo "==> [3/5] migrate --force"
set +e
timeout 60 php artisan migrate --force -v
MIGRATE_EXIT=$?
set -e
echo "==> [3/5] exit code: $MIGRATE_EXIT"

echo "==> [4/5] storage:link"
php artisan storage:link || true
echo "==> [4/5] OK"

# --no-reload WAJIB ada supaya PHP_CLI_SERVER_WORKERS (di-set lewat Railway
# variables) beneran dipakai -- tanpa flag ini, Laravel diam-diam nolak
# multi-worker & cuma jalanin 1 proses server (lihat warning "Unable to
# respect the PHP_CLI_SERVER_WORKERS environment variable without the
# --no-reload flag" di php artisan serve). Tanpa worker lebih dari 1, request
# yang ditahan lama (mis. long-polling) bisa nge-block SELURUH situs karena
# cuma ada 1 proses yang gantian ngelayani semua orang.
#
# -d upload_max_filesize & post_max_size: PHP built-in server (php artisan serve)
# defaultnya ikut php.ini sistem yang bisa saja hanya 2MB atau 8MB -- jauh di
# bawah batas 10 MB yang kita izinkan di validasi Laravel. Akibatnya file yang
# ukurannya di antara batas PHP dan batas Laravel dianggap "gagal upload" oleh
# PHP SEBELUM request bahkan sampai ke controller, sehingga Laravel melempar
# error "The lampiran.0 failed to upload." bukan pesan validasi yang bermakna.
# Solusi: paksa batas PHP sama dengan batas aplikasi.
#
# upload_max_filesize=110M -- HARUS >= batas terbesar single-file di aplikasi.
# Sejak fitur "Latar Belakang Video Beranda" (hero_video, lihat
# SettingController::updateLanding) dinaikkan ke 100 MB, 110M dikasih ruang
# lebih supaya tidak mepet.
# post_max_size=120M -- total ukuran SEMUA field dalam satu request (video
# 100MB + gambar 5MB bisa saja terkirim BERSAMAAN kalau Admin ganti keduanya
# sekaligus di form Pengaturan Umum, plus overhead multipart boundary).
# memory_limit=512M -- dinaikkan dari 256M karena PHP built-in server memuat
# seluruh isi upload video (sampai ~100 MB) ke memori saat memproses request
# multipart; 256M terlalu mepet dan berisiko fatal error "Allowed memory
# size exhausted" pas Admin upload video mendekati batas maksimal.
#
# Fallback port 8080 (BUKAN 8000) -- ini harus SAMA PERSIS dengan "Target port"
# domain publik di Railway (Settings > Networking). Kalau $PORT dari Railway
# ternyata tidak ke-set dan fallback-nya beda dari target port domain, proxy
# Railway akan connect ke port yang tidak ada yang dengar (nobody listening),
# dan hasilnya "Application failed to respond" walau app-nya sendiri hidup.
PORT="${PORT:-8080}"
echo "==> [5/5] starting php artisan serve on 0.0.0.0:${PORT}"
# max_input_time=300 -- waktu (detik) yang diizinkan PHP untuk MENERIMA
# data request dari klien (termasuk membaca body upload). Default PHP adalah
# 60 detik -- terlalu singkat untuk upload video mendekati 100 MB di koneksi
# lambat; request akan di-cut oleh PHP sebelum file selesai diterima dan
# hasilnya 500 / "failed to upload" tanpa pesan yang bermakna.
# max_execution_time=300 -- waktu (detik) untuk MEMPROSES request setelah
# data diterima (validasi ffprobe + store file ke disk). Default 30 detik
# juga terlalu mepet untuk file besar di volume Railway yang lambat I/O-nya.
# 300 detik (5 menit) memberi ruang lebih dari cukup untuk keduanya.
exec php -d upload_max_filesize=110M -d post_max_size=120M -d memory_limit=512M -d max_input_time=300 -d max_execution_time=300 artisan serve --host=0.0.0.0 --port="${PORT}" --no-reload
