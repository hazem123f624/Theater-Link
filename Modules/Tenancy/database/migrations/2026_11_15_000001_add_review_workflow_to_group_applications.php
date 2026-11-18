<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_members', function (Blueprint $table): void {
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
        });

        Schema::table('group_applications', function (Blueprint $table): void {
            $table->string('previous_status', 40)->nullable()->after('status');
            $table->timestamp('last_action_at')->nullable()->after('reviewed_at');
            $table->timestamp('suspended_at')->nullable()->after('last_action_at');
            $table->text('suspension_reason')->nullable()->after('suspended_at');

            $table->index(['tenant_id', 'status']);
        });

        Schema::table('group_application_documents', function (Blueprint $table): void {
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->foreignId('uploaded_by')->nullable()->after('reviewed_at')->constrained('tenant_members')->nullOnDelete();
            $table->unsignedInteger('version')->default(1)->after('uploaded_by');
            $table->timestamp('resubmitted_at')->nullable()->after('version');
        });

        // Existing members were created after OTP verification in Phase 2.
        DB::table('tenant_members')
            ->whereNotNull('phone')
            ->whereNull('phone_verified_at')
            ->update(['phone_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('group_application_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn(['reviewed_at', 'version', 'resubmitted_at']);
        });

        Schema::table('group_applications', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropColumn(['previous_status', 'last_action_at', 'suspended_at', 'suspension_reason']);
        });

        Schema::table('tenant_members', function (Blueprint $table): void {
            $table->dropColumn('phone_verified_at');
        });
    }
};
