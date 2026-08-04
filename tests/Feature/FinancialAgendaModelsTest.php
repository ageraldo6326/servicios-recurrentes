<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommitmentPaymentStatus;
use App\Enums\FinancialCommitmentFrequency;
use App\Models\Beneficiary;
use App\Models\CommitmentPayment;
use App\Models\FinancialCommitment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAgendaModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_commitment_belongs_to_beneficiary_and_has_payments(): void
    {
        $beneficiary = Beneficiary::query()->create([
            'name' => 'Banco Popular',
            'type' => 'Banco',
        ]);

        $commitment = FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Tarjeta empresarial',
            'category' => 'Tarjeta de crédito',
            'frequency' => FinancialCommitmentFrequency::Monthly,
            'suggested_amount' => 1250,
            'has_cutoff' => true,
            'cutoff_day' => 15,
            'due_day' => 30,
        ]);

        $payment = CommitmentPayment::query()->create([
            'financial_commitment_id' => $commitment->id,
            'period_start' => '2026-08-01',
            'due_date' => '2026-08-30',
            'status' => CommitmentPaymentStatus::Pending,
        ]);

        $this->assertTrue($commitment->beneficiary->is($beneficiary));
        $this->assertTrue($beneficiary->financialCommitments->contains($commitment));
        $this->assertTrue($commitment->payments->contains($payment));
        $this->assertTrue($payment->financialCommitment->is($commitment));
        $this->assertSame(FinancialCommitmentFrequency::Monthly, $commitment->frequency);
        $this->assertSame(CommitmentPaymentStatus::Pending, $payment->status);
    }

    public function test_a_commitment_cannot_have_two_payments_for_the_same_period(): void
    {
        $beneficiary = Beneficiary::query()->create([
            'name' => 'Claro',
            'type' => 'Telecomunicaciones',
        ]);

        $commitment = FinancialCommitment::query()->create([
            'beneficiary_id' => $beneficiary->id,
            'name' => 'Internet oficina',
            'category' => 'Internet',
            'frequency' => FinancialCommitmentFrequency::Monthly,
            'due_day' => 10,
        ]);

        $attributes = [
            'financial_commitment_id' => $commitment->id,
            'period_start' => '2026-08-01',
            'due_date' => '2026-08-10',
            'status' => CommitmentPaymentStatus::Pending,
        ];

        CommitmentPayment::query()->create($attributes);

        $this->expectException(UniqueConstraintViolationException::class);
        CommitmentPayment::query()->create($attributes);
    }
}
