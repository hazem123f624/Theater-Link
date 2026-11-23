<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('show_id')->constrained('shows')->cascadeOnDelete();
            $table->foreignId('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->string('status', 20)->default('confirmed')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->char('currency', 3)->default('SAR');
            $table->string('idempotency_key', 100);
            $table->string('request_hash', 64);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('cancelled_by_type', 30)->nullable();
            $table->unsignedBigInteger('cancelled_by_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'idempotency_key'], 'bookings_user_idempotency_unique');
            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['show_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('booking_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('ticket_tier_id')->constrained('ticket_tiers')->restrictOnDelete();
            $table->string('tier_name_en')->nullable();
            $table->string('tier_name_ar')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->char('currency', 3)->default('SAR');
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->unique(['booking_id', 'ticket_tier_id'], 'booking_items_booking_tier_unique');
            $table->index(['ticket_tier_id']);
        });

        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('booking_item_id')->constrained('booking_items')->cascadeOnDelete();
            $table->foreignId('ticket_tier_id')->constrained('ticket_tiers')->restrictOnDelete();
            $table->foreignId('show_id')->constrained('shows')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('ticket_number', 60)->unique();
            $table->string('qr_token', 100)->unique();
            $table->string('status', 20)->default('valid')->index();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_item_id', 'sequence'], 'tickets_item_sequence_unique');
            $table->index(['tenant_id', 'status']);
            $table->index(['booking_id', 'status']);
            $table->index(['show_id', 'status']);
        });

        Schema::create('booking_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('action', 60);
            $table->string('actor_type', 30)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'created_at']);
        });

        DB::statement('ALTER TABLE booking_items ADD CONSTRAINT booking_items_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE ticket_tiers ADD CONSTRAINT ticket_tiers_inventory_check CHECK (sold_quantity + held_quantity <= total_quantity)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE ticket_tiers DROP CONSTRAINT ticket_tiers_inventory_check');
        DB::statement('ALTER TABLE booking_items DROP CONSTRAINT booking_items_quantity_check');

        Schema::dropIfExists('booking_status_histories');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
    }
};
