<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theater_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('settings')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('settings')->nullOnDelete();
            $table->foreignId('logo_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('group_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->foreignId('logo_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('group_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->string('reference', 40)->unique();
            $table->string('status', 40)->default('pending_review')->index();
            $table->text('rejection_reason')->nullable();
            $table->text('review_feedback')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });

        Schema::create('group_application_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained('group_applications')->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->foreignId('media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->string('original_name')->nullable();
            $table->string('status', 40)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'document_type']);
        });

        Schema::create('tenant_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->foreignId('role_id')->nullable()->constrained('tenant_roles')->nullOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('tenant_members')->nullOnDelete();
            $table->string('token', 80)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_invitations');
        Schema::dropIfExists('group_application_documents');
        Schema::dropIfExists('group_applications');
        Schema::dropIfExists('group_profiles');
        Schema::dropIfExists('theater_profiles');
    }
};
