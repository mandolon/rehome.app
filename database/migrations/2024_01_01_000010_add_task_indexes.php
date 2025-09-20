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
        Schema::table('tasks', function (Blueprint $table) {
            // Composite index for filtering tasks by project, category, and status
            $table->index(['project_id', 'category', 'status'], 'tasks_project_category_status_idx');
            
            // Index for assignee-based queries
            $table->index('assignee_id', 'tasks_assignee_idx');
            
            // Index for due date queries (overdue tasks)
            $table->index('due_date', 'tasks_due_date_idx');
            
            // Index for client visibility filtering
            $table->index('allow_client', 'tasks_allow_client_idx');
            
            // Composite index for common filtering scenarios
            $table->index(['status', 'due_date'], 'tasks_status_due_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_project_category_status_idx');
            $table->dropIndex('tasks_assignee_idx');
            $table->dropIndex('tasks_due_date_idx');
            $table->dropIndex('tasks_allow_client_idx');
            $table->dropIndex('tasks_status_due_date_idx');
        });
    }
};
