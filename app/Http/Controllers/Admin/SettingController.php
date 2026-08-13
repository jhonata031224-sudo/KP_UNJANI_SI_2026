<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function updateLanding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero_eyebrow'=>['nullable','string','max:255'],
            'hero_judul_awal'=>['nullable','string','max:50'],
            'hero_judul_aksen'=>['nullable','string','max:50'],
            'hero_subjudul'=>['nullable','string','max:255'],
            'hero_deskripsi'=>['nullable','string','max:2000'],
            'hero_image'=>['nullable','image','max:5120'],
            'logo_file'=>['nullable','image','max:5120'],
            'fitur'=>['required','array','size:4'],
            'fitur.*.judul'=>['required','string','max:100'],
            'fitur.*.deskripsi'=>['required','string','max:500'],
            'tentang_deskripsi'=>['nullable','string','max:4000'],
            'tentang_moto_judul'=>['nullable','string','max:255'],
            'tentang_moto_deskripsi'=>['nullable','string','max:2000'],
            'alamat'=>['nullable','string','max:1000'],
            'email_kontak'=>['nullable','email','max:255'],
            'telepon_kontak'=>['nullable','string','max:30'],
            'website'=>['nullable','url','max:255'],
            'sosial_media'=>['required','array'],
            'sosial_media.*.platform'=>['required','string','max:30'],
            'sosial_media.*.label'=>['nullable','string','max:100'],
            'sosial_media.*.url'=>['nullable','url','max:500'],
            'landing_content'=>['nullable','string','max:30000'],
        ]);

        $pengaturan = Pengaturan::current();

        if ($request->hasFile('hero_image')) {
            if ($pengaturan->hero_image_path) Storage::disk('public')->delete($pengaturan->hero_image_path);
            $validated['hero_image_path'] = $request->file('hero_image')->store('pengaturan', 'public');
        }
        if ($request->hasFile('logo_file')) {
            if ($pengaturan->logo_path) Storage::disk('public')->delete($pengaturan->logo_path);
            $validated['logo_path'] = $request->file('logo_file')->store('pengaturan', 'public');
        }

        if (!empty($validated['landing_content'])) {
            $decoded = json_decode($validated['landing_content'], true);
            if (is_array($decoded)) $validated['landing_content'] = $decoded;
            else unset($validated['landing_content']);
        } else {
            unset($validated['landing_content']);
        }

        unset($validated['hero_image'], $validated['logo_file']);
        $pengaturan->update($validated);
        ActivityLog::catat('pengaturan.landing.update', 'Memperbarui seluruh konten halaman landing (branding, navigasi, beranda, fitur, tentang, kontak, footer).');

        return back()->with('status', 'Konten halaman landing berhasil disimpan.');
    }
}
