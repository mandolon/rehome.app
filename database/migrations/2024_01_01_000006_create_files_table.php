<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->integer('size');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['project_id', 'created_at']);
            $table->index(['account_id', 'mime_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
