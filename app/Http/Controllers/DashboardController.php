<?php

namespace App\Http\Controllers;

use App\Enums\ChargeStatus;
use App\Enums\ContractedServiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\Gestion;
use App\Models\Payment;
use App\Models\ProviderInvoice;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function operational()
    {
        $today = now()->toDateString();
        $tomorrowDate = now()->addDay()->toDateString();
        $pendingChargeStatuses = [ChargeStatus::Pending, ChargeStatus::Partial, ChargeStatus::Overdue];

        return view('dashboard.operational', [
            'overdue' => Charge::with('contractedService.client')
                ->whereIn('status', $pendingChargeStatuses)
                ->whereDate('due_date', '<', $today)
                ->orderBy('due_date')
                ->get(),
            'todayCharges' => Charge::with('contractedService.client', 'contractedService.catalogService')
                ->whereIn('status', $pendingChargeStatuses)
                ->whereDate('due_date', $today)
                ->orderBy('due_date')
                ->get(),
            'promises' => Gestion::with('client', 'contractedService.catalogService')
                ->whereDate('promised_payment_date', $today)
                ->latest('occurred_at')
                ->get(),
            'tomorrow' => Gestion::with('client', 'contractedService.catalogService')
                ->whereDate('next_follow_up_at', $tomorrowDate)
                ->orderBy('next_follow_up_at')
                ->get(),
            'pendingPayments' => Payment::where('status', PaymentStatus::Pending)->latest('received_at')->get(),
        ]);
    }

    public function executive(Request $request)
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfMonth();
        $charges = Charge::with('contractedService')->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])->get();
        $invoices = ProviderInvoice::whereBetween('due_date', [$from->toDateString(), $to->toDateString()])->get();
        $collected = Payment::where('status', PaymentStatus::Validated)->whereBetween('received_at', [$from->toDateString(), $to->toDateString()])->sum('amount');
        $services = \App\Models\ContractedService::where('status', ContractedServiceStatus::Active)
            ->whereDate('starts_at', '<=', $to->toDateString())
            ->where(function ($query) use ($from) {
                $query->whereNull('cancelled_at')->orWhereDate('cancelled_at', '>=', $from->toDateString());
            })
            ->get();

        $serviceProjectedIncome = 0;
        $serviceProjectedCosts = 0;

        foreach ($services as $service) {
            $month = $service->starts_at->copy()->startOfMonth()->addMonth();

            while ($month->lte($to)) {
                $dueDate = $month->copy()->day(min($service->billing_day, $month->daysInMonth));

                if ($dueDate->gte($from) && (! $service->cancelled_at || $dueDate->lte($service->cancelled_at))) {
                    $hasCharge = $charges->contains(fn (Charge $charge) => $charge->contracted_service_id === $service->id && $charge->due_date->isSameDay($dueDate));

                    if (! $hasCharge) {
                        $serviceProjectedIncome += (float) $service->price;
                    }

                    $serviceProjectedCosts += (float) $service->cost;
                }

                $month->addMonth();
            }
        }

        $projectedIncome = (float) $charges->sum('amount') + $serviceProjectedIncome;
        $projectedCosts = $serviceProjectedCosts > 0 ? $serviceProjectedCosts : (float) $invoices->sum('amount');

        return view('dashboard.executive', compact('from', 'to', 'charges', 'invoices', 'collected', 'projectedIncome', 'projectedCosts'));
    }
}
