<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommitmentPaymentStatus;
use App\Models\Beneficiary;
use App\Models\CommitmentPayment;
use App\Models\FinancialCommitment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialCommitmentCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_cancel_a_commitment_without_deleting_history(): void
    {
        $user = User::factory()->create();
        $commitment = $this->commitment();
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $paid = $this->occurrence($commitment, $today->subMonthNoOverflow(), CommitmentPaymentStatus::Paid);
        $future = $this->occurrence($commitment, $today->addMonthNoOverflow(), CommitmentPaymentStatus::Pending);
        $overdue = $this->occurrence($commitment, $today->subDay(), CommitmentPaymentStatus::Pending);

        $response = $this->actingAs($user)->post(route('financial-agenda.commitments.cancel', $commitment), [
            'cancellation_reason' => 'Préstamo liquidado.',
        ]);

        $response->assertRedirect(route('financial-agenda.commitments.index'));
        $this->assertDatabaseHas('financial_commitments', [
            'id' => $commitment->id,
            'is_active' => 0,
            'cancellation_reason' => 'Préstamo liquidado.',
            'cancelled_by_user_id' => $user->id,
        ]);
        $this->assertNotNull($commitment->fresh()->cancelled_at);
        $this->assertDatabaseHas('commitment_payments', ['id' => $paid->id, 'status' => 'paid']);
        $this->assertDatabaseHas('commitment_payments', ['id' => $future->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('commitment_payments', ['id' => $overdue->id, 'status' => 'pending']);
    }

    public function test_cancellation_requires_a_reason(): void
    {
        $user = User::factory()->create();
        $commitment = $this->commitment();

        $this->actingAs($user)
            ->from(route('financial-agenda.commitments.edit', $commitment))
            ->post(route('financial-agenda.commitments.cancel', $commitment), [])
            ->assertRedirect(route('financial-agenda.commitments.edit', $commitment))
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertTrue($commitment->fresh()->is_active);
    }

    public function test_a_cancelled_commitment_cannot_be_cancelled_again(): void
    {
        $user = User::factory()->create();
        $commitment = $this->commitment();
        $commitment->update([
            'is_active' => false,
            'cancelled_at' => CarbonImmutable::now(config('app.timezone'))->toDateString(),
            'cancellation_reason' => 'Ya cancelado.',
        ]);

        $this->actingAs($user)
            ->post(route('financial-agenda.commitments.cancel', $commitment), ['cancellation_reason' => 'Segundo intento'])
            ->assertForbidden();
    }

    private function commitment(): FinancialCommitment
    {
        $beneficiary = Beneficiary::query()->create(['name' => 'Banco de prueba', 'type' => 'Banco']);

        return FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Préstamo de prueba',
            'category' => 'Préstamo',
            'frequency' => 'monthly',
            'suggested_amount' => 1000,
            'due_day' => 10,
            'is_active' => true,
        ]);
    }

    private function occurrence(FinancialCommitment $commitment, CarbonImmutable $dueDate, CommitmentPaymentStatus $status): CommitmentPayment
    {
        return CommitmentPayment::query()->create([
            'financial_commitment_id' => $commitment->id,
            'period_start' => $dueDate->startOfMonth()->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'expected_amount' => 1000,
            'status' => $status,
        ]);
    }
}
