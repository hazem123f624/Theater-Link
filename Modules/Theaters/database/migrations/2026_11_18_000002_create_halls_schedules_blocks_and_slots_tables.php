<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('halls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('capacity');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedTinyInteger('active_state')->storedAs('CASE WHEN deleted_at IS NULL THEN 1 ELSE NULL END');

            $table->unique(['tenant_id', 'name', 'active_state'], 'halls_tenant_name_active_unique');
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('hall_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('slot_duration_minutes');
            $table->decimal('price', 12, 2)->default(0);
            $table->char('currency', 3)->default('SAR');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['hall_id', 'day_of_week']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('hall_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('tenant_members')->nullOnDelete();
            $table->timestamps();

            $table->index(['hall_id', 'starts_at']);
            $table->index(['tenant_id', 'starts_at']);
        });

        Schema::create('hall_time_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->foreignId('hall_schedule_id')->nullable()->constrained('hall_schedules')->nullOnDelete();
            $table->foreignId('block_id')->nullable()->constrained('hall_blocks')->nullOnDelete();
            $table->unsignedBigInteger('show_id')->nullable()->index();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('available')->index();
            $table->decimal('price', 12, 2)->default(0);
            $table->char('currency', 3)->default('SAR');
            $table->timestamps();

            $table->unique(['hall_id', 'starts_at'], 'hall_slots_hall_start_unique');
            $table->index(['tenant_id', 'status', 'starts_at']);
            $table->index(['hall_id', 'status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hall_time_slots');
        Schema::dropIfExists('hall_blocks');
        Schema::dropIfExists('hall_schedules');
        Schema::dropIfExists('halls');
    }
};
