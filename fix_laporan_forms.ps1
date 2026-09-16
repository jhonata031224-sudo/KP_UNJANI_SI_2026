# Script: fix_laporan_forms.ps1
# Fungsi: Mengganti form Tambah Laporan prototype menjadi form yang tersambung ke database

$folder = "resources\views\siberad\dashboards"
$files = Get-ChildItem -Path $folder -Filter "*.blade.php" -Recurse

$oldForm = @'
          <form class="form-grid" id="formTambahLaporan" style="padding:22px;" novalidate>
            <div class="form-field">
              <label for="personelTambahLaporan">Personel Terkait</label>
              <select id="personelTambahLaporan" required>
                @foreach($penempatan as $p)
                  <option>{{ $p['nama'] }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-field">
              <label for="prioritasTambahLaporan">Prioritas</label>
              <select id="prioritasTambahLaporan" required>
                <option>Tinggi</option><option>Sedang</option><option>Rendah</option>
              </select>
            </div>
            <div class="form-field full">
              <label for="perihalTambahLaporan">Perihal</label>
              <input id="perihalTambahLaporan" type="text" placeholder="Contoh: Pengajuan penempatan personel baru" required>
            </div>
            <div class="form-field full">
              <label for="deskripsiTambahLaporan">Deskripsi</label>
              <textarea id="deskripsiTambahLaporan" rows="4" placeholder="Jelaskan kronologi dan dampaknya..." required></textarea>
            </div>
            <div class="form-field full">
              <label for="lampiranTambahLaporan">Lampiran (bukti / dokumentasi)</label>
              <input id="lampiranTambahLaporan" type="file" accept="application/pdf,.pdf">
              <span class="form-hint">Format PDF, maksimal 20 MB.</span>
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="button" onclick="alert('Prototype — form Tambah Laporan belum tersambung ke database.')">Simpan Laporan</button>
            </div>
          </form>
'@

$newForm = @'
          @if(session('status'))
            <div class="alert alert-success" style="margin-bottom:16px;padding:12px 16px;background:var(--green-dim,#14532d33);border:1px solid var(--green,#22c55e);border-radius:8px;color:var(--green,#22c55e);font-size:13.5px;">
              {{ session('status') }}
            </div>
          @endif
          <form class="form-grid" method="POST" action="{{ route('laporan.store') }}" enctype="multipart/form-data" style="padding:22px;">
            @csrf
            <div class="form-field">
              <label for="prioritasTambahLaporan">Prioritas</label>
              <select id="prioritasTambahLaporan" name="prioritas" required>
                <option value="Tinggi">Tinggi</option>
                <option value="Sedang" selected>Sedang</option>
                <option value="Rendah">Rendah</option>
              </select>
            </div>
            <div class="form-field full">
              <label for="perihalTambahLaporan">Perihal</label>
              <input id="perihalTambahLaporan" name="perihal" type="text" placeholder="Contoh: Pengajuan penempatan personel baru" required>
              @error('perihal')<span class="form-hint" style="color:var(--red);">{{ $message }}</span>@enderror
            </div>
            <div class="form-field full">
              <label for="deskripsiTambahLaporan">Deskripsi</label>
              <textarea id="deskripsiTambahLaporan" name="deskripsi" rows="4" placeholder="Jelaskan kronologi dan dampaknya..." required></textarea>
              @error('deskripsi')<span class="form-hint" style="color:var(--red);">{{ $message }}</span>@enderror
            </div>
            <div class="form-field full">
              <label for="lampiranTambahLaporan">Lampiran (bukti / dokumentasi)</label>
              <input id="lampiranTambahLaporan" name="lampiran" type="file" accept="application/pdf,.pdf">
              <span class="form-hint">Format PDF, maksimal 20 MB.</span>
              @error('lampiran')<span class="form-hint" style="color:var(--red);">{{ $message }}</span>@enderror
            </div>
            <div class="form-field full">
              <button class="btn btn-primary" type="submit">Simpan Laporan</button>
            </div>
          </form>
'@

$count = 0
foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    if ($content -match [regex]::Escape("alert('Prototype — form Tambah Laporan belum tersambung ke database.')")) {
        $content = $content.Replace($oldForm, $newForm)
        Set-Content $file.FullName $content -Encoding UTF8 -NoNewline
        Write-Host "✅ Fixed: $($file.Name)"
        $count++
    }
}

Write-Host ""
Write-Host "Selesai! $count file diupdate."
