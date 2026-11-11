<?php

namespace App\Http\Controllers;

use App\Actions\SynchronizePendingCommitmentAmounts;
use App\Enums\CommitmentPaymentStatus;
use App\Enums\FinancialCommitmentFrequency;
use App\Http\Requests\CancelFinancialCommitmentRequest;
use App\Http\Requests\FinancialCommitmentRequest;
use App\Models\Beneficiary;
use App\Models\FinancialCommitment;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FinancialCommitmentController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', FinancialCommitment::class);

        return view('financial-agenda.commitments.form', [
            'commitment' => new FinancialCommitment([
                'frequency' => FinancialCommitmentFrequency::Monthly,
                'is_active' => true,
            ]),
            'beneficiaries' => Beneficiary::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(FinancialCommitmentRequest $request): RedirectResponse
    {
        Gate::authorize('create', FinancialCommitment::class);
        FinancialCommitment::query()->create($request->validated());

        return redirect()->route('financial-agenda.commitments.index')
            ->with('success', 'Compromiso financiero creado.');
    }

    public function edit(FinancialCommitment $commitment): View
    {
        Gate::authorize('update', $commitment);

        return view('financial-agenda.commitments.form', [
            'commitment' => $commitment,
            'beneficiaries' => Beneficiary::query()->orderBy('name')->get(),
        ]);
    }

    public function update(
        FinancialCommitmentRequest $request,
        FinancialCommitment $commitment,
        SynchronizePendingCommitmentAmounts $synchronizePendingAmounts,
    ): RedirectResponse {
        Gate::authorize('update', $commitment);
        $data = $request->validated();
        $shouldSynchronizePendingAmounts = $commitment->cancelled_at === null
            && $request->boolean('sync_pending_occurrence_amounts');
        unset($data['sync_pending_occurrence_amounts']);

        if ($commitment->cancelled_at !== null) {
            $data['is_active'] = false;
        }

        $updatedOccurrences = DB::transaction(function () use (
            $commitment,
            $data,
            $shouldSynchronizePendingAmounts,
            $synchronizePendingAmounts,
        ): int {
            $commitment->update($data);

            return $shouldSynchronizePendingAmounts
                ? $synchronizePendingAmounts->handle($commitment->refresh())
                : 0;
        });

        $message = 'Compromiso financiero actualizado.';

        if ($shouldSynchronizePendingAmounts) {
            $message .= " Se actualizaron {$updatedOccurrences} obligaciones pendientes.";
        }

        return redirect()->route('financial-agenda.commitments.index')
            ->with('success', $message);
    }

    public function cancel(CancelFinancialCommitmentRequest $request, FinancialCommitment $commitment): RedirectResponse
    {
        Gate::authorize('cancel', $commitment);

        $today = CarbonImmutable::now(config('app.timezone'))->toDateString();

        DB::transaction(function () use ($request, $commitment, $today): void {
            $commitment->update([
                'is_active' => false,
                'cancelled_at' => $today,
                'cancelled_by_user_id' => $request->user()->id,
                'cancellation_reason' => $request->validated('cancellation_reason'),
            ]);

            $commitment->payments()
                ->whereIn('status', [CommitmentPaymentStatus::Pending->value, CommitmentPaymentStatus::PartiallyPaid->value])
                ->whereDate('due_date', '>=', $today)
                ->update(['status' => CommitmentPaymentStatus::Cancelled]);
        });

        return redirect()->route('financial-agenda.commitments.index')
            ->with('success', 'Compromiso cancelado; el historial se conservó.');
    }
}
