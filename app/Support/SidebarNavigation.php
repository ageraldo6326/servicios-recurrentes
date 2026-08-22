<?php

namespace App\Support;

use App\Models\User;

final class SidebarNavigation
{
    /**
     * @return array<int, array{key: string, label: string, items: array<int, array{key: string, route: string, active: array<int, string>, icon: string, label: string}>}>
     */
    public static function forUser(User $user): array
    {
        $savedOrder = $user->sidebar_menu_order ?? [];

        return collect(self::sections())
            ->map(function (array $section) use ($savedOrder): array {
                $positions = array_flip($savedOrder[$section['key']] ?? []);

                $section['items'] = collect($section['items'])
                    ->sortBy(fn (array $item): int => $positions[$item['key']] ?? PHP_INT_MAX)
                    ->values()
                    ->all();

                return $section;
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function sectionKeys(): array
    {
        return array_column(self::sections(), 'key');
    }

    /**
     * @return array<int, string>
     */
    public static function itemKeys(string $sectionKey): array
    {
        $section = collect(self::sections())->firstWhere('key', $sectionKey);

        return $section === null ? [] : array_column($section['items'], 'key');
    }

    /**
     * @param array<int, string> $order
     */
    public static function hasValidOrder(string $sectionKey, array $order): bool
    {
        $expected = self::itemKeys($sectionKey);
        $received = array_values($order);

        sort($expected);
        sort($received);

        return $expected === $received;
    }

    /**
     * @return array<int, array{key: string, label: string, items: array<int, array{key: string, route: string, active: array<int, string>, icon: string, label: string}>}>
     */
    private static function sections(): array
    {
        return [
            [
                'key' => 'daily',
                'label' => 'Operación diaria',
                'items' => [
                    ['key' => 'follow-up', 'route' => 'dashboard', 'active' => ['dashboard', 'dashboard.follow-up'], 'icon' => '⌂', 'label' => 'Seguimiento de hoy'],
                    ['key' => 'commitments', 'route' => 'financial-agenda.index', 'active' => ['financial-agenda.index', 'financial-agenda.beneficiaries.*', 'financial-agenda.commitments.*'], 'icon' => '◷', 'label' => 'Gestión de compromisos'],
                    ['key' => 'agenda', 'route' => 'tasks.index', 'active' => ['tasks.*'], 'icon' => '✓', 'label' => 'Agenda'],
                    ['key' => 'notebooks', 'route' => 'notebooks.index', 'active' => ['notebooks.*'], 'icon' => '✎', 'label' => 'Cuadernos'],
                    ['key' => 'collections', 'route' => 'charges.index', 'active' => ['charges.*', 'payments.*'], 'icon' => '▣', 'label' => 'Cobranza'],
                    ['key' => 'invoices', 'route' => 'commercial.invoices.index', 'active' => ['commercial.invoices.*'], 'icon' => '▧', 'label' => 'Facturas'],
                    ['key' => 'gestions', 'route' => 'gestions.index', 'active' => ['gestions.*'], 'icon' => '✦', 'label' => 'Gestiones'],
                ],
            ],
            [
                'key' => 'clients-services',
                'label' => 'Clientes y servicios',
                'items' => [
                    ['key' => 'clients', 'route' => 'clients.index', 'active' => ['clients.*'], 'icon' => '♙', 'label' => 'Clientes'],
                    ['key' => 'contracted-services', 'route' => 'contracted-services.index', 'active' => ['contracted-services.*'], 'icon' => '◫', 'label' => 'Servicios contratados'],
                    ['key' => 'catalog-services', 'route' => 'catalog-services.index', 'active' => ['catalog-services.*'], 'icon' => '▤', 'label' => 'Catálogo de servicios'],
                    ['key' => 'providers', 'route' => 'providers.index', 'active' => ['providers.*', 'provider-invoices.*'], 'icon' => '⌘', 'label' => 'Proveedores'],
                ],
            ],
            [
                'key' => 'finance',
                'label' => 'Finanzas',
                'items' => [
                    ['key' => 'financial-projection', 'route' => 'dashboard.executive', 'active' => ['dashboard.executive'], 'icon' => '◒', 'label' => 'Proyección financiera'],
                    ['key' => 'credit-cards', 'route' => 'financial-agenda.cards.dashboard', 'active' => ['financial-agenda.cards.*'], 'icon' => '💳', 'label' => 'Tarjetas'],
                    ['key' => 'unplanned-expenses', 'route' => 'commercial.unplanned-expenses.dashboard', 'active' => ['commercial.unplanned-expenses.*'], 'icon' => '⌁', 'label' => 'Gastos hormiga'],
                ],
            ],
            [
                'key' => 'commercial',
                'label' => 'Comercial',
                'items' => [
                    ['key' => 'commercial-dashboard', 'route' => 'commercial.dashboard', 'active' => ['commercial.dashboard'], 'icon' => '◉', 'label' => 'Dashboard'],
                    ['key' => 'quotes', 'route' => 'commercial.quotes.index', 'active' => ['commercial.quotes.*'], 'icon' => '◇', 'label' => 'Cotizaciones'],
                ],
            ],
            [
                'key' => 'tools',
                'label' => 'Herramientas',
                'items' => [
                    ['key' => 'operational-dashboard', 'route' => 'dashboard.operational', 'active' => ['dashboard.operational'], 'icon' => '▣', 'label' => 'Panel operativo'],
                    ['key' => 'breaks', 'route' => 'breaks.dashboard', 'active' => ['breaks.*'], 'icon' => '◌', 'label' => 'Descansos activos'],
                ],
            ],
            [
                'key' => 'system',
                'label' => 'Sistema',
                'items' => [
                    ['key' => 'settings', 'route' => 'settings.company.edit', 'active' => ['settings.*'], 'icon' => '⚙', 'label' => 'Configuración'],
                ],
            ],
        ];
    }
}
