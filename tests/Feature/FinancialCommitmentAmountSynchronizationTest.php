<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommitmentPaymentStatus;
use App\Models\Beneficiary;
use App\Models\CommitmentPayment;
use App\Models\FinancialCommitment;
use App\Models\User;
use App\Services\CommitmentOccurrenceService;
use App\Services\FinancialCommitmentAgendaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialCommitmentAmountSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_synchronizes_every_unpaid_occurrence_even_when_the_template_amount_did_not_change(): void
    {
        $user = User::factory()->create();
        $commitment = $this->commitment('3000.00');

        $pending = $this->occurrence($commitment, '2026-08-01', CommitmentPaymentStatus::Pending, '5000.00');
        $projected = $this->occurrence($commitment, '2026-09-01', CommitmentPaymentStatus::Projected, '5000.00');
        $partial = $this->occurrence($commitment, '2026-10-01', CommitmentPaymentStatus::PartiallyPaid, '5000.00', '1000.00');
        $paid = $this->occurrence($commitment, '2026-11-01', CommitmentPaymentStatus::Paid, '5000.00', '5000.00');
        $cancelled = $this->occurrence($commitment, '2026-12-01', CommitmentPaymentStatus::Cancelled, '5000.00');

        $response = $this->actingAs($user)->put(
            route('financial-agenda.commitments.update', $commitment),
            $this->payload($commitment, '3000.00', true),
        );

        $response
            ->assertRedirect(route('financial-agenda.commitments.index'))
            ->assertSessionHas('success', 'Compromiso financiero actualizado. Se actualizaron 3 obligaciones pendientes.');

        $this->assertSame('3000.00', $pending->fresh()->expected_amount);
        $this->assertSame('3000.00', $projected->fresh()->expected_amount);
        $this->assertSame('3000.00', $partial->fresh()->expected_amount);
        $this->assertSame('5000.00', $paid->fresh()->expected_amount);
        $this->assertSame('5000.00', $cancelled->fresh()->expected_amount);

        $agenda = app(FinancialCommitmentAgendaService::class)->forOccurrence(
            $pending->fresh(['financialCommitment', 'entries']),
            CarbonImmutable::parse('2026-08-05'),
        );

        $this->assertSame(3000.0, $agenda['expected_amount']);
    }

    public function test_it_can_increase_the_amount_of_generated_pending_occurrences(): void
    {
        $user = User::factory()->create();
        $commitment = $this->commitment('3000.00');
        $pending = $this->occurrence($commitment, '2026-08-01', CommitmentPaymentStatus::Pending, '3000.00');

        $this->actingAs($user)->put(
            route('financial-agenda.commitments.update', $commitment),
            $this->payload($commitment, '5000.00', true),
        )->assertRedirect(route('financial-agenda.commitments.index'));

        $this->assertSame('5000.00', $commitment->fresh()->suggested_amount);
        $this->assertSame('5000.00', $pending->fresh()->expected_amount);
    }

    public function test_without_synchronization_existing_amounts_remain_and_new_occurrences_use_the_new_amount(): void
    {
        $user = User::factory()->create();
        $commitment = $this->commitment('5000.00');
        $commitment->forceFill(['created_at' => CarbonImmutable::parse('2026-08-01')])->saveQuietly();
        $existing = $this->occurrence($commitment, '2026-08-01', CommitmentPaymentStatus::Pending, '5000.00');

        $this->actingAs($user)->put(
            route('financial-agenda.commitments.update', $commitment),
            $this->payload($commitment, '3000.00', false),
        )->assertRedirect(route('financial-agenda.commitments.index'));

        $occurrences = app(CommitmentOccurrenceService::class)->ensureForDate(
            $commitment->fresh(),
            CarbonImmutable::parse('2026-09-01'),
        );
        $newOccurrence = $occurrences->firstWhere(
            fn (CommitmentPayment $occurrence): bool => $occurrence->period_start->isSameDay('2026-09-01'),
        );

        $this->assertSame('5000.00', $existing->fresh()->expected_amount);
        $this->assertNotNull($newOccurrence);
        $this->assertSame('3000.00', $newOccurrence->expected_amount);
    }

    public function test_the_edit_form_explains_that_synchronization_runs_without_an_amount_change(): void
    {
        $user = User::factory()->create();
        $commitment = $this->commitment('3000.00');

        $this->actingAs($user)
            ->get(route('financial-agenda.commitments.edit', $commitment))
            ->assertOk()
            ->assertSee('Aplicar el monto a las obligaciones pendientes ya generadas')
            ->assertSee('Se ejecuta aunque el monto sugerido no haya cambiado.');
    }

    private function commitment(string $amount): FinancialCommitment
    {
        $beneficiary = Beneficiary::query()->create([
            'name' => 'Cuido de bebé',
            'type' => 'Servicio',
        ]);

        return FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Cuido de bebé',
            'category' => 'Cuidado',
            'frequency' => 'monthly',
            'suggested_amount' => $amount,
            'due_day' => 10,
            'is_active' => true,
        ]);
    }

    private function occurrence(
        FinancialCommitment $commitment,
        string $periodStart,
        CommitmentPaymentStatus $status,
        string $expectedAmount,
        ?string $amountPaid = null,
    ): CommitmentPayment {
        return CommitmentPayment::query()->create([
            'financial_commitment_id' => $commitment->id,
            'period_start' => $periodStart,
            'due_date' => CarbonImmutable::parse($periodStart)->setDay(10)->toDateString(),
            'expected_amount' => $expectedAmount,
            'status' => $status,
            'amount_paid' => $amountPaid,
        ]);
    }

    /** @return array<string, string> */
    private function payload(FinancialCommitment $commitment, string $amount, bool $synchronize): array
    {
        return [
            'beneficiary_id' => (string) $commitment->beneficiary_id,
            'name' => $commitment->name,
            'category' => $commitment->category,
            'frequency' => $commitment->frequency->value,
            'suggested_amount' => $amount,
            'has_cutoff' => '0',
            'is_credit_card' => '0',
            'due_day' => (string) $commitment->due_day,
            'activation_days_before_due' => '15',
            'is_active' => '1',
            'sync_pending_occurrence_amounts' => $synchronize ? '1' : '0',
        ];
    }
}
