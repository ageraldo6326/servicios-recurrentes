<?php

namespace App\Http\Controllers;

use App\Enums\ContractedServiceStatus;
use App\Enums\ChargeStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\ContractedServiceRequest;
use App\Models\CatalogService;
use App\Models\Charge;
use App\Models\Client;
use App\Models\ContractedService;
use App\Models\Gestion;
use App\Models\Payment;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractedServiceController extends Controller
{
    public function index()
    {
        return view('contracted-services.index', ['services' => ContractedService::with('client', 'catalogService', 'provider')->orderByDesc('contracted_services.created_at')->orderBy(Client::select('name')->whereColumn('clients.id', 'contracted_services.client_id'))->paginate(15)]);
    }

    public function create()
    {
        return view('contracted-services.form', ['service' => new ContractedService(['status' => ContractedServiceStatus::Active->value, 'price_currency' => 'USD', 'cost_currency' => 'USD']), 'clients' => Client::orderBy('name')->get(), 'catalogServices' => CatalogService::where('is_active', true)->orderBy('name')->get(), 'providers' => Provider::orderBy('name')->get()]);
    }

    public function store(ContractedServiceRequest $request)
    {
        $data = $request->validated();
        abort_unless(CatalogService::whereKey($data['catalog_service_id'])->where('is_active', true)->exists(), 422, 'El servicio del catálogo está inactivo.');

        ContractedService::create([...$data, 'status' => ContractedServiceStatus::Active]);

        return redirect()->route('contracted-services.index')->with('success', 'Servicio contratado creado.');
    }

    public function edit(ContractedService $contractedService)
    {
        return view('contracted-services.form', ['service' => $contractedService, 'clients' => Client::orderBy('name')->get(), 'catalogServices' => CatalogService::orderBy('name')->get(), 'providers' => Provider::orderBy('name')->get()]);
    }

    public function update(ContractedServiceRequest $request, ContractedService $contractedService)
    {
        $data = $request->validated();
        abort_unless(CatalogService::whereKey($data['catalog_service_id'])->where('is_active', true)->exists(), 422, 'El servicio del catálogo está inactivo.');

        $contractedService->update($data);

        return redirect()->route('contracted-services.index')->with('success', 'Servicio contratado actualizado.');
    }

    public function destroy(ContractedService $contractedService)
    {
        if ($contractedService->charges()->exists() || $contractedService->gestions()->exists()) {
            return back()->with('error', 'No se puede borrar este servicio porque tiene cobros o gestiones asociadas.');
        }

        $contractedService->delete();

        return redirect()->route('contracted-services.index')->with('success', 'Servicio contratado eliminado.');
    }

    public function cancel(Request $request, ContractedService $contractedService)
    {
        $data = $request->validate(['cancellation_reason' => ['required', 'string', 'max:2000']]);
        $contractedService->update(['status' => ContractedServiceStatus::Cancelled, 'cancelled_at' => now(), 'cancellation_reason' => $data['cancellation_reason']]);

        return back()->with('success', 'Servicio cancelado inmediatamente.');
    }

    public function markAsPaid(ContractedService $contractedService)
    {
        abort_unless($contractedService->status === ContractedServiceStatus::Active, 422, 'Solo se pueden pagar servicios activos.');

        DB::transaction(function () use ($contractedService): void {
            $billingDate = now()->startOfMonth()->day(min((int) $contractedService->billing_day, now()->daysInMonth));
            $charge = $contractedService->charges()
                ->with('payments')
                ->whereDate('due_date', $billingDate)
                ->latest('created_at')
                ->first();

            if (! $charge) {
                $charge = Charge::create([
                    'contracted_service_id' => $contractedService->id,
                    'status' => ChargeStatus::Paid,
                    'amount' => $contractedService->price,
                    'currency' => $contractedService->price_currency,
                    'due_date' => $billingDate,
                ]);
            } else {
                foreach ($charge->payments->where('status', PaymentStatus::Pending) as $payment) {
                    $payment->update(['status' => PaymentStatus::Validated, 'validated_at' => now()]);
                }

                $charge->update(['status' => ChargeStatus::Paid]);
            }

            $allocatedAmount = $charge->fresh('payments')->payments->sum(fn (Payment $payment): float => (float) $payment->pivot->amount);
            $remainingAmount = max(0, (float) $charge->amount - $allocatedAmount);

            if ($remainingAmount > 0) {
                $payment = Payment::create([
                    'amount' => $remainingAmount,
                    'currency' => $charge->currency,
                    'received_at' => now()->toDateString(),
                    'status' => PaymentStatus::Validated,
                    'validated_at' => now(),
                ]);
                $payment->charges()->attach($charge->id, ['amount' => $remainingAmount, 'currency' => $charge->currency]);
            }

            Gestion::create([
                'client_id' => $contractedService->client_id,
                'contracted_service_id' => $contractedService->id,
                'type' => 'Pago recibido',
                'occurred_at' => now(),
                'result' => 'El cliente envió el pago sin contacto previo.',
                'observations' => 'Pago marcado desde el Dashboard de Seguimiento.',
            ]);
        });

        return back()->with('success', 'Pago registrado y servicio preparado para el próximo período.');
    }

}
