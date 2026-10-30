<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupFileController extends Controller
{
    private const DISK = 'local';
    private const FOLDER = 'backups';
    private const MAX_UPLOAD_KB = 51200; // 50 MB

    /**
     * Upload file backup .sql/.sqlite ke storage backup aplikasi.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup_file' => [
                'required',
                'file',
                'max:'.self::MAX_UPLOAD_KB,
            ],
        ], [
            'backup_file.required' => 'File backup wajib dipilih.',
            'backup_file.file' => 'File backup tidak valid.',
            'backup_file.max' => 'Ukuran backup maksimal 50 MB.',
        ]);

        $file = $validated['backup_file'];
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, ['sql', 'sqlite'], true)) {
            return back()->with('error', 'Format backup hanya boleh .sql atau .sqlite.');
        }

        $originalBase = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^A-Za-z0-9_-]+/', '_', $originalBase) ?: 'backup';
        $filename = 'uploaded_'.now()->format('Y-m-d_His').'_'.$safeBase.'.'.$extension;
        $path = self::FOLDER.'/'.$filename;

        try {
            Storage::disk(self::DISK)->makeDirectory(self::FOLDER);
            $stored = Storage::disk(self::DISK)->putFileAs(self::FOLDER, $file, $filename);

            if ($stored === false) {
                throw new \RuntimeException('File backup gagal disimpan.');
            }

            ActivityLog::catat('backup.upload', "Mengunggah backup database \"{$filename}\".");

            return back()->with('status', 'Backup berhasil diunggah.');
        } catch (\Throwable $e) {
            Log::error('Gagal mengunggah backup database.', [
                'filename' => $filename,
                'exception' => $e,
            ]);

            return back()->with('error', 'Gagal mengunggah backup. Periksa storage server.');
        }
    }

    /**
     * Hapus file backup dari storage.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $safeFilename = basename($filename);
        $extension = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));

        abort_unless(in_array($extension, ['sql', 'sqlite'], true), 404);

        $path = self::FOLDER.'/'.$safeFilename;
        $disk = Storage::disk(self::DISK);
        abort_unless($disk->exists($path), 404);

        try {
            if (! $disk->delete($path)) {
                throw new \RuntimeException('File backup gagal dihapus.');
            }

            try {
                ActivityLog::catat('backup.delete', "Menghapus backup database \"{$safeFilename}\".");
            } catch (\Throwable $e) {
                Log::warning('Gagal mencatat aktivitas hapus backup.', [
                    'filename' => $safeFilename,
                    'exception' => $e,
                ]);
            }

            return back()->with('status', 'Backup berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus backup database.', [
                'filename' => $safeFilename,
                'exception' => $e,
            ]);

            return back()->with('error', 'Backup gagal dihapus.');
        }
    }
}
