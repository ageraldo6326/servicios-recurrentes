<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_commitments', function (Blueprint $table): void {
            $table->boolean('is_credit_card')->default(false)->after('has_cutoff');
            $table->unsignedTinyInteger('payment_safety_days')->default(2)->after('due_day');
            $table->decimal('credit_limit', 12, 2)->nullable()->after('payment_safety_days');
            $table->decimal('current_balance', 12, 2)->nullable()->after('credit_limit');
            $table->decimal('statement_balance', 12, 2)->nullable()->after('current_balance');
            $table->string('card_currency', 3)->nullable()->after('statement_balance');
            $table->unsignedTinyInteger('purchase_excellent_days')->default(7)->after('card_currency');
            $table->unsignedTinyInteger('purchase_good_days')->default(15)->after('purchase_excellent_days');
            $table->unsignedTinyInteger('purchase_regular_days')->default(22)->after('purchase_good_days');
            $table->string('cutoff_alert_days')->nullable()->after('purchase_regular_days');
            $table->string('payment_alert_days')->nullable()->after('cutoff_alert_days');
        });
    }

    public function down(): void
    {
        Schema::table('financial_commitments', function (Blueprint $table): void {
            $table->dropColumn(['is_credit_card', 'payment_safety_days', 'credit_limit', 'current_balance', 'statement_balance', 'card_currency', 'purchase_excellent_days', 'purchase_good_days', 'purchase_regular_days', 'cutoff_alert_days', 'payment_alert_days']);
        });
    }
};
