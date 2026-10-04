<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\ContractedService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class ContractedServiceBillingPeriodService
{
    public function oldestUnpaidDueDate(ContractedService $service, CarbonImmutable $referenceDate): CarbonImmutable
    {
        $charges = $service->relationLoaded('charges')
            ? $service->charges
            : $service->charges()->get();
        $timezone = $referenceDate->getTimezone();
        $startsAt = CarbonImmutable::parse($service->starts_at->toDateString(), $timezone)->startOfDay();
        $scheduledDueDate = $this->billingDateForMonth($service, $startsAt->startOfMonth());

        if ($scheduledDueDate->lte($startsAt)) {
            $scheduledDueDate = $this->billingDateForMonth($service, $startsAt->startOfMonth()->addMonth());
        }

        $paidDueDates = $charges
            ->filter(fn (Charge $charge): bool => $charge->status === ChargeStatus::Paid && $charge->due_date !== null)
            ->map(fn (Charge $charge): string => $charge->due_date->toDateString())
            ->unique();

        while ($paidDueDates->contains($scheduledDueDate->toDateString())) {
            $scheduledDueDate = $this->billingDateForMonth($service, $scheduledDueDate->startOfMonth()->addMonth());
        }

        $oldestExplicitUnpaidDate = $this->oldestExplicitUnpaidDate($charges, $timezone);

        if ($oldestExplicitUnpaidDate !== null && $oldestExplicitUnpaidDate->lt($scheduledDueDate)) {
            return $oldestExplicitUnpaidDate;
        }

        return $scheduledDueDate;
    }

    private function oldestExplicitUnpaidDate(Collection $charges, \DateTimeZone $timezone): ?CarbonImmutable
    {
        $charge = $charges
            ->filter(fn (Charge $charge): bool => in_array($charge->status, [
                ChargeStatus::Pending,
                ChargeStatus::Partial,
                ChargeStatus::Overdue,
            ], true) && $charge->due_date !== null)
            ->sortBy(fn (Charge $charge): int => $charge->due_date->timestamp)
            ->first();

        return $charge === null
            ? null
            : CarbonImmutable::parse($charge->due_date->toDateString(), $timezone)->startOfDay();
    }

    private function billingDateForMonth(ContractedService $service, CarbonImmutable $month): CarbonImmutable
    {
        return $month->day(min((int) $service->billing_day, $month->daysInMonth));
    }
}
