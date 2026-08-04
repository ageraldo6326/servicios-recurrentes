<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RegisterCommitmentPayment;
use App\Enums\CommitmentPaymentStatus;
use App\Models\Beneficiary;
use App\Models\CommitmentPayment;
use App\Models\FinancialCommitment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterCommitmentPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_a_payment_marks_current_period_paid_and_creates_next_period(): void
    {
        $commitment = $this->commitment(31);

        $payment = app(RegisterCommitmentPayment::class)->handle(
            $commitment,
            CarbonImmutable::parse('2026-08-01'),
            CarbonImmutable::parse('2026-08-04'),
            '125.00',
            'Pago realizado desde la cuenta empresarial.',
            'commitment-receipts/payment.pdf',
        );

        $this->assertSame(CommitmentPaymentStatus::Paid, $payment->status);
        $this->assertSame('2026-08-04', $payment->paid_at->toDateString());
        $this->assertSame(1, CommitmentPayment::query()->where('financial_commitment_id', $commitment->id)->whereDate('period_start', '2026-08-01')->where('status', 'paid')->where('receipt_path', 'commitment-receipts/payment.pdf')->count());
        $this->assertSame(1, CommitmentPayment::query()->where('financial_commitment_id', $commitment->id)->whereDate('period_start', '2026-09-01')->whereDate('due_date', '2026-09-30')->where('status', 'pending')->count());
    }

    public function test_registering_the_same_period_does_not_duplicate_the_next_period(): void
    {
        $commitment = $this->commitment(10);
        $action = app(RegisterCommitmentPayment::class);
        $period = CarbonImmutable::parse('2026-08-01');

        $action->handle($commitment, $period, CarbonImmutable::parse('2026-08-05'), null, null, null);
        $action->handle($commitment, $period, CarbonImmutable::parse('2026-08-06'), '200.00', null, null);

        $this->assertSame(2, CommitmentPayment::query()->where('financial_commitment_id', $commitment->id)->count());
        $this->assertSame(1, CommitmentPayment::query()->where('financial_commitment_id', $commitment->id)->whereDate('period_start', '2026-08-01')->where('amount_paid', '200.00')->count());
    }

    private function commitment(int $dueDay): FinancialCommitment
    {
        $beneficiary = Beneficiary::query()->create(['name' => 'Proveedor de prueba', 'type' => 'Servicio']);

        return FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Compromiso recurrente',
            'category' => 'General',
            'frequency' => 'monthly',
            'due_day' => $dueDay,
            'is_active' => true,
        ]);
    }
}
