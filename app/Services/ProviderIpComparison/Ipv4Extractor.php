<?php

declare(strict_types=1);

namespace App\Services\ProviderIpComparison;

final class Ipv4Extractor
{
    /**
     * @return array{
     *     ips: list<string>,
     *     discarded: list<array{value: string, reason: string}>,
     *     duplicate_count: int
     * }
     */
    public function extract(string $text): array
    {
        preg_match_all('/(?<![\d.])(?:\d{1,3}\.){3}\d{1,3}(?![\d.])/', $text, $ipv4Matches);

        /** @var array<string, int> $occurrences */
        $occurrences = [];
        /** @var array<string, string> $discarded */
        $discarded = [];

        foreach ($ipv4Matches[0] as $candidate) {
            $ip = trim($candidate);

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                $discarded[$ip] = 'Formato IPv4 inválido';

                continue;
            }

            $occurrences[$ip] = ($occurrences[$ip] ?? 0) + 1;
        }

        $this->collectIpv6Candidates($text, $discarded);

        $duplicateCount = 0;
        foreach ($occurrences as $ip => $count) {
            if ($count <= 1) {
                continue;
            }

            $duplicateCount += $count - 1;
            $discarded[$ip] = sprintf('Repetida (%d apariciones)', $count);
        }

        return [
            'ips' => array_keys($occurrences),
            'discarded' => collect($discarded)
                ->map(fn (string $reason, string $value): array => ['value' => $value, 'reason' => $reason])
                ->values()
                ->all(),
            'duplicate_count' => $duplicateCount,
        ];
    }

    /**
     * @param  array<string, string>  $discarded
     */
    private function collectIpv6Candidates(string $text, array &$discarded): void
    {
        preg_match_all('/(?<![A-Fa-f0-9:])(?:[A-Fa-f0-9]{0,4}:){2,}[A-Fa-f0-9]{0,4}(?![A-Fa-f0-9:])/', $text, $ipv6Matches);

        foreach ($ipv6Matches[0] as $candidate) {
            $ip = trim($candidate);

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                $discarded[$ip] = 'IPv6 fuera de alcance inicial';
            }
        }
    }
}
