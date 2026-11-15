<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('tenant_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('tenant_permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
            $table->index('tenant_id');
        });

        Schema::create('tenant_member_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('tenant_members')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('tenant_roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['member_id', 'role_id']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_member_roles');
        Schema::dropIfExists('tenant_role_permissions');
    }
};
