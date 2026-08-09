<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unplanned_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('place')->nullable();
            $table->date('expense_date')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->string('context')->nullable();
            $table->string('status')->default('pending');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->index(['expense_date', 'status']);
            $table->index('type');
        });

        Schema::create('unplanned_expense_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('unplanned_expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unplanned_expense_histories');
        Schema::dropIfExists('unplanned_expenses');
    }
};
