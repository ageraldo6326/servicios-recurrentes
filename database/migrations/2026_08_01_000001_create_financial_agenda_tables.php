<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->boolean('is_active')->default(true);
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_commitments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('category');
            $table->string('frequency');
            $table->decimal('suggested_amount', 12, 2)->nullable();
            $table->boolean('has_cutoff')->default(false);
            $table->unsignedTinyInteger('cutoff_day')->nullable();
            $table->unsignedTinyInteger('due_day');
            $table->boolean('is_active')->default(true);
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('commitment_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('financial_commitment_id')->constrained()->restrictOnDelete();
            $table->date('period_start');
            $table->date('due_date');
            $table->string('status')->default('pending');
            $table->date('paid_at')->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->text('observations')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();

            $table->unique(['financial_commitment_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commitment_payments');
        Schema::dropIfExists('financial_commitments');
        Schema::dropIfExists('beneficiaries');
    }
};
