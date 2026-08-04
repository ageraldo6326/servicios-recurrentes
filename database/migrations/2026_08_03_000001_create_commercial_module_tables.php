<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('contact_name')->nullable();
            $table->string('contact_position')->nullable();
            $table->string('commercial_email')->nullable();
            $table->string('commercial_phone')->nullable();
            $table->string('commercial_address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('preferred_currency', 3)->nullable();
            $table->text('commercial_notes')->nullable();
        });

        Schema::create('commercial_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number')->unique();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('commercial_quote_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->constrained('commercial_quotes')->cascadeOnDelete();
            $table->string('concept');
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit')->default('unidad');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('commercial_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('commercial_quotes')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number')->unique();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        Schema::create('commercial_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('commercial_invoices')->cascadeOnDelete();
            $table->string('concept');
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit')->default('unidad');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('commercial_line_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('concept');
            $table->text('description')->nullable();
            $table->string('unit')->default('unidad');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('commercial_document_histories', function (Blueprint $table): void {
            $table->id();
            $table->morphs('document');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_document_histories');
        Schema::dropIfExists('commercial_line_templates');
        Schema::dropIfExists('commercial_invoice_items');
        Schema::dropIfExists('commercial_invoices');
        Schema::dropIfExists('commercial_quote_items');
        Schema::dropIfExists('commercial_quotes');
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn(['contact_name', 'contact_position', 'commercial_email', 'commercial_phone', 'commercial_address', 'city', 'province', 'country', 'tax_id', 'payment_terms', 'preferred_currency', 'commercial_notes']);
        });
    }
};
