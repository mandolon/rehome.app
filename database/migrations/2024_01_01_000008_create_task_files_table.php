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
        Schema::create('task_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id')->index();
            $table->unsignedBigInteger('file_id')->index();
            $table->bigInteger('added_by_id')->index()->comment('references users(id)');
            $table->string('attachment_type')->default('attachment')->comment('attachment | redline | revision');
            $table->text('notes')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            // Foreign key constraints
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
            $table->foreign('added_by_id')->references('id')->on('users')->onDelete('cascade');

            // Unique constraint to prevent duplicate file attachments
            $table->unique(['task_id', 'file_id']);

            // Indexes for performance
            $table->index(['task_id', 'attachment_type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_files');
    }
};
