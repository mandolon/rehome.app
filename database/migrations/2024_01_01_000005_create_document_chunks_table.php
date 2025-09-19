<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->json('embedding')->nullable(); // Vector embedding for RAG
            $table->integer('chunk_index'); // Order within document
            $table->integer('token_count')->nullable();
            $table->json('metadata')->nullable(); // page numbers, sections, etc.
            $table->timestamps();
            
            $table->index(['document_id', 'chunk_index']);
            $table->index(['account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
