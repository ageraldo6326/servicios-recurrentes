<?php

namespace App\Providers;

use App\Livewire\AIAnalysis\Panel as AiAnalysisPanel;
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
    }
}
