<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->timestamps();
        });

        Schema::create('catalog_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('payment_method');
            $table->boolean('accepts_partial_payments')->default(false);
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('contracted_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('catalog_service_id')->constrained('catalog_services')->restrictOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 12, 2);
            $table->string('price_currency', 3);
            $table->decimal('cost', 12, 2);
            $table->string('cost_currency', 3);
            $table->string('ip')->nullable();
            $table->unsignedTinyInteger('billing_day');
            $table->string('status');
            $table->date('starts_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contracted_service_id')->constrained()->restrictOnDelete();
            $table->string('status');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->date('due_date');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->date('received_at');
            $table->string('status');
            $table->string('evidence_path')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('charge_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->timestamps();

            $table->unique(['payment_id', 'charge_id']);
        });

        Schema::create('gestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('contracted_service_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->dateTime('occurred_at');
            $table->string('result');
            $table->string('phone_used')->nullable();
            $table->date('promised_payment_date')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('provider_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->date('due_date');
            $table->string('status');
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_invoices');
        Schema::dropIfExists('gestions');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('charges');
        Schema::dropIfExists('contracted_services');
        Schema::dropIfExists('providers');
        Schema::dropIfExists('catalog_services');
        Schema::dropIfExists('clients');
    }
};
