<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('title', 150);
            $table->string('storage_path')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->char('sha256', 64);
            $table->string('status', 20)->default('pending');
            $table->string('error_message', 500)->nullable();
            $table->unsignedSmallInteger('page_count')->nullable();
            $table->unsignedInteger('char_count')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->unique(['project_id', 'sha256']);
        });

        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->unsignedSmallInteger('page')->nullable();
            $table->string('section')->nullable();
            $table->text('content');
            $table->timestamps();

            $table->unique(['document_id', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
        Schema::dropIfExists('documents');
    }
};
