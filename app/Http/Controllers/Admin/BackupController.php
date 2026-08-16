<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
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
            ->map(function ($f) {
                $waktu = \Illuminate\Support\Carbon::createFromTimestamp(Storage::disk(self::DISK)->lastModified($f));

                return [
                    'nama' => basename($f),
                    'path' => $f,
                    'ukuran' => round(Storage::disk(self::DISK)->size($f) / 1024, 1).' KB',
                    'timestamp' => Storage::disk(self::DISK)->lastModified($f),
                    'tanggal' => $waktu->translatedFormat('d M Y'),
                    'tanggal_iso' => $waktu->format('Y-m-d'),
                    'jam' => $waktu->translatedFormat('H:i'),
                ];
            })
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
        $extension = $connection === 'sqlite' ? 'sqlite' : 'sql';

        try {
            Storage::disk(self::DISK)->makeDirectory(self::FOLDER);

            $filename = self::FOLDER.'/'.$this->nextBackupFilename($extension);

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

    /**
     * Nama file backup memakai pola "siberad.ext", "siberad-1.ext",
     * "siberad-2.ext", dst. -- tanpa tanggal di nama file karena tanggal
     * sudah ditampilkan di kolom terpisah pada riwayat backup. Setiap
     * backup baru tetap dapat nama file unik supaya riwayat sebelumnya
     * tidak tertimpa.
     */
    private function nextBackupFilename(string $extension): string
    {
        $pattern = '/^siberad(?:-(\d+))?\.'.preg_quote($extension, '/').'$/i';

        $maxSuffix = collect(Storage::disk(self::DISK)->files(self::FOLDER))
            ->map(fn ($f) => basename($f))
            ->filter(fn ($name) => preg_match($pattern, $name) === 1)
            ->map(function ($name) use ($pattern) {
                preg_match($pattern, $name, $m);

                return isset($m[1]) && $m[1] !== '' ? (int) $m[1] : 0;
            })
            ->max();

        if ($maxSuffix === null) {
            return "siberad.{$extension}";
        }

        return 'siberad-'.($maxSuffix + 1).".{$extension}";
    }

    /**
     * Tampilkan isi SQL secara aman di browser. Hanya preview teks,
     * tidak pernah mengeksekusi SQL.
     */
    public function view(string $filename): Response
    {
        $path = $this->validatedBackupPath($filename, ['sql', 'sqlite']);
        $disk = Storage::disk(self::DISK);
        $fullPath = $disk->path($path);

        abort_unless(is_file($fullPath) && is_readable($fullPath), 404);

        $maxBytes = 2 * 1024 * 1024;
        $content = file_get_contents($fullPath, false, null, 0, $maxBytes);
        $content = $content === false ? '' : $content;

        if ((int) filesize($fullPath) > $maxBytes) {
            $content .= "\n\n-- [Preview dibatasi 2 MB. Unduh file untuk melihat isi lengkap.] --\n";
        }

        return response($content, 200, [
            'Content-Type' => $pathinfo = (pathinfo($fullPath, PATHINFO_EXTENSION) === 'sqlite'
                ? 'application/vnd.sqlite3'
                : 'text/plain; charset=UTF-8'),
            'Content-Disposition' => 'inline; filename="'.basename($safeFilename = $filename).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Restore backup SQL ke database aktif.
     * Sebelum restore dibuat safety backup otomatis agar keadaan sebelum restore
     * masih dapat dipulihkan.
     */
    public function restore(string $filename): RedirectResponse
    {
        $connection = (string) Config::get('database.default');

        if ($connection !== 'mysql') {
            return back()->with('error', 'Restore saat ini hanya tersedia untuk database MySQL.');
        }

        try {
            $path = $this->validatedBackupPath($filename, ['sql']);
            $disk = Storage::disk(self::DISK);
            $fullPath = $disk->path($path);

            abort_unless(is_file($fullPath) && is_readable($fullPath), 404);

            Storage::disk(self::DISK)->makeDirectory(self::FOLDER);
            $safetyFilename = self::FOLDER.'/pre_restore_'.now()->format('Y-m-d_His').'.sql';
            $this->backupMysql($safetyFilename);

            $this->restoreMysql($fullPath);

            ActivityLog::catat('backup.restore', "Restore database dari backup \"{$filename}\". Safety backup: ".basename($safetyFilename).'.');

            return back()->with('status', 'Restore berhasil. Safety backup sebelum restore: '.basename($safetyFilename).'.');
        } catch (\Throwable $e) {
            Log::error('Gagal restore backup database.', [
                'filename' => $filename,
                'connection' => $connection,
                'exception' => $e,
            ]);

            return back()->with('error', 'Restore gagal. Database tidak diubah jika proses gagal pada tahap validasi; periksa log server.');
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

    private function restoreMysql(string $fullPath): void
    {
        $cfg = Config::get('database.connections.mysql');
        $sql = file_get_contents($fullPath);

        if ($sql === false || trim($sql) === '') {
            throw new \RuntimeException('File backup SQL kosong atau tidak bisa dibaca.');
        }

        if ($this->commandExists('mysql')) {
            $process = new Process([
                'mysql',
                '-h', (string) $cfg['host'],
                '-P', (string) ($cfg['port'] ?? 3306),
                '-u', (string) $cfg['username'],
                '--password='.(string) ($cfg['password'] ?? ''),
                (string) $cfg['database'],
            ]);
            $process->setInput($sql);
            $process->setTimeout(900);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException('mysql client gagal menjalankan restore: '.trim($process->getErrorOutput()));
            }
            return;
        }

        // Fallback native hanya untuk format backup SIBERAD yang kita hasilkan,
        // sehingga dump eksternal yang memakai DELIMITER/routine kompleks tidak
        // dipaksa dieksekusi parser sederhana ini.
        if (! str_starts_with(ltrim($sql), '-- SIBERAD MySQL backup')) {
            throw new \RuntimeException('Server tidak memiliki mysql client. Restore fallback hanya mendukung backup SIBERAD.');
        }

        $pdo = DB::connection('mysql')->getPdo();
        $statements = $this->splitSqlStatements($sql);
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if ($statement === '' || str_starts_with($statement, '--')) {
                    continue;
                }
                $pdo->exec($statement);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $single = false;
        $double = false;
        $backtick = false;
        $lineComment = false;
        $blockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($lineComment) {
                if ($char === "\n") {
                    $lineComment = false;
                    $buffer .= $char;
                }
                continue;
            }

            if ($blockComment) {
                if ($char === '*' && $next === '/') {
                    $blockComment = false;
                    $i++;
                }
                continue;
            }

            if (! $single && ! $double && ! $backtick) {
                if ($char === '#' || ($char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2])))) {
                    $lineComment = true;
                    if ($char === '-' && $next === '-') {
                        $i++;
                    }
                    continue;
                }
                if ($char === '/' && $next === '*') {
                    $blockComment = true;
                    $i++;
                    continue;
                }
            }

            if ($char === "\\" && ($single || $double) && $i + 1 < $length) {
                $buffer .= $char.$sql[++$i];
                continue;
            }

            if ($char === "'" && ! $double && ! $backtick) {
                $single = ! $single;
                $buffer .= $char;
                continue;
            }

            if ($char === '"' && ! $single && ! $backtick) {
                $double = ! $double;
                $buffer .= $char;
                continue;
            }

            if ($char === '`' && ! $single && ! $double) {
                $backtick = ! $backtick;
                $buffer .= $char;
                continue;
            }

            if ($char === ';' && ! $single && ! $double && ! $backtick) {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }

    private function validatedBackupPath(string $filename, array $allowedExtensions): string
    {
        $safeFilename = basename($filename);
        $extension = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));

        abort_unless(in_array($extension, $allowedExtensions, true), 404);

        $path = self::FOLDER.'/'.$safeFilename;
        abort_unless(Storage::disk(self::DISK)->exists($path), 404);

        return $path;
    }

    /**
     * Unduh salah satu file backup.
     */
    public function download(string $filename): Response
    {
        $path = $this->validatedBackupPath($filename, ['sql', 'sqlite']);
        $disk = Storage::disk(self::DISK);
        $fullPath = $disk->path($path);

        abort_unless(is_file($fullPath) && is_readable($fullPath), 404);

        // Logging tidak boleh menyebabkan proses download gagal jika pencatatan
        // aktivitas sedang bermasalah.
        try {
            ActivityLog::catat('backup.download', 'Mengunduh backup database "'.basename($path).'".');
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat aktivitas unduh backup.', [
                'filename' => basename($path),
                'exception' => $e,
            ]);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = $extension === 'sqlite'
            ? 'application/vnd.sqlite3'
            : 'application/sql';

        return response()->download($fullPath, basename($path), [
            'Content-Type' => $mime,
            'Content-Length' => (string) filesize($fullPath),
            'X-Content-Type-Options' => 'nosniff',
        ]);
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
}
