<?php

declare(strict_types=1);

namespace App\Services\ProviderIpComparison;

use App\Enums\ContractedServiceStatus;
use App\Models\ContractedService;
use App\Models\Provider;

final class ProviderContractedIpComparisonService
{
    public function __construct(
        private readonly Ipv4Extractor $ipv4Extractor,
    ) {}

    /**
     * @return array{
     *     provider: array{id: int, name: string},
     *     summary: array{valid_pasted_ips: int, matching_ips: int, provider_only_ips: int, service_only_ips: int, services_without_internal_ip: int, duplicate_pasted_ips: int},
     *     matches: list<array{ip: string, services: list<array{id: int, name: string, client: ?string, status: string}>}>,
     *     provider_only: list<array{ip: string}>,
     *     service_only: list<array{ip: string, service: array{id: int, name: string, client: ?string, status: string}}>,
     *     services_without_internal_ip: list<array{id: int, name: string, client: ?string, status: string, stored_ip: ?string}>,
     *     invalid_or_discarded: list<array{value: string, reason: string}>
     * }
     */
    public function compare(Provider $provider, string $pastedText): array
    {
        $extraction = $this->ipv4Extractor->extract($pastedText);

        $services = ContractedService::query()
            ->with(['client:id,name', 'catalogService:id,name'])
            ->where('provider_id', $provider->id)
            ->where('status', ContractedServiceStatus::Active->value)
            ->get();

        /** @var array<string, list<array{id: int, name: string, client: ?string, status: string}>> $servicesByIp */
        $servicesByIp = [];
        /** @var list<array{id: int, name: string, client: ?string, status: string, stored_ip: ?string}> $servicesWithoutInternalIp */
        $servicesWithoutInternalIp = [];

        foreach ($services as $service) {
            $serviceData = [
                'id' => $service->id,
                'name' => $service->catalogService?->name ?? 'Servicio #'.$service->id,
                'client' => $service->client?->name,
                'status' => $service->status->value,
            ];
            $storedIp = is_string($service->ip) ? trim($service->ip) : null;

            if ($storedIp === null || filter_var($storedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                $servicesWithoutInternalIp[] = [...$serviceData, 'stored_ip' => $storedIp];

                continue;
            }

            $servicesByIp[$storedIp][] = $serviceData;
        }

        $pastedIps = $extraction['ips'];
        $pastedIpLookup = array_fill_keys($pastedIps, true);
        $serviceIps = array_keys($servicesByIp);

        $matches = [];
        $providerOnly = [];
        foreach ($pastedIps as $ip) {
            if (isset($servicesByIp[$ip])) {
                $matches[] = ['ip' => $ip, 'services' => $servicesByIp[$ip]];
            } else {
                $providerOnly[] = ['ip' => $ip];
            }
        }

        $serviceOnly = [];
        foreach ($serviceIps as $ip) {
            if (isset($pastedIpLookup[$ip])) {
                continue;
            }

            foreach ($servicesByIp[$ip] as $service) {
                $serviceOnly[] = ['ip' => $ip, 'service' => $service];
            }
        }

        return [
            'provider' => ['id' => $provider->id, 'name' => $provider->name],
            'summary' => [
                'valid_pasted_ips' => count($pastedIps),
                'matching_ips' => count($matches),
                'provider_only_ips' => count($providerOnly),
                'service_only_ips' => count($serviceOnly),
                'services_without_internal_ip' => count($servicesWithoutInternalIp),
                'duplicate_pasted_ips' => $extraction['duplicate_count'],
            ],
            'matches' => $matches,
            'provider_only' => $providerOnly,
            'service_only' => $serviceOnly,
            'services_without_internal_ip' => $servicesWithoutInternalIp,
            'invalid_or_discarded' => $extraction['discarded'],
        ];
    }
}
