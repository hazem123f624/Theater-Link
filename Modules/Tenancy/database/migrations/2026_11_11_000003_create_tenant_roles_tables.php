<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('template_key', 50)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('tenant_role_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id')->constrained('tenant_roles')->cascadeOnDelete();
            $table->string('lang', 5);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['role_id', 'lang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_role_translations');
        Schema::dropIfExists('tenant_roles');
    }
};
