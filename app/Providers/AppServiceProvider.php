<?php

namespace App\Providers;

use App\Livewire\AIAnalysis\Panel as AiAnalysisPanel;
use App\Models\DatabaseBackupRun;
use App\Models\DatabaseBackupSetting;
use App\Policies\DatabaseBackupRunPolicy;
use App\Policies\DatabaseBackupSettingPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El acrónimo “AI” no sigue la conversión automática de aliases de Livewire en Linux.
        Livewire::component('ai-analysis.panel', AiAnalysisPanel::class);
        Gate::policy(DatabaseBackupRun::class, DatabaseBackupRunPolicy::class);
        Gate::policy(DatabaseBackupSetting::class, DatabaseBackupSettingPolicy::class);

        RateLimiter::for('database-backup-generation', fn (Request $request): Limit => Limit::perMinute(2)
            ->by((string) ($request->user()?->id ?? $request->ip())));
    }
}
