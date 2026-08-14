<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notebooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('color', 32)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'position']);
            $table->index(['user_id', 'archived_at']);
        });

        Schema::create('notebook_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notebook_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('color', 32)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['notebook_id', 'position']);
            $table->index(['notebook_id', 'archived_at']);
        });

        Schema::create('note_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notebook_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('note_pages')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255)->nullable();
            $table->json('content_json')->nullable();
            $table->text('searchable_text')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_favorite')->default(false);
            $table->unsignedInteger('content_version')->default(1);
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['notebook_section_id', 'parent_id', 'position']);
            $table->index(['created_by', 'last_edited_at']);
            $table->index(['created_by', 'is_favorite', 'last_edited_at']);
        });

        Schema::create('note_page_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('note_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 255)->nullable();
            $table->json('content_json')->nullable();
            $table->unsignedInteger('content_version');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['note_page_id', 'created_at']);
        });

        Schema::create('note_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('note_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 80);
            $table->string('path');
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('mime_type', 150);
            $table->string('extension', 16)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['note_page_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        // Intentionally no-op: this project preserves user data and schema history.
    }
};
