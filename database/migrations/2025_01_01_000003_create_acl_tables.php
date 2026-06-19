<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();   // e.g. billing, dns, support
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('scope', ['system', 'tenant']);
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['name', 'scope', 'tenant_id']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();

            $table->unique(['role_id', 'module_id']);
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('modules');
    }
};
