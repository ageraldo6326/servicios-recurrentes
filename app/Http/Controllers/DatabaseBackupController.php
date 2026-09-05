<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ConfigureDatabaseBackupSetting;
use App\Enums\DatabaseBackupRunStatus;
use App\Http\Requests\UpdateDatabaseBackupSettingRequest;
use App\Models\DatabaseBackupRun;
use App\Models\DatabaseBackupSetting;
use App\Services\DatabaseBackups\DatabaseBackupAlreadyRunning;
use App\Services\DatabaseBackups\DatabaseBackupException;
use App\Services\DatabaseBackups\DatabaseBackupService;
use App\Services\DatabaseBackups\DatabaseBackupStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DatabaseBackupController extends Controller
{
    public function index(DatabaseBackupStatusService $statusService, DatabaseBackupService $backupService)
    {
        Gate::authorize('viewAny', DatabaseBackupRun::class);
        $backupService->expireStaleRuns();
        $backupService->cleanExpiredTemporaryFiles();

        return view('database-backups.index', [
            'status' => $statusService->status(),
            'setting' => DatabaseBackupSetting::query()->firstOrCreate(['id' => 1], ['reminder_interval_days' => 7]),
            'runs' => DatabaseBackupRun::query()->with('user')->latest('started_at')->limit(12)->get(),
            'isProcessing' => DatabaseBackupRun::query()
                ->where('status', DatabaseBackupRunStatus::Processing)
                ->where('started_at', '>=', now()->subSeconds((int) config('database-backups.lock_seconds', 1800)))
                ->exists(),
        ]);
    }

    public function update(UpdateDatabaseBackupSettingRequest $request, ConfigureDatabaseBackupSetting $action): RedirectResponse
    {
        $action->execute($request->user(), $request->integer('reminder_interval_days'));

        return back()->with('success', 'Recordatorio de respaldo actualizado.');
    }

    public function generate(Request $request, DatabaseBackupService $backupService): JsonResponse
    {
        Gate::authorize('create', DatabaseBackupRun::class);

        try {
            $result = $backupService->generate($request->user());
            $token = Str::random(80);
            Cache::put('database-backup-download:'.$token, [
                'user_id' => $request->user()->id,
                'path' => $result->temporaryPath,
                'file_name' => $result->fileName,
            ], now()->addMinutes(5));

            return response()->json([
                'message' => 'Respaldo generado. La descarga comenzará ahora.',
                'download_url' => route('database-backups.download', ['token' => $token]),
            ]);
        } catch (DatabaseBackupAlreadyRunning $exception) {
            return response()->json(['message' => $exception->publicMessage], 409);
        } catch (DatabaseBackupException $exception) {
            return response()->json([
                'message' => $exception->publicMessage,
                'reference' => $exception->errorCode,
            ], 422);
        }
    }

    public function download(Request $request, string $token): BinaryFileResponse
    {
        Gate::authorize('create', DatabaseBackupRun::class);

        $download = Cache::pull('database-backup-download:'.$token);
        abort_unless(is_array($download) && ($download['user_id'] ?? null) === $request->user()->id, 404);
        abort_unless(is_string($download['path'] ?? null) && is_file($download['path']), 404);

        return response()->download($download['path'], $download['file_name'], [
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }
}
