<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('user_id'); // owner
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
