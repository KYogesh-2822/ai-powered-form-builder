<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('schema');
            $table->json('settings')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status', 'updated_at']);
            $table->index(['status', 'published_at']);
        });

        Schema::create('form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->uuid('reference')->unique();
            $table->json('answers');
            $table->longText('searchable_text')->nullable();
            $table->string('status', 20)->default('complete');
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->dateTime('submitted_at');
            $table->timestamps();

            $table->index(['form_id', 'submitted_at']);
            $table->index(['form_id', 'status']);
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('forms');
    }
};
