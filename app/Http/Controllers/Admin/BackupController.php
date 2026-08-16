<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    private const DISK = 'local';

    private const FOLDER = 'backups';

    /**
     * Daftar file backup yang sudah pernah dibuat, dipakai tab "Backup Database"
     * untuk menampilkan riwayat + tombol unduh.
     */
    public function index()
    {
        return collect(Storage::disk(self::DISK)->files(self::FOLDER))
            ->filter(fn ($f) => str_ends_with(strtolower($f), '.sqlite') || str_ends_with(strtolower($f), '.sql'))
            ->map(fn ($f) => [
                'nama' => basename($f),
                'path' => $f,
                'ukuran' => round(Storage::disk(self::DISK)->size($f) / 1024, 1).' KB',
                'timestamp' => Storage::disk(self::DISK)->lastModified($f),
                'tanggal' => \Illuminate\Support\Carbon::createFromTimestamp(Storage::disk(self::DISK)->lastModified($f))->translatedFormat('d M Y H:i'),
            ])
            ->sortByDesc('timestamp')
            ->values();
    }

    /**
     * Buat backup baru.
     *
     * SQLite: salin file database secara utuh.
     * MySQL: pakai mysqldump bila binary tersedia; bila tidak tersedia
     * (umum pada container PHP production), fallback ke dump native PDO
     * sehingga backup tetap bisa dibuat tanpa binary tambahan di server.
     */
    public function store(): RedirectResponse
    {
        $connection = (string) Config::get('database.default');
        $timestamp = now()->format('Y-m-d_His');
        $filename = self::FOLDER."/backup_{$timestamp}.".($connection === 'sqlite' ? 'sqlite' : 'sql');

        try {
            Storage::disk(self::DISK)->makeDirectory(self::FOLDER);

            if ($connection === 'sqlite') {
                $this->backupSqlite($filename);
            } elseif ($connection === 'mysql') {
                $this->backupMysql($filename);
            } else {
                throw new \RuntimeException("Koneksi database '{$connection}' belum didukung.");
            }

            ActivityLog::catat('backup.create', "Membuat backup database ({$connection}).");

            return back()->with('status', 'Backup database berhasil dibuat.');
        } catch (\Throwable $e) {
            Log::error('Gagal membuat backup database.', [
                'connection' => $connection,
                'exception' => $e,
            ]);

            return back()->with('error', 'Gagal membuat backup database. Periksa koneksi database dan log server.');
        }
    }

    private function backupSqlite(string $filename): void
    {
        $dbPath = (string) Config::get('database.connections.sqlite.database');

        if ($dbPath === '' || $dbPath === ':memory:') {
            throw new \RuntimeException('Database SQLite tidak menggunakan file fisik.');
        }

        if (! is_file($dbPath) || ! is_readable($dbPath)) {
            throw new \RuntimeException('File database SQLite tidak ditemukan atau tidak bisa dibaca.');
        }

        if (! Storage::disk(self::DISK)->put($filename, file_get_contents($dbPath))) {
            throw new \RuntimeException('File backup SQLite gagal ditulis ke storage.');
        }
    }

    private function backupMysql(string $filename): void
    {
        $cfg = Config::get('database.connections.mysql');
        $fullPath = Storage::disk(self::DISK)->path($filename);

        // Railway/container PHP sering tidak membawa mysqldump. Pakai binary
        // kalau tersedia karena lebih efisien, kemudian fallback ke PDO dump.
        if ($this->commandExists('mysqldump')) {
            $process = new Process([
                'mysqldump',
                '--single-transaction',
                '--quick',
                '--skip-lock-tables',
                '--routines',
                '--triggers',
                '-h', (string) $cfg['host'],
                '-P', (string) ($cfg['port'] ?? 3306),
                '-u', (string) $cfg['username'],
                '--password='.(string) ($cfg['password'] ?? ''),
                (string) $cfg['database'],
            ]);
            $process->setTimeout(600);
            $process->run();

            if ($process->isSuccessful() && trim($process->getOutput()) !== '') {
                if (file_put_contents($fullPath, $process->getOutput()) === false) {
                    throw new \RuntimeException('File backup MySQL gagal ditulis.');
                }
                return;
            }
        }

        $this->backupMysqlWithPdo($fullPath);
    }

    private function backupMysqlWithPdo(string $fullPath): void
    {
        $pdo = DB::connection('mysql')->getPdo();
        $database = (string) DB::connection('mysql')->getDatabaseName();
        $handle = fopen($fullPath, 'wb');

        if ($handle === false) {
            throw new \RuntimeException('File backup MySQL gagal dibuka untuk ditulis.');
        }

        try {
            fwrite($handle, "-- SIBERAD MySQL backup\n");
            fwrite($handle, "-- Database: ".$database."\n");
            fwrite($handle, "-- Generated: ".now()->toDateTimeString()."\n\n");
            fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($tables as $tableInfo) {
                $tableName = (string) array_values($tableInfo)[0];
                $safeTable = str_replace('`', '``', $tableName);
                $create = $pdo->query("SHOW CREATE TABLE `{$safeTable}`")->fetch(\PDO::FETCH_ASSOC);
                $createSql = (string) ($create['Create Table'] ?? '');

                if ($createSql === '') {
                    continue;
                }

                fwrite($handle, "DROP TABLE IF EXISTS `{$safeTable}`;\n");
                fwrite($handle, $createSql.";\n\n");

                $stmt = $pdo->query("SELECT * FROM `{$safeTable}`");
                $columns = $stmt->columnCount();
                $columnNames = [];

                for ($i = 0; $i < $columns; $i++) {
                    $meta = $stmt->getColumnMeta($i);
                    $name = (string) ($meta['name'] ?? $i);
                    $columnNames[] = '`'.str_replace('`', '``', $name).'`';
                }

                $columnSql = implode(', ', $columnNames);

                while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                    $values = array_map(function ($value) use ($pdo) {
                        if ($value === null) {
                            return 'NULL';
                        }

                        return $pdo->quote((string) $value);
                    }, $row);

                    fwrite($handle, "INSERT INTO `{$safeTable}` ({$columnSql}) VALUES (".implode(', ', $values).");\n");
                }

                fwrite($handle, "\n");
            }

            // Simpan definisi view setelah seluruh tabel agar dependensi tabel
            // sudah tersedia ketika backup direstore.
            $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($views as $viewInfo) {
                $viewName = (string) array_values($viewInfo)[0];
                $safeView = str_replace('`', '``', $viewName);
                $create = $pdo->query("SHOW CREATE VIEW `{$safeView}`")->fetch(\PDO::FETCH_ASSOC);
                $createSql = (string) ($create['Create View'] ?? '');

                if ($createSql !== '') {
                    fwrite($handle, "DROP VIEW IF EXISTS `{$safeView}`;\n");
                    fwrite($handle, $createSql.";\n\n");
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }

        if (! is_file($fullPath) || filesize($fullPath) === 0) {
            throw new \RuntimeException('Dump MySQL menghasilkan file kosong.');
        }
    }

    private function commandExists(string $command): bool
    {
        try {
            $process = Process::fromShellCommandline('command -v '.escapeshellarg($command));
            $process->setTimeout(5);
            $process->run();

            return $process->isSuccessful() && trim($process->getOutput()) !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Unduh salah satu file backup.
     *
     * Pakai response download berbasis path fisik supaya tidak tergantung pada
     * driver Flysystem yang mungkin berbeda di environment production.
     */
    public function download(string $filename): Response
    {
        $safeFilename = basename($filename);
        $extension = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));

        abort_unless(in_array($extension, ['sql', 'sqlite'], true), 404);

        $path = self::FOLDER.'/'.$safeFilename;
        $disk = Storage::disk(self::DISK);

        abort_unless($disk->exists($path), 404);

        $fullPath = $disk->path($path);
        abort_unless(is_file($fullPath) && is_readable($fullPath), 404);

        // Logging tidak boleh menyebabkan proses download gagal jika pencatatan
        // aktivitas sedang bermasalah.
        try {
            ActivityLog::catat('backup.download', "Mengunduh backup database \"{$safeFilename}\".");
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat aktivitas unduh backup.', [
                'filename' => $safeFilename,
                'exception' => $e,
            ]);
        }

        $mime = $extension === 'sqlite'
            ? 'application/vnd.sqlite3'
            : 'application/sql';

        return response()->download($fullPath, $safeFilename, [
            'Content-Type' => $mime,
            'Content-Length' => (string) filesize($fullPath),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
