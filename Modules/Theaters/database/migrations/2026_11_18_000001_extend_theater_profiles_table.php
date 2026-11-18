<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theater_profiles', function (Blueprint $table): void {
            $table->foreignId('cover_media_id')->nullable()->after('logo_media_id')->constrained('media_files')->nullOnDelete();
            $table->string('timezone', 60)->default('Asia/Riyadh')->after('city_id');
        });
    }

    public function down(): void
    {
        Schema::table('theater_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cover_media_id');
            $table->dropColumn('timezone');
        });
    }
};
