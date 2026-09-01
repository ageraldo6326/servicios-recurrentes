<?php

declare(strict_types=1);

namespace App\Livewire\ProviderIpComparison;

use App\Models\Provider;
use App\Services\ProviderIpComparison\ProviderContractedIpComparisonService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class CompareForm extends Component
{
    public ?int $providerId = null;

    public bool $inventoryComplete = true;

    public string $pastedText = '';

    /** @var array<string, mixed>|null */
    public ?array $comparison = null;

    public function updatedProviderId(): void
    {
        $this->comparison = null;
    }

    public function compare(ProviderContractedIpComparisonService $comparisonService): void
    {
        $validated = $this->validate();
        $provider = Provider::query()->findOrFail($validated['providerId']);

        $comparison = $comparisonService->compare($provider, $validated['pastedText']);
        $this->pastedText = '';

        if ($comparison['summary']['valid_pasted_ips'] === 0) {
            $this->comparison = null;
            $this->addError('pastedText', 'No se encontraron direcciones IPv4 válidas para comparar.');

            return;
        }

        $this->comparison = $comparison;
    }

    public function clear(): void
    {
        $this->reset(['providerId', 'pastedText', 'comparison']);
        $this->inventoryComplete = true;
        $this->resetValidation();
    }

    /** @return array<string, list<string|Rule>> */
    protected function rules(): array
    {
        return [
            'providerId' => ['required', 'integer', Rule::exists('providers', 'id')],
            'pastedText' => ['required', 'string', 'max:1048576'],
        ];
    }

    public function render(): View
    {
        return view('livewire.provider-ip-comparison.compare-form', [
            'providers' => Provider::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
