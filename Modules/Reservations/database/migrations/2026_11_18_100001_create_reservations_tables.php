<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('theater_tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('group_tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->foreignId('hall_time_slot_id')->constrained('hall_time_slots')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('tenant_members')->nullOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->decimal('price', 12, 2);
            $table->char('currency', 3)->default('SAR');
            $table->timestamp('hold_expires_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('idempotency_key', 100);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['group_tenant_id', 'idempotency_key'], 'reservations_group_idempotency_unique');
            $table->index(['theater_tenant_id', 'status']);
            $table->index(['group_tenant_id', 'status']);
            $table->index(['hall_time_slot_id', 'status']);
            $table->index(['status', 'hold_expires_at']);
        });

        Schema::create('reservation_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('action', 60);
            $table->string('actor_type', 30)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'created_at']);
        });

        Schema::table('hall_time_slots', function (Blueprint $table): void {
            $table->foreignId('reservation_id')->nullable()->after('show_id')->constrained('reservations')->nullOnDelete();
            $table->index(['reservation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('hall_time_slots', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reservation_id');
        });

        Schema::dropIfExists('reservation_status_histories');
        Schema::dropIfExists('reservations');
    }
};
