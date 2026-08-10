<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commitment_payments', function (Blueprint $table): void {
            $table->date('cutoff_date')->nullable()->after('period_start');
            $table->decimal('expected_amount', 12, 2)->nullable()->after('due_date');
        });

        Schema::create('commitment_payment_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('commitment_payment_id')
                ->constrained('commitment_payments')
                ->cascadeOnDelete();
            $table->date('paid_at');
            $table->decimal('amount', 12, 2);
            $table->text('observations')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commitment_payment_entries');

        Schema::table('commitment_payments', function (Blueprint $table): void {
            $table->dropColumn(['cutoff_date', 'expected_amount']);
        });
    }
};
