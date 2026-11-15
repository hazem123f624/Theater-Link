<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('module', 80)->index();
            $table->string('action', 40);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('tenant_permission_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('permission_id')->constrained('tenant_permissions')->cascadeOnDelete();
            $table->string('lang', 5);
            $table->string('title');
            $table->timestamps();

            $table->unique(['permission_id', 'lang']);
        });

        Schema::create('tenant_permission_tenant_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('permission_id')->constrained('tenant_permissions')->cascadeOnDelete();
            $table->string('tenant_type', 20);
            $table->timestamps();

            $table->unique(['permission_id', 'tenant_type']);
            $table->index('tenant_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_permission_tenant_types');
        Schema::dropIfExists('tenant_permission_translations');
        Schema::dropIfExists('tenant_permissions');
    }
};
