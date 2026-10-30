<?php

namespace App\Http\Controllers;

use App\Actions\RejectPayment;
use App\Enums\ChargeStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\RejectPaymentRequest;
use App\Models\Charge;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $payments = Payment::with([
            'charges.contractedService.client',
            'charges.contractedService.catalogService',
            'rejectedBy',
        ])->when($search !== '', function ($query) use ($search): void {
            $term = "%{$search}%";

            $query->where(function ($query) use ($search, $term): void {
                $query->where('currency', 'like', $term)
                    ->orWhere('status', 'like', $term)
                    ->orWhere('rejection_reason', 'like', $term)
                    ->orWhereHas('charges.contractedService.client', function ($client) use ($term): void {
                        $client->where('name', 'like', $term)
                            ->orWhere('phone', 'like', $term);
                    })
                    ->orWhereHas('charges.contractedService.catalogService', fn ($service) => $service->where('name', 'like', $term))
                    ->orWhereHas('charges.contractedService', function ($service) use ($term): void {
                        $service->where('ip', 'like', $term)
                            ->orWhere('observations', 'like', $term);
                    });

                if (ctype_digit($search)) {
                    $query->orWhereKey((int) $search);
                }
            });
        })->latest('received_at')->paginate(20)->withQueryString();

        return view('payments.index', compact('payments', 'search'));
    }

    public function create()
    {
        return view('payments.form', ['charges' => Charge::with('contractedService.client', 'contractedService.catalogService')->whereIn('status', [ChargeStatus::Pending, ChargeStatus::Partial])->orderBy('due_date')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0'], 'currency' => ['required', 'string', 'size:3'], 'received_at' => ['required', 'date'], 'evidence_path' => ['nullable', 'string', 'max:255'], 'charge_id' => ['nullable', 'exists:charges,id'], 'allocation_amount' => ['nullable', 'numeric', 'gt:0']]);
        DB::transaction(function () use ($data) {
            $payment = Payment::create([...$data, 'status' => PaymentStatus::Pending->value]);
            if (! empty($data['charge_id'])) {
                $payment->charges()->attach($data['charge_id'], ['amount' => $data['allocation_amount'] ?? $data['amount'], 'currency' => $data['currency']]);
            }
        });

        return redirect()->route('payments.index')->with('success', 'Pago registrado y pendiente de validar.');
    }

    public function validatePayment(Payment $payment): RedirectResponse
    {
        abort_unless($payment->status === PaymentStatus::Pending, 422, 'Solo se puede validar un pago pendiente.');

        $payment->update(['status' => PaymentStatus::Validated, 'validated_at' => now()]);

        return back()->with('success', 'Pago validado.');
    }

    public function reject(RejectPaymentRequest $request, Payment $payment, RejectPayment $rejectPayment): RedirectResponse
    {
        $rejectPayment->execute(
            $payment,
            $request->user(),
            $request->string('rejection_reason')->trim()->toString(),
        );

        return back()->with('success', 'Pago anulado. Los cobros relacionados fueron recalculados sin borrar el historial.');
    }
}
