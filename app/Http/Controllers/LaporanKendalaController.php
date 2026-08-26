<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanKendala;
use App\Models\Satuan;
use App\Models\User;
use App\Notifications\LaporanKendalaBaruDiterima;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Alur "Kirim Laporan" (kendala/laporan rutin) khusus 21 Kasansi (Kotama)
 * LANGSUNG ke DANPUS -- tanpa lewat Satlak. Berbeda dari LaporanController
 * (yang terikat alur Permintaan Laporan Danpus/Wadan), fitur ini bebas dikirim
 * kapan saja oleh Kasansi tanpa perlu ada permintaan laporan lebih dulu.
 *
 * Laporan kendala memakai tabel/model sendiri supaya tidak pernah bercampur
 * dengan alur Permintaan Laporan. Setelah Danpus menekan Konfirmasi pada
 * detail, record diberi tanda konfirmasi dan ditampilkan di Arsip Kendala
 * Kasansi yang terpisah.
 */
class LaporanKendalaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // Lampiran WAJIB untuk laporan kendala Kasansi -> Danpus (beda dari
        // alur "Kirim Laporan" biasa yang lampirannya opsional). Divalidasi
        // lagi di sini sebagai jaring pengaman -- validasi di frontend
        // (modal peringatan sebelum submit) bisa saja terlewat kalau ada
        // yang mengirim request langsung tanpa lewat form.
        $validated = $request->validate([
            'perihal' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string', 'max:10000'],
            'prioritas' => ['required', 'in:Tinggi,Sedang,Rendah'],
            'lampiran' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'lampiran.required' => 'Lampiran wajib diisi untuk mengirim laporan kendala ke Danpus.',
        ]);

        $user = $request->user()->load('satuan');
        $satuanAsal = $user->satuan;
        abort_unless($satuanAsal, 403, 'Akun ini belum terhubung ke satuan manapun.');
        abort_unless(
            in_array(strtoupper((string) $satuanAsal->kode), Satuan::KODE_KOTAMA, true),
            403,
            'Hanya Kasansi yang dapat mengirim laporan kendala ke Danpus.'
        );

        $tujuan = Satuan::where('kode', 'DANPUS')->firstOrFail();

        $lampiranPath = $request->hasFile('lampiran')
            ? $request->file('lampiran')->store('lampiran-kendala', 'public')
            : null;

        $kendala = LaporanKendala::create([
            'satuan_id' => $satuanAsal->id,
            'user_id' => $user->id,
            'tujuan_satuan_id' => $tujuan->id,
            'perihal' => $validated['perihal'],
            'deskripsi' => $validated['deskripsi'],
            'prioritas' => $validated['prioritas'],
            'lampiran_path' => $lampiranPath,
            'status' => LaporanKendala::STATUS_MENUNGGU,
        ]);

        foreach (User::where('satuan_id', $tujuan->id)->get() as $penerima) {
            $penerima->notify(new LaporanKendalaBaruDiterima($kendala));
        }

        ActivityLog::catat('laporan-kendala.create', "Mengirim laporan kendala \"{$kendala->perihal}\" ke {$tujuan->nama}.", $user, [
            'laporan_kendala_id' => $kendala->id,
            'tujuan_satuan' => $tujuan->nama,
            'prioritas' => $kendala->prioritas,
        ]);

        return back()->with('status', 'Laporan kendala berhasil dikirim ke '.$tujuan->nama.'.');
    }

    public function updateStatus(Request $request, LaporanKendala $laporanKendala): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Ditindaklanjuti,Selesai,Ditolak,Dikonfirmasi'],
            'catatan' => ['nullable', 'string', 'max:5000', 'required_if:status,Ditolak'],
        ], [
            'catatan.required_if' => 'Catatan penolakan wajib diisi.',
        ]);

        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403, 'Akun belum terhubung ke satuan.');

        $kodeSatuan = strtoupper((string) $satuan->kode);
        abort_unless(
            in_array($kodeSatuan, ['DANPUS', 'WADAN'], true),
            403,
            'Anda bukan penerima laporan kendala ini.'
        );

        // Konfirmasi/arsip adalah tindakan khusus Danpus. Wadan tetap boleh
        // menindaklanjuti status laporan, tetapi tidak memindahkannya ke arsip
        // penerimaan Danpus.
        if ($validated['status'] === LaporanKendala::STATUS_DIKONFIRMASI) {
            abort_unless($kodeSatuan === 'DANPUS', 403, 'Hanya Danpus yang dapat mengonfirmasi dan mengarsipkan laporan kendala.');
            abort_unless(!$laporanKendala->confirmed_at, 422, 'Laporan kendala ini sudah dikonfirmasi dan diarsipkan.');

            $laporanKendala->update([
                'status' => LaporanKendala::STATUS_DIKONFIRMASI,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);

            ActivityLog::catat('laporan-kendala.confirm', "Mengonfirmasi dan mengarsipkan laporan kendala \"{$laporanKendala->perihal}\".", $user, [
                'laporan_kendala_id' => $laporanKendala->id,
                'status' => LaporanKendala::STATUS_DIKONFIRMASI,
            ]);

            return back()->with('status', 'Laporan kendala berhasil dikonfirmasi dan dipindahkan ke Arsip Kendala Kasansi.');
        }

        abort_unless(!$laporanKendala->confirmed_at, 422, 'Laporan kendala ini sudah berada di arsip dan tidak dapat ditindaklanjuti dari daftar masuk.');

        $laporanKendala->update([
            'status' => $validated['status'],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        ActivityLog::catat('laporan-kendala.status', "Memperbarui status laporan kendala \"{$laporanKendala->perihal}\" menjadi {$laporanKendala->status}.", $user, [
            'laporan_kendala_id' => $laporanKendala->id,
            'status' => $laporanKendala->status,
        ]);

        return back()->with('status', 'Status laporan kendala berhasil diperbarui menjadi '.$laporanKendala->status.'.');
    }

    public function destroy(Request $request, LaporanKendala $laporanKendala): RedirectResponse
    {
        $user = $request->user()->load('satuan');
        $satuan = $user->satuan;
        abort_unless($satuan, 403);
        abort_unless((int) $laporanKendala->satuan_id === (int) $satuan->id, 403);

        if ($laporanKendala->lampiran_path) {
            Storage::disk('public')->delete($laporanKendala->lampiran_path);
        }
        $perihal = $laporanKendala->perihal;
        $laporanKendala->delete();

        ActivityLog::catat('laporan-kendala.delete', "Menghapus laporan kendala \"{$perihal}\" dari riwayat.", $user, [
            'laporan_kendala_id' => $laporanKendala->id,
        ]);

        return back()->with('status', 'Laporan kendala berhasil dihapus.');
    }
}
