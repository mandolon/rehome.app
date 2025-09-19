<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'team', 'client'])->default('client');
            $table->json('permissions')->nullable(); // Additional permissions beyond role
            $table->string('avatar_url')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            $table->index(['account_id', 'role']);
            $table->index(['email', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
