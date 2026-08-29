<?php

namespace App\Http\Controllers;

use App\Models\LaporanKendalaTembusan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sisi PENERIMA tembusan (4 Satlak/4 Sdir) laporan kendala Kasansi.
 * Satu-satunya aksi yang tersedia di sini adalah menandai "sudah dibaca" --
 * penerima tembusan TIDAK bisa menindaklanjuti/menolak/mengonfirmasi,
 * wewenang itu tetap murni milik DANPUS lewat LaporanKendalaController.
 */
class LaporanKendalaTembusanController extends Controller
{
    public function tandaiDibaca(Request $request, LaporanKendalaTembusan $laporanKendalaTembusan): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');
        abort_unless(
            (int) $laporanKendalaTembusan->satuan_id === (int) $satuan->id,
            403,
            'Tembusan ini bukan untuk satuan Anda.'
        );

        // dibaca_at dicatat PER SATUAN, bukan per user -- begitu satuan ini
        // pertama kali menandai dibaca, pengguna lain di satuan yang sama
        // tidak perlu menandai ulang, jadi cukup idempotent (tidak
        // menimpa dibaca_at/dibaca_oleh yang sudah ada).
        if (! $laporanKendalaTembusan->dibaca_at) {
            $laporanKendalaTembusan->update([
                'dibaca_at' => now(),
                'dibaca_oleh' => $user->id,
            ]);
        }

        return back()->with('status', 'Tembusan ditandai sudah dibaca.');
    }
}
