<?php

declare(strict_types=1);

namespace App\Livewire\BusinessCoach;

use App\Models\BusinessCoachNote;
use App\Services\BusinessCoach\BusinessCoachContextService;
use App\Services\BusinessCoach\BusinessCoachService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;

final class Panel extends Component
{
    public bool $open = false;

    public bool $loading = false;

    public bool $saved = false;

    public string $analysis = '';

    public string $error = '';

    public string $page = '';

    public string $routeName = 'dashboard';

    public function mount(): void
    {
        $this->routeName = request()->route()?->getName() ?? 'dashboard';
    }

    public function analyze(BusinessCoachContextService $contextService, BusinessCoachService $coach): void
    {
        $this->runAnalysis(false, $contextService, $coach);
    }

    public function forceAnalyze(BusinessCoachContextService $contextService, BusinessCoachService $coach): void
    {
        $this->runAnalysis(true, $contextService, $coach);
    }

    private function runAnalysis(bool $force, BusinessCoachContextService $contextService, BusinessCoachService $coach): void
    {
        $this->open = true;
        $this->loading = true;
        $this->error = '';
        $this->saved = false;
        $context = $contextService->build($this->routeName);
        $this->page = (string) ($context['page'] ?? 'dashboard');
        $key = 'business-coach:v2:'.sha1(json_encode([
            'route' => $this->routeName,
            'model' => config('services.openai.model', 'gpt-5-mini'),
            'context' => $context,
        ], JSON_THROW_ON_ERROR));

        try {
            $this->analysis = Cache::lock($key.':lock', 90)->block(15, function () use ($key, $context, $coach, $force): string {
                $ttl = now()->addMinutes((int) config('services.openai.cache_minutes', 30));

                if ($force) {
                    $analysis = $coach->analyze($context);
                    Cache::put($key, $analysis, $ttl);

                    return $analysis;
                }

                return Cache::remember($key, $ttl, fn (): string => $coach->analyze($context));
            });
        } catch (\Throwable $exception) {
            $this->analysis = '';
            $this->error = $exception->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function saveNote(): void
    {
        if ($this->analysis === '' || ! auth()->check()) {
            return;
        }

        BusinessCoachNote::query()->create(['user_id' => auth()->id(), 'page' => $this->page, 'content' => $this->analysis]);
        $this->saved = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function resetForNavigation(string $path): void
    {
        $this->routeName = $this->routeNameFromPath($path);
        $this->open = false;
        $this->loading = false;
        $this->saved = false;
        $this->analysis = '';
        $this->error = '';
        $this->page = '';
    }

    public function render()
    {
        return view('livewire.business-coach.panel', [
            'renderedAnalysis' => $this->analysis === ''
                ? ''
                : Str::markdown($this->analysis, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
        ]);
    }

    private function routeNameFromPath(string $path): string
    {
        return match (true) {
            str_starts_with($path, '/clients') => 'clients.index',
            str_starts_with($path, '/catalog-services') => 'catalog-services.index',
            str_starts_with($path, '/contracted-services') => 'contracted-services.index',
            str_starts_with($path, '/charges') => 'charges.index',
            str_starts_with($path, '/payments') => 'payments.index',
            str_starts_with($path, '/providers') => 'providers.index',
            str_starts_with($path, '/provider-invoices') => 'provider-invoices.index',
            str_starts_with($path, '/gestions') => 'gestions.index',
            str_starts_with($path, '/financial-agenda') => 'financial-agenda.index',
            str_contains($path, '/seguimiento') => 'dashboard.follow-up',
            str_contains($path, '/operativo') => 'dashboard.operational',
            str_contains($path, '/ejecutivo') => 'dashboard.executive',
            default => 'dashboard',
        };
    }
}
