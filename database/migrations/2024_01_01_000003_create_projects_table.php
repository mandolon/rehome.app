<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // owner
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->enum('phase', ['design', 'permit', 'construction', 'complete'])->default('design');
            $table->string('zoning')->nullable();
            $table->json('metadata')->nullable(); // flexible data storage
            $table->timestamps();
            
            $table->index(['account_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
