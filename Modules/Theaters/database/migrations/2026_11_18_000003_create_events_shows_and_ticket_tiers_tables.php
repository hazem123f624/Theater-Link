<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('event_category_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('event_categories')->cascadeOnDelete();
            $table->string('lang', 5);
            $table->string('name');
            $table->timestamps();

            $table->unique(['category_id', 'lang']);
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('event_categories')->nullOnDelete();
            $table->string('slug', 160)->unique();
            $table->foreignId('poster_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'published_at']);
        });

        Schema::create('event_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('lang', 5);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'lang']);
        });

        Schema::create('shows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('scheduled')->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'starts_at']);
            $table->index(['hall_id', 'starts_at']);
            $table->index(['tenant_id', 'status', 'starts_at']);
        });

        Schema::create('ticket_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('show_id')->constrained('shows')->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->char('currency', 3)->default('SAR');
            $table->unsignedInteger('total_quantity');
            $table->unsignedInteger('sold_quantity')->default(0);
            $table->unsignedInteger('held_quantity')->default(0);
            $table->dateTime('sale_starts_at')->nullable();
            $table->dateTime('sale_ends_at')->nullable();
            $table->unsignedInteger('per_booking_limit')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['show_id', 'is_active']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('ticket_tier_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_tier_id')->constrained('ticket_tiers')->cascadeOnDelete();
            $table->string('lang', 5);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['ticket_tier_id', 'lang']);
        });

        Schema::table('hall_time_slots', function (Blueprint $table): void {
            $table->foreign('show_id')->references('id')->on('shows')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hall_time_slots', function (Blueprint $table): void {
            $table->dropForeign(['show_id']);
        });

        Schema::dropIfExists('ticket_tier_translations');
        Schema::dropIfExists('ticket_tiers');
        Schema::dropIfExists('shows');
        Schema::dropIfExists('event_translations');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_category_translations');
        Schema::dropIfExists('event_categories');
    }
};
