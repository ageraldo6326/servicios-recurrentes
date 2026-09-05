<?php

declare(strict_types=1);

namespace App\Services\DatabaseBackups;

use App\Enums\DatabaseBackupRunStatus;
use App\Models\DatabaseBackupRun;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class DatabaseBackupService
{
    public function generate(User $user): DatabaseBackupResult
    {
        $this->assertMysqlConnection();
        $this->cleanExpiredTemporaryFiles();

        $lock = Cache::lock('database-backups:running', (int) config('database-backups.lock_seconds', 1800));
        if (! $lock->get()) {
            throw new DatabaseBackupAlreadyRunning;
        }

        $run = null;
        $backupPath = null;
        $optionsPath = null;

        try {
            $startedAt = now();
            $fileName = $this->logicalFileName($startedAt);
            $run = DatabaseBackupRun::query()->create([
                'user_id' => $user->id,
                'status' => DatabaseBackupRunStatus::Processing,
                'file_name' => $fileName,
                'started_at' => $startedAt,
            ]);

            $directory = $this->temporaryDirectory();
            $token = Str::random(40);
            $backupPath = $directory.DIRECTORY_SEPARATOR.$token.'.sql.gz';
            $optionsPath = $directory.DIRECTORY_SEPARATOR.$token.'.cnf';
            $this->writeClientOptions($optionsPath);

            $tool = $this->dumpTool();
            $this->streamDumpToGzip($tool, $optionsPath, $backupPath);
            $this->assertValidGzip($backupPath);

            $completedAt = now();
            $fileSize = filesize($backupPath);
            if (! is_int($fileSize) || $fileSize < 1) {
                throw new DatabaseBackupException('empty_file', 'No fue posible generar el respaldo. Revise la configuración del servidor.', 'Generated archive is empty.');
            }

            $run->update([
                'status' => DatabaseBackupRunStatus::Completed,
                'file_size_bytes' => $fileSize,
                'completed_at' => $completedAt,
                'duration_seconds' => max(0, $startedAt->diffInSeconds($completedAt)),
            ]);

            Log::info('Database backup completed.', [
                'run_id' => $run->id,
                'user_id' => $user->id,
                'duration_seconds' => $run->duration_seconds,
                'file_size_bytes' => $fileSize,
                'tool' => basename($tool),
            ]);

            return new DatabaseBackupResult($run, $backupPath, $fileName);
        } catch (DatabaseBackupException $exception) {
            $this->markFailed($run, $exception->errorCode, $exception->publicMessage, $exception);
            $this->deleteTemporaryFile($backupPath);

            throw $exception;
        } catch (Throwable $exception) {
            $publicException = new DatabaseBackupException(
                'generation_failed',
                'No fue posible generar el respaldo. Revise la configuración del servidor.',
                $exception->getMessage(),
            );
            $this->markFailed($run, $publicException->errorCode, $publicException->publicMessage, $exception);
            $this->deleteTemporaryFile($backupPath);

            throw $publicException;
        } finally {
            $this->deleteTemporaryFile($optionsPath);
            $lock->release();
        }
    }

    public function cleanExpiredTemporaryFiles(): void
    {
        $directory = $this->temporaryDirectory();
        $expiresAt = now()->subSeconds((int) config('database-backups.temporary_file_max_age_seconds', 3600))->getTimestamp();

        foreach (File::files($directory) as $file) {
            if ($file->getMTime() < $expiresAt) {
                File::delete($file->getPathname());
            }
        }
    }

    public function expireStaleRuns(): void
    {
        $expiredBefore = now()->subSeconds((int) config('database-backups.lock_seconds', 1800));

        DatabaseBackupRun::query()
            ->where('status', DatabaseBackupRunStatus::Processing)
            ->where('started_at', '<', $expiredBefore)
            ->get()
            ->each(function (DatabaseBackupRun $run): void {
                $completedAt = now();
                $run->update([
                    'status' => DatabaseBackupRunStatus::Failed,
                    'completed_at' => $completedAt,
                    'duration_seconds' => max(0, $run->started_at->diffInSeconds($completedAt)),
                    'error_code' => 'process_expired',
                    'error_message' => 'El proceso no finalizó dentro del tiempo permitido.',
                ]);
            });
    }

    private function assertMysqlConnection(): void
    {
        $connection = config('database.connections.'.config('database.default'));
        if (! is_array($connection) || ! in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new DatabaseBackupException(
                'unsupported_connection',
                'El respaldo manual requiere una conexión MySQL o MariaDB.',
                'The configured database connection is not MySQL or MariaDB.',
            );
        }
    }

    private function dumpTool(): string
    {
        $configuredPath = trim((string) config('database-backups.dump_binary', ''));
        $candidates = array_filter([$configuredPath, 'mariadb-dump', 'mysqldump']);

        foreach ($candidates as $candidate) {
            try {
                $process = new Process([$candidate, '--version']);
                $process->setTimeout(5);
                $process->run();

                if ($process->isSuccessful()) {
                    return $candidate;
                }
            } catch (Throwable) {
                continue;
            }
        }

        throw new DatabaseBackupException(
            'dump_tool_unavailable',
            'La herramienta de respaldo no está disponible en el servidor.',
            'Neither mariadb-dump nor mysqldump is available.',
        );
    }

    private function streamDumpToGzip(string $tool, string $optionsPath, string $backupPath): void
    {
        $connection = config('database.connections.'.config('database.default'));
        $stream = @gzopen($backupPath, 'wb9');
        if ($stream === false) {
            throw new DatabaseBackupException('temporary_storage_unavailable', 'No hay espacio temporal suficiente para completar el respaldo.', 'Could not open the gzip archive for writing.');
        }

        $stderr = '';
        $uncompressedBytes = 0;

        try {
            $command = [
                $tool,
                '--defaults-extra-file='.$optionsPath,
                '--single-transaction',
                '--quick',
                '--routines',
                '--triggers',
                '--events',
                '--no-tablespaces',
                '--default-character-set='.(string) ($connection['charset'] ?? 'utf8mb4'),
                '--databases',
                (string) $connection['database'],
            ];
            $process = new Process($command);
            $process->setTimeout((int) config('database-backups.process_timeout_seconds', 900));
            try {
                $process->run(function (string $type, string $buffer) use ($stream, &$stderr, &$uncompressedBytes): void {
                    if ($type === Process::ERR) {
                        $stderr = Str::limit($stderr.$buffer, 2000, '');

                        return;
                    }

                    $uncompressedBytes += strlen($buffer);
                    $maxBytes = (int) config('database-backups.max_uncompressed_bytes', 0);
                    if ($maxBytes > 0 && $uncompressedBytes > $maxBytes) {
                        throw new DatabaseBackupException('size_limit_exceeded', 'El respaldo excedió el tamaño temporal máximo permitido.', 'The dump exceeded the configured uncompressed size limit.');
                    }

                    if (gzwrite($stream, $buffer) === false) {
                        throw new DatabaseBackupException('temporary_storage_unavailable', 'No hay espacio temporal suficiente para completar el respaldo.', 'Writing to the gzip archive failed.');
                    }
                });
            } catch (ProcessTimedOutException $exception) {
                throw new DatabaseBackupException('timeout', 'El respaldo excedió el tiempo máximo permitido.', $exception->getMessage());
            }

            if (! $process->isSuccessful()) {
                $reason = $process->getExitCode() === 124 ? 'The dump command timed out.' : 'The dump command exited unsuccessfully: '.Str::limit(trim($stderr), 500, '');
                $code = $process->getExitCode() === 124 ? 'timeout' : 'dump_failed';
                $message = $code === 'timeout'
                    ? 'El respaldo excedió el tiempo máximo permitido.'
                    : 'No fue posible generar el respaldo. Revise la configuración del servidor.';

                throw new DatabaseBackupException($code, $message, $reason);
            }
        } finally {
            gzclose($stream);
        }
    }

    private function assertValidGzip(string $backupPath): void
    {
        clearstatcache(true, $backupPath);
        if (! is_file($backupPath) || filesize($backupPath) < 1) {
            throw new DatabaseBackupException('empty_file', 'No fue posible generar el respaldo. Revise la configuración del servidor.', 'Generated archive is missing or empty.');
        }

        $stream = @gzopen($backupPath, 'rb');
        if ($stream === false) {
            throw new DatabaseBackupException('invalid_gzip', 'No fue posible validar el respaldo generado.', 'The generated archive cannot be opened as gzip.');
        }

        try {
            while (! gzeof($stream)) {
                if (gzread($stream, 65536) === false) {
                    throw new DatabaseBackupException('invalid_gzip', 'No fue posible validar el respaldo generado.', 'The generated archive failed integrity validation.');
                }
            }
        } finally {
            gzclose($stream);
        }
    }

    private function writeClientOptions(string $optionsPath): void
    {
        $connection = config('database.connections.'.config('database.default'));
        $lines = ['[client]'];
        foreach (['host', 'port', 'username', 'password', 'unix_socket'] as $key) {
            $value = $connection[$key] ?? null;
            if ($value !== null && $value !== '') {
                $option = $key === 'username' ? 'user' : $key;
                $lines[] = $option.'='.$this->optionValue((string) $value);
            }
        }

        if (file_put_contents($optionsPath, implode(PHP_EOL, $lines).PHP_EOL, LOCK_EX) === false) {
            throw new DatabaseBackupException('temporary_storage_unavailable', 'No hay espacio temporal suficiente para completar el respaldo.', 'Could not write the temporary client options file.');
        }

        @chmod($optionsPath, 0600);
    }

    private function optionValue(string $value): string
    {
        return '"'.str_replace(['\\', '"', "\r", "\n"], ['\\\\', '\\"', '\\r', '\\n'], $value).'"';
    }

    private function temporaryDirectory(): string
    {
        $directory = storage_path('app/private/database-backups');
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0700, true, true);
        }
        @chmod($directory, 0700);

        return $directory;
    }

    private function logicalFileName(CarbonInterface $startedAt): string
    {
        $systemName = Str::slug((string) config('app.name', 'sistema')) ?: 'sistema';

        return 'backup_'.$systemName.'_'.$startedAt->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d_H-i-s').'.sql.gz';
    }

    private function markFailed(?DatabaseBackupRun $run, string $errorCode, string $publicMessage, Throwable $exception): void
    {
        if ($run === null) {
            return;
        }

        $completedAt = now();
        $run->update([
            'status' => DatabaseBackupRunStatus::Failed,
            'completed_at' => $completedAt,
            'duration_seconds' => max(0, $run->started_at->diffInSeconds($completedAt)),
            'error_code' => $errorCode,
            'error_message' => $publicMessage,
        ]);

        Log::warning('Database backup failed.', [
            'run_id' => $run->id,
            'user_id' => $run->user_id,
            'error_code' => $errorCode,
            'exception' => $exception::class,
        ]);
    }

    private function deleteTemporaryFile(?string $path): void
    {
        if ($path !== null && is_file($path)) {
            File::delete($path);
        }
    }
}
