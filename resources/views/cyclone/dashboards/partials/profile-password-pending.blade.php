{{-- Blok "Permintaan Sedang Diproses" di tab Ganti Password (Pengaturan Akun).
     Dipakai render awal (laporan-role / laporan-pimpinan) DAN dikirim sebagai
     `pending_html` di respons JSON PermintaanResetPasswordController::store()
     supaya setelah submit lewat AJAX, blok ini bisa disisipkan tanpa reload.
     Butuh $permintaan (PermintaanResetPassword). --}}
<div class="profile-pending-state" id="profilePasswordPending" data-permintaan-id="{{ $permintaan->id }}">
  <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>
  <h4>Permintaan Sedang Diproses</h4>
  <p>Diajukan {{ $permintaan->created_at->translatedFormat('d M Y H:i') }} — menunggu keputusan Admin.</p>
</div>
