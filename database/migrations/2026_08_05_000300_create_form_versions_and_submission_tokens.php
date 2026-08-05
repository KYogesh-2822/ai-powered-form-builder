<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version');
            $table->string('source', 30);
            $table->string('summary', 255);
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->json('schema');
            $table->dateTime('created_at');

            $table->unique(['form_id', 'version']);
            $table->index(['form_id', 'created_at']);
        });

        DB::table('forms')->orderBy('id')->chunkById(100, function ($forms): void {
            foreach ($forms as $form) {
                DB::table('form_versions')->insert([
                    'form_id' => $form->id,
                    'user_id' => $form->user_id,
                    'version' => $form->version,
                    'source' => 'migration',
                    'summary' => 'Snapshot created during versioning rollout',
                    'title' => $form->title,
                    'description' => $form->description,
                    'schema' => $form->schema,
                    'created_at' => $form->updated_at,
                ]);
            }
        });

        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->uuid('submission_token')->nullable()->after('reference');
            $table->unique(['form_id', 'submission_token']);
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table): void {
            $table->dropUnique(['form_id', 'submission_token']);
            $table->dropColumn('submission_token');
        });
        Schema::dropIfExists('form_versions');
    }
};
