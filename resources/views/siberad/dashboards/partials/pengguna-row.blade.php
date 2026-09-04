{{-- Satu baris tabel Daftar Pengguna (#tblPengguna). Dipakai render awal
     (@foreach di admin.blade.php) DAN dikirim sebagai rows_html di respons
     JSON UserController::store()/update()/destroy() supaya tabel-nya bisa
     sinkron tanpa reload (modal Tambah/Ubah tetap kebuka setelah submit,
     baris baru/berubah mendarat di posisi sesuai jenjang organisasi).
     Butuh $p (User, sebaiknya sudah with('satuan')) dan $authUserId (id admin
     yang sedang login -- tombol Hapus disembunyikan untuk baris akun sendiri). --}}
@php
  $kategoriLabel = match ($p->satuan->kategori ?? null) {
    \App\Models\Satuan::KATEGORI_ADMIN => 'Admin',
    \App\Models\Satuan::KATEGORI_PIMPINAN => 'Pimpinan',
    \App\Models\Satuan::KATEGORI_UNSUR_PELAYANAN => 'Unsur Pelayanan',
    \App\Models\Satuan::KATEGORI_UNSUR_PEMBANTU_PIMPINAN => 'Unsur Pembantu Pimpinan',
    \App\Models\Satuan::KATEGORI_DIREKTORAT => 'Direktorat',
    \App\Models\Satuan::KATEGORI_KOTAMA => 'Kasansi',
    default => 'Satlak',
  };
@endphp
<tr data-user-id="{{ $p->id }}" data-filter-value="{{ $kategoriLabel }}" data-search-value="{{ strtolower($p->name.' '.($p->satuan->nama ?? '').' '.($p->satuan->kode ?? '')) }}">
  <td>{{ $p->name }}</td>
  <td><span class="badge badge-plain">{{ $p->username }}</span></td>
  <td style="color:var(--text-muted);">{{ $p->email ?: '-' }}</td>
  <td>{{ $p->satuan->nama_keterangan ?? '-' }}</td>
  <td>
    <div class="btn-row">
      <button class="table-action-btn edit" type="button" onclick="bukaUbahPengguna(this)"
        data-action="{{ route('admin.users.update', $p) }}"
        data-user-id="{{ $p->id }}"
        data-name="{{ $p->name }}"
        data-username="{{ $p->username }}"
        data-email="{{ $p->email }}"
        data-satuan-id="{{ $p->satuan_id }}">Ubah</button>
      @if($p->id !== $authUserId)
      <button class="table-action-btn danger" type="button" onclick="bukaHapusPengguna(this)"
        data-action="{{ route('admin.users.destroy', $p) }}"
        data-nama="{{ $p->name }}">Hapus</button>
      @endif
    </div>
  </td>
</tr>
