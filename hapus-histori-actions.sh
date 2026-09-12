#!/usr/bin/env bash
# ==============================================================================
# Hapus histori run GitHub Actions yang lama (terutama status Failure/Cancelled)
# untuk repo KP_UNJANI_SI_2026, branch refactor-fokus-laporan.
#
# CARA PAKAI:
#   1. Install GitHub CLI kalau belum ada: https://cli.github.com/
#   2. Login dulu (interaktif, token TIDAK perlu ditulis di script ini):
#        gh auth login
#   3. Jalankan script ini:
#        chmod +x hapus-histori-actions.sh
#        ./hapus-histori-actions.sh
#
# Script ini akan menghapus SEMUA run dari 3 workflow yang sudah dihapus
# (fix-landing-editor.yml, fix-pimpinan-submenu.yml, landing-panels-once.yml),
# lalu menghapus semua run "Laravel CI" yang berstatus failure/cancelled.
# Run yang SUKSES (hijau) TIDAK ikut dihapus.
# ==============================================================================

set -euo pipefail

REPO="jhonata031224-sudo/KP_UNJANI_SI_2026"

echo "Mengecek autentikasi gh CLI..."
gh auth status || { echo "Jalankan 'gh auth login' dulu."; exit 1; }

delete_runs_for_workflow () {
  local workflow_file="$1"
  echo ""
  echo "=== Menghapus semua run dari: $workflow_file ==="
  # Ambil semua run id untuk workflow ini (termasuk yang sudah tidak ada file-nya),
  # paginate 100 per halaman sampai habis.
  gh run list --repo "$REPO" --workflow "$workflow_file" --limit 1000 --json databaseId \
    --jq '.[].databaseId' 2>/dev/null | while read -r run_id; do
      echo "Menghapus run id $run_id ..."
      gh run delete "$run_id" --repo "$REPO" 2>/dev/null || echo "  (gagal/sudah terhapus, lanjut)"
  done
}

delete_failed_runs_for_workflow () {
  local workflow_file="$1"
  echo ""
  echo "=== Menghapus run FAILURE/CANCELLED dari: $workflow_file ==="
  gh run list --repo "$REPO" --workflow "$workflow_file" --limit 1000 \
    --json databaseId,conclusion \
    --jq '.[] | select(.conclusion=="failure" or .conclusion=="cancelled") | .databaseId' 2>/dev/null \
    | while read -r run_id; do
      echo "Menghapus run id $run_id ..."
      gh run delete "$run_id" --repo "$REPO" 2>/dev/null || echo "  (gagal/sudah terhapus, lanjut)"
  done
}

# 1) Workflow yang sudah dihapus dari kode -- hapus SEMUA histori run-nya.
delete_runs_for_workflow "fix-landing-editor.yml"
delete_runs_for_workflow "fix-pimpinan-submenu.yml"
delete_runs_for_workflow "landing-panels-once.yml"

# 2) Laravel CI -- hapus yang gagal/dibatalkan saja, sisakan yang sukses (hijau).
delete_failed_runs_for_workflow "laravel.yml"

echo ""
echo "Selesai. Refresh halaman Actions di GitHub untuk lihat hasilnya."
