<?php

declare(strict_types=1);

namespace App\Services\BusinessCoach;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class BusinessCoachContextService
{
    public function __construct(private readonly Request $request) {}

    /** @return array<string, mixed> */
    public function build(?string $routeName = null): array
    {
        $route = $routeName ?? $this->request->route()?->getName() ?? 'dashboard';

        $builder = match (true) {
            str_starts_with($route, 'clients.') => new ClientsContextBuilder,
            str_starts_with($route, 'catalog-services.') => new CatalogServicesContextBuilder,
            str_starts_with($route, 'contracted-services.') => new ContractedServicesContextBuilder,
            str_starts_with($route, 'charges.') || str_starts_with($route, 'payments.') => new ChargesContextBuilder,
            str_starts_with($route, 'providers.') || str_starts_with($route, 'provider-invoices.') => new ProvidersContextBuilder,
            str_starts_with($route, 'financial-agenda.') => new FinancialAgendaContextBuilder,
            str_starts_with($route, 'gestions.') || $route === 'dashboard' || $route === 'dashboard.follow-up' => new FollowUpContextBuilder,
            $route === 'dashboard.operational' || $route === 'dashboard.executive' => new DashboardContextBuilder,
            default => new DashboardContextBuilder,
        };

        $context = $builder->build();
        $context['_source_version'] = $this->sourceVersion($route);

        return $context;
    }

    /** @return array<string, string|null> */
    private function sourceVersion(string $route): array
    {
        $tables = match (true) {
            str_starts_with($route, 'clients.') => ['clients', 'contracted_services', 'charges', 'gestions'],
            str_starts_with($route, 'catalog-services.') => ['catalog_services', 'contracted_services'],
            str_starts_with($route, 'contracted-services.') => ['contracted_services', 'clients', 'providers', 'charges', 'payments', 'payment_allocations', 'gestions'],
            str_starts_with($route, 'charges.') || str_starts_with($route, 'payments.') => ['charges', 'payments', 'payment_allocations', 'gestions'],
            str_starts_with($route, 'providers.') || str_starts_with($route, 'provider-invoices.') => ['providers', 'provider_invoices', 'contracted_services'],
            str_starts_with($route, 'financial-agenda.') => ['financial_commitments', 'commitment_payments', 'beneficiaries', 'exchange_rates'],
            str_starts_with($route, 'gestions.') || $route === 'dashboard' || $route === 'dashboard.follow-up' => ['gestions', 'clients', 'contracted_services', 'charges'],
            default => ['clients', 'contracted_services', 'charges', 'payments', 'payment_allocations', 'gestions', 'providers', 'provider_invoices', 'financial_commitments', 'commitment_payments'],
        };

        return collect($tables)->mapWithKeys(fn (string $table): array => [
            $table => DB::table($table)->max('updated_at'),
        ])->all();
    }
}
