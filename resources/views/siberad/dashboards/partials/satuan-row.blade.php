{{-- Satu baris tabel Data Satuan (#tblSatuan). Dipakai render awal (@forelse
     di admin.blade.php) DAN dikirim sebagai row_html di respons JSON
     SatuanController::store()/update() supaya sesudah submit AJAX baris-nya
     bisa disisipkan/diganti tanpa reload. Butuh $s (Satuan, sebaiknya sudah
     withCount('users')). --}}
@php
  $kategoriFilterLabel = match ($s->kategori) {
    \App\Models\Satuan::KATEGORI_ADMIN => 'Admin',
    \App\Models\Satuan::KATEGORI_PIMPINAN => 'Pimpinan',
    \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
    \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
    \App\Models\Satuan::KATEGORI_DIREKTORAT => 'Direktorat',
    \App\Models\Satuan::KATEGORI_KOTAMA => 'Kasansi',
    default => 'Satlak',
  };
@endphp
<tr data-satuan-id="{{ $s->id }}" data-filter-value="{{ $kategoriFilterLabel }}">
  <td><span class="badge">{{ $s->kode }}</span></td>
  <td>{{ $s->nama }}</td>
  <td style="color:var(--text-muted);">{{ $kategoriFilterLabel }}</td>
  <td>{{ $s->users_count ?? $s->users()->count() }}</td>
  <td>
    <div class="btn-row">
      <button class="table-action-btn edit" type="button" onclick="bukaUbahSatuan(this)"
        data-action="{{ route('admin.satuan.update', $s) }}"
        data-satuan-id="{{ $s->id }}"
        data-kode="{{ $s->kode }}"
        data-nama="{{ $s->nama }}"
        data-kategori="{{ $s->kategori }}"
        data-deskripsi="{{ $s->deskripsi }}">Ubah</button>
      {{-- Satuan kategori Admin tidak pernah bisa dihapus (selalu punya akun
           admin terdaftar -> SatuanController::destroy nolak), jadi tombol
           Hapus-nya sekalian tidak dirender. --}}
      @if($s->kategori !== \App\Models\Satuan::KATEGORI_ADMIN)
      <button class="table-action-btn danger" type="button" onclick="bukaHapusSatuan(this)"
        data-action="{{ route('admin.satuan.destroy', $s) }}"
        data-nama="{{ $s->nama }}">Hapus</button>
      @endif
    </div>
  </td>
</tr>
