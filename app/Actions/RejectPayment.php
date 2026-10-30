<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ChargeStatus;
use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\Gestion;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RejectPayment
{
    public function execute(Payment $payment, User $user, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $user, $reason): Payment {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($lockedPayment->status !== PaymentStatus::Validated) {
                throw ValidationException::withMessages([
                    'payment' => 'Solo se puede anular un pago validado.',
                ]);
            }

            $chargeIds = $lockedPayment->charges()->pluck('charges.id');

            $lockedPayment->update([
                'status' => PaymentStatus::Rejected,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'rejected_by' => $user->id,
            ]);

            foreach ($chargeIds as $chargeId) {
                $charge = Charge::query()
                    ->with('contractedService')
                    ->lockForUpdate()
                    ->findOrFail($chargeId);
                $validatedAmount = (float) $charge->payments()
                    ->where('payments.status', PaymentStatus::Validated->value)
                    ->sum('payment_allocations.amount');
                $chargeStatus = $this->chargeStatus($charge, $validatedAmount);

                $charge->update(['status' => $chargeStatus]);

                Gestion::query()->create([
                    'client_id' => $charge->contractedService->client_id,
                    'contracted_service_id' => $charge->contracted_service_id,
                    'charge_id' => $charge->id,
                    'type' => 'Pago anulado',
                    'occurred_at' => now(),
                    'result' => 'El pago fue anulado y el cobro quedó pendiente nuevamente.',
                    'observations' => "Pago #{$lockedPayment->id} anulado. Motivo: {$reason}",
                ]);
            }

            return $lockedPayment->fresh(['charges', 'rejectedBy']);
        });
    }

    private function chargeStatus(Charge $charge, float $validatedAmount): ChargeStatus
    {
        if ($validatedAmount >= (float) $charge->amount) {
            return ChargeStatus::Paid;
        }

        return $validatedAmount > 0
            ? ChargeStatus::Partial
            : ChargeStatus::Pending;
    }
}
