<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_application_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained('group_applications')->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('group_application_documents')->nullOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->string('action', 60);
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('tenant_member_id')->nullable()->constrained('tenant_members')->nullOnDelete();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'created_at'], 'gash_application_created_index');
            $table->index(['action', 'created_at'], 'gash_action_created_index');
        });

        Schema::create('group_application_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('group_application_documents')->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->string('original_name')->nullable();
            $table->unsignedInteger('version');
            $table->foreignId('uploaded_by')->nullable()->constrained('tenant_members')->nullOnDelete();
            $table->timestamps();

            $table->unique(['document_id', 'version'], 'gadv_document_version_unique');
            $table->index('created_at', 'gadv_created_index');
        });

        Schema::create('app_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('type', 80);
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'app_notifications_notifiable_read_index');
            $table->index(['type', 'created_at'], 'app_notifications_type_created_index');
        });

        // Seed a baseline history event for applications created before Phase 3.
        $applications = DB::table('group_applications')->get(['id', 'status', 'submitted_at', 'created_at']);

        foreach ($applications as $application) {
            DB::table('group_application_status_histories')->insert([
                'application_id' => $application->id,
                'from_status' => null,
                'to_status' => $application->status,
                'action' => 'submitted',
                'created_at' => $application->submitted_at ?? $application->created_at,
                'updated_at' => $application->submitted_at ?? $application->created_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('group_application_document_versions');
        Schema::dropIfExists('group_application_status_histories');
    }
};
